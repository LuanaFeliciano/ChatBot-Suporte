<?php

namespace App\Enums;

enum AuditAction: string
{
    case DocumentUploaded = 'document.uploaded';
    case DocumentDeleted = 'document.deleted';
    case DocumentError = 'document.error';
    case DocumentUpdated = 'document.updated';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserDeactivated = 'user.deactivated';
    case UserRoleChanged = 'user.role_changed';
    case RolePermissionsUpdated = 'role.permissions_updated';
}
