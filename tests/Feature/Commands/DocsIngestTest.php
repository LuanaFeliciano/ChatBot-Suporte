<?php

use App\Enums\AuditAction;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Services\DocumentService;

beforeEach(function () {
    $this->file = tempnam(sys_get_temp_dir(), 'ingest_test_');
    file_put_contents($this->file, 'Test document content for ingestion.');
});

afterEach(function () {
    if (file_exists($this->file)) {
        unlink($this->file);
    }
});

function mockIngestService(
    string $openaiFileId = 'file-abc',
    string $vsFileId = 'vsf-abc',
    string $indexStatus = 'completed',
): DocumentService {
    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('uploadFile')->andReturn($openaiFileId);
    $mock->shouldReceive('addToVectorStore')->andReturn($vsFileId);
    $mock->shouldReceive('pollIndexingStatus')->andReturn($indexStatus);

    return $mock;
}

it('creates a document record with status pending before uploading', function () {
    $statusAtUpload = null;
    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('uploadFile')
        ->andReturnUsing(function () use (&$statusAtUpload) {
            $statusAtUpload = Document::first()?->status;

            return 'file-abc';
        });
    $mock->shouldReceive('addToVectorStore')->andReturn('vsf-abc');
    $mock->shouldReceive('pollIndexingStatus')->andReturn('completed');
    $this->app->instance(DocumentService::class, $mock);

    $this->artisan('docs:ingest', ['path' => $this->file, '--name' => 'Test Doc']);

    expect($statusAtUpload)->toBe(DocumentStatus::Pending);
});

it('updates status to uploading after the file is uploaded to OpenAI', function () {
    $statusAtAddToVS = null;
    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('uploadFile')->andReturn('file-abc');
    $mock->shouldReceive('addToVectorStore')
        ->andReturnUsing(function () use (&$statusAtAddToVS) {
            $statusAtAddToVS = Document::first()?->status;

            return 'vsf-abc';
        });
    $mock->shouldReceive('pollIndexingStatus')->andReturn('completed');
    $this->app->instance(DocumentService::class, $mock);

    $this->artisan('docs:ingest', ['path' => $this->file]);

    expect($statusAtAddToVS)->toBe(DocumentStatus::Uploading);
});

it('updates status to indexed after successful Vector Store indexing', function () {
    $this->app->instance(DocumentService::class, mockIngestService());

    $this->artisan('docs:ingest', ['path' => $this->file])->assertSuccessful();

    expect(Document::first()->status)->toBe(DocumentStatus::Indexed);
});

it('stores openai_file_id and vector_store_file_id on the document record', function () {
    $this->app->instance(DocumentService::class, mockIngestService('file-xyz', 'vsf-xyz'));

    $this->artisan('docs:ingest', ['path' => $this->file]);

    $document = Document::first();
    expect($document->openai_file_id)->toBe('file-xyz')
        ->and($document->vector_store_file_id)->toBe('vsf-xyz');
});

it('stores --name, --module, --version in the document attributes json column', function () {
    $this->app->instance(DocumentService::class, mockIngestService());

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
    $this->app->instance(DocumentService::class, mockIngestService());

    $this->artisan('docs:ingest', ['path' => $this->file]);

    $document = Document::first();
    expect($document->file_size)->toBe(filesize($this->file))
        ->and($document->mime_type)->toBe('text/plain')
        ->and($document->uploaded_by)->toBeString()->not->toBeEmpty();
});

it('writes a document.uploaded audit_log record on success', function () {
    $this->app->instance(DocumentService::class, mockIngestService());

    $this->artisan('docs:ingest', ['path' => $this->file]);

    $log = AuditLog::first();
    expect($log->action)->toBe(AuditAction::DocumentUploaded)
        ->and($log->entity_id)->toBe(Document::first()->id);
});

it('updates status to error and populates error_message when the upload fails', function () {
    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('uploadFile')->andThrow(new RuntimeException('API connection failed'));
    $this->app->instance(DocumentService::class, $mock);

    $this->artisan('docs:ingest', ['path' => $this->file])->assertFailed();

    $document = Document::first();
    expect($document->status)->toBe(DocumentStatus::Error)
        ->and($document->error_message)->toBe('API connection failed');
});

it('writes a document.error audit_log record on failure', function () {
    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('uploadFile')->andThrow(new RuntimeException('Upload failed'));
    $this->app->instance(DocumentService::class, $mock);

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
