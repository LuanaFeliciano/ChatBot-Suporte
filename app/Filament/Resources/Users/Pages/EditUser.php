<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\RoleName;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $oldRole = null;

    protected string $newRole;

    protected bool $roleChanged = false;

    protected bool $oldIsActive = true;

    protected bool $newIsActive = true;

    protected bool $wasDeactivated = false;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action): void {
                    if (! $this->record->hasRole(RoleName::Admin)) {
                        return;
                    }

                    if (User::role(RoleName::Admin)->where('id', '!=', $this->record->id)->exists()) {
                        return;
                    }

                    Notification::make()
                        ->danger()
                        ->title(__('users.validation.cannot_delete_last_admin'))
                        ->send();

                    $action->halt();
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role'] = $this->record->roles->first()?->name;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $newRole = $data['role'] instanceof RoleName ? $data['role']->value : $data['role'];

        unset($data['role']);

        $this->oldRole = $this->record->roles->first()?->name;
        $this->newRole = $newRole;
        $this->roleChanged = $this->oldRole !== $this->newRole;

        $this->oldIsActive = (bool) $this->record->is_active;
        $this->newIsActive = (bool) ($data['is_active'] ?? $this->oldIsActive);
        $this->wasDeactivated = $this->oldIsActive && ! $this->newIsActive;

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->roleChanged) {
            $this->record->syncRoles([$this->newRole]);
        }

        AuditLog::create([
            'action' => AuditAction::UserUpdated,
            'entity_type' => AuditEntityType::User,
            'entity_id' => $this->record->id,
            'performed_by' => auth()->user()->email,
            'payload' => [
                'name' => $this->record->name,
                'email' => $this->record->email,
                'role' => $this->newRole,
                'is_active' => $this->newIsActive,
            ],
        ]);

        if ($this->wasDeactivated) {
            AuditLog::create([
                'action' => AuditAction::UserDeactivated,
                'entity_type' => AuditEntityType::User,
                'entity_id' => $this->record->id,
                'performed_by' => auth()->user()->email,
                'payload' => [
                    'name' => $this->record->name,
                    'email' => $this->record->email,
                ],
            ]);
        }

        if ($this->roleChanged) {
            AuditLog::create([
                'action' => AuditAction::UserRoleChanged,
                'entity_type' => AuditEntityType::User,
                'entity_id' => $this->record->id,
                'performed_by' => auth()->user()->email,
                'payload' => [
                    'name' => $this->record->name,
                    'email' => $this->record->email,
                    'old_role' => $this->oldRole,
                    'new_role' => $this->newRole,
                ],
            ]);
        }
    }
}
