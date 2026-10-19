<?php

namespace Modules\Academic\Services;

use Illuminate\Support\Collection;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;

/**
 * Read API for the rest of the application: downstream modules ask here instead of querying academic tables.
 */
class AcademicDirectory
{
    public function currentSession(): ?AcademicSession
    {
        return AcademicSession::query()->where('is_current', true)->first()
            ?? AcademicSession::query()->latest('id')->first();
    }

    /**
     * @return Collection<int, AcademicSession>
     */
    public function sessions(): Collection
    {
        return AcademicSession::query()->orderByDesc('name')->get();
    }

    /**
     * @return Collection<int, SchoolClass>
     */
    public function classes(bool $onlyActive = true): Collection
    {
        return SchoolClass::query()
            ->with('sections')
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->orderBy('numeric_order')
            ->get();
    }

    public function findClass(int $classId): ?SchoolClass
    {
        return SchoolClass::query()->find($classId);
    }

    public function sectionBelongsToClass(?int $sectionId, int $classId): bool
    {
        return $sectionId === null
            || Section::query()->whereKey($sectionId)->where('class_id', $classId)->exists();
    }

    /**
     * Subjects taught in a class in display order, with is_optional on the pivot.
     *
     * @return Collection<int, Subject>
     */
    public function subjectsOf(int $classId): Collection
    {
        return SchoolClass::query()->findOrFail($classId)->subjects;
    }

    /**
     * Dropdown payload shared by admin screens: sessions plus classes with sections.
     *
     * @return array{sessions: list<array<string, mixed>>, classes: list<array<string, mixed>>, current_session_id: int|null}
     */
    public function options(): array
    {
        return [
            'current_session_id' => $this->currentSession()?->id,
            'sessions' => $this->sessions()->map(fn (AcademicSession $session): array => ['id' => $session->id, 'name' => $session->name])->all(),
            'classes' => $this->classes(false)->map(fn (SchoolClass $class): array => [
                'id' => $class->id,
                'name' => $class->displayName(),
                'division' => $class->division?->value,
                'sections' => $class->sections->map(fn (Section $section): array => ['id' => $section->id, 'name' => $section->displayName()])->all(),
            ])->all(),
        ];
    }
}
