<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ManageRoles);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(PermissionName::ManageRoles);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ManageRoles);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(PermissionName::ManageRoles);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(PermissionName::ManageRoles);
    }
}
