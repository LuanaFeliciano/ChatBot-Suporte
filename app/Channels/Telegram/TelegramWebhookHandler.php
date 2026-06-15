<?php

namespace App\Channels\Telegram;

use App\Jobs\ProcessChatMessage;
use App\Models\BotMessage;
use App\Services\ChatService;
use App\Services\IdempotencyService;
use App\Services\RateLimitService;
use Illuminate\Support\Facades\Redis;

class TelegramWebhookHandler
{
    private const FEEDBACK_THANK_YOU = 'Obrigado pelo retorno!';

    private const RATE_LIMIT_MESSAGE = 'Você enviou muitas mensagens em um curto período. Aguarde um momento antes de continuar.';

    public function __construct(
        private readonly TelegramAdapter $adapter,
        private readonly IdempotencyService $idempotency,
        private readonly RateLimitService $rateLimit,
        private readonly ChatService $chatService,
    ) {}

    public function handle(array $payload): void
    {
        if (isset($payload['callback_query'])) {
            $this->handleFeedback($payload['callback_query']);

            return;
        }

        $message = $this->adapter->extractMessage($payload);
        if (! $message) {
            return;
        }

        ['message_id' => $messageId, 'channel_user' => $channelUser,
            'user_name' => $userName, 'text' => $question] = $message;

        if ($this->idempotency->isDuplicate('telegram', $messageId)) {
            return;
        }

        if ($this->rateLimit->isRateLimited('telegram', $channelUser)) {
            //$this->adapter->sendReply($channelUser, self::RATE_LIMIT_MESSAGE);
            //para ativar a mensagem de limite so descomentar
            return;
        }

        $hash = hash('sha256', $channelUser);
        $pendingKey = "user_pending:telegram:{$hash}";
        $genKey = "user_gen:telegram:{$hash}";

        // adiciona no fim da lista, vai ser usada como caixa temporaria das mensagens do usuario
        // serve para agrupar mensagens enviadas em sequencia de um mesmo usuario
        Redis::connection()->client()->rpush($pendingKey, json_encode([
            'user_name' => $userName,
            'question' => $question,
        ]));
        Redis::connection()->client()->expire($pendingKey, 300); // a chave vai expirar em 5min (evita lixo acumluado no redis)

        // contador de mensagens
        $generation = (int) Redis::connection()->client()->incr($genKey);
        Redis::connection()->client()->expire($genKey, 300);

        ProcessChatMessage::dispatch('telegram', $channelUser, $generation)
            ->onQueue('chat')
            ->delay(now()->addSeconds(3));
    }

    private function handleFeedback(array $callbackQuery): void
    {
        $callbackId = (string) ($callbackQuery['id'] ?? '');
        $data = (string) ($callbackQuery['data'] ?? '');

        if (! preg_match('/^feedback:(\d+):(up|down)$/', $data, $matches)) {
            $this->adapter->answerCallback($callbackId);

            return;
        }

        $message = BotMessage::find((int) $matches[1]);

        if (! $message) {
            $this->adapter->answerCallback($callbackId);

            return;
        }

        $alreadyRated = $message->was_helpful !== null;

        $this->chatService->recordFeedback($message, $matches[2] === 'up');

        if (! $alreadyRated) {
            $channelUser = (string) ($callbackQuery['message']['chat']['id'] ?? $message->channel_user);
            $channelMessageId = (string) ($callbackQuery['message']['message_id'] ?? $message->channel_message_id);

            $this->adapter->editMessage($channelUser, $channelMessageId, $message->answer."\n\n".self::FEEDBACK_THANK_YOU);
        }

        $this->adapter->answerCallback($callbackId);
    }
}
