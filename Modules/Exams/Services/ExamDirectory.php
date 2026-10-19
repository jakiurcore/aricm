<?php

namespace Modules\Exams\Services;

use Illuminate\Support\Collection;
use Modules\Exams\Models\Exam;
use Modules\Exams\Models\ExamRoutine;
use Modules\Exams\Models\ExamSubject;

/**
 * Read API for Results, Fees and the portals: ask here instead of querying exam tables.
 */
class ExamDirectory
{
    public function find(int $id): ?Exam
    {
        return Exam::query()->find($id);
    }

    /**
     * @return Collection<int, Exam>
     */
    public function forClass(int $classId, ?int $sessionId = null): Collection
    {
        return Exam::query()
            ->whereHas('classes', fn ($query) => $query->whereKey($classId))
            ->when($sessionId, fn ($query, int $id) => $query->where('session_id', $id))
            ->orderByDesc('start_date')->orderByDesc('id')
            ->get();
    }

    public function examHasClass(Exam $exam, int $classId): bool
    {
        return $exam->classes()->whereKey($classId)->exists();
    }

    /**
     * Subjects of an exam for one class, with their components, in display order.
     *
     * @return Collection<int, ExamSubject>
     */
    public function subjectsFor(Exam $exam, int $classId): Collection
    {
        return ExamSubject::query()
            ->with(['subject', 'components'])
            ->where(['exam_id' => $exam->id, 'class_id' => $classId])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return Collection<int, ExamRoutine>
     */
    public function routineFor(Exam $exam, int $classId): Collection
    {
        return ExamRoutine::query()
            ->with('subject')
            ->where(['exam_id' => $exam->id, 'class_id' => $classId])
            ->orderBy('exam_date')->orderBy('start_time')
            ->get();
    }
}
