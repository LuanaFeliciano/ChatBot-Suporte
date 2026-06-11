<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('roles.fields.name'))
                    ->formatStateUsing(fn (string $state): string => RoleName::tryFrom($state)?->getLabel() ?? $state)
                    ->badge(),
                TextColumn::make('permissions.name')
                    ->label(__('roles.fields.permissions'))
                    ->formatStateUsing(fn (string $state): string => PermissionName::tryFrom($state)?->getLabel() ?? $state)
                    ->badge()
                    ->listWithLineBreaks(),
                TextColumn::make('users_count')
                    ->label(__('roles.fields.users_count'))
                    ->state(fn (Role $record): int => User::role($record->name)->count()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
