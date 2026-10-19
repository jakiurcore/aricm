<?php

namespace Modules\Results\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Exams\Models\Exam;
use Modules\Results\Http\Requests\GradingScaleRequest;
use Modules\Results\Models\GradingScale;

class GradingScaleController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('results.view') || $request->user()->can('results.grading'), 403);

        return response()->json(['data' => GradingScale::query()->with('items')->orderBy('id')->get()]);
    }

    public function store(GradingScaleRequest $request): JsonResponse
    {
        $scale = $this->persist(new GradingScale, $request);

        return response()->json(['data' => $scale], 201);
    }

    public function update(GradingScaleRequest $request, GradingScale $scale): JsonResponse
    {
        return response()->json(['data' => $this->persist($scale, $request)]);
    }

    public function destroy(Request $request, GradingScale $scale): JsonResponse
    {
        abort_unless($request->user()->can('results.grading'), 403);
        abort_if($scale->is_default || Exam::query()->where('grading_scale_id', $scale->id)->exists(), 422, __('results::messages.scale_in_use'));

        $scale->delete();

        return response()->json(null, 204);
    }

    protected function persist(GradingScale $scale, GradingScaleRequest $request): GradingScale
    {
        return DB::transaction(function () use ($scale, $request): GradingScale {
            $makeDefault = $request->boolean('is_default') || ! GradingScale::query()->where('is_default', true)->whereKeyNot($scale->id)->exists();

            if ($makeDefault) {
                GradingScale::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $scale->fill(['name' => $request->input('name'), 'is_default' => $makeDefault])->save();
            $scale->items()->delete();

            foreach ($request->validated('items') as $item) {
                $scale->items()->create($item);
            }

            return $scale->load('items');
        });
    }
}
