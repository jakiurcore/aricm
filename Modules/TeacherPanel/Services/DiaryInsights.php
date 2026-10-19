<?php

namespace Modules\TeacherPanel\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Models\DiaryAcknowledgement;
use Modules\Diary\Models\DiaryEntry;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Services\StudentDirectory;

/**
 * How many guardians have opened a diary entry: per entry, and per section and day (a student counts once if they
 * opened any published entry of that day).
 */
class DiaryInsights
{
    /** @var array<string, int> */
    private array $sizes = [];

    public function __construct(protected StudentDirectory $students) {}

    public function sectionSize(int $classId, int $sectionId): int
    {
        return $this->sizes["{$classId}:{$sectionId}"] ??= $this->students->query(['class_id' => $classId, 'section_id' => $sectionId, 'status' => StudentStatus::Active->value])->count();
    }

    /**
     * Entry id => number of students who have seen it.
     *
     * @param  Collection<int, DiaryEntry>  $entries
     * @return array<int, int>
     */
    public function seenCounts(Collection $entries): array
    {
        if ($entries->isEmpty()) {
            return [];
        }

        $counts = DiaryAcknowledgement::query()->whereIn('diary_entry_id', $entries->pluck('id'))->whereNotNull('seen_at')
            ->selectRaw('diary_entry_id, count(*) as total')->groupBy('diary_entry_id')->pluck('total', 'diary_entry_id');

        return $entries->mapWithKeys(fn (DiaryEntry $entry): array => [$entry->id => (int) ($counts[$entry->id] ?? 0)])->all();
    }

    /**
     * Ids of the students who opened at least one published entry of the section on the day.
     *
     * @return Collection<int, int>
     */
    public function studentsWhoSaw(int $sectionId, CarbonInterface $date): Collection
    {
        return DiaryAcknowledgement::query()->whereNotNull('seen_at')
            ->whereHas('entry', fn ($query) => $query->where('section_id', $sectionId)->whereDate('entry_date', $date->toDateString())->where('status', DiaryStatus::Published->value))
            ->pluck('student_id')->unique()->values();
    }
}
