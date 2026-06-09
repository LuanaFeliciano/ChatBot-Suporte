<?php

use App\Http\Controllers\Webhook\TelegramController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/telegram', TelegramController::class)
    ->middleware('telegram.webhook')
    ->name('webhook.telegram');
