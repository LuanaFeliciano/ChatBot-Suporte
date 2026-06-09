<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'original_filename',
        'openai_file_id',
        'vector_store_file_id',
        'status',
        'attributes',
        'error_message',
        'uploaded_by',
        'file_size',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'attributes' => 'array',
            'file_size' => 'integer',
        ];
    }
}
