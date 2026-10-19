<?php

namespace Modules\Fees\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Bkash = 'bkash';
    case Nagad = 'nagad';
    case Rocket = 'rocket';
    case Bank = 'bank';
    case Gateway = 'gateway';

    public function label(): string
    {
        return __("fees::messages.payment_method.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Methods a guardian can pay by on their own (a mobile wallet or bank deposit with a transaction id).
     *
     * @return list<self>
     */
    public static function manual(): array
    {
        return [self::Bkash, self::Nagad, self::Rocket, self::Bank];
    }
}
