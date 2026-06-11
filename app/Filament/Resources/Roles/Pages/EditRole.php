<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\RoleName;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** @var array<int, string>|null */
    protected ?array $permissionsToSync = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = $this->record->permissions->pluck('name')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->name === RoleName::Admin->value) {
            unset($data['permissions']);
            $this->permissionsToSync = null;

            return $data;
        }

        $this->permissionsToSync = $data['permissions'] ?? [];
        unset($data['permissions']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->permissionsToSync === null) {
            return;
        }

        $this->record->syncPermissions($this->permissionsToSync);

        AuditLog::create([
            'action' => AuditAction::RolePermissionsUpdated,
            'entity_type' => AuditEntityType::Role,
            'entity_id' => $this->record->id,
            'performed_by' => auth()->user()->email,
            'payload' => [
                'role' => $this->record->name,
                'permissions' => $this->permissionsToSync,
            ],
        ]);
    }
}
