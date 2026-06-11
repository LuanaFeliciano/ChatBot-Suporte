<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\RoleName;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.fields.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('users.fields.email'))
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label(__('users.fields.role'))
                    ->formatStateUsing(fn (?string $state): string => $state !== null ? (RoleName::tryFrom($state)?->getLabel() ?? $state) : '')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label(__('users.fields.is_active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label(__('users.fields.last_login'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('users.fields.never')),
                TextColumn::make('created_at')
                    ->label(__('users.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label(__('users.fields.role'))
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => RoleName::tryFrom($record->name)?->getLabel() ?? $record->name),
                TernaryFilter::make('is_active')
                    ->label(__('users.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (DeleteBulkAction $action): void {
                            $selectedAdminIds = $action->getSelectedRecords()
                                ->filter(fn (User $user): bool => $user->hasRole(RoleName::Admin))
                                ->pluck('id');

                            if ($selectedAdminIds->isEmpty()) {
                                return;
                            }

                            if (User::role(RoleName::Admin)->whereNotIn('id', $selectedAdminIds)->exists()) {
                                return;
                            }

                            Notification::make()
                                ->danger()
                                ->title(__('users.validation.cannot_delete_last_admin'))
                                ->send();

                            $action->halt();
                        }),
                ]),
            ]);
    }
}
