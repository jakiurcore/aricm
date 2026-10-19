<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Academic\Http\Controllers\Api\Concerns\GuardsDeletion;
use Modules\Academic\Http\Requests\SubjectRequest;
use Modules\Academic\Models\Subject;

class SubjectController
{
    use GuardsDeletion;

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Subject::class);

        return response()->json(['data' => Subject::query()->orderBy('name_bn')->get()]);
    }

    public function store(SubjectRequest $request): JsonResponse
    {
        return response()->json(['data' => Subject::create($request->validated())], 201);
    }

    public function update(SubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject->update($request->validated());

        return response()->json(['data' => $subject]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        Gate::authorize('delete', $subject);

        return $this->deleteUnlessInUse($subject);
    }
}
