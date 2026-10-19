<?php

namespace Modules\Core\Support;

final readonly class VerificationResult
{
    /**
     * @param  array<string, string>  $details  Label => value, already translated and formatted.
     */
    public function __construct(
        public string $title,
        public array $details,
    ) {}
}
