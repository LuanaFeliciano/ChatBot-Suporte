<?php

namespace App\Channels\Telegram;

use App\Jobs\ProcessChatMessage;
use App\Services\IdempotencyService;
use App\Services\RateLimitService;
use Illuminate\Support\Facades\Redis;

class TelegramWebhookHandler
{
    public function __construct(
        private readonly TelegramAdapter $adapter,
        private readonly IdempotencyService $idempotency,
        private readonly RateLimitService $rateLimit,
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
            return;
        }

        $hash = hash('sha256', $channelUser);
        $pendingKey = "user_pending:telegram:{$hash}";
        $genKey = "user_gen:telegram:{$hash}";

        Redis::connection()->client()->rpush($pendingKey, json_encode([
            'user_name' => $userName,
            'question' => $question,
        ]));
        Redis::connection()->client()->expire($pendingKey, 300);

        $generation = (int) Redis::connection()->client()->incr($genKey);
        Redis::connection()->client()->expire($genKey, 300);

        ProcessChatMessage::dispatch('telegram', $channelUser, $generation)
            ->onQueue('chat')
            ->delay(now()->addSeconds(3));
    }
}
