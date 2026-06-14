<?php

use App\Enums\AuditAction;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\FilesContract;
use OpenAI\Contracts\Resources\VectorStoresContract;
use OpenAI\Contracts\Resources\VectorStoresFilesContract;
use OpenAI\Responses\Files\CreateResponse;
use OpenAI\Responses\VectorStores\Files\VectorStoreFileResponse;

beforeEach(function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);

    $this->file = tempnam(sys_get_temp_dir(), 'ingest_test_');
    file_put_contents($this->file, 'Test document content for ingestion.');
});

afterEach(function () {
    if (file_exists($this->file)) {
        unlink($this->file);
    }
});

function mockIngestClient(
    string $openaiFileId = 'file-abc',
    string $vsFileId = 'vsf-abc',
    string $indexStatus = 'completed',
    ?callable $onUpload = null,
    ?callable $onAddToVectorStore = null,
): ClientContract {
    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('upload')
        ->andReturnUsing(function () use ($openaiFileId, $onUpload) {
            if ($onUpload) {
                $onUpload();
            }

            return CreateResponse::fake(['id' => $openaiFileId]);
        });

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('create')
        ->andReturnUsing(function () use ($vsFileId, $onAddToVectorStore) {
            if ($onAddToVectorStore) {
                $onAddToVectorStore();
            }

            return VectorStoreFileResponse::fake(['id' => $vsFileId, 'chunking_strategy' => ['type' => 'other']]);
        });
    $vsFilesResource->shouldReceive('retrieve')
        ->andReturn(VectorStoreFileResponse::fake(['status' => $indexStatus, 'chunking_strategy' => ['type' => 'other']]));

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    return $client;
}

function mockFailingUploadClient(string $errorMessage): ClientContract
{
    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('upload')->andThrow(new RuntimeException($errorMessage));

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);

    return $client;
}

it('creates a document record with status pending before uploading', function () {
    $statusAtUpload = null;

    $this->app->instance(ClientContract::class, mockIngestClient(
        onUpload: function () use (&$statusAtUpload) {
            $statusAtUpload = Document::first()?->status;
        },
    ));

    $this->artisan('docs:ingest', ['path' => $this->file, '--name' => 'Test Doc']);

    expect($statusAtUpload)->toBe(DocumentStatus::Pending);
});

it('updates status to uploading after the file is uploaded to OpenAI', function () {
    $statusAtAddToVS = null;

    $this->app->instance(ClientContract::class, mockIngestClient(
        onAddToVectorStore: function () use (&$statusAtAddToVS) {
            $statusAtAddToVS = Document::first()?->status;
        },
    ));

    $this->artisan('docs:ingest', ['path' => $this->file]);

    expect($statusAtAddToVS)->toBe(DocumentStatus::Uploading);
});

it('updates status to indexed after successful Vector Store indexing', function () {
    $this->app->instance(ClientContract::class, mockIngestClient());

    $this->artisan('docs:ingest', ['path' => $this->file])->assertSuccessful();

    expect(Document::first()->status)->toBe(DocumentStatus::Indexed);
});

it('stores openai_file_id and vector_store_file_id on the document record', function () {
    $this->app->instance(ClientContract::class, mockIngestClient('file-xyz', 'vsf-xyz'));

    $this->artisan('docs:ingest', ['path' => $this->file]);

    $document = Document::first();
    expect($document->openai_file_id)->toBe('file-xyz')
        ->and($document->vector_store_file_id)->toBe('vsf-xyz');
});

it('stores --name, --module, --version in the document attributes json column', function () {
    $this->app->instance(ClientContract::class, mockIngestClient());

    $this->artisan('docs:ingest', [
        'path' => $this->file,
        '--name' => 'My Guide',
        '--module' => 'core',
        '--doc-version' => '2.0',
    ]);

    $document = Document::first();
    expect($document->name)->toBe('My Guide')
        ->and($document->attributes['module'])->toBe('core')
        ->and($document->attributes['version'])->toBe('2.0');
});

it('stores file_size, mime_type, and uploaded_by on the document record', function () {
    $this->app->instance(ClientContract::class, mockIngestClient());

    $this->artisan('docs:ingest', ['path' => $this->file]);

    $document = Document::first();
    expect($document->file_size)->toBe(filesize($this->file))
        ->and($document->mime_type)->toBe('text/plain')
        ->and($document->uploaded_by)->toBeString()->not->toBeEmpty();
});

it('writes a document.uploaded audit_log record on success', function () {
    $this->app->instance(ClientContract::class, mockIngestClient());

    $this->artisan('docs:ingest', ['path' => $this->file]);

    $log = AuditLog::first();
    expect($log->action)->toBe(AuditAction::DocumentUploaded)
        ->and($log->entity_id)->toBe(Document::first()->id);
});

it('updates status to error and populates error_message when the upload fails', function () {
    $this->app->instance(ClientContract::class, mockFailingUploadClient('API connection failed'));

    $this->artisan('docs:ingest', ['path' => $this->file])->assertFailed();

    $document = Document::first();
    expect($document->status)->toBe(DocumentStatus::Error)
        ->and($document->error_message)->toBe('API connection failed');
});

it('writes a document.error audit_log record on failure', function () {
    $this->app->instance(ClientContract::class, mockFailingUploadClient('Upload failed'));

    $this->artisan('docs:ingest', ['path' => $this->file]);

    $log = AuditLog::first();
    expect($log->action)->toBe(AuditAction::DocumentError);
});

it('exits with a non-zero code and error message when the file path does not exist', function () {
    $this->artisan('docs:ingest', ['path' => '/tmp/nonexistent_file_abc123.pdf'])
        ->expectsOutputToContain('File not found')
        ->assertFailed();

    expect(Document::count())->toBe(0);
});

it('exits with a non-zero code and error message for unsupported mime types (e.g. .docx)', function () {
    $file = tempnam(sys_get_temp_dir(), 'ingest_docx_');
    // Write JPEG magic bytes so mime_content_type detects it as image/jpeg (unsupported)
    file_put_contents($file, "\xFF\xD8\xFF\xE0".str_repeat('x', 100));

    $this->artisan('docs:ingest', ['path' => $file])
        ->expectsOutputToContain('Unsupported file type')
        ->assertFailed();

    expect(Document::count())->toBe(0);

    unlink($file);
});
