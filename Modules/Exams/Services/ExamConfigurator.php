<?php

namespace Modules\Exams\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamRoutine;
use Modules\Exams\Models\ExamSubject;
use Modules\Exams\Models\ExamSubjectComponent;

/**
 * Writes an exam's classes, per-class subject setup (marks and components) and routine.
 */
class ExamConfigurator
{
    public function __construct(protected AcademicDirectory $academic) {}

    /**
     * @param  list<int>  $classIds
     */
    public function syncClasses(Exam $exam, array $classIds): void
    {
        $removed = $exam->classes()->pluck('classes.id')->diff($classIds);

        $this->guard(function () use ($exam, $classIds, $removed): void {
            DB::transaction(function () use ($exam, $classIds, $removed): void {
                ExamSubject::query()->where('exam_id', $exam->id)->whereIn('class_id', $removed)->delete();
                ExamRoutine::query()->where('exam_id', $exam->id)->whereIn('class_id', $removed)->delete();
                $exam->classes()->sync($classIds);
            });
        });
    }

    /**
     * Replace the subject setup of a class. Existing rows are updated in place (so marks keep pointing at them);
     * rows that are no longer listed are removed, which fails while marks exist for them.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function setSubjects(Exam $exam, int $classId, array $rows): void
    {
        $this->guard(function () use ($exam, $classId, $rows): void {
            DB::transaction(function () use ($exam, $classId, $rows): void {
                $keep = [];

                foreach (array_values($rows) as $index => $row) {
                    $components = $row['components'] ?? [];

                    $subject = ExamSubject::query()->updateOrCreate(
                        ['exam_id' => $exam->id, 'class_id' => $classId, 'subject_id' => $row['subject_id']],
                        [
                            // With components the subject total is always their sum.
                            'full_marks' => $components !== [] ? array_sum(array_column($components, 'full_marks')) : $row['full_marks'],
                            'pass_marks' => $row['pass_marks'],
                            'include_in_gpa' => (bool) ($row['include_in_gpa'] ?? true),
                            'is_optional' => (bool) ($row['is_optional'] ?? false),
                            'sort_order' => $index + 1,
                        ],
                    );

                    $this->syncComponents($subject, $components);
                    $keep[] = $subject->id;
                }

                ExamSubject::query()->where(['exam_id' => $exam->id, 'class_id' => $classId])->whereNotIn('id', $keep)->delete();
            });
        });
    }

    /**
     * @param  list<array<string, mixed>>  $components
     */
    protected function syncComponents(ExamSubject $subject, array $components): void
    {
        $keep = [];

        foreach (array_values($components) as $index => $component) {
            $attributes = [
                'name_bn' => $component['name_bn'],
                'name_en' => $component['name_en'] ?? null,
                'full_marks' => $component['full_marks'],
                'pass_marks' => $component['pass_marks'] ?? 0,
                'sort_order' => $index + 1,
            ];

            $model = isset($component['id']) ? $subject->components()->find($component['id']) : null;
            $model ? $model->update($attributes) : $model = $subject->components()->create($attributes);
            $keep[] = $model->id;
        }

        ExamSubjectComponent::query()->where('exam_subject_id', $subject->id)->whereNotIn('id', $keep)->delete();
    }

    /**
     * Add the subjects mapped to the class in Academic (default 100/33) that the exam does not have yet.
     */
    public function seedFromClass(Exam $exam, int $classId): int
    {
        $existing = ExamSubject::query()->where(['exam_id' => $exam->id, 'class_id' => $classId])->pluck('subject_id');
        $position = ExamSubject::query()->where(['exam_id' => $exam->id, 'class_id' => $classId])->max('sort_order');
        $added = 0;

        foreach ($this->academic->subjectsOf($classId) as $subject) {
            if ($existing->contains($subject->id)) {
                continue;
            }

            ExamSubject::query()->create([
                'exam_id' => $exam->id, 'class_id' => $classId, 'subject_id' => $subject->id,
                'full_marks' => 100, 'pass_marks' => 33, 'include_in_gpa' => ! $subject->pivot->is_optional,
                'is_optional' => (bool) $subject->pivot->is_optional, 'sort_order' => ++$position,
            ]);
            $added++;
        }

        return $added;
    }

    /**
     * Copy the subject setup (marks and components) of a class from another exam, replacing what is there.
     */
    public function copyFrom(Exam $target, Exam $source, int $classId): int
    {
        $rows = ExamSubject::query()->with('components')->where(['exam_id' => $source->id, 'class_id' => $classId])->orderBy('sort_order')->get()
            ->map(fn (ExamSubject $subject): array => [
                'subject_id' => $subject->subject_id, 'full_marks' => $subject->full_marks, 'pass_marks' => $subject->pass_marks,
                'include_in_gpa' => $subject->include_in_gpa, 'is_optional' => $subject->is_optional,
                'components' => $subject->components->map(fn ($component): array => $component->only(['name_bn', 'name_en', 'full_marks', 'pass_marks']))->all(),
            ])->all();

        $this->setSubjects($target, $classId, $rows);

        return count($rows);
    }

    /**
     * Replace the routine of a class.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function setRoutine(Exam $exam, int $classId, array $rows): void
    {
        DB::transaction(function () use ($exam, $classId, $rows): void {
            ExamRoutine::query()->where(['exam_id' => $exam->id, 'class_id' => $classId])->delete();

            foreach ($rows as $row) {
                ExamRoutine::query()->create([
                    'exam_id' => $exam->id, 'class_id' => $classId, 'subject_id' => $row['subject_id'],
                    'exam_date' => $row['exam_date'], 'start_time' => $row['start_time'], 'end_time' => $row['end_time'], 'room' => $row['room'] ?? null,
                ]);
            }
        });
    }

    protected function guard(callable $work): void
    {
        try {
            $work();
        } catch (QueryException) {
            abort(422, __('exams::messages.in_use'));
        }
    }
}
