<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Academic\Models\Holiday;
use Modules\Academic\Services\SchoolCalendar;
use Modules\Core\Services\Settings;

class HolidayController
{
    public function index(Request $request, SchoolCalendar $calendar): JsonResponse
    {
        abort_unless($request->user()->can('academic.view'), 403);

        $year = $request->integer('year') ?: (int) now()->format('Y');

        return response()->json([
            'data' => Holiday::query()->whereYear('date', $year)->orderBy('date')->get(),
            'weekly' => $calendar->weeklyHolidays(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('academic.manage'), 403);

        $data = $request->validate(['date' => ['required', 'date', Rule::unique('school_calendar_holidays', 'date')], 'title_bn' => ['required', 'string', 'max:255'], 'title_en' => ['nullable', 'string', 'max:255']]);

        return response()->json(['data' => Holiday::create($data)], 201);
    }

    public function update(Request $request, Holiday $holiday): JsonResponse
    {
        abort_unless($request->user()->can('academic.manage'), 403);

        $holiday->update($request->validate(['date' => ['required', 'date', Rule::unique('school_calendar_holidays', 'date')->ignore($holiday)], 'title_bn' => ['required', 'string', 'max:255'], 'title_en' => ['nullable', 'string', 'max:255']]));

        return response()->json(['data' => $holiday]);
    }

    public function destroy(Request $request, Holiday $holiday): JsonResponse
    {
        abort_unless($request->user()->can('academic.manage'), 403);

        $holiday->delete();

        return response()->json(null, 204);
    }

    public function updateWeekly(Request $request, Settings $settings): JsonResponse
    {
        abort_unless($request->user()->can('academic.manage'), 403);

        $days = $request->validate(['days' => ['present', 'array'], 'days.*' => [Rule::in(SchoolCalendar::WEEKDAYS)]])['days'];
        $settings->set('academic_weekly_holidays', array_values(array_unique($days)));

        return response()->json(['data' => ['weekly' => $days]]);
    }
}
