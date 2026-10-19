<?php

namespace Modules\Results\Services\Engine;

final readonly class ComponentSpec
{
    public function __construct(public int $id, public string $name, public float $fullMarks, public float $passMarks) {}
}
