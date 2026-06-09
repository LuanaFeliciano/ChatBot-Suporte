<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class IdempotencyService
{
    public function isDuplicate(string $canal, string|int $messageId): bool
    {
        $key = "msg_lock_{$canal}_{$messageId}";
        // Use the underlying phpredis client directly to avoid facade signature mismatch
        $acquired = Redis::connection()->client()->set($key, '1', ['nx', 'ex' => 30]);

        return ! $acquired;
    }
}
