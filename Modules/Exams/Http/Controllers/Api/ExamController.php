<?php

namespace Modules\Exams\Http\Controllers\Api;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\Exams\Http\Requests\ExamRequest;
use Modules\Exams\Http\Resources\ExamResource;
use Modules\Exams\Models\Exam;
use Modules\Exams\Services\ExamConfigurator;

class ExamController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Exam::class);

        $exams = Exam::query()
            ->with(['session', 'classes'])
            ->when($request->integer('session_id'), fn ($query, int $id) => $query->where('session_id', $id))
            ->when($request->string('status')->toString(), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->string('search')->toString(), fn ($query, string $search) => $query->where(fn ($inner) => $inner->where('name_bn', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%")))
            ->orderByDesc('start_date')->orderByDesc('id')
            ->paginate(15);

        return ExamResource::collection($exams);
    }

    public function show(Exam $exam): ExamResource
    {
        Gate::authorize('view', $exam);

        return new ExamResource($exam->load(['session', 'classes']));
    }

    public function store(ExamRequest $request, ExamConfigurator $configurator): JsonResponse
    {
        $exam = Exam::create($request->safe()->except('class_ids'));
        $configurator->syncClasses($exam, $request->validated('class_ids', []));

        return (new ExamResource($exam->load(['session', 'classes'])))->response()->setStatusCode(201);
    }

    public function update(ExamRequest $request, Exam $exam, ExamConfigurator $configurator): ExamResource
    {
        $exam->update($request->safe()->except('class_ids'));

        if ($request->has('class_ids')) {
            $configurator->syncClasses($exam, $request->validated('class_ids', []));
        }

        return new ExamResource($exam->load(['session', 'classes']));
    }

    public function destroy(Exam $exam): JsonResponse
    {
        Gate::authorize('delete', $exam);

        try {
            $exam->delete();
        } catch (QueryException) {
            abort(422, __('exams::messages.in_use'));
        }

        return response()->json(null, 204);
    }
}
