<?php

namespace App\Filament\Widgets;

use App\Enums\PermissionName;
use App\Models\BotMessage;
use App\Models\Channel;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AiPerformanceWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(PermissionName::ViewAnalytics) ?? false;
    }

    protected function getStats(): array
    {
        $query = $this->baseQuery();

        $stats = [
            Stat::make(
                __('analytics.ai_performance.avg_response_time'),
                $this->formatMs($this->average($query, 'response_ms')),
            ),
            Stat::make(
                __('analytics.ai_performance.p95_response_time'),
                $this->formatMs($this->percentile($query, 'response_ms', 0.95)),
            ),
            Stat::make(
                __('analytics.ai_performance.escalated'),
                $this->formatPercentage($this->percentageEscalated($query)),
            ),
            Stat::make(
                __('analytics.ai_performance.fresh_sessions'),
                $this->formatPercentage($this->percentageFreshSessions($query)),
            ),
        ];

        foreach (Channel::all() as $channel) {
            $channelQuery = (clone $query)->where('channel_id', $channel->id);

            $stats[] = Stat::make(
                __('analytics.ai_performance.channel_avg_response_time', ['channel' => $channel->name]),
                $this->formatMs($this->average($channelQuery, 'response_ms')),
            );

            $stats[] = Stat::make(
                __('analytics.ai_performance.channel_p95_response_time', ['channel' => $channel->name]),
                $this->formatMs($this->percentile($channelQuery, 'response_ms', 0.95)),
            );
        }

        return $stats;
    }

    private function baseQuery(): Builder
    {
        $days = (int) ($this->pageFilters['range'] ?? 30);

        return BotMessage::where('created_at', '>=', now()->subDays($days - 1)->startOfDay());
    }

    private function average(Builder $query, string $column): ?float
    {
        return (clone $query)->avg($column);
    }

    private function percentile(Builder $query, string $column, float $percentile): ?float
    {
        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        return (clone $query)
            ->selectRaw("percentile_cont({$percentile}) within group (order by {$wrapped}) as aggregate")
            ->value('aggregate');
    }

    private function percentageEscalated(Builder $query): ?float
    {
        $total = (clone $query)->count();

        if ($total === 0) {
            return null;
        }

        $escalated = (clone $query)->escalated()->count();

        return $escalated / $total * 100;
    }

    private function percentageFreshSessions(Builder $query): ?float
    {
        $total = (clone $query)->count();

        if ($total === 0) {
            return null;
        }

        $fresh = (clone $query)->where('was_fresh_session', true)->count();

        return $fresh / $total * 100;
    }

    private function formatMs(?float $value): string
    {
        return $value !== null ? number_format($value / 1000, 1).'s' : '—';
    }

    private function formatPercentage(?float $value): string
    {
        return $value !== null ? number_format($value, 1).'%' : '—';
    }
}
