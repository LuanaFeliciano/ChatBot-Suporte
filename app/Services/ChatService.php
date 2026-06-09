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

    private const GREETING_RESPONSE = 'Olá! Posso te ajudar com dúvidas sobre o aplicativo. Me conta o que está acontecendo ou qual é a sua dúvida. 😊';

    private const GREETINGS = [
        'oi', 'olá', 'ola', 'oie', 'hey', 'hi', 'hello',
        'bom dia', 'boa tarde', 'boa noite',
        'opa', 'eai', 'e aí', 'e ai', 'tudo bem', 'tudo bom',
        'start',
    ];

    private function isGreeting(string $text): bool
    {
        $normalized = mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $text)));

        return in_array($normalized, self::GREETINGS, strict: true);
    }

    private function cleanAnswer(string $answer): string
    {
        // Strip OpenAI File Search citations wrapped in Unicode private-use sentinels (U+E200…U+E201)
        $answer = preg_replace('/\x{E200}filecite\x{E202}[\w]+\x{E201}/u', '', $answer);
        // Fallback: strip bare filecite markers if sentinels were already stripped upstream
        $answer = preg_replace('/filecite\w+/', '', $answer ?? '');

        return trim($answer ?? '');
    }

    public function process(
        string $canal,
        string $channelUser,
        ?string $userName,
        string $question,
    ): string {
        if ($this->isGreeting($question)) {
            return self::GREETING_RESPONSE;
        }

        if (! Document::where('status', DocumentStatus::Indexed)->exists()) {
            return 'Ainda estamos configurando nossa base de conhecimento. Em breve estaremos prontos para te ajudar! Por enquanto, entre em contato com o suporte humano.';
        }

        $freshSession = ! $this->session->hasActiveSession($canal, $channelUser);

        $startedAt = now();
        $agent = new SupportAgent($canal, $channelUser, $freshSession);
        $response = $agent->prompt($question);
        $responseMs = (int) $startedAt->diffInMilliseconds(now());
        $answer = $this->cleanAnswer((string) $response);

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
