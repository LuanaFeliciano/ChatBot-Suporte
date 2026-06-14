<?php

namespace App\Filament\Widgets;

use App\Enums\PermissionName;
use App\Models\BotMessage;
use App\Models\Channel;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsageOverviewWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(PermissionName::ViewAnalytics) ?? false;
    }

    protected function getStats(): array
    {
        $stats = [
            Stat::make(__('analytics.usage.total_conversations'), BotMessage::count()),
            Stat::make(
                __('analytics.usage.conversations_last_7_days'),
                BotMessage::whereDate('created_at', '>=', now()->subDays(7))->count(),
            ),
            Stat::make(
                __('analytics.usage.conversations_last_30_days'),
                BotMessage::whereDate('created_at', '>=', now()->subDays(30))->count(),
            ),
            Stat::make(
                __('analytics.usage.unique_users'),
                BotMessage::distinct('channel_user')->count('channel_user'),
            ),
        ];

        foreach (Channel::all() as $channel) {
            $stats[] = Stat::make(
                __('analytics.usage.messages_per_channel', ['channel' => $channel->name]),
                BotMessage::where('channel_id', $channel->id)->count(),
            );
        }

        return $stats;
    }
}
