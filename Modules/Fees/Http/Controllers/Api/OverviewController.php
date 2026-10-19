<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Fees\Services\DashboardOverview;

class OverviewController
{
    public function __invoke(Request $request, DashboardOverview $overview): JsonResponse
    {
        return response()->json(['data' => $overview->for($request->user())]);
    }
}
