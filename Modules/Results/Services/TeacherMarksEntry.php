<?php

namespace Modules\Results\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Core\Services\Settings;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamSubject;
use Modules\Exams\Services\ExamDirectory;
use Modules\Students\Services\StudentDirectory;
use Modules\Teachers\Services\TeacherScope;

/**
 * Lets a teacher enter marks for their own subjects, in their own sections, while the setting
 * "teacher_marks_entry_enabled" is on. Everything is narrowed through TeacherScope::canTeach: the grid shows only the
 * teacher's subject columns and a save touching any other subject, section or student is refused.
 */
class TeacherMarksEntry
{
    public function __construct(
        protected Settings $settings,
        protected TeacherScope $scope,
        protected MarksService $marks,
        protected ExamDirectory $exams,
        protected StudentDirectory $students,
        protected AcademicDirectory $academic,
    ) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('teacher_marks_entry_enabled', false);
    }

    /**
     * What the teacher can enter right now: exams of the current session whose result is not published, crossed with
     * the sections and subjects they teach that the exam covers.
     *
     * @return list<array{exam_id: int, exam: string, class_id: int, class: string, section_id: int, section: string, subjects: list<array{exam_subject_id: int, name: string, full_marks: int}>}>
     */
    public function options(?Authenticatable $user): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $options = [];
        $sessionId = $this->academic->currentSession()?->id;

        foreach ($this->scope->sections($user) as $section) {
            foreach ($this->exams->forClass($section['class_id'], $sessionId)->where('result_published', false) as $exam) {
                $subjects = $this->subjects($user, $exam, $section['class_id'], $section['section_id']);

                if ($subjects === []) {
                    continue;
                }

                $options[] = [
                    'exam_id' => $exam->id, 'exam' => $exam->displayName(), 'class_id' => $section['class_id'], 'class' => $section['class_name'], 'section_id' => $section['section_id'], 'section' => $section['section_name'],
                    'subjects' => array_map(fn (ExamSubject $subject): array => ['exam_subject_id' => $subject->id, 'name' => $subject->subject->displayName(), 'full_marks' => $subject->full_marks], $subjects),
                ];
            }
        }

        return $options;
    }

    /**
     * The section's roster with only the columns of the teacher's subjects.
     *
     * @return array<string, mixed>
     */
    public function grid(?Authenticatable $user, Exam $exam, int $classId, int $sectionId): array
    {
        $allowed = $this->allowedIds($user, $exam, $classId, $sectionId);
        $grid = $this->marks->grid($exam, $classId, $sectionId);

        $grid['columns'] = array_values(array_filter($grid['columns'], fn (array $column): bool => in_array($column['exam_subject_id'], $allowed, true)));
        $keys = array_flip(array_column($grid['columns'], 'key'));
        $grid['marks'] = array_map(fn (array $row): array => array_intersect_key($row, $keys), $grid['marks']);

        return $grid;
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @return int Cells written.
     */
    public function save(?Authenticatable $user, Exam $exam, int $classId, int $sectionId, array $entries): int
    {
        $allowed = $this->allowedIds($user, $exam, $classId, $sectionId);
        $roster = $this->students->activeInClass($classId, $sectionId, $exam->session_id)->pluck('id')->flip();
        $errors = [];

        foreach ($entries as $index => $entry) {
            if (! in_array((int) ($entry['exam_subject_id'] ?? 0), $allowed, true) || ! $roster->has((int) ($entry['student_id'] ?? 0))) {
                $errors["entries.{$index}.student_id"] = __('results::messages.invalid_cell');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $this->marks->save($exam, $classId, $entries, $user->getAuthIdentifier(), $allowed);
    }

    /**
     * @return list<int> Exam subject ids the user may enter marks for in this section.
     */
    protected function allowedIds(?Authenticatable $user, Exam $exam, int $classId, int $sectionId): array
    {
        abort_unless($this->enabled(), 403, __('results::messages.teacher_entry_off'));
        abort_unless($this->exams->examHasClass($exam, $classId), 404);

        $ids = array_map(fn (ExamSubject $subject): int => $subject->id, $this->subjects($user, $exam, $classId, $sectionId));
        abort_if($ids === [], 403);

        return $ids;
    }

    /**
     * @return list<ExamSubject>
     */
    protected function subjects(?Authenticatable $user, Exam $exam, int $classId, int $sectionId): array
    {
        return $this->exams->subjectsFor($exam, $classId)
            ->filter(fn (ExamSubject $subject): bool => $this->scope->canTeach($user, $classId, $sectionId, $subject->subject_id))
            ->values()->all();
    }
}
