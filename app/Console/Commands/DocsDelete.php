<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Models\AuditLog;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('docs:delete {document_id}')]
#[Description('Remove a document from the Vector Store and soft-delete it')]
class DocsDelete extends Command
{
    public function handle(DocumentService $docs): int
    {
        $id = $this->argument('document_id');
        $document = Document::find($id);

        if (! $document) {
            $trashed = Document::withTrashed()->find($id);
            if ($trashed) {
                $this->error("Document #{$id} is already deleted.");
            } else {
                $this->error("Document #{$id} not found.");
            }

            return Command::FAILURE;
        }

        $docs->removeFromVectorStore($document->vector_store_file_id);
        $docs->deleteFile($document->openai_file_id);
        $document->delete();

        AuditLog::create([
            'action' => AuditAction::DocumentDeleted,
            'entity_type' => AuditEntityType::Document,
            'entity_id' => $document->id,
            'payload' => $document->toArray(),
            'performed_by' => get_current_user() ?: 'cli',
        ]);

        $this->info("Document #{$id} removed successfully.");

        return Command::SUCCESS;
    }
}
