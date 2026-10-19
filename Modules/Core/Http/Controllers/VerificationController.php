<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Core\Services\VerificationRegistry;

class VerificationController
{
    public function show(VerificationRegistry $registry, string $type, string $hash): View
    {
        return view('core::verify.show', [
            'type' => $type,
            'result' => $registry->resolve($type, $hash),
        ]);
    }
}
