<?php

namespace Modules\Results\Services;

use Modules\Exams\Models\Exam;
use Modules\Students\Models\Student;

/**
 * The public lookup: exam + class + roll or student ID + date of birth. A wrong combination looks the same as no
 * result, so it cannot be used to discover which students exist.
 */
class ResultSearch
{
    public function find(Exam $exam, int $classId, string $identifier, string $dateOfBirth): ?Student
    {
        $identifier = trim($identifier);

        return Student::query()
            ->where('class_id', $classId)
            ->where('session_id', $exam->session_id)
            ->whereDate('date_of_birth', $dateOfBirth)
            ->where(fn ($query) => $query->where('student_id', strtoupper($identifier))->when(ctype_digit($identifier), fn ($inner) => $inner->orWhere('roll', (int) $identifier)))
            ->first();
    }
}
