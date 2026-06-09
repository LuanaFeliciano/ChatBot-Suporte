<?php

namespace App\Services;

use OpenAI\Contracts\ClientContract;

class DocumentService
{
    public function __construct(private readonly ClientContract $openai) {}

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
