<?php

namespace Modules\Diary\Enums;

enum RemarkKind: string
{
    case Praise = 'praise';
    case Concern = 'concern';
    case Info = 'info';

    public function label(): string
    {
        return __('diary::messages.remark_kinds.'.$this->value);
    }
}
