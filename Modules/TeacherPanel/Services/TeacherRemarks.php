<?php

namespace Modules\TeacherPanel\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Models\DiaryStudentRemark;
use Modules\Diary\Services\DiaryService;
use Modules\Students\Models\Student;
use Modules\Teachers\Services\TeacherScope;

/**
 * Today's private remarks about students. A remark hangs on one of the teacher's diary entries, so the entry of the
 * section for today is found (or opened as an empty draft) first.
 */
class TeacherRemarks
{
    public function __construct(protected TeacherScope $scope, protected DiaryService $diary) {}

    /**
     * @param  list<int>  $studentIds
     * @return Collection<int, DiaryStudentRemark>
     */
    public function add(Authenticatable $user, int $classId, int $sectionId, array $studentIds, RemarkKind $kind, string $text): Collection
    {
        $entry = $this->entryFor($user, $classId, $sectionId);

        return Student::query()->whereIn('id', $studentIds)->get()
            ->map(fn (Student $student): DiaryStudentRemark => $this->diary->addRemark($user, $entry, $student, $kind, $text));
    }

    /**
     * The teacher's entry for the section today: an existing one (any subject), else an empty draft for the first
     * subject taught there (or the general note when the teacher is only the class teacher).
     */
    public function entryFor(Authenticatable $user, int $classId, int $sectionId): DiaryEntry
    {
        $teacher = $this->scope->teacherFor($user);
        abort_unless($teacher && $this->scope->canSee($user, $classId, $sectionId), 403);

        $existing = DiaryEntry::query()->where(['teacher_id' => $teacher->id, 'class_id' => $classId, 'section_id' => $sectionId])
            ->whereDate('entry_date', now()->toDateString())->orderByRaw('subject_id is null')->orderBy('subject_id')->first();

        if ($existing) {
            return $existing;
        }

        $subjectId = $this->scope->subjectIds($user, $classId, $sectionId)[0] ?? null;

        return $this->diary->save($user, ['class_id' => $classId, 'section_id' => $sectionId, 'subject_id' => $subjectId, 'entry_date' => now()->toDateString()]);
    }

    /**
     * The teacher's remarks written today, grouped by student.
     *
     * @return Collection<int, Collection<int, DiaryStudentRemark>>
     */
    public function today(Authenticatable $user, int $sectionId): Collection
    {
        $teacher = $this->scope->teacherFor($user);

        return DiaryStudentRemark::query()->where('teacher_id', $teacher?->id)
            ->whereHas('entry', fn ($query) => $query->where('section_id', $sectionId)->whereDate('entry_date', now()->toDateString()))
            ->orderBy('id')->get()->groupBy('student_id');
    }

    /**
     * Each student's most recent remarks from this teacher (up to three), newest first.
     *
     * @return Collection<int, Collection<int, DiaryStudentRemark>>
     */
    public function recent(Authenticatable $user, int $sectionId): Collection
    {
        $teacher = $this->scope->teacherFor($user);

        return DiaryStudentRemark::query()->with('entry')->where('teacher_id', $teacher?->id)
            ->whereHas('entry', fn ($query) => $query->where('section_id', $sectionId))
            ->orderByDesc('id')->limit(300)->get()->groupBy('student_id')->map(fn (Collection $remarks): Collection => $remarks->take(3)->values());
    }
}
