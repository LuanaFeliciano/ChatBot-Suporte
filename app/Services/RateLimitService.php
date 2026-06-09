<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;

class RateLimitService
{
    public function isRateLimited(string $canal, string $channelUserId): bool
    {
        $allowed = RateLimiter::attempt(
            key: "rate_limit_{$canal}_".hash('sha256', $channelUserId),
            maxAttempts: 10,
            callback: fn () => true,
            decaySeconds: 60,
        );

        return ! $allowed;
    }
}
