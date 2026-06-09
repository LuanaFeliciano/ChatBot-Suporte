<?php

namespace App\Services;

use App\Ai\Agents\SupportAgent;
use App\Enums\DocumentStatus;
use App\Models\BotMessage;
use App\Models\Channel;
use App\Models\Document;

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
        if (! Document::where('status', DocumentStatus::Indexed)->exists()) {
            return 'Ainda estamos configurando nossa base de conhecimento. Em breve estaremos prontos para te ajudar! Por enquanto, entre em contato com o suporte humano.';
        }

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
