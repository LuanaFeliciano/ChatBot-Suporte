<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTelegramWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.telegram.webhook_secret');
        $provided = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (! $provided || ! hash_equals($expected, $provided)) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
