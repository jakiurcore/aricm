<?php

namespace Modules\Academic\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounts\Models\User;

/**
 * Shared by every academic model: anyone with academic.view may read, academic.manage may change.
 */
abstract class AcademicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('academic.view') || $user->can('academic.manage');
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('academic.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can('academic.manage');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('academic.manage');
    }
}
