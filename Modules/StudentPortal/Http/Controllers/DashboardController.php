<?php

namespace Modules\StudentPortal\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Exams\Enums\ExamStatus;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\ExamDirectory;
use Modules\Fees\Services\StudentFeeDesk;
use Modules\Notices\Services\NoticeDirectory;
use Modules\Results\Services\ResultDirectory;
use Modules\StudentPortal\Services\PortalDiary;
use Modules\Students\Models\Student;

class DashboardController
{
    public function __invoke(Request $request, NoticeDirectory $notices, ExamDirectory $exams, ResultDirectory $results, StudentFeeDesk $fees, PortalDiary $diary): View
    {
        /** @var Student $student */
        $student = $request->user('student');
        $student->load(['schoolClass', 'section', 'session']);

        $openInvoices = $fees->openInvoices($student);
        $latestResult = $results->latestPublishedFor($student);

        return view('student-portal::pages.dashboard', [
            'student' => $student,
            'due' => $fees->totalDue($student),
            'openInvoices' => $openInvoices,
            'firstDue' => $openInvoices->first(fn ($invoice): bool => $invoice->due_date !== null)?->due_date,
            'diary' => $diary->today($student),
            'exam' => $this->upcomingExam($exams, $student),
            'notice' => $notices->latest(1)->first(),
            'result' => $latestResult,
            'resultEligibility' => $latestResult ? $results->eligibility($latestResult->exam, $student) : null,
            'notifications' => $student->unreadNotifications()->limit(5)->get(),
        ]);
    }

    public function readNotifications(Request $request): RedirectResponse
    {
        $request->user('student')->unreadNotifications->markAsRead();

        return back();
    }

    /**
     * The next exam that has not started yet, otherwise the most recent one (never a draft).
     */
    protected function upcomingExam(ExamDirectory $exams, Student $student): ?Exam
    {
        $visible = $exams->forClass($student->class_id, $student->session_id)->filter(fn (Exam $exam): bool => $exam->status !== ExamStatus::Draft);

        return $visible->filter(fn (Exam $exam): bool => $exam->start_date !== null && $exam->start_date->isFuture() || $exam->start_date?->isToday())->sortBy('start_date')->first() ?? $visible->first();
    }
}
