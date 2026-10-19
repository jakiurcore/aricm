<?php

namespace Modules\Diary\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Models\DiaryAcknowledgement;
use Modules\Diary\Models\DiaryEntry;
use Modules\Students\Models\Student;

/**
 * The student's side of the diary: published entries for their section, their own remarks, and seen / acknowledged state.
 */
class DiaryFeed
{
    /** How far back "unseen" counts. */
    private const int UNSEEN_DAYS = 14;

    public function __construct(protected SchoolCalendar $calendar) {}

    /**
     * Published entries for the student's section on one day, subject by subject, with the student's own remarks and acknowledgement.
     *
     * @return Collection<int, DiaryEntry>
     */
    public function forDay(Student $student, CarbonInterface $date): Collection
    {
        return $this->visible($student)
            ->whereDate('entry_date', $date->toDateString())
            ->with(['subject', 'teacher', 'attachments', 'remarks' => fn ($query) => $query->where('student_id', $student->id)->with('teacher'), 'acknowledgements' => fn ($query) => $query->where('student_id', $student->id)])
            ->orderByRaw('subject_id is null')->orderBy('id')
            ->get();
    }

    /**
     * One week (Saturday to Friday) around the date: per day whether it is a school day, how many entries, how many unseen.
     *
     * @return list<array{date: Carbon, is_school_day: bool, entries: int, unseen: int}>
     */
    public function week(Student $student, CarbonInterface $anchor): array
    {
        $start = Carbon::instance($anchor)->startOfWeek(Carbon::SATURDAY);
        $end = $start->copy()->addDays(6);

        $entries = $this->visible($student)->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->with(['acknowledgements' => fn ($query) => $query->where('student_id', $student->id)])->get()
            ->groupBy(fn (DiaryEntry $entry): string => $entry->entry_date->toDateString());

        $days = [];

        for ($day = $start->copy(); $day->lessThanOrEqualTo($end); $day->addDay()) {
            $of = $entries->get($day->toDateString(), collect());

            $days[] = [
                'date' => $day->copy(), 'is_school_day' => $this->calendar->isSchoolDay($day), 'entries' => $of->count(),
                'unseen' => $of->filter(fn (DiaryEntry $entry): bool => $entry->acknowledgements->first()?->seen_at === null)->count(),
            ];
        }

        return $days;
    }

    public function unseenCount(Student $student): int
    {
        return $this->visible($student)
            ->where('entry_date', '>=', now()->subDays(self::UNSEEN_DAYS)->toDateString())
            ->whereDoesntHave('acknowledgements', fn ($query) => $query->where('student_id', $student->id)->whereNotNull('seen_at'))
            ->count();
    }

    /**
     * Mark the given entries as seen (first time only).
     *
     * @param  iterable<DiaryEntry>  $entries
     */
    public function markSeen(Student $student, iterable $entries): void
    {
        foreach ($entries as $entry) {
            $ack = DiaryAcknowledgement::query()->firstOrCreate(['diary_entry_id' => $entry->id, 'student_id' => $student->id]);

            if ($ack->seen_at === null) {
                $ack->update(['seen_at' => now()]);
            }
        }
    }

    /**
     * "I have seen this" for one entry. Returns false when the entry is not one the student may see.
     */
    public function acknowledge(Student $student, DiaryEntry $entry): bool
    {
        if (! $this->visible($student)->whereKey($entry->id)->exists()) {
            return false;
        }

        $ack = DiaryAcknowledgement::query()->firstOrCreate(['diary_entry_id' => $entry->id, 'student_id' => $student->id]);
        $ack->update(['seen_at' => $ack->seen_at ?? now(), 'acknowledged_at' => $ack->acknowledged_at ?? now()]);

        return true;
    }

    /**
     * Acknowledge everything for a day in one tap.
     */
    public function acknowledgeDay(Student $student, CarbonInterface $date): int
    {
        $entries = $this->forDay($student, $date);

        foreach ($entries as $entry) {
            $this->acknowledge($student, $entry);
        }

        return $entries->count();
    }

    /**
     * @return Builder<DiaryEntry>
     */
    protected function visible(Student $student)
    {
        return DiaryEntry::query()
            ->where('status', DiaryStatus::Published->value)
            ->where('class_id', $student->class_id)->where('section_id', $student->section_id)
            ->when($student->session_id, fn ($query, $sessionId) => $query->where('session_id', $sessionId));
    }
}
