<?php

namespace Modules\TeacherPanel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\Section;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Enums\RemarkKind;
use Modules\Diary\Models\DiaryStudentRemark;
use Modules\Students\Services\StudentDirectory;
use Modules\TeacherPanel\Http\Requests\StoreRemarksRequest;
use Modules\TeacherPanel\Services\TeacherRemarks;
use Modules\TeacherPanel\Support\PanelFormat;
use Modules\Teachers\Services\TeacherScope;

/**
 * The remarks screen: tap a student for a private remark, or select several for "homework not brought" at once.
 */
class RemarksController
{
    /** The kinds offered on the screen, with the glyph and the colour pair used for their chips. */
    public const array KINDS = [
        'praise' => ['icon' => '★', 'color' => 'success'],
        'no_homework' => ['icon' => '!', 'color' => 'warn'],
        'inattentive' => ['icon' => '!', 'color' => 'warn'],
        'absent' => ['icon' => '○', 'color' => 'danger'],
        'other' => ['icon' => '✎', 'color' => 'info'],
    ];

    public function __construct(protected TeacherScope $scope, protected TeacherRemarks $remarks) {}

    public function index(Request $request, StudentDirectory $students, SchoolCalendar $calendar): View
    {
        $user = $request->user();
        $sections = collect($this->scope->sections($user))->map(fn (array $row): array => [...$row, 'label' => PanelFormat::section($row['class_name'], $row['section_name'])])->values();
        $current = $sections->firstWhere('section_id', $request->integer('section_id')) ?? $sections->first();

        $roster = collect();
        $today = collect();
        $recent = collect();

        if ($current) {
            $roster = $students->activeInClass($current['class_id'], $current['section_id'])->values();
            $today = $this->remarks->today($user, $current['section_id']);
            $recent = $this->remarks->recent($user, $current['section_id']);
        }

        return view('teacher-panel::pages.remarks', [
            'sections' => $sections, 'current' => $current, 'isSchoolDay' => $calendar->isSchoolDay(now()),
            'students' => $roster->map(fn ($student): array => [
                'id' => $student->id, 'name' => $student->displayName(), 'roll' => $student->roll, 'initial' => PanelFormat::initial($student->displayName()),
                'today' => $today->get($student->id, collect())->map(fn (DiaryStudentRemark $remark): string => $remark->kind->value)->unique()->values()->all(),
                'recent' => $recent->get($student->id, collect())->map(fn (DiaryStudentRemark $remark): array => $this->present($remark))->all(),
            ])->values(),
            'kinds' => collect(self::KINDS)->map(fn (array $kind, string $value): array => [...$kind, 'value' => $value, 'label' => RemarkKind::from($value)->label()])->values(),
            'allKinds' => collect(RemarkKind::cases())->mapWithKeys(fn (RemarkKind $kind): array => [$kind->value => ['label' => $kind->label(), ...(self::KINDS[$kind->value] ?? ['icon' => '✎', 'color' => 'info'])]]),
        ]);
    }

    /**
     * Saves one remark for every selected student of the section.
     */
    public function store(StoreRemarksRequest $request): JsonResponse
    {
        $section = Section::findOrFail($request->integer('section_id'));

        $saved = $this->remarks->add($request->user(), $section->class_id, $section->id, $request->input('student_ids'), RemarkKind::from($request->string('kind')->toString()), (string) $request->input('text', ''));

        return response()->json(['data' => $saved->map(fn (DiaryStudentRemark $remark): array => ['student_id' => $remark->student_id, ...$this->present($remark)])->values()], 201);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(DiaryStudentRemark $remark): array
    {
        $date = $remark->entry?->entry_date;

        return [
            'id' => $remark->id, 'kind' => $remark->kind->value, 'text' => $remark->text, 'today' => $date?->isToday() ?? false,
            'when' => $date ? PanelFormat::shortDate($date) : '',
        ];
    }
}
