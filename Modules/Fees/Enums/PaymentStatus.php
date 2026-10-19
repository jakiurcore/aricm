<?php

namespace Modules\Fees\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Failed = 'failed';

    public function label(): string
    {
        return __("fees::messages.payment_status.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
