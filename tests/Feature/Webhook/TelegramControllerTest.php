<?php

use App\Channels\Telegram\TelegramWebhookHandler;

beforeEach(function () {
    config(['services.telegram.webhook_secret' => 'test-secret']);
});

it('POST /api/webhook/telegram returns 204 with a valid signature header', function () {
    $this->app->instance(TelegramWebhookHandler::class, new class extends TelegramWebhookHandler
    {
        public function __construct() {}

        public function handle(array $payload): void {}
    });

    $this->postJson('/api/webhook/telegram', ['update_id' => 1], [
        'X-Telegram-Bot-Api-Secret-Token' => 'test-secret',
    ])->assertNoContent();
});

it('POST /api/webhook/telegram returns 401 when the signature header is missing', function () {
    $this->postJson('/api/webhook/telegram', ['update_id' => 1])
        ->assertUnauthorized();
});

it('POST /api/webhook/telegram delegates the payload to TelegramWebhookHandler', function () {
    $received = null;

    $this->app->instance(TelegramWebhookHandler::class, new class($received) extends TelegramWebhookHandler
    {
        public function __construct(private mixed &$received) {}

        public function handle(array $payload): void
        {
            $this->received = $payload;
        }
    });

    $payload = ['update_id' => 99, 'message' => ['text' => 'test']];

    $this->postJson('/api/webhook/telegram', $payload, [
        'X-Telegram-Bot-Api-Secret-Token' => 'test-secret',
    ])->assertNoContent();

    expect($received)->toBe($payload);
});

it('POST /api/webhook/telegram always returns 204 regardless of handler outcome', function () {
    $this->app->instance(TelegramWebhookHandler::class, new class extends TelegramWebhookHandler
    {
        public function __construct() {}

        public function handle(array $payload): void {}
    });

    $this->postJson('/api/webhook/telegram', [], [
        'X-Telegram-Bot-Api-Secret-Token' => 'test-secret',
    ])->assertNoContent();
});
