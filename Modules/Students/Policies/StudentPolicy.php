<?php

namespace Modules\Students\Policies;

use Modules\Accounts\Models\User;
use Modules\Students\Models\Student;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can('students.view');
    }

    public function create(User $user): bool
    {
        return $user->can('students.manage');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('students.manage');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('students.manage');
    }

    public function resetPassword(User $user, Student $student): bool
    {
        return $user->can('students.reset_password');
    }

    public function import(User $user): bool
    {
        return $user->can('students.import');
    }

    public function promote(User $user): bool
    {
        return $user->can('students.promote');
    }
}
