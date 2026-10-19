<?php

namespace Modules\TeacherPanel\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryCompliance;
use Modules\Students\Models\Student;
use Modules\Students\Services\StudentDirectory;

/**
 * The class teacher's day: which subjects have their diary, how many guardians have seen it, who has not, and every
 * entry of the day in full.
 */
class ClassDayReport
{
    public function __construct(protected DiaryCompliance $compliance, protected DiaryInsights $insights, protected StudentDirectory $students) {}

    /**
     * @return array{
     *     expected: int, written: int, total: int, seen: int, missing: list<array<string, mixed>>,
     *     notSeen: Collection<int, Student>, entries: Collection<int, DiaryEntry>, seenCounts: array<int, int>, hasEntries: bool
     * }
     */
    public function build(int $classId, int $sectionId, CarbonInterface $date): array
    {
        $rows = collect($this->compliance->forDate($date)['rows'])->where('section_id', $sectionId)->values();
        $entries = DiaryEntry::query()->with(['teacher', 'subject', 'attachments'])->where('section_id', $sectionId)->whereDate('entry_date', $date->toDateString())
            ->orderByRaw('subject_id is null')->orderBy('subject_id')->get();

        $published = $entries->filter(fn (DiaryEntry $entry): bool => $entry->isPublished());
        $total = $this->insights->sectionSize($classId, $sectionId);
        $sawIds = $this->insights->studentsWhoSaw($sectionId, $date);

        $notSeen = $published->isEmpty() ? collect() : $this->students->activeInClass($classId, $sectionId)->reject(fn (Student $student): bool => $sawIds->contains($student->id))->values();

        return [
            'expected' => $rows->count(),
            'written' => $rows->where('status', 'published')->count(),
            'total' => $total,
            'seen' => $published->isEmpty() ? 0 : max(0, $total - $notSeen->count()),
            'missing' => $rows->where('status', '!=', 'published')->values()->all(),
            'notSeen' => $notSeen,
            'entries' => $entries,
            'seenCounts' => $this->insights->seenCounts($published),
            'hasEntries' => $entries->isNotEmpty(),
        ];
    }
}
