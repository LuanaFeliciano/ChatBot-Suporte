<?php

namespace App\Filament\Widgets;

use App\Enums\PermissionName;
use App\Models\BotMessage;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DailyMessageVolumeChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(PermissionName::ViewAnalytics) ?? false;
    }

    public function getHeading(): string
    {
        return __('analytics.usage.daily_message_volume');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) ($this->pageFilters['range'] ?? 30);

        $start = now()->subDays($days - 1)->startOfDay();

        $counts = BotMessage::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $start)
            ->groupBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $labels[] = $date;
            $data[] = (int) ($counts[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('analytics.usage.daily_message_volume'),
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
