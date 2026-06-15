<?php

use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

it('returns false for a new message — lock is acquired', function () {
    $service = new IdempotencyService;

    expect($service->isDuplicate('telegram', '123'))->toBeFalse();
});

it('returns true for the same message_id sent twice — duplicate', function () {
    $service = new IdempotencyService;
    $service->isDuplicate('telegram', '123');

    expect($service->isDuplicate('telegram', '123'))->toBeTrue();
});

it('lock key is scoped per canal and message_id', function () {
    $service = new IdempotencyService;
    $service->isDuplicate('telegram', '42');

    expect(Redis::exists('idempotency:telegram:42'))->toBe(1);
});

it('same message_id on different canals do not conflict', function () {
    $service = new IdempotencyService;
    $service->isDuplicate('telegram', '99');

    expect($service->isDuplicate('whatsapp', '99'))->toBeFalse();
});

it('different message_ids on the same canal do not conflict', function () {
    $service = new IdempotencyService;
    $service->isDuplicate('telegram', '1');

    expect($service->isDuplicate('telegram', '2'))->toBeFalse();
});

it('acquired lock has a TTL of 24 hours', function () {
    $service = new IdempotencyService;
    $service->isDuplicate('telegram', '42');

    expect(Redis::ttl('idempotency:telegram:42'))
        ->toBeGreaterThan(0)
        ->toBeLessThanOrEqual(86400);
});
