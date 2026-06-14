<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\Document;

class KnowledgeGapAnalyzer
{
    private const STOP_WORDS = [
        'como', 'para', 'quando', 'onde', 'porque', 'qual', 'quais', 'quem',
        'uma', 'um', 'umas', 'uns', 'meu', 'minha', 'meus', 'minhas',
        'seu', 'sua', 'seus', 'suas', 'isso', 'esse', 'essa', 'este', 'esta',
        'nao', 'sim', 'com', 'sem', 'por', 'mais', 'menos', 'tem', 'ter',
        'fazer', 'faco', 'app', 'aplicativo', 'sobre', 'pode', 'posso',
        'preciso', 'gostaria', 'voce', 'estou', 'esta',
    ];

    /**
     * @return array{label: string, document: ?Document, route: ?string}
     */
    public function recommend(string $representativeQuestion): array
    {
        $document = $this->findRelatedDocument($representativeQuestion);

        return match (true) {
            $document === null => [
                'label' => 'New documentation needed',
                'document' => null,
                'route' => null,
            ],
            $document->status === DocumentStatus::Error => [
                'label' => 'Fix indexing error',
                'document' => $document,
                'route' => DocumentResource::getUrl('view', ['record' => $document]),
            ],
            default => [
                'label' => 'Review existing content — possibly outdated',
                'document' => $document,
                'route' => DocumentResource::getUrl('index'),
            ],
        };
    }

    private function findRelatedDocument(string $representativeQuestion): ?Document
    {
        $keywords = $this->keywords($representativeQuestion);

        if ($keywords === []) {
            return null;
        }

        return Document::query()
            ->get()
            ->first(function (Document $document) use ($keywords): bool {
                $haystack = mb_strtolower($document->name.' '.($document->attributes['module'] ?? ''));

                foreach ($keywords as $keyword) {
                    if (str_contains($haystack, $keyword)) {
                        return true;
                    }
                }

                return false;
            });
    }

    /**
     * @return array<int, string>
     */
    private function keywords(string $question): array
    {
        return collect(explode(' ', $question))
            ->filter(fn (string $word): bool => mb_strlen($word) >= 4 && ! in_array($word, self::STOP_WORDS, true))
            ->values()
            ->all();
    }
}
