<?php

use App\Ai\Agents\SupportAgent;
use App\Channels\Telegram\TelegramWebhookHandler;
use App\Enums\DocumentStatus;
use App\Models\BotMessage;
use App\Models\Channel;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    SupportAgent::fake(['Resposta de teste.']);
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 555]])]);
    Redis::flushdb();
    Channel::create(['name' => 'Telegram', 'slug' => 'telegram', 'is_active' => true]);
    Document::factory()->create(['status' => DocumentStatus::Indexed]);
});

it('ignores payloads with no message key', function () {
    app(TelegramWebhookHandler::class)->handle(['update_id' => 999]);

    expect(BotMessage::count())->toBe(0);
    Http::assertNothingSent();
});

it('ignores payloads where the message has no text (e.g. a photo)', function () {
    $payload = [
        'update_id' => 999,
        'message' => ['message_id' => 1, 'from' => ['id' => 111], 'photo' => []],
    ];

    app(TelegramWebhookHandler::class)->handle($payload);

    expect(BotMessage::count())->toBe(0);
    Http::assertNothingSent();
});

it('returns immediately for a duplicate message_id without calling ChatService', function () {
    $payload = [
        'update_id' => 1,
        'message' => ['message_id' => 42, 'from' => ['id' => 111222333, 'first_name' => 'Alice'], 'text' => 'Olá?'],
    ];

    $handler = app(TelegramWebhookHandler::class);
    $handler->handle($payload);
    $handler->handle($payload);

    expect(BotMessage::count())->toBe(1);
});

it('sends the throttle message when the user is rate limited and does not call ChatService', function () {
    $key = 'rate_limit_telegram_'.hash('sha256', '111222333');
    Redis::set($key, 10);
    Redis::expire($key, 60);

    $payload = [
        'update_id' => 1,
        'message' => ['message_id' => 99, 'from' => ['id' => 111222333, 'first_name' => 'Alice'], 'text' => 'Spam?'],
    ];

    app(TelegramWebhookHandler::class)->handle($payload);

    expect(BotMessage::count())->toBe(0);
    Http::assertSent(fn ($req) => str_contains($req['text'], 'muitas mensagens'));
});

it('calls ChatService with canal, channelUser, userName, and question for a valid message', function () {
    $payload = [
        'update_id' => 1,
        'message' => ['message_id' => 1, 'from' => ['id' => 111222333, 'first_name' => 'Alice'], 'text' => 'Como funciona?'],
    ];

    app(TelegramWebhookHandler::class)->handle($payload);

    $msg = BotMessage::first();
    expect($msg)->not->toBeNull()
        ->and($msg->channel_user)->toBe('111222333')
        ->and($msg->user_name)->toBe('Alice')
        ->and($msg->question)->toBe('Como funciona?');
});

it('sends the ChatService answer back to the user via TelegramAdapter', function () {
    $payload = [
        'update_id' => 1,
        'message' => ['message_id' => 1, 'from' => ['id' => 111222333, 'first_name' => 'Alice'], 'text' => 'Como funciona?'],
    ];

    app(TelegramWebhookHandler::class)->handle($payload);

    Http::assertSent(fn ($req) => str_contains($req->url(), '/sendMessage') &&
        $req['chat_id'] === '111222333' &&
        $req['text'] === 'Resposta de teste.'
    );
});

it('persists a BotMessage for every non-duplicate, non-throttled message', function () {
    $handler = app(TelegramWebhookHandler::class);

    $handler->handle([
        'update_id' => 1,
        'message' => ['message_id' => 1, 'from' => ['id' => 111222333, 'first_name' => 'Alice'], 'text' => 'Primeira?'],
    ]);

    $handler->handle([
        'update_id' => 2,
        'message' => ['message_id' => 2, 'from' => ['id' => 111222333, 'first_name' => 'Alice'], 'text' => 'Segunda?'],
    ]);

    expect(BotMessage::count())->toBe(2);
});
