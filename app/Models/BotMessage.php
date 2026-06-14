<?php

namespace App\Models;

use App\Ai\Agents\SupportAgent;
use Database\Factories\BotMessageFactory;
use Illuminate\Database\Eloquent\Builder;
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
        'was_fresh_session',
    ];

    protected function casts(): array
    {
        return [
            'was_helpful' => 'boolean',
            'was_fresh_session' => 'boolean',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function isEscalated(): bool
    {
        return str_contains($this->answer, SupportAgent::ESCALATION_PHRASE);
    }

    public function scopeEscalated(Builder $query): Builder
    {
        return $query->where('answer', 'like', '%'.SupportAgent::ESCALATION_PHRASE.'%');
    }

    public function formatResponseMs(): ?string
    {
        if ($this->response_ms === null) {
            return null;
        }

        return number_format($this->response_ms / 1000, 1).'s';
    }
}
