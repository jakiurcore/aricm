<?php

namespace Modules\Exams\Policies;

use Modules\Accounts\Models\User;
use Modules\Exams\Models\Exam;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('exams.view');
    }

    public function view(User $user, Exam $exam): bool
    {
        return $user->can('exams.view');
    }

    public function create(User $user): bool
    {
        return $user->can('exams.manage');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->can('exams.manage');
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->can('exams.manage');
    }

    public function manageAdmitCards(User $user, Exam $exam): bool
    {
        return $user->can('exams.admit_card');
    }
}
