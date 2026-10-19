<?php

namespace Modules\StudentPortal\Http\Controllers;

use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryFeed;
use Modules\Students\Models\Student;

class DiaryController
{
    public function __construct(protected DiaryFeed $feed, protected SchoolCalendar $calendar) {}

    /**
     * One day of the student's diary with a week strip. Opening a day marks its entries as seen.
     */
    public function index(Request $request): View
    {
        /** @var Student $student */
        $student = $request->user('student');
        $date = $this->date($request);

        $entries = $this->feed->forDay($student, $date);
        // "New" is decided before the entries are marked as seen.
        $new = $entries->filter(fn (DiaryEntry $entry): bool => $entry->acknowledgements->first()?->seen_at === null)->pluck('id')->all();
        $week = $this->feed->week($student, $date);
        $this->feed->markSeen($student, $entries);

        return view('student-portal::pages.diary', [
            'student' => $student, 'date' => $date, 'entries' => $entries, 'new' => $new,
            // The strip is rendered after marking, so the dots reflect what is still unseen on other days.
            'week' => $this->feed->week($student, $date),
            'previousWeek' => Carbon::instance($date)->startOfWeek(Carbon::SATURDAY)->subDay(),
            'nextWeek' => Carbon::instance($date)->startOfWeek(Carbon::SATURDAY)->addDays(7),
            'weekHasFuture' => end($week)['date']->lessThan(now()->startOfDay()),
            'allAcknowledged' => $entries->isNotEmpty() && $entries->every(fn (DiaryEntry $entry): bool => $entry->acknowledgements->first()?->acknowledged_at !== null),
        ]);
    }

    public function acknowledge(Request $request, DiaryEntry $entry): RedirectResponse
    {
        abort_unless($this->feed->acknowledge($request->user('student'), $entry), 404);

        return back();
    }

    public function acknowledgeDay(Request $request): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $this->feed->acknowledgeDay($request->user('student'), Carbon::parse($data['date']));

        return back();
    }

    /**
     * The requested day (never in the future), or the latest school day up to today.
     */
    protected function date(Request $request): CarbonInterface
    {
        $requested = $request->query('date');

        if (is_string($requested) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested)) {
            return min(Carbon::parse($requested)->startOfDay(), now()->startOfDay());
        }

        $today = now()->startOfDay();

        return $this->calendar->isSchoolDay($today) ? $today : $this->calendar->previousSchoolDay($today);
    }
}
