<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Fees\Http\Requests\FeeHeadRequest;
use Modules\Fees\Models\FeeHead;

class FeeHeadController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('fees.view'), 403);

        return response()->json(['data' => FeeHead::query()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(FeeHeadRequest $request): JsonResponse
    {
        return response()->json(['data' => FeeHead::create([...$request->validated(), 'sort_order' => $request->integer('sort_order')])], 201);
    }

    public function update(FeeHeadRequest $request, FeeHead $head): JsonResponse
    {
        $head->update([...$request->validated(), 'sort_order' => $request->integer('sort_order')]);

        return response()->json(['data' => $head]);
    }

    public function destroy(Request $request, FeeHead $head): JsonResponse
    {
        abort_unless($request->user()->can('fees.manage_structure'), 403);

        try {
            $head->delete();
        } catch (QueryException) {
            abort(422, __('fees::messages.head_in_use'));
        }

        return response()->json(null, 204);
    }
}
