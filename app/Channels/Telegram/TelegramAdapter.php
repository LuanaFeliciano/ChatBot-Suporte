<?php

namespace App\Channels\Telegram;

use App\Channels\Contracts\ChannelAdapterInterface;
use Illuminate\Support\Facades\Http;

class TelegramAdapter implements ChannelAdapterInterface
{
    public function extractMessage(array $payload): ?array
    {
        $message = $payload['message'] ?? null;
        if (! $message || ! isset($message['text'])) {
            return null;
        }

        $from = $message['from'] ?? [];
        $firstName = trim($from['first_name'] ?? '');
        $lastName = trim($from['last_name'] ?? '');
        $fullName = trim("{$firstName} {$lastName}") ?: null;

        return [
            'message_id' => (string) $message['message_id'],
            'channel_user' => (string) ($from['id'] ?? ''),
            'user_name' => $fullName,
            'text' => $message['text'],
        ];
    }

    public function sendReply(string $channelUser, string $text): void
    {
        Http::post(
            'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage',
            ['chat_id' => $channelUser, 'text' => $text, 'parse_mode' => 'HTML'],
        );
    }
}
