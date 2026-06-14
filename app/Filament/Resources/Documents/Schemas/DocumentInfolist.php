<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class DocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('original_filename'),
                TextEntry::make('openai_file_id')
                    ->placeholder('-'),
                TextEntry::make('vector_store_file_id')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('error_message')
                    ->label(__('documents.fields.error_message'))
                    ->icon(Heroicon::ExclamationTriangle)
                    ->color('danger')
                    ->weight(FontWeight::Bold)
                    ->visible(fn (Document $record): bool => $record->status === DocumentStatus::Error)
                    ->columnSpanFull(),
                TextEntry::make('uploaded_by')
                    ->placeholder('-'),
                TextEntry::make('file_size')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('mime_type')
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Document $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
