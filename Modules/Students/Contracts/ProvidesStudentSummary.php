<?php

namespace Modules\Students\Contracts;

use Modules\Students\Models\Student;
use Modules\Students\Support\StudentSummaryItem;

/**
 * Implemented by downstream modules (Fees: dues, Results: latest GPA, Diary: unseen entries) to add facts to the
 * student profile and the portal dashboard without Students importing them.
 */
interface ProvidesStudentSummary
{
    /**
     * @return list<StudentSummaryItem>
     */
    public function studentSummary(Student $student): array;
}
