<?php

namespace App\Filament\Widgets;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\DocumentStatus;
use App\Enums\PermissionName;
use App\Filament\Pages\Dashboard as AnalyticsDashboard;
use App\Filament\Pages\KnowledgeGaps;
use App\Filament\Resources\BotMessages\BotMessageResource;
use App\Filament\Resources\Documents\DocumentResource;
use App\Models\AuditLog;
use App\Models\BotMessage;
use App\Models\Document;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class DashboardOverviewWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.dashboard-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array{cards: array<int, array{label: string, value: string}>, actions: array<int, array{label: string, url: string, icon: Heroicon}>}
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'cards' => $user->can(PermissionName::ViewAnalytics) ? $this->adminCards() : $this->supportCards(),
            'actions' => $this->actions($user),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function adminCards(): array
    {
        return [
            [
                'label' => __('dashboard.cards.conversations_today'),
                'value' => (string) BotMessage::whereDate('created_at', today())->count(),
            ],
            [
                'label' => __('dashboard.cards.conversations_last_7_days'),
                'value' => (string) BotMessage::where('created_at', '>=', now()->subDays(7))->count(),
            ],
            [
                'label' => __('dashboard.cards.pending_knowledge_gaps'),
                'value' => (string) $this->pendingKnowledgeGapsCount(),
            ],
            [
                'label' => __('dashboard.cards.indexed_documents'),
                'value' => (string) Document::where('status', DocumentStatus::Indexed)->count(),
            ],
            [
                'label' => __('dashboard.cards.last_indexed_at'),
                'value' => $this->lastIndexedAt(),
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function supportCards(): array
    {
        return [
            [
                'label' => __('dashboard.cards.recent_conversations'),
                'value' => (string) BotMessage::where('created_at', '>=', now()->subHours(24))->count(),
            ],
            [
                'label' => __('dashboard.cards.pending_review'),
                'value' => (string) $this->pendingKnowledgeGapsCount(),
            ],
            [
                'label' => __('dashboard.cards.documents_processing'),
                'value' => (string) Document::whereIn('status', [DocumentStatus::Pending, DocumentStatus::Uploading])->count(),
            ],
        ];
    }

    private function pendingKnowledgeGapsCount(): int
    {
        return BotMessage::where(
            fn (Builder $query): Builder => $query->escalated()
                ->orWhere(fn (Builder $query): Builder => $query->noDocumentIndexed())
        )->count();
    }

    private function lastIndexedAt(): string
    {
        $auditLog = AuditLog::where('entity_type', AuditEntityType::Document)
            ->whereIn('action', [AuditAction::DocumentUploaded, AuditAction::DocumentUpdated])
            ->latest()
            ->first();

        return $auditLog ? $auditLog->created_at->diffForHumans() : __('dashboard.cards.never');
    }

    /**
     * @return array<int, array{label: string, url: string, icon: Heroicon}>
     */
    private function actions(User $user): array
    {
        return array_values(array_filter([
            $user->can(PermissionName::ViewConversations) ? [
                'label' => __('dashboard.actions.conversations'),
                'url' => BotMessageResource::getUrl(),
                'icon' => Heroicon::OutlinedChatBubbleLeftRight,
            ] : null,
            KnowledgeGaps::canAccess() ? [
                'label' => __('dashboard.actions.knowledge_gaps'),
                'url' => KnowledgeGaps::getUrl(),
                'icon' => Heroicon::OutlinedExclamationTriangle,
            ] : null,
            DocumentResource::canViewAny() ? [
                'label' => __('dashboard.actions.knowledge_base'),
                'url' => DocumentResource::getUrl(),
                'icon' => Heroicon::OutlinedRectangleStack,
            ] : null,
            $user->can(PermissionName::ViewAnalytics) ? [
                'label' => __('dashboard.actions.analytics'),
                'url' => AnalyticsDashboard::getUrl(),
                'icon' => Heroicon::OutlinedChartBar,
            ] : null,
        ]));
    }
}
