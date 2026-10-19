<?php

namespace Modules\Core\Services;

use Illuminate\Support\Arr;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Facades\Modules;

/**
 * Collects every module's lang/{locale}/admin.php into one flat map for the React admin.
 * Keys look like "accounts::admin.users.title", the same as the PHP side.
 */
class JsTranslations
{
    private const string FILE = 'admin';

    /**
     * @return array<string, string>
     */
    public function for(string $locale): array
    {
        $fallback = config('app.fallback_locale');
        $messages = [];

        foreach (array_unique([$fallback, $locale]) as $code) {
            foreach (Modules::providing(ProvidesTranslations::class) as $module) {
                $file = Modules::path($module, $module->translations())."/{$code}/".self::FILE.'.php';

                if (! is_file($file)) {
                    continue;
                }

                foreach (Arr::dot(require $file) as $key => $value) {
                    $messages["{$module->name()}::".self::FILE.".{$key}"] = (string) $value;
                }
            }
        }

        return $messages;
    }
}
