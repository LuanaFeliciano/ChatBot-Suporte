<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PermissionName: string implements HasLabel
{
    case ManageUsers = 'manage-users';
    case ManageRoles = 'manage-roles';
    case ManageDocuments = 'manage-documents';
    case ViewDocuments = 'view-documents';
    case ViewConversations = 'view-conversations';
    case ViewAnalytics = 'view-analytics';
    case ViewAuditLogs = 'view-audit-logs';
    case ViewKnowledgeGaps = 'view-knowledge-gaps';
    case ViewFeedback = 'view-feedback';

    public function getLabel(): string
    {
        return __('enums.permission_name.'.$this->value);
    }
}
