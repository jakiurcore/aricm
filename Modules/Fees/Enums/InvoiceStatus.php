<?php

namespace Modules\Fees\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("fees::messages.invoice_status.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuses in which an invoice can still receive a payment.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Unpaid, self::Partial];
    }
}
