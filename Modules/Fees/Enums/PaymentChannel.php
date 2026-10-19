<?php

namespace Modules\Fees\Enums;

enum PaymentChannel: string
{
    case Counter = 'counter';
    case StudentManual = 'student_manual';
    case Gateway = 'gateway';

    public function label(): string
    {
        return __("fees::messages.payment_channel.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
