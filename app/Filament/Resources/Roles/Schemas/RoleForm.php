<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CheckboxList::make('permissions')
                    ->label(__('roles.fields.permissions'))
                    ->options(PermissionName::class)
                    ->columns(2)
                    ->disabled(fn (?Role $record): bool => $record?->name === RoleName::Admin->value),
            ]);
    }
}
