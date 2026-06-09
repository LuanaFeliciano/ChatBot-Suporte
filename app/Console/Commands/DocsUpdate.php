<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('docs:update {document_id} {path} {--name=} {--module=} {--doc-version=}')]
#[Description('Replace a document in the Vector Store, inheriting metadata from the existing record')]
class DocsUpdate extends Command
{
    public function handle(DocumentService $docs): int
    {
        $id = $this->argument('document_id');
        $path = $this->argument('path');

        $oldDocument = Document::find($id);

        if (! $oldDocument) {
            $trashed = Document::withTrashed()->find($id);
            if ($trashed) {
                $this->error("Document #{$id} is already deleted.");
            } else {
                $this->error("Document #{$id} not found.");
            }

            return Command::FAILURE;
        }

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return Command::FAILURE;
        }

        $mimeType = mime_content_type($path);
        if (! in_array($mimeType, ['application/pdf', 'text/plain'], true)) {
            $this->error("Unsupported file type: {$mimeType}. Only PDF and plain text are accepted.");

            return Command::FAILURE;
        }

        $oldAttributes = $oldDocument->attributes ?? [];
        $newAttributes = array_filter([
            'module' => $this->option('module') ?? $oldAttributes['module'] ?? null,
            'version' => $this->option('doc-version') ?? $oldAttributes['version'] ?? null,
        ]);

        $newDocument = Document::create([
            'name' => $this->option('name') ?? $oldDocument->name,
            'original_filename' => basename($path),
            'status' => DocumentStatus::Pending,
            'attributes' => $newAttributes ?: null,
            'mime_type' => $mimeType,
            'file_size' => filesize($path),
            'uploaded_by' => get_current_user() ?: 'cli',
        ]);

        try {
            $openaiFileId = $docs->uploadFile($path, $mimeType);
            $newDocument->update([
                'openai_file_id' => $openaiFileId,
                'status' => DocumentStatus::Uploading,
            ]);

            $vectorStoreFileId = $docs->addToVectorStore($openaiFileId);
            $newDocument->update(['vector_store_file_id' => $vectorStoreFileId]);

            $indexStatus = $docs->pollIndexingStatus($vectorStoreFileId);
            if ($indexStatus !== 'completed') {
                throw new \RuntimeException('Indexing failed or timed out.');
            }

            $newDocument->update(['status' => DocumentStatus::Indexed]);

            $docs->removeFromVectorStore($oldDocument->vector_store_file_id);
            $docs->deleteFile($oldDocument->openai_file_id);
            $oldDocument->delete();

            AuditLog::create([
                'action' => AuditAction::DocumentUpdated,
                'entity_type' => AuditEntityType::Document,
                'entity_id' => $newDocument->id,
                'payload' => array_merge($newDocument->refresh()->toArray(), ['old_document_id' => $oldDocument->id]),
                'performed_by' => get_current_user() ?: 'cli',
            ]);

            $this->info("Document #{$oldDocument->id} replaced by #{$newDocument->id} successfully.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $newDocument->update([
                'status' => DocumentStatus::Error,
                'error_message' => $e->getMessage(),
            ]);

            AuditLog::create([
                'action' => AuditAction::DocumentError,
                'entity_type' => AuditEntityType::Document,
                'entity_id' => $newDocument->id,
                'payload' => $newDocument->refresh()->toArray(),
                'performed_by' => get_current_user() ?: 'cli',
            ]);

            $this->error("Update error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
