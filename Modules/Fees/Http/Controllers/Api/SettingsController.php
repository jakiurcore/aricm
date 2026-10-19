<?php

namespace Modules\Fees\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Fees\Http\Requests\FeeSettingsRequest;
use Modules\Fees\Services\FeeReports;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Support\Money;

class SettingsController
{
    public function show(Request $request, FeeSettings $settings): JsonResponse
    {
        abort_unless($request->user()->can('fees.settings') || $request->user()->can('fees.view'), 403);

        return response()->json(['data' => $this->present($settings)]);
    }

    public function update(FeeSettingsRequest $request, FeeSettings $settings): JsonResponse
    {
        $values = $request->validated();
        $values['late_fine_amount'] = Money::taka($values['late_fine_amount'])->poisha;

        $settings->update($values);

        activity('fees')->causedBy($request->user())->withProperties(['keys' => array_keys($values)])->log('settings updated');

        return response()->json(['data' => $this->present($settings)]);
    }

    public function dashboard(Request $request, FeeReports $reports): JsonResponse
    {
        abort_unless($request->user()->can('fees.view'), 403);

        return response()->json(['data' => $reports->dashboard()]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(FeeSettings $settings): array
    {
        return $settings->all();
    }
}
