<?php

namespace App\Providers;

use App\Channels\Contracts\ChannelAdapterInterface;
use App\Channels\Telegram\TelegramAdapter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChannelAdapterInterface::class, TelegramAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
