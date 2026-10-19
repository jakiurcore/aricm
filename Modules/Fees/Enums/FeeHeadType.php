<?php

namespace Modules\Fees\Enums;

enum FeeHeadType: string
{
    case Monthly = 'monthly';
    case OneTime = 'one_time';
    case Exam = 'exam';

    public function label(): string
    {
        return __("fees::messages.fee_head_type.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
