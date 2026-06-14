<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
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

        $newDocument = $docs->replace($oldDocument, $path, [
            'name' => $this->option('name'),
            'module' => $this->option('module'),
            'version' => $this->option('doc-version'),
            'uploaded_by' => get_current_user() ?: 'cli',
        ]);

        if ($newDocument->status === DocumentStatus::Indexed) {
            $this->info("Document #{$oldDocument->id} replaced by #{$newDocument->id} successfully.");

            return Command::SUCCESS;
        }

        $this->error("Update error: {$newDocument->error_message}");

        return Command::FAILURE;
    }
}
