<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IndexDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly Document $document,
        public readonly string $path,
        public readonly ?Document $documentToRetire = null,
    ) {}

    public function handle(DocumentService $docs): void
    {
        if ($this->documentToRetire) {
            $docs->finalizeReplace($this->document, $this->path, $this->documentToRetire);

            return;
        }

        $docs->ingest($this->document, $this->path, $this->document->mime_type);
    }
}
