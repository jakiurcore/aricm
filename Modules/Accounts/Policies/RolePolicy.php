<?php

namespace Modules\Accounts\Policies;

use Modules\Accounts\Models\Role;
use Modules\Accounts\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.manage') || $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.manage') && $role->name !== User::SUPER_ADMIN;
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role) && ! $role->isSystem();
    }
}
