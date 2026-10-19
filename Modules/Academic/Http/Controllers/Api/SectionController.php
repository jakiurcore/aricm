<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Academic\Http\Controllers\Api\Concerns\GuardsDeletion;
use Modules\Academic\Http\Requests\SectionRequest;
use Modules\Academic\Models\Section;

class SectionController
{
    use GuardsDeletion;

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Section::class);

        $sections = Section::query()
            ->with('schoolClass:id,name_bn,name_en')
            ->when($request->integer('class_id'), fn ($query, int $classId) => $query->where('class_id', $classId))
            ->orderBy('class_id')->orderBy('name_bn')
            ->get();

        return response()->json(['data' => $sections]);
    }

    public function store(SectionRequest $request): JsonResponse
    {
        return response()->json(['data' => Section::create($request->validated())], 201);
    }

    public function update(SectionRequest $request, Section $section): JsonResponse
    {
        $section->update($request->validated());

        return response()->json(['data' => $section]);
    }

    public function destroy(Section $section): JsonResponse
    {
        Gate::authorize('delete', $section);

        return $this->deleteUnlessInUse($section);
    }
}
