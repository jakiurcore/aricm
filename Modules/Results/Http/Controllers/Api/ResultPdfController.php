<?php

namespace Modules\Results\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Academic\Models\SchoolClass;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Exams\Models\Exam;
use Modules\Results\Models\ExamResult;
use Modules\Results\Services\ResultPdf;

class ResultPdfController
{
    use ServesPdf;

    public function marksheets(Request $request, Exam $exam, SchoolClass $class, ResultPdf $pdf): Response
    {
        abort_unless($request->user()->can('results.view'), 403);

        $results = ExamResult::query()
            ->where(['exam_id' => $exam->id, 'class_id' => $class->id])
            ->when($request->integer('section_id'), fn ($query, int $id) => $query->where('section_id', $id))
            ->when($request->integer('student_id'), fn ($query, int $id) => $query->where('student_id', $id))
            ->orderBy('roll')->limit(200)->get();

        abort_if($results->isEmpty(), 404);

        return $this->pdf($pdf->marksheets($results), 'marksheets.pdf');
    }

    public function tabulation(Request $request, Exam $exam, SchoolClass $class, ResultPdf $pdf): Response
    {
        abort_unless($request->user()->can('results.view'), 403);
        abort_unless($exam->classes()->whereKey($class->id)->exists(), 404);

        return $this->pdf($pdf->tabulation($exam, $class->id, $request->integer('section_id') ?: null), 'tabulation.pdf');
    }

    public function merit(Request $request, Exam $exam, SchoolClass $class, ResultPdf $pdf): Response
    {
        abort_unless($request->user()->can('results.view'), 403);
        abort_unless($exam->classes()->whereKey($class->id)->exists(), 404);

        return $this->pdf($pdf->meritList($exam, $class->id, $request->integer('section_id') ?: null), 'merit-list.pdf');
    }
}
