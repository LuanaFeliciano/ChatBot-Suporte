<?php

namespace App\Http\Controllers\Webhook;

use App\Channels\Telegram\TelegramWebhookHandler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TelegramController extends Controller
{
    public function __invoke(Request $request, TelegramWebhookHandler $handler): Response
    {
        $handler->handle($request->all());

        return response()->noContent();
    }
}
