<?php

namespace Modules\Results\Services\Engine;

final readonly class MarkInput
{
    public function __construct(public ?float $obtained, public bool $absent = false) {}

    public static function absent(): self
    {
        return new self(null, true);
    }
}
