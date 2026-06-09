<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('docs:list')]
#[Description('List all documents in the knowledge base')]
class DocsList extends Command
{
    public function handle(): void
    {
        $documents = Document::withTrashed()->orderByDesc('created_at')->get();

        if ($documents->isEmpty()) {
            $this->info('No documents found.');

            return;
        }

        $this->table(
            ['ID', 'Name', 'Status', 'Module', 'Version', 'Size', 'Uploaded At', 'Deleted At'],
            $documents->map(fn ($doc) => [
                $doc->id,
                $doc->name,
                $doc->status->value,
                $doc->attributes['module'] ?? '—',
                $doc->attributes['version'] ?? '—',
                $doc->file_size ? number_format($doc->file_size / 1024, 1).' KB' : '—',
                $doc->created_at->format('Y-m-d H:i'),
                $doc->deleted_at?->format('Y-m-d H:i') ?? '—',
            ]),
        );
    }
}
