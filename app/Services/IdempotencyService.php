<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class IdempotencyService
{
    public function isDuplicate(string $canal, string|int $messageId): bool
    {
        $key = "idempotency:{$canal}:{$messageId}";
        $acquired = Redis::connection()->client()->set($key, '1', ['nx', 'ex' => 86400]);

        return ! $acquired;
    }
}
