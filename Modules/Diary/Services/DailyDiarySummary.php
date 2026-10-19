<?php

namespace Modules\Diary\Services;

use Carbon\CarbonInterface;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Core\Services\Sms\SmsManager;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Models\DiaryEntry;
use Modules\Students\Models\Student;

/**
 * The 6 PM messages: one short SMS to each guardian whose child's section has published diary entries today, and
 * (optionally) a reminder to teachers who have not written yet. Both are off until enabled in the diary settings.
 */
class DailyDiarySummary
{
    public function __construct(
        protected DiarySettings $settings,
        protected DiaryCompliance $compliance,
        protected SmsManager $sms,
        protected SchoolCalendar $calendar,
    ) {}

    /**
     * @return array{guardians: int, teachers: int}
     */
    public function send(CarbonInterface $date): array
    {
        $sent = ['guardians' => 0, 'teachers' => 0];

        if (! $this->settings->enabled() || ! $this->calendar->isSchoolDay($date)) {
            return $sent;
        }

        if ($this->settings->get('sms_enabled')) {
            $sent['guardians'] = $this->guardians($date);
        }

        if ($this->settings->get('sms_teacher_reminder')) {
            $sent['teachers'] = $this->teachers($date);
        }

        return $sent;
    }

    protected function guardians(CarbonInterface $date): int
    {
        $sent = 0;

        $entries = DiaryEntry::query()->where('status', DiaryStatus::Published->value)->whereDate('entry_date', $date->toDateString())->get()
            ->groupBy('section_id');

        foreach ($entries as $sectionId => $sectionEntries) {
            $subjects = $sectionEntries->count();
            $homework = $sectionEntries->filter(fn (DiaryEntry $entry): bool => filled($entry->homework))->count();
            $classId = $sectionEntries->first()->class_id;

            Student::query()->where('class_id', $classId)->where('section_id', $sectionId)->where('status', 'active')->whereNotNull('guardian_phone')
                ->chunkById(200, function ($students) use (&$sent, $subjects, $homework): void {
                    foreach ($students as $student) {
                        $message = __('diary::messages.sms.guardian', ['name' => $student->displayName(), 'subjects' => bn_number($subjects), 'homework' => bn_number($homework)]);

                        if ($this->sms->send($student->guardian_phone, $message)) {
                            $sent++;
                        }
                    }
                });
        }

        return $sent;
    }

    /**
     * Remind every teacher who still owes an entry for the date (the monitor's "send everyone a reminder" button).
     *
     * @return int Messages the gateway accepted.
     */
    public function remindTeachers(CarbonInterface $date): int
    {
        return $this->calendar->isSchoolDay($date) ? $this->teachers($date) : 0;
    }

    protected function teachers(CarbonInterface $date): int
    {
        $sent = 0;

        foreach (collect($this->compliance->missing($date))->groupBy('teacher_id') as $rows) {
            $list = $rows->map(fn (array $row): string => "{$row['class']}-{$row['section']} {$row['subject']}")->take(6)->implode(', ');

            if ($this->sms->send($rows->first()['phone'], __('diary::messages.sms.teacher', ['list' => $list]))) {
                $sent++;
            }
        }

        return $sent;
    }
}
