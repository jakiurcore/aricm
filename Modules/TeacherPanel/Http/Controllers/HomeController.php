<?php

namespace Modules\TeacherPanel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Diary\Services\DiaryCompliance;
use Modules\Teachers\Services\TeacherScope;

class HomeController
{
    public function __invoke(Request $request, TeacherScope $scope, SchoolCalendar $calendar, DiaryCompliance $compliance): View
    {
        $teacher = $scope->teacherFor($request->user());
        $today = now();
        $mine = collect($compliance->forDate($today)['rows'])->where('teacher_id', $teacher->id);

        return view('teacher-panel::pages.home', [
            'teacher' => $teacher,
            'sections' => $scope->sections($request->user()),
            'isSchoolDay' => $calendar->isSchoolDay($today),
            'today' => $today,
            'expected' => $mine->count(),
            'published' => $mine->where('status', 'published')->count(),
            'pending' => $mine->where('status', '!=', 'published')->values(),
        ]);
    }
}
