<?php

namespace Modules\Results\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamSubject;
use Modules\Exams\Services\ExamDirectory;
use Modules\Results\Models\ExamResult;
use Modules\Results\Models\GradingScale;
use Modules\Results\Models\Mark;
use Modules\Results\Services\Engine\ComponentSpec;
use Modules\Results\Services\Engine\GradeScale;
use Modules\Results\Services\Engine\MarkInput;
use Modules\Results\Services\Engine\ResultCalculator;
use Modules\Results\Services\Engine\StudentOutcome;
use Modules\Results\Services\Engine\SubjectSpec;
use Modules\Students\Services\StudentDirectory;

/**
 * Turns entered marks into stored results (totals, GPA, grade, positions). Safe to run again: it rewrites.
 */
class ResultProcessor
{
    public function __construct(
        protected ExamDirectory $exams,
        protected StudentDirectory $students,
        protected ResultCalculator $calculator,
    ) {}

    /**
     * @return array{class_id: int, students: int, passed: int, failed: int, incomplete: int}
     */
    public function process(Exam $exam, int $classId): array
    {
        abort_if($exam->result_published, 422, __('results::messages.unpublish_first'));
        abort_unless($this->exams->examHasClass($exam, $classId), 404);

        $subjects = $this->exams->subjectsFor($exam, $classId);
        abort_if($subjects->isEmpty(), 422, __('results::messages.no_subjects'));

        $specs = $subjects->map(fn (ExamSubject $subject): SubjectSpec => $this->spec($subject))->all();
        $scale = $this->scale($exam);
        $bonus = (bool) setting('results_optional_bonus', false);
        $students = $this->students->activeInClass($classId, null, $exam->session_id);

        $marksByStudent = Mark::query()
            ->whereIn('student_id', $students->pluck('id'))->whereIn('exam_subject_id', $subjects->pluck('id'))
            ->get()->groupBy('student_id');

        $rows = [];
        foreach ($students as $student) {
            $marks = [];
            foreach ($marksByStudent->get($student->id, []) as $mark) {
                $marks["{$mark->exam_subject_id}:{$mark->component_key}"] = new MarkInput($mark->obtained, $mark->is_absent);
            }

            $rows[$student->id] = ['student' => $student, 'outcome' => $this->calculator->calculate($specs, $marks, $scale, $bonus)];
        }

        DB::transaction(function () use ($exam, $classId, $rows): void {
            ExamResult::query()->where(['exam_id' => $exam->id, 'class_id' => $classId])->whereNotIn('student_id', array_keys($rows))->delete();

            foreach ($rows as $studentId => ['student' => $student, 'outcome' => $outcome]) {
                ExamResult::query()->updateOrCreate(['exam_id' => $exam->id, 'student_id' => $studentId], $this->attributes($student, $classId, $outcome));
            }

            $this->assignPositions($exam, $classId);
        });

        return [
            'class_id' => $classId,
            'students' => count($rows),
            'passed' => count(array_filter($rows, fn (array $row): bool => $row['outcome']->passed)),
            'failed' => count(array_filter($rows, fn (array $row): bool => ! $row['outcome']->passed)),
            'incomplete' => count(array_filter($rows, fn (array $row): bool => $row['outcome']->incomplete)),
        ];
    }

    /**
     * Rank by passed, GPA, then total marks. Equal results share a position and the next one skips ("1, 1, 3").
     */
    public function assignPositions(Exam $exam, int $classId): void
    {
        $results = ExamResult::query()->where(['exam_id' => $exam->id, 'class_id' => $classId])->get();

        $rank = function (Collection $group, string $column): void {
            $previous = null;
            $position = 0;

            foreach ($group->sort(fn (ExamResult $a, ExamResult $b): int => [$b->is_passed, $b->gpa, $b->total_marks] <=> [$a->is_passed, $a->gpa, $a->total_marks])->values() as $index => $result) {
                $key = [$result->is_passed, $result->gpa, $result->total_marks];

                if ($key !== $previous) {
                    $position = $index + 1;
                    $previous = $key;
                }

                $result->forceFill([$column => $position])->save();
            }
        };

        $rank($results, 'position_in_class');

        foreach ($results->groupBy('section_id') as $section) {
            $rank($section, 'position_in_section');
        }
    }

    protected function spec(ExamSubject $subject): SubjectSpec
    {
        return new SubjectSpec(
            examSubjectId: $subject->id,
            name: $subject->subject->displayName(),
            fullMarks: (float) $subject->full_marks,
            passMarks: (float) $subject->pass_marks,
            includeInGpa: $subject->include_in_gpa,
            isOptional: $subject->is_optional,
            components: $subject->components->map(fn ($component): ComponentSpec => new ComponentSpec($component->id, $component->displayName(), (float) $component->full_marks, (float) $component->pass_marks))->all(),
        );
    }

    protected function scale(Exam $exam): GradeScale
    {
        $scale = ($exam->grading_scale_id ? GradingScale::query()->with('items')->find($exam->grading_scale_id) : null)
            ?? GradingScale::default()?->load('items');

        abort_unless($scale, 422, __('results::messages.no_scale'));

        return $scale->toEngine();
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes($student, int $classId, StudentOutcome $outcome): array
    {
        return [
            'class_id' => $classId,
            'section_id' => $student->section_id,
            'roll' => $student->roll,
            'total_marks' => $outcome->total,
            'full_marks' => $outcome->fullMarks,
            'percentage' => $outcome->percentage,
            'gpa' => $outcome->gpa,
            'grade' => $outcome->grade,
            'is_passed' => $outcome->passed,
            'is_incomplete' => $outcome->incomplete,
            'failed_subjects_count' => $outcome->failedSubjects,
            'subject_results' => array_map(fn ($subject): array => $subject->toArray(), $outcome->subjects),
            'position_in_class' => null,
            'position_in_section' => null,
        ];
    }
}
