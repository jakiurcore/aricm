<?php

namespace Modules\Results\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\PdfService;
use Modules\Core\Services\QrCodeService;
use Modules\Core\Support\Branding;
use Modules\Exams\Models\Exam;
use Modules\Results\Models\ExamResult;
use Modules\Results\Models\GradingScale;
use Modules\Results\Support\MarksheetHash;

/**
 * The printed result documents: marksheets (A4, one per student, with a QR to the verification page), the
 * tabulation sheet (landscape) and the merit list.
 */
class ResultPdf
{
    public function __construct(protected PdfService $pdf, protected QrCodeService $qr, protected ResultDirectory $results) {}

    /**
     * @param  Collection<int, ExamResult>  $results
     */
    public function marksheets(Collection $results): string
    {
        $results->each->loadMissing(['exam.session', 'student.schoolClass', 'student.section']);

        $sheets = $results->map(fn (ExamResult $result): array => [
            'result' => $result,
            'student' => $result->student,
            'photo' => $result->student->photo && Storage::disk('public')->exists($result->student->photo) ? Storage::disk('public')->path($result->student->photo) : null,
            'qr' => $this->qr->forVerification('marksheet', MarksheetHash::for($result), 140),
            'scale' => $this->scaleFor($result->exam),
        ]);

        $html = view('results::pdf.marksheets', ['sheets' => $sheets, 'brand' => Branding::forPdf(), 'issued' => bn_date(now())])->render();

        return $this->pdf->render($html, ['showImageErrors' => false]);
    }

    public function tabulation(Exam $exam, int $classId, ?int $sectionId = null): string
    {
        $results = $this->results->classResults($exam, $classId, $sectionId);
        $class = $exam->classes()->whereKey($classId)->firstOrFail();
        $subjects = collect($results->first()?->subject_results ?? [])->pluck('name')->all();

        $html = view('results::pdf.tabulation', [
            'exam' => $exam, 'class' => $class, 'results' => $results, 'subjects' => $subjects, 'brand' => Branding::forPdf(),
            'section' => $sectionId ? $results->first()?->section : null,
        ])->render();

        return $this->pdf->render($html, ['orientation' => 'L', 'showImageErrors' => false]);
    }

    public function meritList(Exam $exam, int $classId, ?int $sectionId = null): string
    {
        $results = $this->results->classResults($exam, $classId, $sectionId)->where('is_passed', true)->values();
        $class = $exam->classes()->whereKey($classId)->firstOrFail();

        $html = view('results::pdf.merit', [
            'exam' => $exam, 'class' => $class, 'results' => $results, 'brand' => Branding::forPdf(),
            'section' => $sectionId ? $results->first()?->section : null,
        ])->render();

        return $this->pdf->render($html, ['showImageErrors' => false]);
    }

    /**
     * @return list<array{grade: string, range: string, point: string}>
     */
    protected function scaleFor(Exam $exam): array
    {
        $scale = ($exam->grading_scale_id ? GradingScale::query()->with('items')->find($exam->grading_scale_id) : null) ?? GradingScale::default()?->load('items');

        return $scale ? $scale->items->map(fn ($item): array => [
            'grade' => $item->grade,
            'range' => bn_number($item->min_percent).'–'.bn_number($item->max_percent),
            'point' => bn_number($item->grade_point, 2),
        ])->all() : [];
    }
}
