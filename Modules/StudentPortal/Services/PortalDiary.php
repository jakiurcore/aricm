<?php

namespace Modules\StudentPortal\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryFeed;
use Modules\Students\Models\Student;

/**
 * The diary figures the portal shows beside the day view itself: the dashboard card, the next school day's preparation
 * list and the homework still to hand in. Only published entries of the student's own section are ever read.
 */
class PortalDiary
{
    public function __construct(protected DiaryFeed $feed, protected SchoolCalendar $calendar) {}

    /**
     * What today's diary holds, for the dashboard card.
     *
     * @return array{date: CarbonInterface, subjects: int, homework: int, hasEntries: bool, acknowledged: bool, unseen: bool, prepDay: CarbonInterface, prepSubjects: list<string>}
     */
    public function today(Student $student): array
    {
        $today = now()->startOfDay();
        $entries = $this->feed->forDay($student, $today);
        $next = $this->calendar->nextSchoolDay($today);

        return [
            'date' => $today,
            'subjects' => $entries->whereNotNull('subject_id')->count(),
            'homework' => $entries->filter(fn (DiaryEntry $entry): bool => filled($entry->homework))->count(),
            'hasEntries' => $entries->isNotEmpty(),
            'acknowledged' => $entries->isNotEmpty() && $entries->every(fn (DiaryEntry $entry): bool => $entry->acknowledgements->first()?->acknowledged_at !== null),
            'unseen' => $entries->contains(fn (DiaryEntry $entry): bool => $entry->acknowledgements->first()?->seen_at === null),
            'prepDay' => $next,
            'prepSubjects' => $this->preparation($student, $today, $entries)->pluck('subject')->unique()->take(3)->values()->all(),
        ];
    }

    /**
     * The things to get ready for the next school day: a day's notes and the homework due that day.
     *
     * @param  Collection<int, DiaryEntry>|null  $dayEntries  The selected day's entries, when already loaded.
     * @return Collection<int, array{subject: string, text: string, kind: string}>
     */
    public function preparation(Student $student, CarbonInterface $date, ?Collection $dayEntries = null): Collection
    {
        $dayEntries ??= $this->feed->forDay($student, $date);
        $next = $this->calendar->nextSchoolDay($date);
        $items = collect();

        foreach ($dayEntries as $entry) {
            if (filled($entry->note)) {
                $items->push(['subject' => $entry->subjectName(), 'text' => (string) $entry->note, 'kind' => 'note']);
            }
        }

        foreach ($this->homeworkQuery($student)->whereDate('homework_due_date', $next->toDateString())->get() as $entry) {
            $items->push(['subject' => $entry->subjectName(), 'text' => (string) $entry->homework, 'kind' => 'homework']);
        }

        return $items;
    }

    /**
     * Homework whose due date has not passed, soonest first.
     *
     * @return Collection<int, DiaryEntry>
     */
    public function pendingHomework(Student $student): Collection
    {
        return $this->homeworkQuery($student)->whereDate('homework_due_date', '>=', now()->toDateString())->get();
    }

    /**
     * @return Builder<DiaryEntry>
     */
    protected function homeworkQuery(Student $student)
    {
        return DiaryEntry::query()->with(['subject'])
            ->where('status', DiaryStatus::Published->value)
            ->where('class_id', $student->class_id)->where('section_id', $student->section_id)
            ->when($student->session_id, fn ($query, $sessionId) => $query->where('session_id', $sessionId))
            ->whereNotNull('homework')->where('homework', '!=', '')->whereNotNull('homework_due_date')
            ->orderBy('homework_due_date')->orderBy('id');
    }
}
