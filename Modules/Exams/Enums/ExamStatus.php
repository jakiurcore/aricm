<?php

namespace Modules\Exams\Enums;

enum ExamStatus: string
{
    case Draft = 'draft';
    case Ongoing = 'ongoing';
    case Completed = 'completed';

    public function label(): string
    {
        return __("exams::messages.status.{$this->value}");
    }
}
