<?php

namespace Modules\Diary\Services;

use Carbon\CarbonInterface;
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

    /**
     * Until when a teacher may still edit an entry published at the given time; null means "no limit".
     */
    public function editDeadline(CarbonInterface $publishedAt): ?CarbonInterface
    {
        return match ((string) $this->get('edit_window')) {
            'none' => null,
            'same' => $publishedAt->copy()->endOfDay(),
            '2h' => $publishedAt->copy()->addHours(2),
            'next9' => $publishedAt->copy()->addDay()->setTime(9, 0),
            default => $publishedAt->copy()->addHours($this->editWindowHours()),
        };
    }

    public function studentSeesFuture(): bool
    {
        return (bool) $this->get('student_sees_future');
    }

    public function maxAttachmentKb(): int
    {
        return max(1, (int) $this->get('max_attachment_mb')) * 1024;
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
