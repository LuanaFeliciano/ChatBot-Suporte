<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('docs:status {document_id}')]
#[Description('Show the current indexing status of a document')]
class DocsStatus extends Command
{
    public function handle(): int
    {
        $doc = Document::withTrashed()->find($this->argument('document_id'));

        if (! $doc) {
            $this->error("Document #{$this->argument('document_id')} not found.");

            return Command::FAILURE;
        }

        $this->line("Status:               {$doc->status->value}");
        $this->line('OpenAI File ID:       '.($doc->openai_file_id ?? '—'));
        $this->line('Vector Store File ID: '.($doc->vector_store_file_id ?? '—'));
        $this->line("Created At:           {$doc->created_at}");
        $this->line("Updated At:           {$doc->updated_at}");

        if ($doc->status === DocumentStatus::Error) {
            $this->newLine();
            $this->error("Error: {$doc->error_message}");
        }

        return Command::SUCCESS;
    }
}
