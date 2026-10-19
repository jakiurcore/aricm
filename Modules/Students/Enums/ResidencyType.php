<?php

namespace Modules\Students\Enums;

enum ResidencyType: string
{
    case NonResidential = 'non_residential';
    case Residential = 'residential';
    case DayCare = 'day_care';

    public function label(): string
    {
        return __("students::messages.residency_type.{$this->value}");
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
