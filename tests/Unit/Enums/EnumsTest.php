<?php

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;
use App\Enums\Locale;
use App\Enums\PermissionName;

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
    expect(AuditAction::DocumentUpdated->value)->toBe('document.updated');
    expect(AuditAction::UserCreated->value)->toBe('user.created');
    expect(AuditAction::UserUpdated->value)->toBe('user.updated');
    expect(AuditAction::UserDeactivated->value)->toBe('user.deactivated');
    expect(AuditAction::UserRoleChanged->value)->toBe('user.role_changed');
    expect(AuditAction::RolePermissionsUpdated->value)->toBe('role.permissions_updated');
    expect(AuditAction::cases())->toHaveCount(9);
});

it('AuditEntityType has cases Document and BotMessage with correct string values', function () {
    expect(AuditEntityType::Document->value)->toBe('document');
    expect(AuditEntityType::BotMessage->value)->toBe('bot_message');
    expect(AuditEntityType::User->value)->toBe('user');
    expect(AuditEntityType::Role->value)->toBe('role');
    expect(AuditEntityType::cases())->toHaveCount(4);
});

it('Locale has cases PtBr and En with correct string values', function () {
    expect(Locale::PtBr->value)->toBe('pt_BR');
    expect(Locale::En->value)->toBe('en');
    expect(Locale::cases())->toHaveCount(2);
});

it('PermissionName has the 8 permission slugs from the Role/Permission Matrix with correct string values and labels', function () {
    expect(PermissionName::ManageUsers->value)->toBe('manage-users');
    expect(PermissionName::ManageRoles->value)->toBe('manage-roles');
    expect(PermissionName::ManageDocuments->value)->toBe('manage-documents');
    expect(PermissionName::ViewConversations->value)->toBe('view-conversations');
    expect(PermissionName::ViewAnalytics->value)->toBe('view-analytics');
    expect(PermissionName::ViewAuditLogs->value)->toBe('view-audit-logs');
    expect(PermissionName::ViewKnowledgeGaps->value)->toBe('view-knowledge-gaps');
    expect(PermissionName::ViewFeedback->value)->toBe('view-feedback');
    expect(PermissionName::cases())->toHaveCount(8);

    expect(PermissionName::ManageRoles->getLabel())->toBe(__('enums.permission_name.manage-roles'));
});
