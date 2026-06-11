<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\RoleName;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected RoleName $role;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->role = $data['role'];

        unset($data['role']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole($this->role);

        AuditLog::create([
            'action' => AuditAction::UserCreated,
            'entity_type' => AuditEntityType::User,
            'entity_id' => $this->record->id,
            'performed_by' => auth()->user()->email,
            'payload' => [
                'name' => $this->record->name,
                'email' => $this->record->email,
                'role' => $this->role->value,
            ],
        ]);
    }
}
