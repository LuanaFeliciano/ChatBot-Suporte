<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['services.telegram.webhook_secret' => 'test-secret-token']);

    Route::post('/test-telegram-webhook', fn () => response('ok'))
        ->middleware('telegram.webhook');
});

it('allows requests with a valid X-Telegram-Bot-Api-Secret-Token header', function () {
    $this->postJson('/test-telegram-webhook', [], [
        'X-Telegram-Bot-Api-Secret-Token' => 'test-secret-token',
    ])->assertSuccessful();
});

it('rejects requests with a missing header and returns HTTP 401', function () {
    $this->postJson('/test-telegram-webhook')
        ->assertUnauthorized();
});

it('rejects requests with an incorrect token and returns HTTP 401', function () {
    $this->postJson('/test-telegram-webhook', [], [
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong-token',
    ])->assertUnauthorized();
});

it('does not invoke downstream logic when the token is invalid', function () {
    $invoked = false;

    Route::post('/test-telegram-webhook-downstream', function () use (&$invoked) {
        $invoked = true;

        return response('ok');
    })->middleware('telegram.webhook');

    $this->postJson('/test-telegram-webhook-downstream', [], [
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong-token',
    ])->assertUnauthorized();

    expect($invoked)->toBeFalse();
});
