<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Academic\Services\AcademicDirectory;

class OptionsController
{
    /**
     * Sessions and classes with sections for filter dropdowns on other modules' screens.
     */
    public function __invoke(AcademicDirectory $directory): JsonResponse
    {
        return response()->json(['data' => $directory->options()]);
    }
}
