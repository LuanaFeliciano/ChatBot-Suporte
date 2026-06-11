<?php

namespace App\Providers;

use App\Channels\Contracts\ChannelAdapterInterface;
use App\Channels\Telegram\TelegramAdapter;
use App\Models\BotMessage;
use App\Models\Document;
use App\Models\User;
use App\Policies\BotMessagePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(BotMessage::class, BotMessagePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
