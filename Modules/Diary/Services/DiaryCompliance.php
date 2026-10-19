<?php

namespace Modules\Diary\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\Academic\Services\AcademicDirectory;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Models\DiaryEntry;
use Modules\Teachers\Models\TeacherAssignment;

/**
 * Who has written their diary: every subject assignment of the current session is expected to have a published entry
 * on each school day. Holidays expect nothing.
 */
class DiaryCompliance
{
    public function __construct(protected SchoolCalendar $calendar, protected AcademicDirectory $academic) {}

    /**
     * @return array{date: string, is_school_day: bool, rows: list<array<string, mixed>>, summary: array{expected: int, published: int, draft: int, missing: int, percent: int}}
     */
    public function forDate(CarbonInterface $date): array
    {
        $summary = ['expected' => 0, 'published' => 0, 'draft' => 0, 'missing' => 0, 'percent' => 100];
        $result = ['date' => $date->toDateString(), 'is_school_day' => $this->calendar->isSchoolDay($date), 'rows' => [], 'summary' => $summary];

        if (! $result['is_school_day']) {
            return $result;
        }

        $assignments = TeacherAssignment::query()->with(['teacher', 'schoolClass', 'section', 'subject'])
            ->where('session_id', $this->academic->currentSession()?->id)->whereNotNull('subject_id')
            ->whereHas('teacher', fn ($query) => $query->where('is_active', true))->get();

        $entries = DiaryEntry::query()->whereDate('entry_date', $date->toDateString())->whereNotNull('subject_id')->get()
            ->keyBy(fn (DiaryEntry $entry): string => "{$entry->teacher_id}:{$entry->section_id}:{$entry->subject_id}");

        $rows = [];

        foreach ($assignments as $assignment) {
            $entry = $entries->get("{$assignment->teacher_id}:{$assignment->section_id}:{$assignment->subject_id}");
            $status = $entry === null ? 'missing' : ($entry->status === DiaryStatus::Published && $entry->hasContent() ? 'published' : 'draft');
            $summary[$status === 'published' ? 'published' : ($status === 'draft' ? 'draft' : 'missing')]++;
            $summary['expected']++;

            $rows[] = [
                'teacher_id' => $assignment->teacher_id, 'teacher' => $assignment->teacher->displayName(), 'phone' => $assignment->teacher->phone,
                'class_id' => $assignment->class_id, 'class' => $assignment->schoolClass->displayName(), 'order' => $assignment->schoolClass->numeric_order,
                'section_id' => $assignment->section_id, 'section' => $assignment->section->displayName(), 'subject_id' => $assignment->subject_id, 'subject' => $assignment->subject->displayName(),
                'status' => $status, 'entry_id' => $entry?->id,
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['order'], $a['section_id'], $a['subject_id']] <=> [$b['order'], $b['section_id'], $b['subject_id']]);
        $summary['percent'] = $summary['expected'] > 0 ? (int) round($summary['published'] * 100 / $summary['expected']) : 100;

        return [...$result, 'rows' => array_map(fn (array $row): array => array_diff_key($row, ['order' => 0]), $rows), 'summary' => $summary];
    }

    /**
     * Rows still missing for the date (used by the teacher reminder).
     *
     * @return list<array<string, mixed>>
     */
    public function missing(CarbonInterface $date): array
    {
        return array_values(array_filter($this->forDate($date)['rows'], fn (array $row): bool => $row['status'] !== 'published'));
    }

    /**
     * Per teacher over a date range: how many expected entries were published.
     *
     * @return list<array{teacher_id: int, teacher: string, expected: int, published: int, percent: int}>
     */
    public function byTeacher(CarbonInterface $from, CarbonInterface $to): array
    {
        $totals = [];

        for ($day = Carbon::instance($from)->startOfDay(); $day->lessThanOrEqualTo($to); $day->addDay()) {
            foreach ($this->forDate($day)['rows'] as $row) {
                $totals[$row['teacher_id']] ??= ['teacher_id' => $row['teacher_id'], 'teacher' => $row['teacher'], 'expected' => 0, 'published' => 0];
                $totals[$row['teacher_id']]['expected']++;
                $totals[$row['teacher_id']]['published'] += $row['status'] === 'published' ? 1 : 0;
            }
        }

        $rows = array_map(fn (array $row): array => [...$row, 'percent' => $row['expected'] > 0 ? (int) round($row['published'] * 100 / $row['expected']) : 100], array_values($totals));
        usort($rows, fn (array $a, array $b): int => $a['percent'] <=> $b['percent']);

        return $rows;
    }
}
