<?php

namespace App\Channels\Telegram;

use App\Channels\Contracts\ChannelAdapterInterface;
use Illuminate\Support\Facades\Http;

class TelegramAdapter implements ChannelAdapterInterface
{
    private const THUMBS_UP_LABEL = '👍 Resolvido';

    private const THUMBS_DOWN_LABEL = '👎 Não resolvido';

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

    public function sendReply(string $channelUser, string $text, ?int $botMessageId = null): string
    {
        $payload = ['chat_id' => $channelUser, 'text' => $this->toHtml($text), 'parse_mode' => 'HTML'];

        if ($botMessageId !== null) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [[
                    ['text' => self::THUMBS_UP_LABEL, 'callback_data' => "feedback:{$botMessageId}:up"],
                    ['text' => self::THUMBS_DOWN_LABEL, 'callback_data' => "feedback:{$botMessageId}:down"],
                ]],
            ]);
        }

        $response = Http::post(
            'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage',
            $payload,
        );

        return (string) $response->json('result.message_id', '');
    }

    private function toHtml(string $text): string
    {
        // Bold: **text** → <b>text</b>
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<b>$1</b>', $text);
        // Italic: *text* or _text_ → <i>text</i>
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<i>$1</i>', $text ?? '');
        $text = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<i>$1</i>', $text ?? '');
        // Code: `text` → <code>text</code>
        $text = preg_replace('/`(.+?)`/s', '<code>$1</code>', $text ?? '');

        return $text ?? '';
    }

    public function sendTypingAction(string $channelUser): void
    {
        Http::post(
            'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendChatAction',
            ['chat_id' => $channelUser, 'action' => 'typing'],
        );
    }

    public function editMessage(string $channelUser, string $channelMessageId, string $text): void
    {
        Http::post(
            'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/editMessageText',
            [
                'chat_id' => $channelUser,
                'message_id' => $channelMessageId,
                'text' => $this->toHtml($text),
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => []]),
            ],
        );
    }

    public function answerCallback(string $callbackId, ?string $text = null): void
    {
        Http::post(
            'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/answerCallbackQuery',
            array_filter([
                'callback_query_id' => $callbackId,
                'text' => $text,
            ]),
        );
    }
}
