<?php

namespace Modules\Exams\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\AdmitCardPdf;
use Modules\Exams\Services\AdmitCardService;
use Modules\Exams\Services\ExamDirectory;
use Modules\Students\Models\Student;

class AdmitCardPdfController
{
    use ServesPdf;

    /**
     * Admit cards for a class or section, or for chosen students. By default only students every eligibility rule
     * allows (e.g. fees paid); an admin can print for everyone by turning only_eligible off.
     */
    public function __invoke(Request $request, Exam $exam, AdmitCardService $service, ExamDirectory $directory, AdmitCardPdf $pdf): Response
    {
        Gate::authorize('manageAdmitCards', $exam);

        $data = $request->validate([
            'class_id' => ['required', 'integer'], 'section_id' => ['nullable', 'integer'],
            'student_ids' => ['nullable', 'array', 'max:200'], 'student_ids.*' => ['integer'],
            'only_eligible' => ['nullable', 'boolean'],
        ]);

        abort_unless($directory->examHasClass($exam, (int) $data['class_id']), 404);

        $onlyEligible = $request->boolean('only_eligible', true);
        $students = $service->roster($exam, (int) $data['class_id'], isset($data['section_id']) ? (int) $data['section_id'] : null, checkProviders: $onlyEligible, ignoreRelease: true)
            ->filter(fn (array $row): bool => $row['eligibility']->allowed && (empty($data['student_ids']) || in_array($row['student']->id, $data['student_ids'], true)))
            ->map(fn (array $row): Student => $row['student'])
            ->take(200)->values();

        abort_if($students->isEmpty(), 404, __('exams::messages.admit.nobody_eligible'));

        return $this->pdf($pdf->render($exam, $students), 'admit-cards.pdf');
    }
}
