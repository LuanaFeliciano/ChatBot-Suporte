<?php

use App\Services\RateLimitService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    foreach ([
        'rate_limit_telegram_'.hash('sha256', 'user-1'),
        'rate_limit_telegram_'.hash('sha256', 'user-A'),
        'rate_limit_telegram_'.hash('sha256', 'user-B'),
        'rate_limit_whatsapp_'.hash('sha256', 'user-1'),
    ] as $key) {
        RateLimiter::clear($key);
    }
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
    expect((int) RateLimiter::attempts($expectedKey))->toBe(1);
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

    expect(RateLimiter::availableIn($key))
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(60);
});

it('does not reset the TTL on subsequent increments within the window', function () {
    $service = new RateLimitService;
    $key = 'rate_limit_telegram_'.hash('sha256', 'user-1');

    $start = now();
    Carbon::setTestNow($start);
    $service->isRateLimited('telegram', 'user-1');
    $firstAvailableIn = RateLimiter::availableIn($key);

    Carbon::setTestNow($start->copy()->addSeconds(15));
    $service->isRateLimited('telegram', 'user-1');
    $secondAvailableIn = RateLimiter::availableIn($key);

    Carbon::setTestNow();

    expect($secondAvailableIn)->toBeLessThan($firstAvailableIn);
});
