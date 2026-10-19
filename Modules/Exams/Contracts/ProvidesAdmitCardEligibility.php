<?php

namespace Modules\Exams\Contracts;

use Modules\Exams\Models\Exam;
use Modules\Exams\Support\Eligibility;
use Modules\Students\Models\Student;

/**
 * Implemented by modules that can hold back an admit card (Fees: unpaid exam fee). Exams asks every provider before
 * issuing a card, so Exams never imports them.
 */
interface ProvidesAdmitCardEligibility
{
    public function admitCardEligibility(Exam $exam, Student $student): Eligibility;
}
