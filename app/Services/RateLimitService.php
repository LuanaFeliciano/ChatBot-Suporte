<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RateLimitService
{
    public function isRateLimited(string $canal, string $channelUserId): bool
    {
        $key = 'rate_limit_'.$canal.'_'.hash('sha256', $channelUserId);
        $count = Redis::incr($key);

        if ($count === 1) {
            Redis::expire($key, 60);
        }

        return $count > 10;
    }
}
