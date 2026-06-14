<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
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

        $docs->ingest($document, $path, $mimeType);

        if ($document->refresh()->status === DocumentStatus::Indexed) {
            $this->info("Document #{$document->id} successfully indexed.");

            return Command::SUCCESS;
        }

        $this->error("Indexing error: {$document->error_message}");

        return Command::FAILURE;
    }
}
