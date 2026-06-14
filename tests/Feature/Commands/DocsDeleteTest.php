<?php

use App\Enums\AuditAction;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use OpenAI\Contracts\ClientContract;
use OpenAI\Contracts\Resources\FilesContract;
use OpenAI\Contracts\Resources\VectorStoresContract;
use OpenAI\Contracts\Resources\VectorStoresFilesContract;
use OpenAI\Responses\Files\DeleteResponse;
use OpenAI\Responses\VectorStores\Files\VectorStoreFileDeleteResponse;

beforeEach(function () {
    config(['services.openai.vector_store_id' => 'vs-test-id']);
});

function mockDeleteClient(): ClientContract
{
    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('delete')->andReturn(DeleteResponse::fake());

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('delete')->andReturn(VectorStoreFileDeleteResponse::fake());

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);

    return $client;
}

it('removes the file from the OpenAI Vector Store via DocumentService', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);

    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('delete')->andReturn(DeleteResponse::fake());

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('delete')->once()->with('vs-test-id', 'vsf-abc')->andReturn(VectorStoreFileDeleteResponse::fake());

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);
    $this->app->instance(ClientContract::class, $client);

    $this->artisan('docs:delete', ['document_id' => $doc->id])->assertSuccessful();
});

it('deletes the file from the OpenAI Files API via DocumentService', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-xyz',
        'vector_store_file_id' => 'vsf-xyz',
    ]);

    $filesResource = Mockery::mock(FilesContract::class);
    $filesResource->shouldReceive('delete')->once()->with('file-xyz')->andReturn(DeleteResponse::fake());

    $vsFilesResource = Mockery::mock(VectorStoresFilesContract::class);
    $vsFilesResource->shouldReceive('delete')->andReturn(VectorStoreFileDeleteResponse::fake());

    $vsResource = Mockery::mock(VectorStoresContract::class);
    $vsResource->shouldReceive('files')->andReturn($vsFilesResource);

    $client = Mockery::mock(ClientContract::class);
    $client->shouldReceive('files')->andReturn($filesResource);
    $client->shouldReceive('vectorStores')->andReturn($vsResource);
    $this->app->instance(ClientContract::class, $client);

    $this->artisan('docs:delete', ['document_id' => $doc->id])->assertSuccessful();
});

it('soft-deletes the document record — deleted_at is populated', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);
    $this->app->instance(ClientContract::class, mockDeleteClient());

    $this->artisan('docs:delete', ['document_id' => $doc->id])->assertSuccessful();

    expect(Document::withTrashed()->find($doc->id)->deleted_at)->not->toBeNull();
});

it('writes a document.deleted audit_log record', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);
    $this->app->instance(ClientContract::class, mockDeleteClient());

    $this->artisan('docs:delete', ['document_id' => $doc->id]);

    $log = AuditLog::first();
    expect($log->action)->toBe(AuditAction::DocumentDeleted)
        ->and($log->entity_id)->toBe($doc->id);
});

it('exits with a non-zero code and clear error message when the document id does not exist', function () {
    $this->artisan('docs:delete', ['document_id' => 9999])
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

it('exits with a non-zero code and clear error message when the document is already deleted', function () {
    $doc = Document::factory()->create(['status' => DocumentStatus::Indexed]);
    $doc->delete();

    $this->artisan('docs:delete', ['document_id' => $doc->id])
        ->expectsOutputToContain('already deleted')
        ->assertFailed();
});

it('does not modify any records when the document is not found', function () {
    $this->artisan('docs:delete', ['document_id' => 9999])->assertFailed();

    expect(Document::withTrashed()->count())->toBe(0);
    expect(AuditLog::count())->toBe(0);
});
