<?php

namespace Modules\Students\Contracts;

/**
 * Implemented by Results so the promotion screen can show each student's latest published GPA without Students
 * importing it.
 */
interface ProvidesStudentGpa
{
    /**
     * @param  list<int>  $studentIds
     * @return array<int, float> Keyed by student id; students with no published result are left out.
     */
    public function latestGpas(array $studentIds): array;
}
