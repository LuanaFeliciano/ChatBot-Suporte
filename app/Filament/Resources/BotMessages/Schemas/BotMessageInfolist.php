<?php

namespace App\Filament\Resources\BotMessages\Schemas;

use App\Models\BotMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BotMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label(__('conversations.fields.created_at'))
                    ->dateTime(),
                TextEntry::make('channel.name')
                    ->label(__('conversations.fields.channel')),
                TextEntry::make('channel_user')
                    ->label(__('conversations.fields.channel_user')),
                TextEntry::make('user_name')
                    ->label(__('conversations.fields.user_name'))
                    ->placeholder('-'),
                TextEntry::make('escalated')
                    ->hiddenLabel()
                    ->state(fn (BotMessage $record): ?string => $record->isEscalated()
                        ? __('conversations.fields.escalated')
                        : null)
                    ->badge()
                    ->color('danger')
                    ->visible(fn (BotMessage $record): bool => $record->isEscalated()),
                TextEntry::make('question')
                    ->label(__('conversations.fields.question'))
                    ->columnSpanFull(),
                TextEntry::make('answer')
                    ->label(__('conversations.fields.answer'))
                    ->columnSpanFull(),
                TextEntry::make('was_helpful')
                    ->label(__('conversations.fields.was_helpful'))
                    ->badge()
                    ->formatStateUsing(fn (?bool $state): string => match ($state) {
                        true => __('conversations.filters.resolved'),
                        false => __('conversations.filters.not_resolved'),
                        null => __('conversations.filters.unrated'),
                    })
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
                TextEntry::make('response_ms')
                    ->label(__('conversations.fields.response_ms'))
                    ->state(fn (BotMessage $record): ?string => $record->formatResponseMs())
                    ->placeholder('-'),
            ]);
    }
}
