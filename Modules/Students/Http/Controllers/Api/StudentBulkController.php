<?php

namespace Modules\Students\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Students\Http\Requests\ImportRequest;
use Modules\Students\Http\Requests\PromoteRequest;
use Modules\Students\Models\Student;
use Modules\Students\Services\PromotionService;
use Modules\Students\Services\StudentDirectory;
use Modules\Students\Services\StudentExporter;
use Modules\Students\Services\StudentImporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentBulkController
{
    public function import(ImportRequest $request, StudentImporter $importer): JsonResponse
    {
        $result = $importer->import($request->file('file')->getRealPath(), $request->boolean('dry_run'));

        return response()->json(['data' => $result->toArray()], $result->errors === [] ? 200 : 422);
    }

    public function export(Request $request, StudentDirectory $directory, StudentExporter $exporter): StreamedResponse
    {
        Gate::authorize('viewAny', Student::class);

        $query = $directory->query($request->only(['session_id', 'class_id', 'section_id', 'status', 'search']));

        return response()->streamDownload(fn () => $exporter->write($query, fopen('php://output', 'w')), 'students-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function template(StudentExporter $exporter): StreamedResponse
    {
        Gate::authorize('import', Student::class);

        return response()->streamDownload(fn () => $exporter->writeTemplate(fopen('php://output', 'w')), 'students-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function promote(PromoteRequest $request, PromotionService $promotion): JsonResponse
    {
        $count = $promotion->promote(
            $request->validated('student_ids'),
            $request->validated('session_id'),
            $request->validated('class_id'),
            $request->validated('section_id'),
            $request->validated('roll_mode'),
        );

        return response()->json(['data' => ['promoted' => $count]]);
    }
}
