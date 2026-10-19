<?php

namespace Modules\Core\Contracts;

use Modules\Core\Support\TickerItem;

/**
 * Implemented by modules that put items in the scrolling ticker at the top of every public page (Notices).
 */
interface ProvidesTicker
{
    /**
     * @return list<TickerItem>
     */
    public function tickerItems(): array;
}
