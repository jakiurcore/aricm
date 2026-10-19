<?php

namespace Modules\Core\Services;

use Illuminate\Contracts\Cache\Repository as Cache;
use Modules\Core\Models\Setting;

/**
 * Key/value settings stored in the settings table. Values are JSON encoded, so arrays and booleans round-trip.
 */
class Settings
{
    private const string CACHE_KEY = 'core.settings';

    public function __construct(protected Cache $cache) {}

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->cache->rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')
                ->map(fn (?string $value): mixed => $value === null ? null : json_decode($value, true))
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }

        return config('modules.core.settings.'.$key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value === null ? null : json_encode($value, JSON_UNESCAPED_UNICODE)],
            );
        }

        $this->cache->forget(self::CACHE_KEY);
    }
}
