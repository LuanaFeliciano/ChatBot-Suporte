<?php

namespace App\Services;

use App\Ai\Agents\SupportAgent;
use App\Models\BotMessage;
use App\Models\Channel;

class ChatService
{
    public function __construct(
        private readonly SessionService $session,
    ) {}

    public function process(
        string $canal,
        string $channelUser,
        ?string $userName,
        string $question,
    ): string {
        $freshSession = ! $this->session->hasActiveSession($canal, $channelUser);

        $startedAt = now();
        $agent = new SupportAgent($canal, $channelUser, $freshSession);
        $response = $agent->prompt($question);
        $responseMs = (int) $startedAt->diffInMilliseconds(now());
        $answer = (string) $response;

        $this->session->touchSession($canal, $channelUser);

        $channelId = Channel::where('slug', $canal)->value('id');

        BotMessage::create([
            'channel_id' => $channelId,
            'channel_user' => $channelUser,
            'user_name' => $userName,
            'question' => $question,
            'answer' => $answer,
            'response_ms' => $responseMs,
        ]);

        return $answer;
    }
}
