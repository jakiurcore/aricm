<?php

namespace Modules\Exams\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Services\PdfService;
use Modules\Core\Services\QrCodeService;
use Modules\Core\Support\Bangla;
use Modules\Core\Support\Branding;
use Modules\Core\Support\SignedHash;
use Modules\Exams\Models\Exam;
use Modules\Students\Models\Student;

/**
 * Admit cards, two per A4 sheet (each an A5 landscape card) with that class's routine, the institute's rules and
 * signature lines.
 */
class AdmitCardPdf
{
    public function __construct(protected PdfService $pdf, protected QrCodeService $qr, protected AdmitCardService $cards) {}

    /**
     * @param  Collection<int, Student>  $students
     */
    public function render(Exam $exam, Collection $students): string
    {
        $locale = app()->getLocale();
        $rules = preg_split('/\R/u', (string) (setting("admit_card_rules_{$locale}") ?: setting('admit_card_rules_bn') ?: __('exams::pdf.default_rules')), -1, PREG_SPLIT_NO_EMPTY);

        $cards = $students->map(function (Student $student) use ($exam): array {
            $payload = $this->cards->payload($exam, $student);

            return [
                'student' => $payload['student'],
                'photo' => $student->photo && Storage::disk('public')->exists($student->photo) ? Storage::disk('public')->path($student->photo) : null,
                'qr' => $this->qr->forVerification('student-id', SignedHash::make('student-id', $student->id), 120),
                'routine' => $payload['routine']->map(fn ($row): array => [
                    'date' => bn_date($row->exam_date),
                    'day' => Bangla::weekday($row->exam_date),
                    'subject' => $row->subject->displayName(),
                    'time' => bn_digits(substr($row->start_time, 0, 5)).' – '.bn_digits(substr($row->end_time, 0, 5)),
                    'room' => $row->room,
                ])->all(),
            ];
        });

        $html = view('exams::pdf.admit-cards', ['exam' => $exam, 'pages' => $cards->chunk(2), 'brand' => Branding::forPdf(), 'rules' => $rules])->render();

        return $this->pdf->render($html, ['showImageErrors' => false]);
    }
}
