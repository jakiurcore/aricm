<?php

namespace Modules\Students\Contracts;

/**
 * Implemented by a downstream module (Fees) to say, per student, whether the fee column of the admin list reads paid or due
 * without Students importing it.
 */
interface ProvidesStudentListBadge
{
    /**
     * @param  list<int>  $studentIds
     * @return array<int, 'paid'|'due'> Keyed by student id. Students it knows nothing about are left out.
     */
    public function studentListBadges(array $studentIds): array;
}
