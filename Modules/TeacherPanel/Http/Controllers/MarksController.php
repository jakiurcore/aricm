<?php

namespace Modules\TeacherPanel\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Exams\Models\Exam;
use Modules\Results\Services\TeacherMarksEntry;

class MarksController
{
    public function index(Request $request, TeacherMarksEntry $entry): View
    {
        return view('teacher-panel::pages.marks.index', ['enabled' => $entry->enabled(), 'options' => collect($entry->options($request->user()))->groupBy('exam')]);
    }

    public function entry(Request $request, TeacherMarksEntry $entry, Exam $exam, SchoolClass $class, Section $section): View
    {
        abort_unless($section->class_id === $class->id, 404);

        // Same checks as the JSON endpoint: the grid call refuses a section or exam the teacher may not enter.
        $entry->grid($request->user(), $exam, $class->id, $section->id);

        return view('teacher-panel::pages.marks.entry', ['exam' => $exam, 'class' => $class, 'section' => $section]);
    }
}
