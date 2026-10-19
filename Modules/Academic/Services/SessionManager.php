<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Facades\DB;
use Modules\Academic\Models\AcademicSession;

class SessionManager
{
    /**
     * Make a session the current one; there is only ever one.
     */
    public function makeCurrent(AcademicSession $session): void
    {
        DB::transaction(function () use ($session): void {
            AcademicSession::query()->whereKeyNot($session->id)->where('is_current', true)->update(['is_current' => false]);
            $session->update(['is_current' => true]);
        });
    }
}
