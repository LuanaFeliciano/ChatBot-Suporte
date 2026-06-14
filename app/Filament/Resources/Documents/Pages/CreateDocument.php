<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Jobs\IndexDocument;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    private string $uploadedFilePath;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->uploadedFilePath = Storage::disk('local')->path($data['file']);

        $attributes = array_filter([
            'module' => $data['module'] ?? null,
            'version' => $data['doc_version'] ?? null,
        ]);

        return [
            'name' => $data['name'] ?: $data['original_filename'],
            'original_filename' => $data['original_filename'],
            'status' => DocumentStatus::Pending,
            'attributes' => $attributes ?: null,
            'mime_type' => mime_content_type($this->uploadedFilePath),
            'file_size' => Storage::disk('local')->size($data['file']),
            'uploaded_by' => auth()->user()->email,
        ];
    }

    protected function afterCreate(): void
    {
        IndexDocument::dispatch($this->record, $this->uploadedFilePath);
    }
}
