<?php

namespace App\Services;

use App\Filament\Resources\Documents\DocumentResource;

class KnowledgeGapAnalyzer
{
    /**
     * `escalatedCount` (not `file_search_hit_count`) drives the "new documentation"
     * recommendation: the current `laravel/ai` SDK does not expose `file_citation`
     * annotations from the OpenAI File Search tool, so the hit count is always
     * 0/null and is not a reliable signal of missing content. Escalation, however,
     * is a signal fully controlled by this app's own system prompt. This can be
     * revisited once the SDK exposes File Search citation data.
     *
     * @return array{label: string, route: ?string}
     */
    public function recommend(int $occurrences, int $escalatedCount, bool $hasNegativeFeedback): array
    {
        $escalationRatio = $escalatedCount / $occurrences;

        return match (true) {
            $escalationRatio >= (float) config('suporte.knowledge_gap_escalation_threshold') => [
                'label' => 'new_documentation',
                'route' => DocumentResource::getUrl('create'),
            ],
            $hasNegativeFeedback => [
                'label' => 'review_content',
                'route' => DocumentResource::getUrl('index'),
            ],
            default => [
                'label' => 'ok',
                'route' => null,
            ],
        };
    }
}
