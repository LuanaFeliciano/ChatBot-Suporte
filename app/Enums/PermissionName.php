<?php

namespace App\Enums;

enum PermissionName: string
{
    case ManageUsers = 'manage-users';
    case ManageRoles = 'manage-roles';
    case ManageDocuments = 'manage-documents';
    case ViewConversations = 'view-conversations';
    case ViewAnalytics = 'view-analytics';
    case ViewAuditLogs = 'view-audit-logs';
    case ViewKnowledgeGaps = 'view-knowledge-gaps';
    case ViewFeedback = 'view-feedback';
}
