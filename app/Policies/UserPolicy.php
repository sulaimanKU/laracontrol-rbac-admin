<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission(Permission::ViewUsers);
    }

    public function view(User $actor, User $subject): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $actor->hasPermission(Permission::ManageUsers);
    }

    public function update(User $actor, User $subject): bool
    {
        return $actor->hasPermission(Permission::ManageUsers) && ($actor->isAdmin() || ($actor->role?->name === 'Manager' && $subject->role?->name === 'Member'));
    }
}
