<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Services\Settings;

class SettingsSeeder extends Seeder
{
    /**
     * Writes the institute defaults once; values an admin has already edited are kept.
     */
    public function run(Settings $settings): void
    {
        $existing = $settings->all();

        $defaults = collect(config('modules.core.settings'))
            ->reject(fn (mixed $value, string $key): bool => array_key_exists($key, $existing))
            ->all();

        $settings->setMany($defaults);
    }
}
