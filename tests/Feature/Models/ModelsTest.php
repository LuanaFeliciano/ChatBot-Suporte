<?php

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;
use App\Models\AuditLog;
use App\Models\BotMessage;
use App\Models\Channel;
use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

it('Document model uses SoftDeletes', function () {
    expect(in_array(SoftDeletes::class, class_uses_recursive(Document::class)))->toBeTrue();
});

it('Document model casts status as DocumentStatus enum', function () {
    $doc = Document::factory()->create();

    expect($doc->status)->toBeInstanceOf(DocumentStatus::class);
});

it('Document model casts attributes as array', function () {
    $doc = Document::factory()->create(['attributes' => ['module' => 'v1']]);

    expect($doc->attributes)->toBeArray();
    expect($doc->attributes['module'])->toBe('v1');
});

it('BotMessage model has a channel() BelongsTo relationship to Channel', function () {
    $message = BotMessage::factory()->create();

    expect($message->channel())->toBeInstanceOf(BelongsTo::class);
    expect($message->channel)->toBeInstanceOf(Channel::class);
});

it('BotMessage model casts was_helpful as boolean', function () {
    $message = BotMessage::factory()->create(['was_helpful' => true]);

    expect($message->was_helpful)->toBeBool();
});

it('AuditLog model casts action as AuditAction enum', function () {
    $log = AuditLog::factory()->create(['action' => AuditAction::DocumentUploaded]);

    expect($log->action)->toBeInstanceOf(AuditAction::class);
    expect($log->action)->toBe(AuditAction::DocumentUploaded);
});

it('AuditLog model casts entity_type as AuditEntityType enum', function () {
    $log = AuditLog::factory()->create(['entity_type' => AuditEntityType::Document]);

    expect($log->entity_type)->toBeInstanceOf(AuditEntityType::class);
    expect($log->entity_type)->toBe(AuditEntityType::Document);
});

it('AuditLog model casts payload as array', function () {
    $log = AuditLog::factory()->create(['payload' => ['key' => 'value']]);

    expect($log->payload)->toBeArray();
    expect($log->payload['key'])->toBe('value');
});

it('Document factory creates a valid persisted record', function () {
    $doc = Document::factory()->create();

    expect($doc->id)->not->toBeNull();
    expect($doc->name)->not->toBeEmpty();
    expect($doc->status)->toBeInstanceOf(DocumentStatus::class);
    $this->assertModelExists($doc);
});

it('BotMessage factory creates a valid persisted record', function () {
    $message = BotMessage::factory()->create();

    expect($message->id)->not->toBeNull();
    expect($message->question)->not->toBeEmpty();
    expect($message->answer)->not->toBeEmpty();
    $this->assertModelExists($message);
});

it('AuditLog factory creates a valid persisted record', function () {
    $log = AuditLog::factory()->create();

    expect($log->id)->not->toBeNull();
    expect($log->action)->toBeInstanceOf(AuditAction::class);
    expect($log->entity_type)->toBeInstanceOf(AuditEntityType::class);
    $this->assertModelExists($log);
});
