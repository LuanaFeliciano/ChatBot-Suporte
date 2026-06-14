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

    /**
     * Send a plain-text reply to the given user and return the channel's message ID.
     *
     * When $botMessageId is given, the adapter may attach feedback controls
     * (e.g. 👍/👎 buttons) referencing that bot_messages row.
     */
    public function sendReply(string $channelUser, string $text, ?int $botMessageId = null): string;

    /** Broadcast a "typing…" indicator to the given user (best-effort, no-op if unsupported). */
    public function sendTypingAction(string $channelUser): void;

    /** Edit a previously sent message, replacing its text and removing any attached controls. */
    public function editMessage(string $channelUser, string $channelMessageId, string $text): void;

    /** Acknowledge a callback/interaction so the channel dismisses its loading state. */
    public function answerCallback(string $callbackId, ?string $text = null): void;
}
