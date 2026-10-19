<?php

namespace Modules\Students\Services;

use Modules\Students\Models\Student;

class StudentPasswords
{
    /**
     * The first password: date of birth as DDMMYYYY.
     */
    public function defaultFor(Student $student): string
    {
        return $student->date_of_birth->format('dmY');
    }

    public function reset(Student $student): void
    {
        $student->forceFill([
            'password' => $this->defaultFor($student),
            'must_change_password' => true,
        ])->save();
    }
}
