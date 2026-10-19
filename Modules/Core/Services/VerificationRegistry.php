<?php

namespace Modules\Core\Services;

use Laramod\Facades\Modules;
use Modules\Core\Contracts\ProvidesVerification;
use Modules\Core\Support\VerificationResult;

/**
 * Resolves /verify/{type}/{hash} through whichever module registered the type.
 */
class VerificationRegistry
{
    public function resolve(string $type, string $hash): ?VerificationResult
    {
        foreach (Modules::providing(ProvidesVerification::class) as $module) {
            $handler = $module->verificationHandlers()[$type] ?? null;

            if ($handler !== null) {
                return $handler->verify($hash);
            }
        }

        return null;
    }

    public function url(string $type, string $hash): string
    {
        return route('core.verify', ['type' => $type, 'hash' => $hash]);
    }
}
