<?php

namespace Modules\Core\Http\Controllers\Concerns;

use Illuminate\Http\Response;

trait ServesPdf
{
    /**
     * Respond with PDF bytes, shown inline (the browser's viewer has its own download button).
     */
    protected function pdf(string $bytes, string $filename): Response
    {
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
