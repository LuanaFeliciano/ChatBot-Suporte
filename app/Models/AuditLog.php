<?php

namespace App\Models;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    protected $fillable = [
        'action',
        'entity_type',
        'entity_id',
        'payload',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'entity_type' => AuditEntityType::class,
            'payload' => 'array',
        ];
    }
}
