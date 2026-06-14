<?php

namespace App\Filament\Widgets;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;
use App\Enums\PermissionName;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\AuditLog;
use App\Models\Document;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class KnowledgeBaseHealthWidget extends Widget
{
    protected string $view = 'filament.widgets.knowledge-base-health';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(PermissionName::ViewAnalytics) ?? false;
    }

    public function documentUrl(Document $document): string
    {
        return DocumentResource::getUrl('view', ['record' => $document]);
    }

    protected function getViewData(): array
    {
        return [
            'statusCounts' => $this->statusCounts(),
            'errorDocuments' => $this->errorDocuments(),
            'recentlyUpdated' => $this->recentlyUpdated(),
            'totalIndexed' => $this->totalIndexed(),
            'totalIndexedSize' => $this->totalIndexedSize(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function statusCounts(): array
    {
        // Soft-deleted documents are intentionally excluded (default scope):
        // they no longer represent the active knowledge base.
        $counts = Document::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(DocumentStatus::cases())
            ->mapWithKeys(fn (DocumentStatus $status): array => [
                $status->getLabel() => (int) ($counts[$status->value] ?? 0),
            ])
            ->all();
    }

    protected function errorDocuments(): Collection
    {
        return Document::where('status', DocumentStatus::Error)
            ->latest()
            ->get();
    }

    protected function recentlyUpdated(): Collection
    {
        return AuditLog::where('entity_type', AuditEntityType::Document)
            ->whereIn('action', [AuditAction::DocumentUploaded, AuditAction::DocumentUpdated])
            ->latest()
            ->limit(10)
            ->get();
    }

    protected function totalIndexed(): int
    {
        return Document::where('status', DocumentStatus::Indexed)->count();
    }

    protected function totalIndexedSize(): int
    {
        return (int) Document::where('status', DocumentStatus::Indexed)->sum('file_size');
    }
}
