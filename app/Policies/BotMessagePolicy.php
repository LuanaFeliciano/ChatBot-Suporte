<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\BotMessage;
use App\Models\User;

class BotMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionName::ViewConversations);
    }

    public function view(User $user, BotMessage $botMessage): bool
    {
        return $user->can(PermissionName::ViewConversations);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, BotMessage $botMessage): bool
    {
        return false;
    }

    public function delete(User $user, BotMessage $botMessage): bool
    {
        return false;
    }
}
