<?php

namespace App\Filament\Resources\BotMessages\Tables;

use App\Filament\Resources\BotMessages\BotMessageResource;
use App\Models\BotMessage;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class BotMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('conversations.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('channel.name')
                    ->label(__('conversations.fields.channel')),
                TextColumn::make('channel_user')
                    ->label(__('conversations.fields.channel_user')),
                TextColumn::make('user_name')
                    ->label(__('conversations.fields.user_name'))
                    ->placeholder('-'),
                TextColumn::make('question')
                    ->label(__('conversations.fields.question'))
                    ->limit(60)
                    ->searchable(['question', 'answer']),
                TextColumn::make('response_ms')
                    ->label(__('conversations.fields.response_ms'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (?int $state): string => ($state !== null && $state > config('suporte.slow_response_threshold_ms'))
                        ? 'danger'
                        : 'success')
                    ->summarize([
                        Average::make('average')
                            ->label(__('conversations.summary.average'))
                            ->formatStateUsing(fn (?float $state): ?string => $state !== null ? number_format($state / 1000, 1).'s' : null),
                        Summarizer::make('p95')
                            ->label(__('conversations.summary.p95'))
                            ->using(function (QueryBuilder $query, string $attribute): ?float {
                                $column = $query->getGrammar()->wrap($attribute);

                                return $query->selectRaw("percentile_cont(0.95) within group (order by {$column}) as aggregate")->value('aggregate');
                            })
                            ->formatStateUsing(fn (?float $state): ?string => $state !== null ? number_format($state / 1000, 1).'s' : null),
                    ]),
                IconColumn::make('was_helpful')
                    ->label(__('conversations.fields.was_helpful'))
                    ->icon(fn (?bool $state): Heroicon => match ($state) {
                        true => Heroicon::OutlinedHandThumbUp,
                        false => Heroicon::OutlinedHandThumbDown,
                        null => Heroicon::OutlinedMinus,
                    })
                    ->color(fn (?bool $state): string => match ($state) {
                        true => 'success',
                        false => 'danger',
                        null => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label(__('conversations.filters.channel'))
                    ->relationship('channel', 'name'),
                Filter::make('created_at')
                    ->label(__('conversations.filters.created_at'))
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('conversations.filters.created_from')),
                        DatePicker::make('created_until')
                            ->label(__('conversations.filters.created_until')),
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
                TernaryFilter::make('was_helpful')
                    ->label(__('conversations.filters.was_helpful'))
                    ->trueLabel(__('conversations.filters.resolved'))
                    ->falseLabel(__('conversations.filters.not_resolved'))
                    ->placeholder(__('conversations.filters.unrated')),
                Filter::make('slow')
                    ->label(__('conversations.filters.slow'))
                    ->query(fn (Builder $query): Builder => $query->where('response_ms', '>', config('suporte.slow_response_threshold_ms'))),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('timeline')
                    ->label(__('conversations.actions.timeline'))
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (BotMessage $record): string => BotMessageResource::getUrl('timeline', [
                        'channel' => $record->channel_id,
                        'channelUser' => $record->channel_user,
                    ])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
