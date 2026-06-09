<?php

namespace App\Enums;

enum AuditAction: string
{
    case DocumentUploaded = 'document.uploaded';
    case DocumentDeleted = 'document.deleted';
    case DocumentError = 'document.error';
}
