<?php

namespace Modules\StudentPortal\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Notices\Services\NoticeDirectory;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentSummaryCollector;

class DashboardController
{
    public function __invoke(Request $request, StudentSummaryCollector $summary, NoticeDirectory $notices): View
    {
        /** @var Student $student */
        $student = $request->user('student');
        $student->load(['schoolClass', 'section', 'session']);

        return view('student-portal::pages.dashboard', [
            'student' => $student,
            'summary' => $summary->for($student),
            'notifications' => $student->unreadNotifications()->limit(5)->get(),
            'notices' => $notices->latest(4),
        ]);
    }

    public function readNotifications(Request $request): RedirectResponse
    {
        $request->user('student')->unreadNotifications->markAsRead();

        return back();
    }
}
