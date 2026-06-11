<?php

namespace App\Enums;

enum AuditEntityType: string
{
    case Document = 'document';
    case BotMessage = 'bot_message';
    case User = 'user';
    case Role = 'role';
}
