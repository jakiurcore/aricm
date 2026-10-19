<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Academic\Http\Requests\ClassSubjectRequest;
use Modules\Academic\Models\SchoolClass;

class ClassSubjectController
{
    public function show(SchoolClass $class): JsonResponse
    {
        Gate::authorize('view', $class);

        $mapped = $class->subjects->map(fn ($subject): array => [
            'subject_id' => $subject->id,
            'is_optional' => (bool) $subject->pivot->is_optional,
        ]);

        return response()->json(['data' => $mapped->values()]);
    }

    public function update(ClassSubjectRequest $request, SchoolClass $class): JsonResponse
    {
        $sync = [];

        foreach ($request->validated('subjects') as $index => $row) {
            $sync[$row['subject_id']] = ['is_optional' => (bool) ($row['is_optional'] ?? false), 'sort_order' => $index + 1];
        }

        $class->subjects()->sync($sync);

        return $this->show($class->refresh());
    }
}
