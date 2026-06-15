<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Observers\BotMessageObserver;
use Database\Factories\BotMessageFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([BotMessageObserver::class])]
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
        'file_search_hit_count',
        'question_normalized',
        'channel_message_id',
        'is_escalated',
    ];

    protected function casts(): array
    {
        return [
            'was_helpful' => 'boolean',
            'was_fresh_session' => 'boolean',
            'file_search_hit_count' => 'integer',
            'is_escalated' => 'boolean',
        ];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function isEscalated(): bool
    {
        return (bool) $this->is_escalated;
    }

    public function scopeEscalated(Builder $query): Builder
    {
        return $query->where('is_escalated', true);
    }

    public function scopeNoDocumentIndexed(Builder $query): Builder
    {
        return $query->whereNotExists(function ($query) {
            $query->select('id')
                ->from('documents')
                ->where('status', DocumentStatus::Indexed)
                ->whereColumn('created_at', '<=', 'bot_messages.created_at');
        });
    }

    /**
     * A response is "low confidence" when the agent answered without escalating
     * (i.e. it appeared confident) but the end user reported it as not helpful.
     *
     * `file_search_hit_count` is intentionally NOT used here: the current
     * `laravel/ai` SDK does not expose `file_citation` annotations from the
     * OpenAI File Search tool, so the column is always 0/null regardless of
     * whether relevant documents were actually found. This rule can be
     * revisited once the SDK exposes that data.
     */
    public function scopeLowConfidence(Builder $query): Builder
    {
        return $query
            ->where('was_helpful', false)
            ->whereNot(fn (Builder $query) => $query->escalated());
    }

    public function formatResponseMs(): ?string
    {
        if ($this->response_ms === null) {
            return null;
        }

        return number_format($this->response_ms / 1000, 1).'s';
    }
}
