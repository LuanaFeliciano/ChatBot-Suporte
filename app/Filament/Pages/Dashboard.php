<?php

namespace App\Filament\Pages;

use App\Enums\PermissionName;
use App\Filament\Widgets\AiPerformanceWidget;
use App\Filament\Widgets\DailyMessageVolumeChart;
use App\Filament\Widgets\KnowledgeBaseHealthWidget;
use App\Filament\Widgets\UsageOverviewWidget;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'analytics';

    protected static ?string $slug = 'analytics';

    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return __('analytics.navigation_label');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedChartBar;
    }

    public function getTitle(): string|Htmlable
    {
        return __('analytics.navigation_label');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermissionName::ViewAnalytics) ?? false;
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('range')
                    ->label(__('analytics.filters.range'))
                    ->options([
                        '7' => __('analytics.filters.range_7'),
                        '30' => __('analytics.filters.range_30'),
                        '90' => __('analytics.filters.range_90'),
                    ])
                    ->default('30')
                    ->native(false)
                    ->live(),
            ]);
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            UsageOverviewWidget::class,
            DailyMessageVolumeChart::class,
            AiPerformanceWidget::class,
            KnowledgeBaseHealthWidget::class,
        ];
    }
}
