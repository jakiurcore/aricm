<?php

namespace Modules\Results\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\PdfService;
use Modules\Core\Services\QrCodeService;
use Modules\Core\Support\Bangla;
use Modules\Core\Support\Branding;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamSubject;
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

        $passMarks = ExamSubject::query()->whereIn('exam_id', $results->pluck('exam_id')->unique())->pluck('pass_marks', 'id');
        $scales = [];

        $sheets = $results->map(function (ExamResult $result) use ($passMarks, &$scales): array {
            $scales[$result->exam_id] ??= $this->scaleFor($result->exam);

            return [
                'result' => $result,
                'student' => $result->student,
                'photo' => $result->student->photo && Storage::disk('public')->exists($result->student->photo) ? Storage::disk('public')->path($result->student->photo) : null,
                'qr' => $this->qr->forVerification('marksheet', MarksheetHash::for($result), 140),
                'scale' => $scales[$result->exam_id],
                'position' => $result->position_in_class ? $this->ordinal((int) $result->position_in_class) : '—',
                'subjects' => collect($result->subject_results)->map(fn (array $subject): array => [
                    ...$subject,
                    'passMarks' => isset($subject['examSubjectId'], $passMarks[$subject['examSubjectId']]) ? $passMarks[$subject['examSubjectId']] : null,
                    'parts' => count($subject['components']) > 1
                        ? collect($subject['components'])->map(fn (array $component): string => $component['name'].' '.($component['absent'] ? __('results::pdf.absent') : bn_number($component['obtained'] ?? 0)).'/'.bn_number($component['full']))->implode(', ')
                        : '',
                ])->all(),
            ];
        });

        $html = view('results::pdf.marksheets', ['sheets' => $sheets, 'brand' => Branding::forPdf(), 'issued' => bn_date(now()), 'placeholder' => Branding::photoPlaceholder()])->render();

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
            'point' => bn_number($item->grade_point, 1),
        ])->all() : [];
    }

    /**
     * "৩য়" / "3rd": the merit position as the sheet prints it.
     */
    protected function ordinal(int $position): string
    {
        if (! Bangla::isBangla()) {
            $suffix = in_array($position % 100, [11, 12, 13], true) ? 'th' : match ($position % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };

            return $position.$suffix;
        }

        $suffix = match (true) {
            $position === 1 => 'ম',
            in_array($position, [2, 3], true) => 'য়',
            $position === 4 => 'র্থ',
            $position <= 10 => 'ম',
            default => 'তম',
        };

        return bn_digits($position).$suffix;
    }
}
