<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ManageUsers);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(PermissionName::ManageUsers);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ManageUsers);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(PermissionName::ManageUsers);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can(PermissionName::ManageUsers);
    }
}
