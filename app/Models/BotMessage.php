<?php

namespace App\Models;

use Database\Factories\BotMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotMessage extends Model
{
    /** @use HasFactory<BotMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'channel_user',
        'user_name',
        'question',
        'answer',
        'response_ms',
        'was_helpful',
    ];

    protected function casts(): array
    {
        return [
            'was_helpful' => 'boolean',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }
}
