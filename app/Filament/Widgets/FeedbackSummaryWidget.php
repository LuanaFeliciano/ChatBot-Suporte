<?php

namespace App\Filament\Widgets;

use App\Enums\PermissionName;
use App\Models\BotMessage;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FeedbackSummaryWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(PermissionName::ViewAnalytics) ?? false;
    }

    public function getHeading(): string
    {
        return __('analytics.feedback.heading');
    }

    public function getDescription(): string
    {
        $total = $this->baseQuery()->count();

        if ($total === 0) {
            return __('analytics.feedback.description', [
                'positive' => '—',
                'negative' => '—',
                'unrated' => '—',
            ]);
        }

        $positive = $this->baseQuery()->where('was_helpful', true)->count();
        $negative = $this->baseQuery()->where('was_helpful', false)->count();
        $unrated = $total - $positive - $negative;

        return __('analytics.feedback.description', [
            'positive' => $this->formatPercentage($positive / $total * 100),
            'negative' => $this->formatPercentage($negative / $total * 100),
            'unrated' => $this->formatPercentage($unrated / $total * 100),
        ]);
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) ($this->pageFilters['range'] ?? 30);
        $start = now()->subDays($days - 1)->startOfDay();

        $positiveCounts = $this->dailyCounts($start, true);
        $negativeCounts = $this->dailyCounts($start, false);

        $labels = [];
        $positiveData = [];
        $negativeData = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $labels[] = $date;
            $positiveData[] = (int) ($positiveCounts[$date] ?? 0);
            $negativeData[] = (int) ($negativeCounts[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('analytics.feedback.positive'),
                    'data' => $positiveData,
                ],
                [
                    'label' => __('analytics.feedback.negative'),
                    'data' => $negativeData,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @return Collection<string, int>
     */
    private function dailyCounts(Carbon $start, bool $wasHelpful): Collection
    {
        return BotMessage::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', $start)
            ->where('was_helpful', $wasHelpful)
            ->groupBy('date')
            ->pluck('total', 'date');
    }

    private function baseQuery(): Builder
    {
        $days = (int) ($this->pageFilters['range'] ?? 30);

        return BotMessage::where('created_at', '>=', now()->subDays($days - 1)->startOfDay());
    }

    private function formatPercentage(float $value): string
    {
        return number_format($value, 1).'%';
    }
}
