<?php

namespace Modules\Teachers\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Teachers\Models\Teacher;
use Modules\Teachers\Models\TeacherAssignment;

/**
 * What a signed-in teacher may touch. Everything is limited to the current session. A user with no teacher record
 * (an admin, an accountant) has an empty scope: admin screens use their own permissions, not this.
 */
class TeacherScope
{
    /** @var array<int, Teacher|null> */
    private array $teachers = [];

    /** @var array<int, Collection<int, TeacherAssignment>> */
    private array $assignments = [];

    public function __construct(protected AcademicDirectory $academic) {}

    public function teacherFor(?Authenticatable $user): ?Teacher
    {
        if ($user === null) {
            return null;
        }

        return $this->teachers[$user->getAuthIdentifier()] ??= Teacher::query()->where('user_id', $user->getAuthIdentifier())->where('is_active', true)->first();
    }

    /**
     * @return Collection<int, TeacherAssignment>
     */
    public function assignments(?Authenticatable $user): Collection
    {
        $teacher = $this->teacherFor($user);

        if (! $teacher) {
            return collect();
        }

        return $this->assignments[$teacher->id] ??= TeacherAssignment::query()
            ->with(['schoolClass', 'section', 'subject'])
            ->where('teacher_id', $teacher->id)->where('session_id', $this->academic->currentSession()?->id)
            ->get();
    }

    /**
     * May the user teach this subject in this section? A null subject asks "any subject at all in this section".
     */
    public function canTeach(?Authenticatable $user, int $classId, ?int $sectionId, ?int $subjectId = null): bool
    {
        return $this->assignments($user)->contains(fn (TeacherAssignment $assignment): bool => $assignment->class_id === $classId
            && ($sectionId === null || $assignment->section_id === $sectionId)
            && ($subjectId === null ? true : $assignment->subject_id === $subjectId)
            && ($subjectId !== null || $assignment->subject_id !== null));
    }

    public function isClassTeacher(?Authenticatable $user, int $classId, ?int $sectionId = null): bool
    {
        return $this->assignments($user)->contains(fn (TeacherAssignment $assignment): bool => $assignment->is_class_teacher
            && $assignment->class_id === $classId && ($sectionId === null || $assignment->section_id === $sectionId));
    }

    /**
     * Whether the user may see this section's students at all: teaches in it or is its class teacher.
     */
    public function canSee(?Authenticatable $user, int $classId, ?int $sectionId): bool
    {
        return $this->assignments($user)->contains(fn (TeacherAssignment $assignment): bool => $assignment->class_id === $classId
            && ($sectionId === null || $assignment->section_id === $sectionId));
    }

    /**
     * The sections the user is attached to, each with the subjects taught there and the class-teacher flag.
     *
     * @return list<array{class_id: int, section_id: int, class_name: string, section_name: string, is_class_teacher: bool, subjects: list<array{id: int, name: string}>}>
     */
    public function sections(?Authenticatable $user): array
    {
        $rows = [];

        foreach ($this->assignments($user)->sortBy(fn (TeacherAssignment $a): string => sprintf('%05d-%05d', $a->schoolClass->numeric_order, $a->section_id)) as $assignment) {
            $row = &$rows[$assignment->section_id];
            $row ??= [
                'class_id' => $assignment->class_id, 'section_id' => $assignment->section_id, 'class_name' => $assignment->schoolClass->displayName(),
                'section_name' => $assignment->section->displayName(), 'is_class_teacher' => false, 'subjects' => [],
            ];

            if ($assignment->is_class_teacher) {
                $row['is_class_teacher'] = true;
            }

            if ($assignment->subject) {
                $row['subjects'][] = ['id' => $assignment->subject->id, 'name' => $assignment->subject->displayName()];
            }
            unset($row);
        }

        return array_values($rows);
    }

    /**
     * @return list<int>
     */
    public function subjectIds(?Authenticatable $user, int $classId, ?int $sectionId = null): array
    {
        return $this->assignments($user)
            ->filter(fn (TeacherAssignment $a): bool => $a->class_id === $classId && $a->subject_id !== null && ($sectionId === null || $a->section_id === $sectionId))
            ->pluck('subject_id')->unique()->values()->all();
    }

    /**
     * Forget cached lookups (after assignments changed within the same request).
     */
    public function refresh(): void
    {
        $this->teachers = [];
        $this->assignments = [];
    }
}
