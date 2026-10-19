<?php

namespace Modules\Results\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamSubject;
use Modules\Exams\Services\ExamDirectory;
use Modules\Results\Models\Mark;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentDirectory;

/**
 * Reads and writes the marks grid of one exam + class. Every id in an entry is re-checked against the exam, the
 * class and its roster: nothing from the client is trusted.
 */
class MarksService
{
    public function __construct(protected ExamDirectory $exams, protected StudentDirectory $students) {}

    /**
     * Grid payload: roster, one column per subject (or per component), and the marks entered so far.
     *
     * @return array{students: list<array<string, mixed>>, columns: list<array<string, mixed>>, marks: array<string, array<string, array{obtained: float|null, absent: bool}>>, published: bool}
     */
    public function grid(Exam $exam, int $classId, ?int $sectionId = null): array
    {
        $students = $this->students->activeInClass($classId, $sectionId, $exam->session_id);
        $columns = $this->columns($exam, $classId);
        $marks = [];

        $rows = Mark::query()->whereIn('student_id', $students->pluck('id'))->whereIn('exam_subject_id', array_unique(array_column($columns, 'exam_subject_id')))->get();

        foreach ($rows as $mark) {
            $marks[$mark->student_id][$mark->exam_subject_id.':'.$mark->component_key] = ['obtained' => $mark->obtained, 'absent' => $mark->is_absent];
        }

        return [
            'students' => $students->map(fn (Student $student): array => ['id' => $student->id, 'roll' => $student->roll, 'name' => $student->name_bn, 'code' => $student->student_id])->values()->all(),
            'columns' => $columns,
            'marks' => $marks,
            'published' => (bool) $exam->result_published,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function columns(Exam $exam, int $classId): array
    {
        $columns = [];

        foreach ($this->exams->subjectsFor($exam, $classId) as $subject) {
            if ($subject->components->isEmpty()) {
                $columns[] = ['key' => "{$subject->id}:0", 'exam_subject_id' => $subject->id, 'component_id' => null, 'subject' => $subject->subject->displayName(), 'label' => $subject->subject->displayName(), 'full' => $subject->full_marks, 'pass' => $subject->pass_marks, 'optional' => $subject->is_optional];

                continue;
            }

            foreach ($subject->components as $component) {
                $columns[] = ['key' => "{$subject->id}:{$component->id}", 'exam_subject_id' => $subject->id, 'component_id' => $component->id, 'subject' => $subject->subject->displayName(), 'label' => $component->displayName(), 'full' => $component->full_marks, 'pass' => $component->pass_marks, 'optional' => $subject->is_optional];
            }
        }

        return $columns;
    }

    /**
     * Save changed cells. A cell with no value and no absent flag clears the stored mark.
     *
     * @param  list<array{student_id: int, exam_subject_id: int, component_id?: int|null, obtained?: float|int|string|null, is_absent?: bool}>  $entries
     * @param  list<int>|null  $onlySubjectIds  Limit to these exam subjects (a teacher's assigned subjects).
     * @return int Number of cells written or cleared.
     *
     * @throws ValidationException
     */
    public function save(Exam $exam, int $classId, array $entries, ?int $userId, ?array $onlySubjectIds = null): int
    {
        abort_if($exam->result_published, 422, __('results::messages.unpublish_first'));
        abort_unless($this->exams->examHasClass($exam, $classId), 404);

        $subjects = ExamSubject::query()->with('components')->where(['exam_id' => $exam->id, 'class_id' => $classId])->get()->keyBy('id');
        $roster = $this->students->activeInClass($classId, null, $exam->session_id)->pluck('id')->flip();
        $errors = [];
        $writes = [];

        foreach ($entries as $index => $entry) {
            $subject = $subjects->get((int) $entry['exam_subject_id']);

            if (! $subject || ! $roster->has((int) $entry['student_id']) || ($onlySubjectIds !== null && ! in_array($subject->id, $onlySubjectIds, true))) {
                $errors["entries.{$index}.student_id"] = __('results::messages.invalid_cell');

                continue;
            }

            $component = null;
            if ($subject->components->isNotEmpty()) {
                $component = $subject->components->firstWhere('id', $entry['component_id'] ?? null);

                if (! $component) {
                    $errors["entries.{$index}.component_id"] = __('results::messages.invalid_cell');

                    continue;
                }
            } elseif (! empty($entry['component_id'])) {
                $errors["entries.{$index}.component_id"] = __('results::messages.invalid_cell');

                continue;
            }

            $full = (float) ($component?->full_marks ?? $subject->full_marks);
            $absent = (bool) ($entry['is_absent'] ?? false);
            $obtained = $entry['obtained'] ?? null;
            $obtained = ($obtained === '' || $obtained === null || $absent) ? null : $obtained;

            if ($obtained !== null && (! is_numeric($obtained) || (float) $obtained < 0 || (float) $obtained > $full)) {
                $errors["entries.{$index}.obtained"] = __('results::messages.marks_range', ['max' => $full]);

                continue;
            }

            $writes[] = [(int) $entry['student_id'], $subject->id, $component?->id, $obtained === null ? null : round((float) $obtained, 2), $absent];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($writes, $userId): void {
            foreach ($writes as [$studentId, $subjectId, $componentId, $obtained, $absent]) {
                $key = ['student_id' => $studentId, 'exam_subject_id' => $subjectId, 'component_key' => $componentId ?? 0];

                if ($obtained === null && ! $absent) {
                    Mark::query()->where($key)->delete();

                    continue;
                }

                Mark::query()->updateOrCreate($key, ['exam_subject_component_id' => $componentId, 'obtained' => $obtained, 'is_absent' => $absent, 'entered_by' => $userId]);
            }
        });

        return count($writes);
    }
}
