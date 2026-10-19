<?php

namespace Modules\StudentPortal\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Exams\Enums\ExamStatus;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\AdmitCardPdf;
use Modules\Exams\Services\AdmitCardService;
use Modules\Exams\Services\ExamDirectory;

class ExamController
{
    use ServesPdf;

    public function routine(Request $request, ExamDirectory $exams): View
    {
        $student = $request->user('student');

        $rows = $exams->forClass($student->class_id, $student->session_id)
            ->filter(fn (Exam $exam): bool => $exam->status !== ExamStatus::Draft)
            ->map(fn (Exam $exam): array => ['exam' => $exam, 'routine' => $exams->routineFor($exam, $student->class_id)])
            ->filter(fn (array $row): bool => $row['routine']->isNotEmpty())->values();

        return view('student-portal::pages.routine', ['rows' => $rows]);
    }

    /**
     * Exams whose admit card is released, each with the eligibility answer (and why, when it is a no).
     */
    public function admit(Request $request, ExamDirectory $exams, AdmitCardService $cards): View
    {
        $student = $request->user('student');

        $rows = $exams->forClass($student->class_id, $student->session_id)
            ->filter(fn (Exam $exam): bool => $exam->admit_card_released)
            ->map(fn (Exam $exam): array => ['exam' => $exam, 'eligibility' => $cards->eligibility($exam, $student)])->values();

        return view('student-portal::pages.admit', ['rows' => $rows]);
    }

    public function admitPdf(Request $request, int $exam, AdmitCardService $cards, AdmitCardPdf $pdf): Response
    {
        $student = $request->user('student');
        $exam = Exam::query()->findOrFail($exam);

        // The same rules as the list, checked again on the download: a URL is not a permission.
        abort_unless($cards->eligibility($exam, $student)->allowed, 403);

        return $this->pdf($pdf->render($exam, collect([$student])), "admit-card-{$student->student_id}.pdf");
    }
}
