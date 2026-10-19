<?php

namespace Modules\Fees\Enums;

enum ConcessionReason: string
{
    case Orphan = 'orphan';
    case Sibling = 'sibling';
    case Merit = 'merit';
    case Special = 'special';
    case Other = 'other';

    public function label(): string
    {
        return __("fees::messages.concession_reason.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
