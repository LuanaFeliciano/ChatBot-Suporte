<?php

use App\Ai\Agents\SupportAgent;
use App\Enums\DocumentStatus;
use App\Models\BotMessage;
use App\Models\Channel;
use App\Models\Document;
use App\Services\ChatService;
use App\Services\SessionService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    SupportAgent::fake(['Resposta do agente.']);
    Redis::flushdb();
    Channel::create(['name' => 'Telegram', 'slug' => 'telegram', 'is_active' => true]);
    Document::factory()->create(['status' => DocumentStatus::Indexed]);
});

it('instantiates SupportAgent with freshSession=true when no Redis session exists', function () {
    $service = new ChatService(new SessionService);
    $answer = $service->process('telegram', 'user_123', 'Alice', 'Minha pergunta?');

    expect($answer)->toBe('Resposta do agente.');
    SupportAgent::assertPrompted('Minha pergunta?');
});

it('instantiates SupportAgent with freshSession=false when a Redis session is active', function () {
    $session = new SessionService;
    $session->touchSession('telegram', 'user_123');

    $service = new ChatService($session);
    $answer = $service->process('telegram', 'user_123', 'Alice', 'Segunda pergunta?');

    expect($answer)->toBe('Resposta do agente.');
    SupportAgent::assertPrompted('Segunda pergunta?');
});

it('persists a BotMessage with channel_id, channel_user, user_name, question, answer', function () {
    $channel = Channel::where('slug', 'telegram')->first();

    $service = new ChatService(new SessionService);
    $service->process('telegram', 'user_456', 'Bob', 'Pergunta de teste?');

    $msg = BotMessage::first();
    expect($msg->channel_id)->toBe($channel->id)
        ->and($msg->channel_user)->toBe('user_456')
        ->and($msg->user_name)->toBe('Bob')
        ->and($msg->question)->toBe('Pergunta de teste?')
        ->and($msg->answer)->toBe('Resposta do agente.');
});

it('records response_ms latency in BotMessage', function () {
    $service = new ChatService(new SessionService);
    $service->process('telegram', 'user_123', null, 'Pergunta?');

    expect(BotMessage::first()->response_ms)->toBeGreaterThanOrEqual(0);
});

it('calls touchSession after a successful response — renews the TTL', function () {
    $session = new SessionService;
    $service = new ChatService($session);

    $service->process('telegram', 'user_123', null, 'Pergunta?');

    expect($session->hasActiveSession('telegram', 'user_123'))->toBeTrue();
});

it('returns the agent answer text as a string', function () {
    $service = new ChatService(new SessionService);
    $answer = $service->process('telegram', 'user_789', 'Carol', 'Pergunta?');

    expect($answer)->toBeString()->toBe('Resposta do agente.');
});

it('persists a BotMessage even when it is the first message (no prior session)', function () {
    $service = new ChatService(new SessionService);
    $service->process('telegram', 'new_user_999', 'Dave', 'Primeira mensagem?');

    expect(BotMessage::where('channel_user', 'new_user_999')->count())->toBe(1);
});
