<?php

use App\Ai\Agents\SupportAgent;
use App\Models\BotMessage;
use App\Models\Channel;
use App\Services\ChatService;
use App\Services\SessionService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
    Channel::create(['name' => 'Telegram', 'slug' => 'telegram', 'is_active' => true]);
});

it('SupportAgent system prompt contains explicit instruction not to fabricate answers', function () {
    expect(SupportAgent::SYSTEM_PROMPT)->toContain('Nunca invente');
});

it('a BotMessage is persisted even when the agent returns a fallback response', function () {
    $url = config('services.support_ticket_url');
    SupportAgent::fake(["Não encontrei essa informação. Abra um chamado: {$url}"]);

    $service = new ChatService(new SessionService);
    $service->process('telegram', 'user_123', 'Alice', 'Pergunta sem resposta?');

    expect(BotMessage::count())->toBe(1);
});

it('fallback answer references the support ticket URL from config', function () {
    $url = config('services.support_ticket_url');
    SupportAgent::fake(["Não encontrei essa informação. Abra um chamado: {$url}"]);

    $service = new ChatService(new SessionService);
    $service->process('telegram', 'user_123', null, 'Pergunta sem resposta?');

    expect(BotMessage::first()->answer)->toContain($url);
});
