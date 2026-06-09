<?php

use App\Enums\DocumentStatus;
use App\Models\Document;

it('displays status, openai_file_id, vector_store_file_id, created_at, updated_at', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'openai_file_id' => 'file-abc',
        'vector_store_file_id' => 'vsf-abc',
    ]);

    $this->artisan('docs:status', ['document_id' => $doc->id])
        ->expectsOutputToContain('indexed')
        ->expectsOutputToContain('file-abc')
        ->expectsOutputToContain('vsf-abc')
        ->assertSuccessful();
});

it('shows the error_message prominently via $this->error() when status is error', function () {
    $doc = Document::factory()->create([
        'status' => DocumentStatus::Error,
        'error_message' => 'Upload timed out',
    ]);

    $this->artisan('docs:status', ['document_id' => $doc->id])
        ->expectsOutputToContain('Upload timed out')
        ->assertSuccessful();
});

it('does not output the error section when status is indexed', function () {
    $doc = Document::factory()->create(['status' => DocumentStatus::Indexed]);

    $output = $this->artisan('docs:status', ['document_id' => $doc->id]);

    $output->assertSuccessful();
    $output->expectsOutputToContain('indexed');
});

it('works for all statuses: pending, uploading, indexed, error', function () {
    foreach (DocumentStatus::cases() as $status) {
        $doc = Document::factory()->create([
            'status' => $status,
            'error_message' => $status === DocumentStatus::Error ? 'Some error' : null,
        ]);

        $this->artisan('docs:status', ['document_id' => $doc->id])
            ->expectsOutputToContain($status->value)
            ->assertSuccessful();
    }
});

it('returns Command::FAILURE and outputs an error when the document does not exist', function () {
    $this->artisan('docs:status', ['document_id' => 9999])
        ->expectsOutputToContain('not found')
        ->assertFailed();
});
