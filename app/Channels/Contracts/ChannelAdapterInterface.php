<?php
namespace App\Channels\Contracts;

interface ChannelAdapterInterface
{
    /**
     * Parse the raw channel payload and return a normalized message array,
     * or null for non-text updates (photos, stickers, etc.).
     *
     * Return shape:
     *   ['message_id' => string, 'channel_user' => string,
     *    'user_name' => string|null, 'text' => string]
     */
    public function extractMessage(array $payload): ?array;

    /** Send a plain-text reply to the given user. */
    public function sendReply(string $channelUser, string $text): void;
}
