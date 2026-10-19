<?php

namespace Modules\Teachers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Models\Section;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Models\TeacherAssignment;

/**
 * Writes assignments with the rules the database cannot express: the section belongs to the class, the subject is
 * taught in the class, no duplicates, and exactly one class teacher per section per session.
 */
class TeacherAssignments
{
    public function __construct(protected TeacherScope $scope) {}

    /**
     * Give a teacher a subject in a section.
     */
    public function assignSubject(Teacher $teacher, int $sessionId, int $classId, int $sectionId, int $subjectId): TeacherAssignment
    {
        $this->checkPlacement($classId, $sectionId);

        if (! DB::table('class_subject')->where('class_id', $classId)->where('subject_id', $subjectId)->exists()) {
            throw ValidationException::withMessages(['subject_id' => __('teachers::messages.subject_not_in_class')]);
        }

        $assignment = TeacherAssignment::query()->firstOrCreate([
            'teacher_id' => $teacher->id, 'session_id' => $sessionId, 'class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => $subjectId,
        ], ['is_class_teacher' => false]);

        $this->scope->refresh();

        return $assignment;
    }

    /**
     * Make the teacher the class teacher of the section, replacing the previous one.
     */
    public function setClassTeacher(Teacher $teacher, int $sessionId, int $classId, int $sectionId): TeacherAssignment
    {
        $this->checkPlacement($classId, $sectionId);

        TeacherAssignment::query()->where('session_id', $sessionId)->where('section_id', $sectionId)->where('is_class_teacher', true)->delete();

        $assignment = TeacherAssignment::create([
            'teacher_id' => $teacher->id, 'session_id' => $sessionId, 'class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => null, 'is_class_teacher' => true,
        ]);

        $this->scope->refresh();

        return $assignment;
    }

    /**
     * The grid's "one teacher per cell": make this teacher the only one for the subject in the section, or none at all.
     */
    public function replaceSubjectTeacher(?Teacher $teacher, int $sessionId, int $classId, int $sectionId, int $subjectId): ?TeacherAssignment
    {
        $this->checkPlacement($classId, $sectionId);

        TeacherAssignment::query()->where(['session_id' => $sessionId, 'section_id' => $sectionId, 'subject_id' => $subjectId, 'is_class_teacher' => false])
            ->when($teacher, fn ($query) => $query->where('teacher_id', '!=', $teacher->id))->delete();

        $assignment = $teacher ? $this->assignSubject($teacher, $sessionId, $classId, $sectionId, $subjectId) : null;
        $this->scope->refresh();

        return $assignment;
    }

    /**
     * Empty the class-teacher slot of a section.
     */
    public function clearClassTeacher(int $sessionId, int $sectionId): void
    {
        TeacherAssignment::query()->where(['session_id' => $sessionId, 'section_id' => $sectionId, 'is_class_teacher' => true])->delete();
        $this->scope->refresh();
    }

    public function remove(TeacherAssignment $assignment): void
    {
        $assignment->delete();
        $this->scope->refresh();
    }

    /**
     * Carry last session's assignments into a new session (sections and subjects are not per-session, so the rows
     * are copied as they are). Existing rows and inactive teachers are skipped.
     *
     * @return int Rows created.
     */
    public function copy(int $fromSessionId, int $toSessionId): int
    {
        $created = 0;

        foreach (TeacherAssignment::query()->with('teacher')->where('session_id', $fromSessionId)->get() as $assignment) {
            if (! $assignment->teacher->is_active) {
                continue;
            }

            $exists = TeacherAssignment::query()->where('session_id', $toSessionId)->where('section_id', $assignment->section_id)
                ->where('subject_id', $assignment->subject_id)->where('is_class_teacher', $assignment->is_class_teacher)
                ->when(! $assignment->is_class_teacher, fn ($query) => $query->where('teacher_id', $assignment->teacher_id))->exists();

            if ($exists) {
                continue;
            }

            TeacherAssignment::create([...$assignment->only(['teacher_id', 'class_id', 'section_id', 'subject_id', 'is_class_teacher']), 'session_id' => $toSessionId]);
            $created++;
        }

        $this->scope->refresh();

        return $created;
    }

    protected function checkPlacement(int $classId, int $sectionId): void
    {
        if (! Section::query()->whereKey($sectionId)->where('class_id', $classId)->exists()) {
            throw ValidationException::withMessages(['section_id' => __('teachers::messages.section_not_in_class')]);
        }
    }
}
