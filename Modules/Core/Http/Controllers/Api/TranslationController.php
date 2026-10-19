<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\Services\JsTranslations;

class TranslationController
{
    public function __invoke(JsTranslations $translations): JsonResponse
    {
        $locale = app()->getLocale();

        return response()->json([
            'locale' => $locale,
            'messages' => $translations->for($locale),
        ]);
    }
}
