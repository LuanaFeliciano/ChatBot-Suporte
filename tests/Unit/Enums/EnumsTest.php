<?php

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;

it('DocumentStatus has cases Pending, Uploading, Indexed, Error with correct string values', function () {
    expect(DocumentStatus::Pending->value)->toBe('pending');
    expect(DocumentStatus::Uploading->value)->toBe('uploading');
    expect(DocumentStatus::Indexed->value)->toBe('indexed');
    expect(DocumentStatus::Error->value)->toBe('error');
    expect(DocumentStatus::cases())->toHaveCount(4);
});

it('AuditAction has cases DocumentUploaded, DocumentDeleted, DocumentError with correct string values', function () {
    expect(AuditAction::DocumentUploaded->value)->toBe('document.uploaded');
    expect(AuditAction::DocumentDeleted->value)->toBe('document.deleted');
    expect(AuditAction::DocumentError->value)->toBe('document.error');
    expect(AuditAction::cases())->toHaveCount(3);
});

it('AuditEntityType has cases Document and BotMessage with correct string values', function () {
    expect(AuditEntityType::Document->value)->toBe('document');
    expect(AuditEntityType::BotMessage->value)->toBe('bot_message');
    expect(AuditEntityType::cases())->toHaveCount(2);
});
