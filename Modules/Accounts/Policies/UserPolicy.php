<?php

namespace Modules\Accounts\Policies;

use Modules\Accounts\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $target): bool
    {
        // Only a super admin may touch another super admin.
        return $user->can('users.manage') && (! $target->isSuperAdmin() || $user->isSuperAdmin());
    }

    public function delete(User $user, User $target): bool
    {
        return $this->update($user, $target) && $user->isNot($target);
    }
}
