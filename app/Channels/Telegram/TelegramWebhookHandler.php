<?php

namespace App\Channels\Telegram;
//aqui sera implementado a logica para processar a mensagem (verificar limit, idempotencia, chamar a ia, salvar no banco e responder)

use App\Services\ChatService;
use App\Services\IdempotencyService;
use App\Services\RateLimitService;

class TelegramWebhookHandler
{
    public function __construct(
        private readonly TelegramAdapter $adapter,
        private readonly IdempotencyService $idempotency,
        private readonly RateLimitService $rateLimit,
        private readonly ChatService $chat,
    ) {}

    public function handle(array $payload): void
    {
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
            $this->adapter->sendReply(
                $channelUser,
                'Você enviou muitas mensagens em pouco tempo. Aguarde um momento e tente novamente. 😊',
            );

            return;
        }

        $answer = $this->chat->process('telegram', $channelUser, $userName, $question);

        $this->adapter->sendReply($channelUser, $answer);
    }
}
