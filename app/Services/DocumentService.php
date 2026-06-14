<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use OpenAI\Contracts\ClientContract;

class DocumentService
{
    public function __construct(private readonly ClientContract $openai) {}

    public function ingest(Document $document, string $path, string $mimeType): void
    {
        $success = $this->indexDocument($document, $path, $mimeType);

        AuditLog::create([
            'action' => $success ? AuditAction::DocumentUploaded : AuditAction::DocumentError,
            'entity_type' => AuditEntityType::Document,
            'entity_id' => $document->id,
            'payload' => $document->refresh()->toArray(),
            'performed_by' => $document->uploaded_by,
        ]);
    }

    /**
     * Re-attempts indexing for a document that previously failed. Resumes from
     * `addToVectorStore` when `openai_file_id` is already set, or re-uploads the
     * original file first otherwise. Writes `document.uploaded`/`document.error`
     * accordingly.
     */
    public function retry(Document $document): void
    {
        $this->ingest($document, $document->attributes['source_path'], $document->mime_type);
    }

    /**
     * Creates a new pending document inheriting metadata from `$old`, then indexes it.
     * On success, the old document's file is removed from the Vector Store and Files API
     * and the old record is soft-deleted. On failure, `$old` is left untouched.
     *
     * @param  array{name?: ?string, module?: ?string, version?: ?string, uploaded_by?: ?string}  $attributes
     */
    public function replace(Document $old, string $path, array $attributes = []): Document
    {
        $oldAttributes = $old->attributes ?? [];
        $newAttributes = array_filter([
            'module' => $attributes['module'] ?? $oldAttributes['module'] ?? null,
            'version' => $attributes['version'] ?? $oldAttributes['version'] ?? null,
        ]);

        $mimeType = mime_content_type($path);

        $newDocument = Document::create([
            'name' => $attributes['name'] ?? $old->name,
            'original_filename' => basename($path),
            'status' => DocumentStatus::Pending,
            'attributes' => $newAttributes ?: null,
            'mime_type' => $mimeType,
            'file_size' => filesize($path),
            'uploaded_by' => $attributes['uploaded_by'] ?? $old->uploaded_by,
        ]);

        $this->finalizeReplace($newDocument, $path, $old);

        return $newDocument;
    }

    /**
     * Indexes `$document` and, on success, retires `$old` (removing it from the
     * Vector Store and Files API, then soft-deleting it). Writes the
     * `document.updated`/`document.error` audit log accordingly.
     */
    public function finalizeReplace(Document $document, string $path, Document $old): void
    {
        $success = $this->indexDocument($document, $path, $document->mime_type);

        if (! $success) {
            AuditLog::create([
                'action' => AuditAction::DocumentError,
                'entity_type' => AuditEntityType::Document,
                'entity_id' => $document->id,
                'payload' => $document->refresh()->toArray(),
                'performed_by' => $document->uploaded_by,
            ]);

            return;
        }

        $this->removeFromVectorStore($old->vector_store_file_id);
        $this->deleteFile($old->openai_file_id);
        $old->delete();

        AuditLog::create([
            'action' => AuditAction::DocumentUpdated,
            'entity_type' => AuditEntityType::Document,
            'entity_id' => $document->id,
            'payload' => array_merge($document->refresh()->toArray(), ['old_document_id' => $old->id]),
            'performed_by' => $document->uploaded_by,
        ]);
    }

    /**
     * Uploads (if needed), adds to the Vector Store, and polls for completion.
     * Resumes from the Vector Store step when the document already has an `openai_file_id`.
     * Updates the document's status throughout, but does not write audit logs.
     */
    private function indexDocument(Document $document, string $path, string $mimeType): bool
    {
        $document->update([
            'attributes' => array_merge($document->attributes ?? [], ['source_path' => $path]),
        ]);

        try {
            if (! $document->openai_file_id) {
                $openaiFileId = $this->uploadFile($path, $mimeType);
                $document->update([
                    'openai_file_id' => $openaiFileId,
                    'status' => DocumentStatus::Uploading,
                ]);
            }

            $vectorStoreFileId = $this->addToVectorStore($document->openai_file_id);
            $document->update([
                'vector_store_file_id' => $vectorStoreFileId,
                'status' => DocumentStatus::Uploading,
            ]);

            $indexStatus = $this->pollIndexingStatus($vectorStoreFileId);
            if ($indexStatus !== 'completed') {
                throw new \RuntimeException('Indexing failed or timed out.');
            }

            $document->update([
                'status' => DocumentStatus::Indexed,
                'error_message' => null,
            ]);

            return true;
        } catch (\Throwable $e) {
            $document->update([
                'status' => DocumentStatus::Error,
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Removes the document's file from the Vector Store and Files API, soft-deletes
     * the record, and writes a `document.deleted` audit log entry.
     */
    public function delete(Document $document): void
    {
        $this->removeFromVectorStore($document->vector_store_file_id);
        $this->deleteFile($document->openai_file_id);
        $document->delete();

        AuditLog::create([
            'action' => AuditAction::DocumentDeleted,
            'entity_type' => AuditEntityType::Document,
            'entity_id' => $document->id,
            'payload' => $document->toArray(),
            'performed_by' => $document->uploaded_by,
        ]);
    }

    public function uploadFile(string $filePath, string $mimeType): string
    {
        $response = $this->openai->files()->upload([
            'purpose' => 'assistants',
            'file' => fopen($filePath, 'r'),
        ]);

        return $response->id;
    }

    public function addToVectorStore(string $openaiFileId): string
    {
        $vsId = config('services.openai.vector_store_id');
        $response = $this->openai->vectorStores()->files()->create($vsId, [
            'file_id' => $openaiFileId,
        ]);

        return $response->id;
    }

    public function pollIndexingStatus(string $vectorStoreFileId, int $maxSeconds = 60): string
    {
        $vsId = config('services.openai.vector_store_id');
        $deadline = now()->addSeconds($maxSeconds);

        do {
            $file = $this->openai->vectorStores()->files()->retrieve($vsId, $vectorStoreFileId);
            if (in_array($file->status, ['completed', 'failed'], true)) {
                return $file->status;
            }
            sleep(2);
        } while (now()->lt($deadline));

        return 'failed';
    }

    public function removeFromVectorStore(string $vectorStoreFileId): void
    {
        $vsId = config('services.openai.vector_store_id');
        $this->openai->vectorStores()->files()->delete($vsId, $vectorStoreFileId);
    }

    public function deleteFile(string $openaiFileId): void
    {
        $this->openai->files()->delete($openaiFileId);
    }
}
