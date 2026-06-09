<?php

use App\Services\SessionService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

it('returns false when no session key exists for the user', function () {
    $service = new SessionService;

    expect($service->hasActiveSession('telegram', 'user_123'))->toBeFalse();
});

it('returns true after touchSession is called', function () {
    $service = new SessionService;
    $service->touchSession('telegram', 'user_123');

    expect($service->hasActiveSession('telegram', 'user_123'))->toBeTrue();
});

it('session key is scoped per canal and uses a sha256 hash of the user id', function () {
    $service = new SessionService;
    $service->touchSession('telegram', 'user_123');

    $expectedKey = 'bot_session_telegram_' . hash('sha256', 'user_123');

    expect(Redis::exists($expectedKey))->toBe(1);
});

it('touchSession sets a TTL of 86400 seconds (24h)', function () {
    $service = new SessionService;
    $service->touchSession('telegram', 'user_123');

    $key = 'bot_session_telegram_' . hash('sha256', 'user_123');

    expect(Redis::ttl($key))
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(86400);
});

it('touchSession renews the TTL on each call — rolling window', function () {
    $service = new SessionService;
    $service->touchSession('telegram', 'user_123');

    $key = 'bot_session_telegram_' . hash('sha256', 'user_123');
    Redis::expire($key, 100);

    $service->touchSession('telegram', 'user_123');

    expect(Redis::ttl($key))->toBeGreaterThan(100);
});

it('different canals maintain independent sessions for the same user', function () {
    $service = new SessionService;
    $service->touchSession('telegram', 'user_123');

    expect($service->hasActiveSession('whatsapp', 'user_123'))->toBeFalse();
});

it('session expires and hasActiveSession returns false after TTL', function () {
    $service = new SessionService;
    $service->touchSession('telegram', 'user_123');

    $key = 'bot_session_telegram_' . hash('sha256', 'user_123');
    Redis::expire($key, 1);
    sleep(2);

    expect($service->hasActiveSession('telegram', 'user_123'))->toBeFalse();
});
