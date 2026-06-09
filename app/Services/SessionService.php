<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class SessionService
{
    private const TTL_SECONDS = 86400;

    public function hasActiveSession(string $canal, string $channelUserId): bool
    {
        return (bool) Redis::exists($this->key($canal, $channelUserId));
    }

    public function touchSession(string $canal, string $channelUserId): void
    {
        Redis::setex($this->key($canal, $channelUserId), self::TTL_SECONDS, 1);
    }

    private function key(string $canal, string $channelUserId): string
    {
        return 'bot_session_'.$canal.'_'.hash('sha256', $channelUserId);
    }
}
