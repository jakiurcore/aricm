<?php

namespace Modules\Academic\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\Academic\Models\Holiday;
use Modules\Core\Services\Settings;

/**
 * Which days the school is open. A day is a school day unless it falls on a weekly holiday (default Friday) or is in
 * the holiday calendar. "The next day" for homework always means the next school day.
 */
class SchoolCalendar
{
    public const array WEEKDAYS = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

    /** @var array<string, bool>|null */
    private ?array $holidays = null;

    public function __construct(protected Settings $settings) {}

    /**
     * @return list<string> Lower-case day names, e.g. ["friday"].
     */
    public function weeklyHolidays(): array
    {
        $days = $this->settings->get('academic_weekly_holidays', ['friday']);

        return array_values(array_intersect(array_map('strtolower', (array) $days), self::WEEKDAYS));
    }

    public function isHoliday(CarbonInterface $date): bool
    {
        $this->holidays ??= Holiday::query()->pluck('date')->mapWithKeys(fn ($day): array => [$day->toDateString() => true])->all();

        return isset($this->holidays[$date->toDateString()]);
    }

    public function isSchoolDay(CarbonInterface $date): bool
    {
        return ! in_array(strtolower($date->englishDayOfWeek), $this->weeklyHolidays(), true) && ! $this->isHoliday($date);
    }

    /**
     * The first school day strictly after the given date.
     */
    public function nextSchoolDay(CarbonInterface $date): Carbon
    {
        $day = Carbon::instance($date)->startOfDay()->addDay();

        // A school with every day marked as a holiday would loop forever: stop after a year.
        for ($i = 0; $i < 366 && ! $this->isSchoolDay($day); $i++) {
            $day->addDay();
        }

        return $day;
    }

    /**
     * The last school day strictly before the given date.
     */
    public function previousSchoolDay(CarbonInterface $date): Carbon
    {
        $day = Carbon::instance($date)->startOfDay()->subDay();

        for ($i = 0; $i < 366 && ! $this->isSchoolDay($day); $i++) {
            $day->subDay();
        }

        return $day;
    }

    /**
     * Forget cached holidays (after the calendar was edited).
     */
    public function refresh(): void
    {
        $this->holidays = null;
    }
}
