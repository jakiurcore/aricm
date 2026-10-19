<?php

namespace Modules\Core\Contracts;

use Modules\Core\Support\VerificationResult;

interface VerificationHandler
{
    /**
     * Resolve a hash from a QR code. Null means the document is unknown or invalid.
     */
    public function verify(string $hash): ?VerificationResult;
}
