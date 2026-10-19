<?php

namespace Modules\Exams\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\AdmitCardService;
use Modules\Exams\Services\ExamDirectory;

class AdmitCardController
{
    public function release(Request $request, Exam $exam): JsonResponse
    {
        Gate::authorize('manageAdmitCards', $exam);

        $exam->update(['admit_card_released' => $request->validate(['released' => ['required', 'boolean']])['released']]);

        return response()->json(['data' => ['admit_card_released' => $exam->admit_card_released]]);
    }

    /**
     * Students of one class/section with their eligibility; admins can preview it before turning the release on.
     */
    public function roster(Request $request, Exam $exam, AdmitCardService $service, ExamDirectory $directory): JsonResponse
    {
        Gate::authorize('manageAdmitCards', $exam);

        $data = $request->validate(['class_id' => ['required', 'integer'], 'section_id' => ['nullable', 'integer']]);
        abort_unless($directory->examHasClass($exam, (int) $data['class_id']), 404);

        $rows = $service->roster($exam, (int) $data['class_id'], isset($data['section_id']) ? (int) $data['section_id'] : null, ignoreRelease: true)->map(fn (array $row): array => [
            'student_id' => $row['student']->id,
            'code' => $row['student']->student_id,
            'name' => $row['student']->name_bn,
            'roll' => $row['student']->roll,
            'eligibility' => $row['eligibility']->toArray(),
        ]);

        return response()->json(['data' => $rows->values()]);
    }
}
