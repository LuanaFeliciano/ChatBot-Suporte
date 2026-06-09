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

#[Signature('docs:ingest {path} {--name=} {--module=} {--doc-version=}')]
#[Description('Upload and index a document into the OpenAI Vector Store')]
class DocsIngest extends Command
{
    public function handle(DocumentService $docs): int
    {
        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return Command::FAILURE;
        }

        $mimeType = mime_content_type($path);
        if (! in_array($mimeType, ['application/pdf', 'text/plain'], true)) {
            $this->error("Unsupported file type: {$mimeType}. Only PDF and plain text are accepted.");

            return Command::FAILURE;
        }

        $attributes = array_filter([
            'module' => $this->option('module'),
            'version' => $this->option('doc-version'),
        ]);

        $document = Document::create([
            'name' => $this->option('name') ?? basename($path),
            'original_filename' => basename($path),
            'status' => DocumentStatus::Pending,
            'attributes' => $attributes ?: null,
            'mime_type' => $mimeType,
            'file_size' => filesize($path),
            'uploaded_by' => get_current_user() ?: 'cli',
        ]);

        try {
            $openaiFileId = $docs->uploadFile($path, $mimeType);
            $document->update([
                'openai_file_id' => $openaiFileId,
                'status' => DocumentStatus::Uploading,
            ]);

            $vectorStoreFileId = $docs->addToVectorStore($openaiFileId);
            $document->update(['vector_store_file_id' => $vectorStoreFileId]);

            $indexStatus = $docs->pollIndexingStatus($vectorStoreFileId);
            if ($indexStatus !== 'completed') {
                throw new \RuntimeException('Indexing failed or timed out.');
            }

            $document->update(['status' => DocumentStatus::Indexed]);
            AuditLog::create([
                'action' => AuditAction::DocumentUploaded,
                'entity_type' => AuditEntityType::Document,
                'entity_id' => $document->id,
                'payload' => $document->refresh()->toArray(),
                'performed_by' => get_current_user() ?: 'cli',
            ]);

            $this->info("Document #{$document->id} successfully indexed.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $document->update([
                'status' => DocumentStatus::Error,
                'error_message' => $e->getMessage(),
            ]);
            AuditLog::create([
                'action' => AuditAction::DocumentError,
                'entity_type' => AuditEntityType::Document,
                'entity_id' => $document->id,
                'payload' => $document->refresh()->toArray(),
                'performed_by' => get_current_user() ?: 'cli',
            ]);

            $this->error("Indexing error: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
