<?php

namespace Modules\StudentPortal\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Exams\Models\Exam;
use Modules\Results\Services\ResultDirectory;
use Modules\Results\Services\ResultPdf;

/**
 * A student sees a result only when it is published and every eligibility provider (e.g. Fees) agrees. The marksheet
 * PDF repeats both checks: knowing the URL is not enough.
 */
class ResultController
{
    use ServesPdf;

    public function index(Request $request, ResultDirectory $results): View
    {
        $student = $request->user('student');

        $rows = $results->publishedExamsFor($student)->map(fn (Exam $exam): array => [
            'exam' => $exam, 'result' => $results->publishedResult($exam, $student), 'eligibility' => $results->eligibility($exam, $student),
        ]);

        return view('student-portal::pages.results.index', ['rows' => $rows]);
    }

    public function show(Request $request, int $exam, ResultDirectory $results): View
    {
        $student = $request->user('student');
        $exam = Exam::query()->findOrFail($exam);
        $result = $results->publishedResult($exam, $student) ?? abort(404);

        return view('student-portal::pages.results.show', ['exam' => $exam, 'result' => $result, 'eligibility' => $results->eligibility($exam, $student)]);
    }

    public function marksheetPdf(Request $request, int $exam, ResultDirectory $results, ResultPdf $pdf): Response
    {
        $student = $request->user('student');
        $exam = Exam::query()->findOrFail($exam);
        $result = $results->publishedResult($exam, $student) ?? abort(404);

        abort_unless($results->eligibility($exam, $student)->allowed, 403);

        return $this->pdf($pdf->marksheets(collect([$result])), "marksheet-{$student->student_id}.pdf");
    }
}
