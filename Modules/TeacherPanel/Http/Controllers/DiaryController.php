<?php

namespace Modules\TeacherPanel\Http\Controllers;

use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Core\Services\Sms\SmsManager;
use Modules\Core\Support\Bangla;
use Modules\Diary\Enums\DiaryStatus;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryCompliance;
use Modules\Diary\Services\DiaryService;
use Modules\Diary\Services\DiarySettings;
use Modules\Students\Services\StudentDirectory;
use Modules\TeacherPanel\Services\ClassDayReport;
use Modules\TeacherPanel\Services\DiaryInsights;
use Modules\TeacherPanel\Support\PanelFormat;
use Modules\Teachers\Services\TeacherScope;

class DiaryController
{
    public function __construct(protected TeacherScope $scope, protected SchoolCalendar $calendar, protected DiarySettings $settings) {}

    /**
     * The teacher's own diary history: every entry they wrote, filterable by day, section and subject. Filtering by a
     * school day also lists the subjects still to be written for it.
     */
    public function index(Request $request, DiaryCompliance $compliance, DiaryInsights $insights, DiaryService $diary): View
    {
        $user = $request->user();
        $teacher = $this->scope->teacherFor($user);
        $sections = collect($this->scope->sections($user));

        $date = $this->filterDate($request->query('date'));
        $sectionId = $sections->contains('section_id', $request->integer('section_id')) ? $request->integer('section_id') : null;
        $subject = $request->query('subject');
        $subject = $subject === 'general' ? 'general' : (is_numeric($subject) ? (int) $subject : null);

        $query = DiaryEntry::query()->where('teacher_id', $teacher->id)
            ->when($date, fn ($query) => $query->whereDate('entry_date', $date->toDateString()))
            ->when($sectionId, fn ($query) => $query->where('section_id', $sectionId))
            ->when($subject === 'general', fn ($query) => $query->whereNull('subject_id'))
            ->when(is_int($subject), fn ($query) => $query->where('subject_id', $subject));

        $total = (clone $query)->count();
        $entries = $query->with(['subject', 'section.schoolClass'])->orderByDesc('entry_date')->orderByDesc('updated_at')->limit(120)->get();

        $missing = collect();

        if ($date && $subject !== 'general') {
            $missing = collect($compliance->forDate($date)['rows'])->where('teacher_id', $teacher->id)->where('status', 'missing')
                ->when($sectionId, fn (Collection $rows) => $rows->where('section_id', $sectionId))
                ->when(is_int($subject), fn (Collection $rows) => $rows->where('subject_id', $subject))->values();
        }

        $subjects = $sections->flatMap(fn (array $section): array => $section['subjects'])->unique('id')->values();

        return view('teacher-panel::pages.diary.index', [
            'entries' => $entries, 'total' => $total, 'missing' => $missing, 'seen' => $insights->seenCounts($entries->filter(fn (DiaryEntry $entry): bool => $entry->isPublished())),
            'sizes' => $entries->mapWithKeys(fn (DiaryEntry $entry): array => [$entry->id => $insights->sectionSize($entry->class_id, $entry->section_id)]),
            'editable' => $entries->mapWithKeys(fn (DiaryEntry $entry): array => [$entry->id => $diary->canEdit($user, $entry) && $this->writable($entry->entry_date)]),
            'sections' => $sections, 'subjects' => $subjects, 'hasGeneral' => $sections->contains('is_class_teacher', true),
            'filters' => ['date' => $date?->toDateString(), 'section_id' => $sectionId, 'subject' => $subject],
            'isHoliday' => $date !== null && ! $this->calendar->isSchoolDay($date), 'date' => $date,
        ]);
    }

    /**
     * The editor for one section, subject (or the class teacher's general note) and day. Without a section it opens
     * the teacher's first one.
     */
    public function write(Request $request, StudentDirectory $students): View|RedirectResponse
    {
        if (! $request->has('class_id') && ! $request->has('section_id')) {
            return $this->openFirstSection($request);
        }

        $data = $request->validate(['class_id' => ['required', 'integer'], 'section_id' => ['required', 'integer'], 'subject_id' => ['nullable', 'integer'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $subjectId = $data['subject_id'] ?? null;
        $allowed = $subjectId === null
            ? $this->scope->isClassTeacher($request->user(), $data['class_id'], $data['section_id'])
            : $this->scope->canTeach($request->user(), $data['class_id'], $data['section_id'], $subjectId);
        abort_unless($allowed, 403);

        $section = Section::with('schoolClass')->findOrFail($data['section_id']);
        abort_unless($section->class_id === (int) $data['class_id'], 404);
        $date = $this->date($request);
        $teacher = $this->scope->teacherFor($request->user());

        $options = collect($this->scope->sections($request->user()))->map(fn (array $row): array => [
            'key' => "{$row['class_id']}-{$row['section_id']}", 'class_id' => $row['class_id'], 'section_id' => $row['section_id'],
            'label' => PanelFormat::section($row['class_name'], $row['section_name']), 'class_teacher' => $row['is_class_teacher'], 'subjects' => $row['subjects'],
        ])->values();

        return view('teacher-panel::pages.diary.write', [
            'section' => $section, 'subject' => $subjectId ? Subject::findOrFail($subjectId) : null, 'date' => $date, 'writable' => $this->writable($date),
            'exists' => DiaryEntry::query()->where(['teacher_id' => $teacher->id, 'section_id' => $section->id, 'subject_id' => $subjectId])->whereDate('entry_date', $date->toDateString())->exists(),
            'students' => $students->activeInClass($section->class_id, $section->id)->map(fn ($student): array => ['id' => $student->id, 'roll' => $student->roll, 'name' => $student->displayName()])->values(),
            'options' => $options, 'sectionLabel' => PanelFormat::section($section->schoolClass->displayName(), $section->displayName()),
            'others' => $subjectId ? $options->filter(fn (array $row): bool => $row['section_id'] !== $section->id && collect($row['subjects'])->contains('id', $subjectId))->values() : collect(),
            'dateNote' => $this->dateNote($date), 'maxAttachments' => $this->settings->maxAttachments(), 'editHours' => $this->settings->editWindowHours(),
            'maxKb' => (int) config('modules.diary.attachments.max_kb', 5120), 'today' => now()->toDateString(),
        ]);
    }

    /**
     * The teacher's latest published entry before the date for the same section and subject, to copy into the editor.
     */
    public function previous(Request $request): JsonResponse
    {
        $data = $request->validate(['class_id' => ['required', 'integer'], 'section_id' => ['required', 'integer'], 'subject_id' => ['nullable', 'integer'], 'date' => ['required', 'date_format:Y-m-d']]);
        $teacher = $this->scope->teacherFor($request->user());

        $entry = DiaryEntry::query()->where(['teacher_id' => $teacher->id, 'class_id' => $data['class_id'], 'section_id' => $data['section_id'], 'subject_id' => $data['subject_id'] ?? null])
            ->where('status', DiaryStatus::Published->value)->whereDate('entry_date', '<', $data['date'])->orderByDesc('entry_date')->first();

        return response()->json(['data' => $entry ? [
            'date' => $entry->entry_date->toDateString(), 'label' => PanelFormat::shortDate($entry->entry_date),
            'lesson' => $entry->lesson, 'next_lesson' => $entry->next_lesson, 'homework' => $entry->homework, 'note' => $entry->note,
        ] : null]);
    }

    /**
     * The class teacher's view of their section for one day: every subject's diary, who wrote it, and who has not seen it.
     */
    public function classDay(Request $request, ClassDayReport $report): View
    {
        [$mySections, $section] = $this->classTeacherSection($request);
        $date = $this->date($request);

        return view('teacher-panel::pages.diary.class-day', [
            'mySections' => $mySections, 'section' => $section, 'date' => $date, 'report' => $report->build($section['class_id'], $section['section_id'], $date),
            'label' => PanelFormat::section($section['class_name'], $section['section_name']),
        ]);
    }

    /**
     * Text the teachers who have not published their diary for the section yet.
     */
    public function remind(Request $request, DiaryCompliance $compliance, SmsManager $sms): JsonResponse
    {
        [, $section] = $this->classTeacherSection($request);
        $date = $this->date($request);

        $sent = 0;

        foreach (collect($compliance->missing($date))->where('section_id', $section['section_id'])->groupBy('teacher_id') as $rows) {
            $list = $rows->map(fn (array $row): string => "{$row['class']}-{$row['section']} {$row['subject']}")->take(6)->implode(', ');
            $sent += $sms->send($rows->first()['phone'], __('diary::messages.sms.teacher', ['list' => $list])) ? 1 : 0;
        }

        return response()->json(['sent' => $sent, 'message' => __('teacher-panel::messages.class_day.reminded', ['count' => bn_digits($sent)])]);
    }

    /**
     * Text the guardians of the students who have not opened the day's diary.
     */
    public function notifyGuardians(Request $request, ClassDayReport $report, SmsManager $sms): JsonResponse
    {
        [, $section] = $this->classTeacherSection($request);
        $date = $this->date($request);

        $sent = 0;

        foreach ($report->build($section['class_id'], $section['section_id'], $date)['notSeen'] as $student) {
            $sent += $sms->send($student->guardian_phone, __('teacher-panel::messages.class_day.guardian_sms', ['name' => $student->displayName()])) ? 1 : 0;
        }

        return response()->json(['sent' => $sent, 'message' => __('teacher-panel::messages.class_day.guardians_texted', ['count' => bn_digits($sent)])]);
    }

    /**
     * The teacher's class-teacher sections and the one chosen (the first by default); a non-class-teacher gets a 403.
     *
     * @return array{0: Collection<int, array<string, mixed>>, 1: array<string, mixed>}
     */
    protected function classTeacherSection(Request $request): array
    {
        $mySections = collect($this->scope->sections($request->user()))->where('is_class_teacher', true)->values();
        abort_if($mySections->isEmpty(), 403);

        $section = $mySections->firstWhere('section_id', $request->integer('section_id') ?: $mySections->first()['section_id']);
        abort_unless($section, 403);

        return [$mySections, $section];
    }

    protected function openFirstSection(Request $request): RedirectResponse
    {
        $first = collect($this->scope->sections($request->user()))->first();

        if ($first === null) {
            return redirect()->route('teacher.home');
        }

        $subjectId = $first['subjects'][0]['id'] ?? null;

        return redirect()->route('teacher.diary.write', array_filter(['class_id' => $first['class_id'], 'section_id' => $first['section_id'], 'subject_id' => $subjectId, 'date' => $request->query('date')]));
    }

    /**
     * Warns about days the diary is unusual for: a holiday, a day gone by, or one that has not come yet.
     */
    protected function dateNote(CarbonInterface $date): ?string
    {
        if (! $this->calendar->isSchoolDay($date)) {
            return __('teacher-panel::messages.diary.note_holiday', ['day' => Bangla::weekday($date)]);
        }

        return $date->lessThan(now()->startOfDay()) ? __('teacher-panel::messages.diary.note_past') : null;
    }

    protected function filterDate(mixed $requested): ?CarbonInterface
    {
        return is_string($requested) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested) ? Carbon::parse($requested)->startOfDay() : null;
    }

    /**
     * The requested day, or today; on a holiday the last school day.
     */
    protected function date(Request $request): CarbonInterface
    {
        $date = $this->filterDate($request->query('date')) ?? now()->startOfDay();

        if ($date->greaterThan(now()->startOfDay())) {
            $date = now()->startOfDay();
        }

        return $request->has('date') || $this->calendar->isSchoolDay($date) ? $date : $this->calendar->previousSchoolDay($date);
    }

    protected function writable(CarbonInterface $date): bool
    {
        return $this->calendar->isSchoolDay($date) && $date->greaterThanOrEqualTo(now()->startOfDay()->subDays($this->settings->backdateDays()));
    }
}
