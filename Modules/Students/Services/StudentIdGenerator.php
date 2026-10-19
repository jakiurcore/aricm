<?php

namespace Modules\Students\Services;

use Modules\Students\Models\Student;

class StudentIdGenerator
{
    public const string PREFIX = 'ARICM';

    /**
     * Next ID for a year, e.g. ARICM-2026-0001. Call inside a transaction: it reads the highest number used so far
     * (trashed students included, so an ID is never reused).
     */
    public function next(int $year): string
    {
        $prefix = self::PREFIX."-{$year}-";

        $last = Student::withTrashed()
            ->where('student_id', 'like', $prefix.'%')
            ->orderByDesc('student_id')
            ->lockForUpdate()
            ->value('student_id');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
