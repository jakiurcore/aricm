<?php

namespace Modules\Results\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\SchoolClass;
use Modules\Exams\Models\Exam;
use Modules\Results\Http\Requests\MarksRequest;
use Modules\Results\Services\MarksService;

class MarksController
{
    public function show(Request $request, Exam $exam, SchoolClass $class, MarksService $marks): JsonResponse
    {
        abort_unless($request->user()->can('results.enter_marks'), 403);

        return response()->json(['data' => $marks->grid($exam, $class->id, $request->integer('section_id') ?: null)]);
    }

    public function update(MarksRequest $request, Exam $exam, SchoolClass $class, MarksService $marks): JsonResponse
    {
        $saved = $marks->save($exam, $class->id, $request->validated('entries'), $request->user()->id);

        return response()->json(['data' => ['saved' => $saved]]);
    }
}
