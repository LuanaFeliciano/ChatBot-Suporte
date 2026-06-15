<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file')
                    ->label(__('documents.fields.file'))
                    ->required()
                    ->acceptedFileTypes(['application/pdf', 'text/plain', 'text/markdown'])
                    ->mimeTypeMap(['md' => 'text/markdown'])
                    ->storeFileNamesIn('original_filename')
                    ->directory('documents')
                    ->disk('local'),
                TextInput::make('name')
                    ->label(__('documents.fields.name')),
                TextInput::make('module')
                    ->label(__('documents.fields.module')),
                TextInput::make('doc_version')
                    ->label(__('documents.fields.doc_version')),
            ]);
    }
}
