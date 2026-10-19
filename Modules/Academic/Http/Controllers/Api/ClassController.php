<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Academic\Http\Controllers\Api\Concerns\GuardsDeletion;
use Modules\Academic\Http\Requests\ClassRequest;
use Modules\Academic\Models\SchoolClass;

class ClassController
{
    use GuardsDeletion;

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', SchoolClass::class);

        $classes = SchoolClass::query()->withCount(['sections', 'subjects'])->orderBy('numeric_order')->get();

        return response()->json(['data' => $classes]);
    }

    public function store(ClassRequest $request): JsonResponse
    {
        return response()->json(['data' => SchoolClass::create($request->validated())], 201);
    }

    public function update(ClassRequest $request, SchoolClass $class): JsonResponse
    {
        $class->update($request->validated());

        return response()->json(['data' => $class]);
    }

    public function destroy(SchoolClass $class): JsonResponse
    {
        Gate::authorize('delete', $class);

        return $this->deleteUnlessInUse($class);
    }
}
