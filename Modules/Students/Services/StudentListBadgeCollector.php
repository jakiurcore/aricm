<?php

namespace Modules\Students\Services;

use Laramod\Facades\Modules;
use Modules\Students\Contracts\ProvidesStudentGpa;
use Modules\Students\Contracts\ProvidesStudentListBadge;

/**
 * Per-student extras the admin lists ask downstream modules for: the fee pill, the latest GPA.
 */
class StudentListBadgeCollector
{
    /**
     * @param  list<int>  $studentIds
     * @return array<int, 'paid'|'due'>
     */
    public function for(array $studentIds): array
    {
        $badges = [];

        if ($studentIds === []) {
            return $badges;
        }

        foreach (Modules::providing(ProvidesStudentListBadge::class) as $module) {
            $badges = $module->studentListBadges($studentIds) + $badges;
        }

        return $badges;
    }

    /**
     * @param  list<int>  $studentIds
     * @return array<int, float>
     */
    public function gpas(array $studentIds): array
    {
        $gpas = [];

        if ($studentIds === []) {
            return $gpas;
        }

        foreach (Modules::providing(ProvidesStudentGpa::class) as $module) {
            $gpas = $module->latestGpas($studentIds) + $gpas;
        }

        return $gpas;
    }
}
