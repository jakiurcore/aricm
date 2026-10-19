<?php

namespace Modules\StudentPortal\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Students\Services\IdCardPdf;

class ProfileController
{
    use ServesPdf;

    public function show(Request $request): View
    {
        return view('student-portal::pages.profile', ['student' => $request->user('student')->load(['schoolClass', 'section', 'session'])]);
    }

    /**
     * The student's own ID card, never anyone else's: the student comes from the session, not the request.
     */
    public function idCard(Request $request, IdCardPdf $cards): Response
    {
        $student = $request->user('student');

        return $this->pdf($cards->render(collect([$student])), "id-card-{$student->student_id}.pdf");
    }
}
