<?php

namespace Modules\Results\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Academic\Models\SchoolClass;
use Modules\Exams\Models\Exam;
use Modules\Results\Models\ExamResult;
use Modules\Results\Services\ResultDirectory;
use Modules\Results\Services\ResultProcessor;
use Modules\Results\Services\ResultPublisher;

class ProcessingController
{
    /**
     * Every exam with how far each of its classes has got: processed students out of the roster.
     */
    public function overview(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('results.view'), 403);

        $processed = ExamResult::query()->selectRaw('exam_id, class_id, count(*) as students, sum(is_incomplete) as incomplete, sum(is_passed) as passed')->groupBy('exam_id', 'class_id')->get()->groupBy('exam_id');

        $exams = Exam::query()->with(['classes', 'session'])->orderByDesc('start_date')->orderByDesc('id')->limit(30)->get()->map(fn (Exam $exam): array => [
            'id' => $exam->id,
            'name' => $exam->displayName(),
            'session' => $exam->session->name,
            'status' => $exam->status->value,
            'published' => (bool) $exam->result_published,
            'classes' => $exam->classes->map(function ($class) use ($processed, $exam): array {
                $row = $processed->get($exam->id)?->firstWhere('class_id', $class->id);

                return ['id' => $class->id, 'name' => $class->displayName(), 'processed' => (int) ($row->students ?? 0), 'passed' => (int) ($row->passed ?? 0), 'incomplete' => (int) ($row->incomplete ?? 0)];
            })->values(),
        ]);

        return response()->json(['data' => $exams]);
    }

    public function process(Request $request, Exam $exam, ResultProcessor $processor): JsonResponse
    {
        abort_unless($request->user()->can('results.process'), 403);

        $data = $request->validate(['class_id' => ['nullable', 'integer']]);
        $classIds = isset($data['class_id']) ? [(int) $data['class_id']] : $exam->classes()->pluck('classes.id')->all();

        return response()->json(['data' => array_map(fn (int $classId): array => $processor->process($exam, $classId), $classIds)]);
    }

    public function preview(Request $request, Exam $exam, SchoolClass $class, ResultDirectory $results): JsonResponse
    {
        abort_unless($request->user()->can('results.view'), 403);

        $rows = $results->classResults($exam, $class->id, $request->integer('section_id') ?: null)->map(fn (ExamResult $result): array => [
            'id' => $result->id,
            'student_id' => $result->student_id,
            'code' => $result->student->student_id,
            'name' => $result->student->name_bn,
            'roll' => $result->roll,
            'section' => $result->section?->displayName(),
            'total_marks' => $result->total_marks,
            'full_marks' => $result->full_marks,
            'percentage' => $result->percentage,
            'gpa' => $result->gpa,
            'grade' => $result->grade,
            'is_passed' => $result->is_passed,
            'is_incomplete' => $result->is_incomplete,
            'position_in_class' => $result->position_in_class,
            'position_in_section' => $result->position_in_section,
            'failed_subjects_count' => $result->failed_subjects_count,
            'subjects' => $result->subject_results,
        ]);

        return response()->json(['data' => $rows->values(), 'published' => (bool) $exam->result_published]);
    }

    public function publish(Request $request, Exam $exam, ResultPublisher $publisher): JsonResponse
    {
        abort_unless($request->user()->can('results.publish'), 403);

        $publisher->publish($exam);

        return response()->json(['data' => ['published' => true]]);
    }

    public function unpublish(Request $request, Exam $exam, ResultPublisher $publisher): JsonResponse
    {
        abort_unless($request->user()->can('results.publish'), 403);

        $publisher->unpublish($exam);

        return response()->json(['data' => ['published' => false]]);
    }
}
