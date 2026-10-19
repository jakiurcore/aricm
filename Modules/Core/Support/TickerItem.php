<?php

namespace Modules\Core\Support;

final readonly class TickerItem
{
    public function __construct(public string $text, public ?string $url = null) {}
}
