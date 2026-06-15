<?php

namespace App\Filament\Pages;

use App\Ai\Agents\SupportAgent;
use App\Enums\DocumentStatus;
use App\Enums\PermissionName;
use App\Filament\Resources\BotMessages\BotMessageResource;
use App\Models\BotMessage;
use App\Models\Document;
use App\Services\KnowledgeGapAnalyzer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class KnowledgeGaps extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $routePath = 'knowledge-gaps';

    protected static ?string $slug = 'knowledge-gaps';

    protected string $view = 'filament.pages.knowledge-gaps';

    #[Url]
    public string $activeTab = 'escalated';

    #[Url]
    public ?string $selectedQuestionNormalized = null;

    public static function getNavigationLabel(): string
    {
        return __('knowledge_gaps.navigation_label');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedExclamationTriangle;
    }

    public function getTitle(): string|Htmlable
    {
        return __('knowledge_gaps.navigation_label');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->can(PermissionName::ViewKnowledgeGaps) ?? false)
            || ($user?->can(PermissionName::ViewFeedback) ?? false);
    }

    /**
     * @return array{positive: int, negative: int, unrated: int}
     */
    public function feedbackCounts(): array
    {
        return [
            'positive' => BotMessage::where('was_helpful', true)->count(),
            'negative' => BotMessage::where('was_helpful', false)->count(),
            'unrated' => BotMessage::whereNull('was_helpful')->count(),
        ];
    }

    public function indexingErrorCount(): int
    {
        return Document::where('status', DocumentStatus::Error)->count();
    }

    /**
     * @return array<string, string>
     */
    public function getTabs(): array
    {
        return [
            'escalated' => __('knowledge_gaps.tabs.escalated'),
            'low_confidence' => __('knowledge_gaps.tabs.low_confidence'),
            'recurring' => __('knowledge_gaps.tabs.recurring'),
            'not_helpful' => __('knowledge_gaps.tabs.not_helpful'),
        ];
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedQuestionNormalized = null;
        $this->tableSort = null;
        $this->resetTable();
    }

    public function selectGroup(string $questionNormalized): void
    {
        $this->selectedQuestionNormalized = $questionNormalized;
        $this->tableSort = null;
        $this->resetTable();
    }

    public function backToGroups(): void
    {
        $this->selectedQuestionNormalized = null;
        $this->tableSort = null;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return match (true) {
            $this->activeTab === 'low_confidence' => $this->lowConfidenceTable($table),
            $this->activeTab === 'recurring' && $this->selectedQuestionNormalized !== null => $this->recurringDrilldownTable($table),
            $this->activeTab === 'recurring' => $this->recurringGroupsTable($table),
            $this->activeTab === 'not_helpful' => $this->notHelpfulTable($table),
            default => $this->escalatedTable($table),
        };
    }

    private function escalatedTable(Table $table): Table
    {
        return $table
            ->query(
                BotMessage::query()
                    ->with('channel')
                    ->where(fn (Builder $query): Builder => $query->escalated()
                        ->orWhere(fn (Builder $query): Builder => $query->noDocumentIndexed()))
            )
            ->columns($this->messageColumns())
            ->filters($this->messageFilters())
            ->defaultSort('created_at', 'desc');
    }

    private function lowConfidenceTable(Table $table): Table
    {
        return $table
            ->query(BotMessage::query()->with('channel')->lowConfidence())
            ->columns($this->messageColumns())
            ->filters($this->messageFilters())
            ->recordActions([$this->viewConversationAction()])
            ->defaultSort('created_at', 'desc');
    }

    private function notHelpfulTable(Table $table): Table
    {
        return $table
            ->query(BotMessage::query()->with('channel')->where('was_helpful', false))
            ->columns($this->messageColumns())
            ->filters($this->messageFilters())
            ->recordActions([$this->viewConversationAction()])
            ->defaultSort('created_at', 'desc');
    }

    private function recurringGroupsTable(Table $table): Table
    {
        $analyzer = app(KnowledgeGapAnalyzer::class);

        return $table
            ->query(
                BotMessage::query()
                    ->select([
                        DB::raw('min(id) as id'),
                        'question_normalized',
                        DB::raw('count(*) as occurrences'),
                        DB::raw('min(created_at) as first_seen'),
                        DB::raw('max(created_at) as last_seen'),
                        DB::raw('count(distinct channel_user) as distinct_users'),
                        DB::raw('coalesce(bool_or(was_helpful = false), false)::int as has_negative_feedback'),
                        DB::raw("count(*) filter (where answer like '%".str_replace("'", "''", SupportAgent::ESCALATION_PHRASE)."%') as escalated_count"),
                    ])
                    ->whereNotNull('question_normalized')
                    ->groupBy('question_normalized')
            )
            ->defaultKeySort(false)
            ->columns([
                IconColumn::make('has_negative_feedback')
                    ->label(__('knowledge_gaps.fields.has_negative_feedback'))
                    ->icon(fn (mixed $state): Heroicon => ((bool) $state) ? Heroicon::OutlinedHandThumbDown : Heroicon::OutlinedMinus)
                    ->color(fn (mixed $state): string => ((bool) $state) ? 'danger' : 'gray'),
                TextColumn::make('question_normalized')
                    ->label(__('knowledge_gaps.fields.question'))
                    ->wrap(),
                TextColumn::make('occurrences')
                    ->label(__('knowledge_gaps.fields.occurrences'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('first_seen')
                    ->label(__('knowledge_gaps.fields.first_seen'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_seen')
                    ->label(__('knowledge_gaps.fields.last_seen'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('distinct_users')
                    ->label(__('knowledge_gaps.fields.distinct_users'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('recommendation')
                    ->label(__('knowledge_gaps.fields.recommendation'))
                    ->state(fn (BotMessage $record): string => __('knowledge_gaps.recommendations.'.$analyzer->recommend($record->occurrences, $record->escalated_count, (bool) $record->has_negative_feedback)['label']))
                    ->url(fn (BotMessage $record): ?string => $analyzer->recommend($record->occurrences, $record->escalated_count, (bool) $record->has_negative_feedback)['route'])
                    ->badge(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('knowledge_gaps.actions.view_messages'))
                    ->action(fn (BotMessage $record) => $this->selectGroup($record->question_normalized)),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByDesc('has_negative_feedback')
                ->orderByDesc('occurrences'));
    }

    private function recurringDrilldownTable(Table $table): Table
    {
        return $table
            ->query(
                BotMessage::query()
                    ->with('channel')
                    ->where('question_normalized', $this->selectedQuestionNormalized)
            )
            ->columns($this->messageColumns())
            ->recordActions([$this->viewConversationAction()])
            ->defaultSort('created_at', 'desc');
    }

    private function viewConversationAction(): Action
    {
        return Action::make('view')
            ->label(__('knowledge_gaps.actions.view_conversation'))
            ->url(fn (BotMessage $record): string => BotMessageResource::getUrl('timeline', [
                'channel' => $record->channel_id,
                'channelUser' => $record->channel_user,
            ]));
    }

    /**
     * @return array<int, TextColumn>
     */
    private function messageColumns(): array
    {
        return [
            TextColumn::make('question')
                ->label(__('knowledge_gaps.fields.question'))
                ->wrap()
                ->limit(80),
            TextColumn::make('created_at')
                ->label(__('knowledge_gaps.fields.created_at'))
                ->dateTime()
                ->sortable(),
            TextColumn::make('channel.name')
                ->label(__('knowledge_gaps.fields.channel')),
            TextColumn::make('user')
                ->label(__('knowledge_gaps.fields.user'))
                ->state(fn (BotMessage $record): string => $record->user_name ?: $record->channel_user),
        ];
    }

    /**
     * @return array<int, Filter|SelectFilter>
     */
    private function messageFilters(): array
    {
        return [
            SelectFilter::make('channel')
                ->label(__('knowledge_gaps.filters.channel'))
                ->relationship('channel', 'name'),
            Filter::make('created_at')
                ->label(__('knowledge_gaps.filters.created_at'))
                ->schema([
                    DatePicker::make('created_from')
                        ->label(__('knowledge_gaps.filters.created_from')),
                    DatePicker::make('created_until')
                        ->label(__('knowledge_gaps.filters.created_until')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                        )
                        ->when(
                            $data['created_until'] ?? null,
                            fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
        ];
    }
}
