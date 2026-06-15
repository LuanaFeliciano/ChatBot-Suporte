<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([PermissionName::ManageDocuments, PermissionName::ViewDocuments]);
    }

    public function view(User $user, Document $document): bool
    {
        return $user->canAny([PermissionName::ManageDocuments, PermissionName::ViewDocuments]);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionName::ManageDocuments);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can(PermissionName::ManageDocuments);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can(PermissionName::ManageDocuments);
    }
}
