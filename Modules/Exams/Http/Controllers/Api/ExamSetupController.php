<?php

namespace Modules\Exams\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Exams\Http\Requests\ExamRoutineRequest;
use Modules\Exams\Http\Requests\ExamSubjectsRequest;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\ExamConfigurator;
use Modules\Exams\Services\ExamDirectory;

class ExamSetupController
{
    public function __construct(protected ExamDirectory $directory, protected AcademicDirectory $academic) {}

    public function show(Exam $exam, SchoolClass $class): JsonResponse
    {
        Gate::authorize('view', $exam);
        $this->ensureClassInExam($exam, $class);

        return response()->json($this->payload($exam, $class));
    }

    public function updateSubjects(ExamSubjectsRequest $request, Exam $exam, SchoolClass $class, ExamConfigurator $configurator): JsonResponse
    {
        $this->ensureClassInExam($exam, $class);

        $configurator->setSubjects($exam, $class->id, $request->validated('subjects'));

        return response()->json($this->payload($exam, $class));
    }

    public function seedSubjects(Exam $exam, SchoolClass $class, ExamConfigurator $configurator): JsonResponse
    {
        Gate::authorize('update', $exam);
        $this->ensureClassInExam($exam, $class);

        $configurator->seedFromClass($exam, $class->id);

        return response()->json($this->payload($exam, $class));
    }

    public function copySubjects(Exam $exam, SchoolClass $class, Exam $source, ExamConfigurator $configurator): JsonResponse
    {
        Gate::authorize('update', $exam);
        $this->ensureClassInExam($exam, $class);
        abort_if($source->is($exam) || ! $this->directory->examHasClass($source, $class->id), 422, __('exams::messages.copy_source_invalid'));

        $configurator->copyFrom($exam, $source, $class->id);

        return response()->json($this->payload($exam, $class));
    }

    public function updateRoutine(ExamRoutineRequest $request, Exam $exam, SchoolClass $class, ExamConfigurator $configurator): JsonResponse
    {
        $this->ensureClassInExam($exam, $class);

        $configurator->setRoutine($exam, $class->id, $request->validated('routine'));

        return response()->json($this->payload($exam, $class));
    }

    protected function ensureClassInExam(Exam $exam, SchoolClass $class): void
    {
        abort_unless($this->directory->examHasClass($exam, $class->id), 404);
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Exam $exam, SchoolClass $class): array
    {
        return [
            'subjects' => $this->directory->subjectsFor($exam, $class->id)->map(fn ($subject): array => [
                'subject_id' => $subject->subject_id,
                'name' => $subject->subject->displayName(),
                'full_marks' => $subject->full_marks,
                'pass_marks' => $subject->pass_marks,
                'include_in_gpa' => $subject->include_in_gpa,
                'is_optional' => $subject->is_optional,
                'components' => $subject->components->map(fn ($component): array => $component->only(['id', 'name_bn', 'name_en', 'full_marks', 'pass_marks']))->values(),
            ])->values(),
            'routine' => $this->directory->routineFor($exam, $class->id)->map(fn ($row): array => [
                'subject_id' => $row->subject_id,
                'subject' => $row->subject->displayName(),
                'exam_date' => $row->exam_date->toDateString(),
                'start_time' => substr($row->start_time, 0, 5),
                'end_time' => substr($row->end_time, 0, 5),
                'room' => $row->room,
            ])->values(),
            'class_subjects' => $this->academic->subjectsOf($class->id)->map(fn ($subject): array => ['id' => $subject->id, 'name' => $subject->displayName()])->values(),
            'other_exams' => $this->directory->forClass($class->id)->reject(fn (Exam $other): bool => $other->is($exam))->map(fn (Exam $other): array => ['id' => $other->id, 'name' => $other->displayName()])->values(),
        ];
    }
}
