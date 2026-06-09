<?php

use App\Services\RateLimitService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

it('returns false for the 1st through 10th messages in a window', function () {
    $service = new RateLimitService;

    foreach (range(1, 10) as $i) {
        expect($service->isRateLimited('telegram', 'user-1'))->toBeFalse("Failed on message #{$i}");
    }
});

it('returns true for the 11th message in the same window', function () {
    $service = new RateLimitService;

    foreach (range(1, 10) as $i) {
        $service->isRateLimited('telegram', 'user-1');
    }

    expect($service->isRateLimited('telegram', 'user-1'))->toBeTrue();
});

it('key is scoped per canal and uses a sha256 hash of the user id', function () {
    $service = new RateLimitService;
    $service->isRateLimited('telegram', 'user-1');

    $expectedKey = 'rate_limit_telegram_'.hash('sha256', 'user-1');
    expect(Redis::exists($expectedKey))->toBe(1);
});

it('different users on the same canal have independent counters', function () {
    $service = new RateLimitService;

    foreach (range(1, 10) as $i) {
        $service->isRateLimited('telegram', 'user-A');
    }

    expect($service->isRateLimited('telegram', 'user-B'))->toBeFalse();
});

it('same user on different canals have independent counters', function () {
    $service = new RateLimitService;

    foreach (range(1, 10) as $i) {
        $service->isRateLimited('telegram', 'user-1');
    }

    expect($service->isRateLimited('whatsapp', 'user-1'))->toBeFalse();
});

it('sets a TTL of 60 seconds on the first increment', function () {
    $service = new RateLimitService;
    $service->isRateLimited('telegram', 'user-1');

    $key = 'rate_limit_telegram_'.hash('sha256', 'user-1');
    expect(Redis::ttl($key))
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(60);
});

it('does not reset the TTL on subsequent increments within the window', function () {
    $service = new RateLimitService;

    $service->isRateLimited('telegram', 'user-1');

    $key = 'rate_limit_telegram_'.hash('sha256', 'user-1');
    $ttlAfterFirst = Redis::ttl($key);

    Redis::setex($key, 45, 3);

    $service->isRateLimited('telegram', 'user-1');

    expect(Redis::ttl($key))->toBeLessThanOrEqual(45);
});
