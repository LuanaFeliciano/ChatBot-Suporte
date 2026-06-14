<?php

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\FilesContract;
use OpenAI\Contracts\Resources\VectorStoresContract;
use OpenAI\Contracts\Resources\VectorStoresFilesContract;
use OpenAI\Responses\Files\CreateResponse;
use OpenAI\Responses\Files\DeleteResponse;
use OpenAI\Responses\VectorStores\Files\VectorStoreFileDeleteResponse;
use OpenAI\Responses\VectorStores\Files\VectorStoreFileResponse;

beforeEach(function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);

    $this->file = tempnam(sys_get_temp_dir(), 'audit_test_');
    file_put_contents($this->file, 'Test document content for audit log tests.');

    $this->app->instance(ClientContract::class, auditMockIngestClient());
});

afterEach(function () {
    if (file_exists($this->file)) {
        unlink($this->file);
    }
});

function auditMockIngestClient(string $openaiFileId = 'file-abc', string $vsFileId = 'vsf-abc'): ClientContract
{
    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('upload')
        ->andReturn(CreateResponse::fake(['id' => $openaiFileId]));

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('create')
        ->andReturn(VectorStoreFileResponse::fake(['id' => $vsFileId, 'chunking_strategy' => ['type' => 'other']]));
    $vsFilesResource->shouldReceive('retrieve')
        ->andReturn(VectorStoreFileResponse::fake(['status' => 'completed', 'chunking_strategy' => ['type' => 'other']]));

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    return $client;
}

function auditMockFailingIngestClient(string $errorMessage): ClientContract
{
    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('upload')->andThrow(new RuntimeException($errorMessage));

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);

    return $client;
}

it('writes a document.uploaded audit_log record after a successful docs:ingest', function () {
    $this->artisan('docs:ingest', ['path' => $this->file])->assertSuccessful();

    expect(AuditLog::where('action', AuditAction::DocumentUploaded->value)->count())->toBe(1);
});

it('writes a document.error audit_log record when docs:ingest fails', function () {
    $this->app->instance(ClientContract::class, auditMockFailingIngestClient('API error'));

    $this->artisan('docs:ingest', ['path' => $this->file])->assertFailed();

    expect(AuditLog::where('action', AuditAction::DocumentError->value)->count())->toBe(1);
});

it('writes a document.deleted audit_log record after docs:delete', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);

    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('delete')->once()->andReturn(DeleteResponse::fake());

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('delete')->once()->andReturn(VectorStoreFileDeleteResponse::fake());

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);
    $this->app->instance(ClientContract::class, $client);

    $this->artisan('docs:delete', ['document_id' => $doc->id])->assertSuccessful();

    expect(AuditLog::where('action', AuditAction::DocumentDeleted->value)->count())->toBe(1);
});

it('audit log payload contains a snapshot of the document record at the time of the action', function () {
    $this->artisan('docs:ingest', ['path' => $this->file, '--name' => 'Snapshot Test Doc'])->assertSuccessful();

    $log = AuditLog::first();

    expect($log->payload)->toBeArray()
        ->and($log->payload)->toHaveKey('id')
        ->and($log->payload)->toHaveKey('name')
        ->and($log->payload['name'])->toBe('Snapshot Test Doc')
        ->and($log->payload)->toHaveKey('status');
});

it('audit log entity_id matches the id of the affected document', function () {
    $this->artisan('docs:ingest', ['path' => $this->file])->assertSuccessful();

    $document = Document::first();
    $log = AuditLog::first();

    expect($log->entity_id)->toBe($document->id);
});

it('audit log entity_type value is document', function () {
    $this->artisan('docs:ingest', ['path' => $this->file])->assertSuccessful();

    $log = AuditLog::first();

    expect($log->entity_type)->toBe(AuditEntityType::Document);
});

it('audit_logs table is append-only — no records are updated or deleted after creation', function () {
    $log1 = AuditLog::factory()->create();
    $log2 = AuditLog::factory()->create();

    $this->artisan('docs:ingest', ['path' => $this->file])->assertSuccessful();

    expect(AuditLog::count())->toBe(3);
    expect(AuditLog::find($log1->id))->not->toBeNull();
    expect(AuditLog::find($log1->id)->action)->toBe($log1->action);
    expect(AuditLog::find($log2->id))->not->toBeNull();
    expect(AuditLog::find($log2->id)->action)->toBe($log2->action);
});

it('audit logs are queryable by the entity_type + entity_id composite index', function () {
    $docA = Document::factory()->create();
    $docB = Document::factory()->create();

    AuditLog::factory()->create([
        'action' => AuditAction::DocumentUploaded,
        'entity_type' => AuditEntityType::Document,
        'entity_id' => $docA->id,
    ]);
    AuditLog::factory()->create([
        'action' => AuditAction::DocumentDeleted,
        'entity_type' => AuditEntityType::Document,
        'entity_id' => $docB->id,
    ]);
    AuditLog::factory()->create([
        'action' => AuditAction::DocumentError,
        'entity_type' => AuditEntityType::Document,
        'entity_id' => $docA->id,
    ]);

    $logsForDocA = AuditLog::where('entity_type', AuditEntityType::Document)
        ->where('entity_id', $docA->id)
        ->get();

    expect($logsForDocA)->toHaveCount(2)
        ->and($logsForDocA->pluck('action')->all())->toContain(AuditAction::DocumentUploaded)
        ->and($logsForDocA->pluck('action')->all())->toContain(AuditAction::DocumentError);
});
