<?php

namespace App\Filament\Resources\Documents\Tables;

use App\Enums\DocumentStatus;
use App\Enums\PermissionName;
use App\Jobs\IndexDocument;
use App\Models\Document;
use App\Services\DocumentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('documents.fields.id'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('documents.fields.name'))
                    ->searchable(),
                TextColumn::make('original_filename')
                    ->label(__('documents.fields.original_filename'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label(__('documents.fields.status'))
                    ->badge(),
                TextColumn::make('module')
                    ->label(__('documents.fields.module'))
                    ->state(fn (Document $record): ?string => $record->attributes['module'] ?? null),
                TextColumn::make('version')
                    ->label(__('documents.fields.doc_version'))
                    ->state(fn (Document $record): ?string => $record->attributes['version'] ?? null),
                TextColumn::make('file_size')
                    ->label(__('documents.fields.file_size'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('documents.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label(__('documents.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('documents.fields.status'))
                    ->options(DocumentStatus::class),
                SelectFilter::make('module')
                    ->label(__('documents.fields.module'))
                    ->options(fn (): array => Document::query()
                        ->whereNotNull('attributes')
                        ->get(['attributes'])
                        ->pluck('attributes.module')
                        ->filter()
                        ->unique()
                        ->mapWithKeys(fn (string $module): array => [$module => $module])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, string $value): Builder => $query->where('attributes->module', $value),
                    )),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('replace')
                    ->label(__('documents.actions.replace'))
                    ->modalHeading(__('documents.actions.replace_modal_heading'))
                    ->modalDescription(__('documents.actions.replace_modal_description'))
                    ->visible(fn (): bool => auth()->user()->can(PermissionName::ManageDocuments->value))
                    ->schema(fn (Document $record): array => [
                        FileUpload::make('file')
                            ->label(__('documents.fields.file'))
                            ->required()
                            ->acceptedFileTypes(['application/pdf', 'text/plain', 'text/markdown'])
                            ->mimeTypeMap(['md' => 'text/markdown'])
                            ->storeFileNamesIn('original_filename')
                            ->directory('documents')
                            ->disk('local'),
                        TextInput::make('name')
                            ->label(__('documents.fields.name'))
                            ->default($record->name),
                        TextInput::make('module')
                            ->label(__('documents.fields.module'))
                            ->default($record->attributes['module'] ?? null),
                        TextInput::make('doc_version')
                            ->label(__('documents.fields.doc_version'))
                            ->default($record->attributes['version'] ?? null),
                    ])
                    ->action(function (array $data, Document $record): void {
                        $path = Storage::disk('local')->path($data['file']);

                        $newDocument = Document::create([
                            'name' => $data['name'] ?: $record->name,
                            'original_filename' => $data['original_filename'],
                            'status' => DocumentStatus::Pending,
                            'attributes' => array_filter([
                                'module' => $data['module'] ?: null,
                                'version' => $data['doc_version'] ?: null,
                            ]) ?: null,
                            'mime_type' => mime_content_type($path),
                            'file_size' => Storage::disk('local')->size($data['file']),
                            'uploaded_by' => auth()->user()->email,
                        ]);

                        IndexDocument::dispatch($newDocument, $path, $record);
                    }),
                Action::make('retry')
                    ->label(__('documents.actions.retry'))
                    ->modalHeading(__('documents.actions.retry_modal_heading'))
                    ->modalDescription(__('documents.actions.retry_modal_description'))
                    ->requiresConfirmation()
                    ->visible(fn (Document $record): bool => $record->status === DocumentStatus::Error && auth()->user()->can(PermissionName::ManageDocuments->value))
                    ->action(fn (Document $record, DocumentService $docs) => $docs->retry($record)),
                Action::make('delete')
                    ->label(__('documents.actions.delete'))
                    ->modalHeading(__('documents.actions.delete_modal_heading'))
                    ->modalDescription(__('documents.actions.delete_modal_description'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Document $record): bool => ! $record->trashed() && auth()->user()->can(PermissionName::ManageDocuments->value))
                    ->action(fn (Document $record, DocumentService $docs) => $docs->delete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s');
    }
}
