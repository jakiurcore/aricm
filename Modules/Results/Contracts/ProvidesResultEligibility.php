<?php

namespace Modules\Results\Contracts;

use Modules\Exams\Models\Exam;
use Modules\Exams\Support\Eligibility;
use Modules\Students\Models\Student;

/**
 * Implemented by modules that can hold a published result back from a student (Fees: unpaid dues, when the setting is
 * on). Results asks every provider before showing a result and never imports them.
 */
interface ProvidesResultEligibility
{
    public function resultEligibility(Exam $exam, Student $student): Eligibility;
}
