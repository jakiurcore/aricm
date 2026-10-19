<?php

namespace Modules\Results\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Models\SchoolClass;
use Modules\Academic\Models\Section;
use Modules\Exams\Models\Exam;
use Modules\Results\Http\Requests\TeacherMarksRequest;
use Modules\Results\Services\TeacherMarksEntry;

/**
 * Teacher marks entry (JSON). The page that uses it lives in the TeacherPanel module.
 */
class TeacherMarksController
{
    public function options(TeacherMarksRequest $request, TeacherMarksEntry $entry): JsonResponse
    {
        return response()->json(['data' => ['enabled' => $entry->enabled(), 'options' => $entry->options($request->user())]]);
    }

    public function show(TeacherMarksRequest $request, TeacherMarksEntry $entry, Exam $exam, SchoolClass $class, Section $section): JsonResponse
    {
        abort_unless($section->class_id === $class->id, 404);

        return response()->json(['data' => $entry->grid($request->user(), $exam, $class->id, $section->id)]);
    }

    public function update(TeacherMarksRequest $request, TeacherMarksEntry $entry, Exam $exam, SchoolClass $class, Section $section): JsonResponse
    {
        abort_unless($section->class_id === $class->id, 404);

        return response()->json(['data' => ['saved' => $entry->save($request->user(), $exam, $class->id, $section->id, $request->validated('entries') ?? [])]]);
    }
}
