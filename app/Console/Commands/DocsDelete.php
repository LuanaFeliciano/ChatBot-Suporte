<?php

namespace App\Console\Commands;

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

        $docs->delete($document);

        $this->info("Document #{$id} removed successfully.");

        return Command::SUCCESS;
    }
}
