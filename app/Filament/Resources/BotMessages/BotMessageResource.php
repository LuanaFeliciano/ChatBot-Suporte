<?php

namespace App\Filament\Resources\BotMessages;

use App\Filament\Resources\BotMessages\Pages\ConversationTimeline;
use App\Filament\Resources\BotMessages\Pages\ListBotMessages;
use App\Filament\Resources\BotMessages\Pages\ViewBotMessage;
use App\Filament\Resources\BotMessages\Schemas\BotMessageInfolist;
use App\Filament\Resources\BotMessages\Tables\BotMessagesTable;
use App\Models\BotMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BotMessageResource extends Resource
{
    protected static ?string $model = BotMessage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function getNavigationLabel(): string
    {
        return __('conversations.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('conversations.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('conversations.plural_model_label');
    }

    public static function table(Table $table): Table
    {
        return BotMessagesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BotMessageInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('channel');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBotMessages::route('/'),
            'view' => ViewBotMessage::route('/{record}'),
            'timeline' => ConversationTimeline::route('/{channel}/{channelUser}/timeline'),
        ];
    }
}
