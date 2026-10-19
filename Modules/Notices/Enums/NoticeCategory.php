<?php

namespace Modules\Notices\Enums;

enum NoticeCategory: string
{
    case General = 'general';
    case Exam = 'exam';
    case Admission = 'admission';
    case Holiday = 'holiday';
    case Event = 'event';
    case Result = 'result';

    public function label(): string
    {
        return __("notices::messages.category.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
