<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('roles.fields.name'))
                    ->formatStateUsing(fn (string $state): string => RoleName::tryFrom($state)?->getLabel() ?? $state),
                TextEntry::make('users_count')
                    ->label(__('roles.fields.users_count'))
                    ->state(fn (Role $record): int => User::role($record->name)->count()),
                RepeatableEntry::make('permissions')
                    ->label(__('roles.fields.permissions'))
                    ->schema([
                        TextEntry::make('name')
                            ->hiddenLabel()
                            ->formatStateUsing(fn (string $state): string => PermissionName::tryFrom($state)?->getLabel() ?? $state),
                    ])
                    ->columns(2),
            ]);
    }
}
