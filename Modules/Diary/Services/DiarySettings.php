<?php

namespace Modules\Diary\Services;

use Modules\Core\Services\Settings;

/**
 * Typed access to the diary settings: Core settings table, "diary_" prefix, defaults from config('modules.diary.defaults').
 */
class DiarySettings
{
    public function __construct(protected Settings $settings) {}

    public function get(string $key): mixed
    {
        return $this->settings->get("diary_{$key}", config("modules.diary.defaults.{$key}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $values = [];

        foreach (array_keys(config('modules.diary.defaults')) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        $prefixed = [];

        foreach ($values as $key => $value) {
            if (array_key_exists($key, config('modules.diary.defaults'))) {
                $prefixed["diary_{$key}"] = $value;
            }
        }

        $this->settings->setMany($prefixed);
    }

    public function enabled(): bool
    {
        return (bool) $this->get('enabled');
    }

    public function editWindowHours(): int
    {
        return max(0, (int) $this->get('edit_window_hours'));
    }

    public function backdateDays(): int
    {
        return max(0, (int) $this->get('backdate_days'));
    }

    public function maxAttachments(): int
    {
        return max(0, (int) $this->get('max_attachments'));
    }
}
