<?php

namespace Modules\Core\Contracts;

/**
 * Implemented by modules whose printed documents carry a QR code to /verify/{type}/{hash}.
 */
interface ProvidesVerification
{
    /**
     * The handlers this module registers, keyed by the {type} segment of the verification URL.
     *
     * @return array<string, VerificationHandler>
     */
    public function verificationHandlers(): array;
}
