<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\RoleName;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('users.fields.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('users.fields.email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label(__('users.fields.password'))
                    ->password()
                    ->revealable()
                    ->confirmed()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn (?string $state) => Hash::make($state))
                    ->dehydrated(fn (?string $state) => filled($state)),
                TextInput::make('password_confirmation')
                    ->label(__('users.fields.password_confirmation'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Select::make('role')
                    ->label(__('users.fields.role'))
                    ->options(RoleName::class)
                    ->native(false)
                    ->required()
                    ->rule(fn (?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        if (! $record instanceof User) {
                            return;
                        }

                        $newRole = $value instanceof RoleName ? $value->value : $value;

                        if ($newRole === RoleName::Admin->value) {
                            return;
                        }

                        if (! $record->hasRole(RoleName::Admin)) {
                            return;
                        }

                        if (User::role(RoleName::Admin)->where('id', '!=', $record->id)->exists()) {
                            return;
                        }

                        $fail(__('users.validation.cannot_remove_last_admin_role'));
                    }),
                Toggle::make('is_active')
                    ->label(__('users.fields.is_active'))
                    ->default(true)
                    ->hiddenOn('create')
                    ->rule(fn (?User $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        if (! $record instanceof User) {
                            return;
                        }

                        if ($value) {
                            return;
                        }

                        if (! $record->hasRole(RoleName::Admin)) {
                            return;
                        }

                        if (User::role(RoleName::Admin)->where('id', '!=', $record->id)->exists()) {
                            return;
                        }

                        $fail(__('users.validation.cannot_deactivate_last_admin'));
                    }),
            ]);
    }
}
