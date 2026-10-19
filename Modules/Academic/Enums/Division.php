<?php

namespace Modules\Academic\Enums;

enum Division: string
{
    case Kids = 'kids';
    case Junior = 'junior';

    public function label(): string
    {
        return __("academic::messages.division.{$this->value}");
    }
}
