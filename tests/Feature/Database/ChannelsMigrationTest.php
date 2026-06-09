<?php

use Illuminate\Support\Facades\Schema;

it('creates the channels table with the expected columns', function () {
    expect(Schema::hasTable('channels'))->toBeTrue();
    expect(Schema::hasColumns('channels', [
        'id', 'name', 'slug', 'description', 'is_active', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('channels slug column has a unique index', function () {
    $indexes = Schema::getIndexes('channels');
    $uniqueColumns = collect($indexes)
        ->filter(fn ($index) => $index['unique'])
        ->flatMap(fn ($index) => $index['columns'])
        ->values()
        ->all();

    expect($uniqueColumns)->toContain('slug');
});
