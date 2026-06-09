<?php

use App\Channels\Telegram\TelegramAdapter;
use Illuminate\Support\Facades\Http;

it('extracts message_id, channel_user, user_name and text from a valid update payload', function () {
    $payload = [
        'message' => [
            'message_id' => 42,
            'from' => ['id' => 9876, 'first_name' => 'Ana', 'last_name' => 'Silva'],
            'text' => 'Olá, preciso de ajuda',
        ],
    ];

    $result = (new TelegramAdapter)->extractMessage($payload);

    expect($result)->toBe([
        'message_id' => '42',
        'channel_user' => '9876',
        'user_name' => 'Ana Silva',
        'text' => 'Olá, preciso de ajuda',
    ]);
});

it('returns null when the payload has no message key', function () {
    expect((new TelegramAdapter)->extractMessage([]))->toBeNull();
});

it('returns null when the message has no text key (e.g. a photo update)', function () {
    $payload = [
        'message' => [
            'message_id' => 10,
            'from' => ['id' => 1],
            'photo' => [['file_id' => 'abc']],
        ],
    ];

    expect((new TelegramAdapter)->extractMessage($payload))->toBeNull();
});

it('user_name is null when both first_name and last_name are absent from payload', function () {
    $payload = [
        'message' => [
            'message_id' => 1,
            'from' => ['id' => 100],
            'text' => 'Hi',
        ],
    ];

    $result = (new TelegramAdapter)->extractMessage($payload);

    expect($result['user_name'])->toBeNull();
});

it('user_name concatenates first_name and last_name when both are present', function () {
    $payload = [
        'message' => [
            'message_id' => 1,
            'from' => ['id' => 100, 'first_name' => 'João', 'last_name' => 'Costa'],
            'text' => 'Hi',
        ],
    ];

    $result = (new TelegramAdapter)->extractMessage($payload);

    expect($result['user_name'])->toBe('João Costa');
});

it('sendReply posts to the Telegram sendMessage endpoint with the correct chat_id and text', function () {
    Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);

    (new TelegramAdapter)->sendReply('123456', 'Hello');

    Http::assertSent(fn ($req) => str_contains($req->url(), '/sendMessage') &&
        $req['chat_id'] === '123456' &&
        $req['text'] === 'Hello'
    );
});
