<?php

namespace Modules\TeacherPanel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Models\DiaryEntry;
use Modules\TeacherPanel\Services\ClassDayReport;
use Modules\TeacherPanel\Services\DiaryInsights;
use Modules\TeacherPanel\Services\TeacherMenu;
use Modules\TeacherPanel\Support\PanelFormat;
use Modules\Teachers\Models\TeacherAssignment;
use Modules\Teachers\Services\TeacherScope;

class HomeController
{
    public function __invoke(Request $request, TeacherScope $scope, SchoolCalendar $calendar, DiaryInsights $insights, ClassDayReport $report, TeacherMenu $menu): View
    {
        $user = $request->user();
        $teacher = $scope->teacherFor($user);
        $today = now()->startOfDay();
        $isSchoolDay = $calendar->isSchoolDay($today);

        $entries = DiaryEntry::query()->where('teacher_id', $teacher->id)->whereDate('entry_date', $today->toDateString())->whereNotNull('subject_id')->get()
            ->keyBy(fn (DiaryEntry $entry): string => "{$entry->section_id}:{$entry->subject_id}");
        $seen = $insights->seenCounts($entries->filter(fn (DiaryEntry $entry): bool => $entry->isPublished()));

        $cards = $scope->assignments($user)->filter(fn (TeacherAssignment $assignment): bool => $assignment->subject_id !== null)
            ->sortBy(fn (TeacherAssignment $assignment): string => sprintf('%05d-%05d-%05d', $assignment->schoolClass->numeric_order, $assignment->section_id, $assignment->subject_id))
            ->map(function (TeacherAssignment $assignment) use ($entries, $seen, $insights, $scope, $user): array {
                $entry = $entries->get("{$assignment->section_id}:{$assignment->subject_id}");

                return [
                    'label' => PanelFormat::section($assignment->schoolClass->displayName(), $assignment->section->displayName()).' – '.$assignment->subject->displayName(),
                    'status' => $entry === null ? 'missing' : ($entry->isPublished() ? 'published' : 'draft'),
                    'entry' => $entry, 'class_teacher' => $scope->isClassTeacher($user, $assignment->class_id, $assignment->section_id),
                    'seen' => $entry ? ($seen[$entry->id] ?? 0) : 0, 'total' => $insights->sectionSize($assignment->class_id, $assignment->section_id),
                    'params' => ['class_id' => $assignment->class_id, 'section_id' => $assignment->section_id, 'subject_id' => $assignment->subject_id],
                ];
            })->values();

        $classTeacher = collect($scope->sections($user))->firstWhere('is_class_teacher', true);
        $classDay = $classTeacher ? $report->build($classTeacher['class_id'], $classTeacher['section_id'], $today) : null;

        return view('teacher-panel::pages.home', [
            'teacher' => $teacher, 'today' => $today, 'next' => $calendar->nextSchoolDay($today), 'isSchoolDay' => $isSchoolDay, 'cards' => $cards,
            'published' => $cards->where('status', 'published')->count(), 'hasProgress' => $cards->contains(fn (array $card): bool => $card['status'] !== 'missing'),
            'classTeacher' => $classTeacher, 'classDay' => $classDay,
            'extraItems' => $menu->extras($menu->items($user)),
        ]);
    }
}
