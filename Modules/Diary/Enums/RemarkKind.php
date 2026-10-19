<?php

namespace Modules\Diary\Enums;

enum RemarkKind: string
{
    case Praise = 'praise';
    case Concern = 'concern';
    case Info = 'info';
    case NoHomework = 'no_homework';
    case Inattentive = 'inattentive';
    case Absent = 'absent';
    case Other = 'other';

    public function label(): string
    {
        return __('diary::messages.remark_kinds.'.$this->value);
    }
}
