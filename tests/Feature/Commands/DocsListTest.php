<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use Illuminate\Support\Facades\Artisan;

it('outputs a table containing all active documents', function () {
    Document::factory()->create(['name' => 'Active Guide', 'status' => DocumentStatus::Indexed]);

    $this->artisan('docs:list')
        ->expectsOutputToContain('Active Guide')
        ->assertSuccessful();
});

it('includes soft-deleted documents in the output', function () {
    $doc = Document::factory()->create(['name' => 'Deleted Guide', 'status' => DocumentStatus::Indexed]);
    $doc->delete();

    $this->artisan('docs:list')
        ->expectsOutputToContain('Deleted Guide')
        ->assertSuccessful();
});

it('shows the deleted_at timestamp for soft-deleted documents', function () {
    $doc = Document::factory()->create(['status' => DocumentStatus::Indexed]);
    $doc->delete();

    $this->artisan('docs:list')
        ->expectsOutputToContain($doc->deleted_at->format('Y-m-d H:i'))
        ->assertSuccessful();
});

it('shows a dash for deleted_at when the document is active', function () {
    Document::factory()->create(['status' => DocumentStatus::Indexed]);

    $this->artisan('docs:list')
        ->expectsOutputToContain('—')
        ->assertSuccessful();
});

it('shows module and version values from the attributes json column', function () {
    Document::factory()->create([
        'status' => DocumentStatus::Indexed,
        'attributes' => ['module' => 'billing', 'version' => '3.1'],
    ]);

    Artisan::call('docs:list');

    expect(Artisan::output())->toContain('billing')->toContain('3.1');
});

it('outputs an empty-state message when no documents exist', function () {
    $this->artisan('docs:list')
        ->expectsOutputToContain('No documents found.')
        ->assertSuccessful();
});

it('table columns include ID, Name, Status, Module, Version, Size, Uploaded At, Deleted At', function () {
    Document::factory()->create(['status' => DocumentStatus::Indexed]);

    Artisan::call('docs:list');
    $output = Artisan::output();

    expect($output)
        ->toContain('ID')
        ->toContain('Name')
        ->toContain('Status')
        ->toContain('Module')
        ->toContain('Version')
        ->toContain('Size')
        ->toContain('Uploaded At')
        ->toContain('Deleted At');
});
