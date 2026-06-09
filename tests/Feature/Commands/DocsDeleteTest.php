<?php

use App\Enums\AuditAction;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\Document;
use App\Services\DocumentService;

function mockDeleteService(): DocumentService
{
    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('removeFromVectorStore')->once();
    $mock->shouldReceive('deleteFile')->once();

    return $mock;
}

it('removes the file from the OpenAI Vector Store via DocumentService', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);

    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('removeFromVectorStore')->once()->with('vsf-abc');
    $mock->shouldReceive('deleteFile')->once();
    $this->app->instance(DocumentService::class, $mock);

    $this->artisan('docs:delete', ['document_id' => $doc->id])->assertSuccessful();
});

it('deletes the file from the OpenAI Files API via DocumentService', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-xyz',
        'vector_store_file_id' => 'vsf-xyz',
    ]);

    $mock = Mockery::mock(DocumentService::class);
    $mock->shouldReceive('removeFromVectorStore')->once();
    $mock->shouldReceive('deleteFile')->once()->with('file-xyz');
    $this->app->instance(DocumentService::class, $mock);

    $this->artisan('docs:delete', ['document_id' => $doc->id])->assertSuccessful();
});

it('soft-deletes the document record — deleted_at is populated', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);
    $this->app->instance(DocumentService::class, mockDeleteService());

    $this->artisan('docs:delete', ['document_id' => $doc->id])->assertSuccessful();

    expect(Document::withTrashed()->find($doc->id)->deleted_at)->not->toBeNull();
});

it('writes a document.deleted audit_log record', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);
    $this->app->instance(DocumentService::class, mockDeleteService());

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
