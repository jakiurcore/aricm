<?php

namespace Modules\Students\Services;

use Laramod\Facades\Modules;
use Modules\Students\Contracts\ProvidesStudentSummary;
use Modules\Students\Models\Student;
use Modules\Students\Support\StudentSummaryItem;

class StudentSummaryCollector
{
    /**
     * @return list<StudentSummaryItem>
     */
    public function for(Student $student): array
    {
        $items = [];

        foreach (Modules::providing(ProvidesStudentSummary::class) as $module) {
            $items = [...$items, ...$module->studentSummary($student)];
        }

        usort($items, fn (StudentSummaryItem $a, StudentSummaryItem $b): int => $a->sortOrder <=> $b->sortOrder);

        return $items;
    }
}
