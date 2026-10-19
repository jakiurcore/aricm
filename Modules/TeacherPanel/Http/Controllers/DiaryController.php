<?php

namespace Modules\TeacherPanel\Http\Controllers;

use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Academic\Models\Section;
use Modules\Academic\Models\Subject;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Models\DiaryEntry;
use Modules\Diary\Services\DiaryCompliance;
use Modules\Diary\Services\DiarySettings;
use Modules\Students\Services\StudentDirectory;
use Modules\Teachers\Services\TeacherScope;

class DiaryController
{
    public function __construct(protected TeacherScope $scope, protected SchoolCalendar $calendar, protected DiarySettings $settings) {}

    /**
     * The teacher's classes for a day with their diary status, ready to open.
     */
    public function index(Request $request, DiaryCompliance $compliance): View
    {
        $teacher = $this->scope->teacherFor($request->user());
        $date = $this->date($request);
        $report = $compliance->forDate($date);
        $rows = collect($report['rows'])->where('teacher_id', $teacher->id)->values();

        $general = collect($this->scope->sections($request->user()))->where('is_class_teacher', true)->map(function (array $section) use ($teacher, $date): array {
            $entry = DiaryEntry::query()->where(['teacher_id' => $teacher->id, 'section_id' => $section['section_id']])->whereNull('subject_id')->whereDate('entry_date', $date->toDateString())->first();

            return [...$section, 'status' => $entry === null ? 'missing' : ($entry->isPublished() ? 'published' : 'draft')];
        })->values();

        return view('teacher-panel::pages.diary.index', [
            'date' => $date, 'isSchoolDay' => $report['is_school_day'], 'rows' => $rows, 'general' => $general, 'writable' => $this->writable($date),
            'previous' => $this->calendar->previousSchoolDay($date), 'next' => $date->lessThan(now()->startOfDay()) ? $this->calendar->nextSchoolDay($date) : null,
        ]);
    }

    /**
     * The editor for one section, subject (or the class teacher's general note) and day.
     */
    public function write(Request $request, StudentDirectory $students): View
    {
        $data = $request->validate(['class_id' => ['required', 'integer'], 'section_id' => ['required', 'integer'], 'subject_id' => ['nullable', 'integer'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $subjectId = $data['subject_id'] ?? null;
        $allowed = $subjectId === null
            ? $this->scope->isClassTeacher($request->user(), $data['class_id'], $data['section_id'])
            : $this->scope->canTeach($request->user(), $data['class_id'], $data['section_id'], $subjectId);
        abort_unless($allowed, 403);

        $section = Section::with('schoolClass')->findOrFail($data['section_id']);
        abort_unless($section->class_id === (int) $data['class_id'], 404);
        $date = $this->date($request);

        return view('teacher-panel::pages.diary.write', [
            'section' => $section, 'subject' => $subjectId ? Subject::findOrFail($subjectId) : null, 'date' => $date, 'writable' => $this->writable($date),
            'students' => $students->activeInClass($section->class_id, $section->id)->map(fn ($student): array => ['id' => $student->id, 'roll' => $student->roll, 'name' => $student->displayName()])->values(),
            'kinds' => collect(RemarkKind::cases())->map(fn (RemarkKind $kind): array => ['value' => $kind->value, 'label' => $kind->label()])->values(),
            'maxAttachments' => $this->settings->maxAttachments(),
        ]);
    }

    /**
     * The class teacher's view of their section for one day: every subject's diary, who wrote it, and the remarks.
     */
    public function classDay(Request $request, DiaryCompliance $compliance): View
    {
        $mySections = collect($this->scope->sections($request->user()))->where('is_class_teacher', true)->values();
        abort_if($mySections->isEmpty(), 403);

        $sectionId = $request->integer('section_id') ?: $mySections->first()['section_id'];
        $section = $mySections->firstWhere('section_id', $sectionId);
        abort_unless($section, 403);

        $date = $this->date($request);
        $rows = collect($compliance->forDate($date)['rows'])->where('section_id', $sectionId)->values();
        $entries = DiaryEntry::query()->with(['teacher', 'subject', 'remarks.student', 'attachments'])->where('section_id', $sectionId)->whereDate('entry_date', $date->toDateString())->get();

        return view('teacher-panel::pages.diary.class-day', [
            'mySections' => $mySections, 'section' => $section, 'date' => $date, 'rows' => $rows, 'entries' => $entries->keyBy('id'),
            'general' => $entries->whereNull('subject_id')->values(), 'bySubject' => $entries->whereNotNull('subject_id')->keyBy('subject_id'),
            'previous' => $this->calendar->previousSchoolDay($date), 'next' => $date->lessThan(now()->startOfDay()) ? $this->calendar->nextSchoolDay($date) : null,
        ]);
    }

    /**
     * The requested day, or today; on a holiday the last school day.
     */
    protected function date(Request $request): CarbonInterface
    {
        $requested = $request->query('date');
        $date = is_string($requested) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requested) ? Carbon::parse($requested)->startOfDay() : now()->startOfDay();

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
