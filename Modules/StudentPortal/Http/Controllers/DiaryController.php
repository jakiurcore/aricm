<?php

namespace Modules\StudentPortal\Http\Controllers;

use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryFeed;
use Modules\Diary\Services\DiarySettings;
use Modules\StudentPortal\Services\PortalDiary;
use Modules\Students\Models\Student;

class DiaryController
{
    public function __construct(protected DiaryFeed $feed, protected SchoolCalendar $calendar, protected PortalDiary $portalDiary) {}

    /**
     * One day of the student's diary with a week strip. Opening a day marks its entries as seen. A day in the future
     * or a holiday without entries is shown as locked, never queried.
     */
    public function index(Request $request): View
    {
        /** @var Student $student */
        $student = $request->user('student');
        $date = $this->date($request);
        $isFuture = $date->greaterThan(now()->endOfDay()) && ! app(DiarySettings::class)->studentSeesFuture();

        $entries = $isFuture ? new Collection : $this->feed->forDay($student, $date);
        $this->feed->markSeen($student, $entries);
        $locked = $isFuture ? 'future' : (! $this->calendar->isSchoolDay($date) && $entries->isEmpty() ? 'holiday' : null);

        $acknowledgedAt = $entries->map(fn (DiaryEntry $entry) => $entry->acknowledgements->first()?->acknowledged_at)->filter();

        return view('student-portal::pages.diary', [
            'student' => $student, 'date' => $date, 'entries' => $entries, 'locked' => $locked,
            // The strip is read after marking, so its dots reflect what is still unseen on other days.
            'week' => $this->feed->week($student, $date),
            'previousWeek' => Carbon::instance($date)->startOfWeek(Carbon::SATURDAY)->subDay(),
            'nextWeek' => Carbon::instance($date)->startOfWeek(Carbon::SATURDAY)->addDays(7),
            'weekStart' => Carbon::instance($date)->startOfWeek(Carbon::SATURDAY),
            'nextSchoolDay' => $this->calendar->nextSchoolDay($date),
            'preparation' => $locked ? new Collection : $this->portalDiary->preparation($student, $date, $entries),
            'remarks' => $entries->flatMap(fn (DiaryEntry $entry) => $entry->remarks->map(fn ($remark) => ['remark' => $remark, 'entry' => $entry]))->values(),
            'pending' => $this->portalDiary->pendingHomework($student),
            'canAcknowledge' => $entries->isNotEmpty(),
            'allAcknowledged' => $entries->isNotEmpty() && $acknowledgedAt->count() === $entries->count(),
            'acknowledgedAt' => $acknowledgedAt->max(),
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
     * The requested day, or the latest school day up to today.
     */
    protected function date(Request $request): CarbonInterface
    {
        $requested = $request->query('date');

        if (is_string($requested) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested) && checkdate((int) substr($requested, 5, 2), (int) substr($requested, 8, 2), (int) substr($requested, 0, 4))) {
            return Carbon::parse($requested)->startOfDay();
        }

        $today = now()->startOfDay();

        return $this->calendar->isSchoolDay($today) ? $today : $this->calendar->previousSchoolDay($today);
    }
}
