<?php

namespace Modules\Website\Services;

use Modules\Academic\Models\SchoolClass;
use Modules\Students\Services\StudentDirectory;
use Modules\Teachers\Models\Teacher;

/**
 * Figures and copy used by Website's own home sections.
 */
class HomeContent
{
    public function __construct(protected StudentDirectory $students) {}

    /**
     * @return list<array{value: string, label: string}>
     */
    public function stats(): array
    {
        $years = max(1, (int) now()->format('Y') - (int) setting('established_year', now()->format('Y')) + 1);

        return [
            ['value' => bn_number($this->students->activeCount()), 'label' => __('website::messages.stats.students')],
            ['value' => bn_number(Teacher::query()->where('is_active', true)->count()), 'label' => __('website::messages.stats.teachers')],
            ['value' => bn_number(SchoolClass::query()->where('is_active', true)->count()), 'label' => __('website::messages.stats.classes')],
            ['value' => bn_number($years), 'label' => __('website::messages.stats.years')],
        ];
    }

    public function principalMessage(): string
    {
        $locale = app()->getLocale();

        return (string) (setting("principal_message_{$locale}") ?: setting('principal_message_bn') ?: __('website::messages.principal.default_message'));
    }

    public function principalName(): string
    {
        $locale = app()->getLocale();

        return (string) (setting("principal_name_{$locale}") ?: setting('principal_name_bn') ?: __('website::messages.principal.default_name'));
    }

    /**
     * @return array{embed: string, link: string}
     */
    public function map(): array
    {
        $lat = (float) (setting('map_lat') ?: config('modules.website.map.lat'));
        $lng = (float) (setting('map_lng') ?: config('modules.website.map.lng'));
        $box = sprintf('%F,%F,%F,%F', $lng - 0.01, $lat - 0.006, $lng + 0.01, $lat + 0.006);

        return [
            'embed' => "https://www.openstreetmap.org/export/embed.html?bbox={$box}&layer=mapnik&marker={$lat},{$lng}",
            'link' => "https://www.openstreetmap.org/?mlat={$lat}&mlon={$lng}#map=16/{$lat}/{$lng}",
        ];
    }
}
