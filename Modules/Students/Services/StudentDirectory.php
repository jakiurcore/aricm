<?php

namespace Modules\Students\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentEnrollment;

/**
 * Read API for downstream modules (Exams, Fees, Results, portals): ask here instead of querying student tables.
 */
class StudentDirectory
{
    public function find(int $id): ?Student
    {
        return Student::query()->find($id);
    }

    public function findByStudentId(string $studentId): ?Student
    {
        return Student::query()->where('student_id', $studentId)->first();
    }

    /**
     * Filtered query used by lists and exports. Recognised keys: session_id, class_id, section_id, status, search.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Student>
     */
    public function query(array $filters = []): Builder
    {
        return Student::query()
            ->when($filters['session_id'] ?? null, fn (Builder $query, $value) => $query->where('session_id', $value))
            ->when($filters['class_id'] ?? null, fn (Builder $query, $value) => $query->where('class_id', $value))
            ->when($filters['section_id'] ?? null, fn (Builder $query, $value) => $query->where('section_id', $value))
            ->when($filters['status'] ?? null, fn (Builder $query, $value) => $query->where('status', $value))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $inner) => $inner
                    ->where('student_id', 'like', "%{$search}%")
                    ->orWhere('name_bn', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('guardian_phone', 'like', "%{$search}%")
                    ->when(ctype_digit($search), fn (Builder $roll) => $roll->orWhere('roll', (int) $search)));
            });
    }

    /**
     * Active students of a class (and optionally one section) in roll order.
     *
     * @return Collection<int, Student>
     */
    public function activeInClass(int $classId, ?int $sectionId = null, ?int $sessionId = null): Collection
    {
        return $this->query(['class_id' => $classId, 'section_id' => $sectionId, 'session_id' => $sessionId, 'status' => StudentStatus::Active->value])
            ->orderByRaw('roll is null')->orderBy('roll')->orderBy('name_bn')
            ->get();
    }

    public function enrollmentFor(Student $student, ?int $sessionId = null): ?StudentEnrollment
    {
        return StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('session_id', $sessionId ?? $student->session_id)
            ->first();
    }

    public function activeCount(): int
    {
        return Student::query()->where('status', StudentStatus::Active->value)->count();
    }
}
