<?php

use Illuminate\Support\Facades\Schema;

it('creates the documents table with all required columns', function () {
    expect(Schema::hasTable('documents'))->toBeTrue();
    expect(Schema::hasColumns('documents', [
        'id', 'name', 'original_filename', 'openai_file_id', 'vector_store_file_id',
        'status', 'attributes', 'error_message', 'uploaded_by', 'file_size', 'mime_type',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('documents table has a soft-delete deleted_at column', function () {
    expect(Schema::hasColumn('documents', 'deleted_at'))->toBeTrue();
});

it('documents table has indexes on status, openai_file_id, vector_store_file_id, deleted_at', function () {
    $indexedColumns = collect(Schema::getIndexes('documents'))
        ->flatMap(fn ($index) => $index['columns'])
        ->values()
        ->all();

    expect($indexedColumns)->toContain('status');
    expect($indexedColumns)->toContain('openai_file_id');
    expect($indexedColumns)->toContain('vector_store_file_id');
    expect($indexedColumns)->toContain('deleted_at');
});

it('creates the bot_messages table with all required columns', function () {
    expect(Schema::hasTable('bot_messages'))->toBeTrue();
    expect(Schema::hasColumns('bot_messages', [
        'id', 'channel_id', 'channel_user', 'user_name', 'question', 'answer',
        'response_ms', 'was_helpful', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('bot_messages table has a composite index on channel_id and channel_user', function () {
    $indexes = Schema::getIndexes('bot_messages');
    $compositeIndex = collect($indexes)->first(
        fn ($index) => in_array('channel_id', $index['columns']) && in_array('channel_user', $index['columns'])
    );

    expect($compositeIndex)->not->toBeNull();
});

it('bot_messages table has an index on created_at', function () {
    $indexedColumns = collect(Schema::getIndexes('bot_messages'))
        ->flatMap(fn ($index) => $index['columns'])
        ->values()
        ->all();

    expect($indexedColumns)->toContain('created_at');
});

it('creates the audit_logs table with all required columns', function () {
    expect(Schema::hasTable('audit_logs'))->toBeTrue();
    expect(Schema::hasColumns('audit_logs', [
        'id', 'action', 'entity_type', 'entity_id', 'payload', 'performed_by',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('audit_logs table has a composite index on entity_type and entity_id', function () {
    $indexes = Schema::getIndexes('audit_logs');
    $compositeIndex = collect($indexes)->first(
        fn ($index) => in_array('entity_type', $index['columns']) && in_array('entity_id', $index['columns'])
    );

    expect($compositeIndex)->not->toBeNull();
});
