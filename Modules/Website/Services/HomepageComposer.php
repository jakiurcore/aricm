<?php

namespace Modules\Website\Services;

use Illuminate\Support\Collection;
use Laramod\Facades\Modules;
use Modules\Core\Contracts\ProvidesHomepageSections;
use Modules\Core\Support\HomepageSection;

/**
 * Builds the home page from whatever modules contribute through ProvidesHomepageSections, in sort order. Website never
 * imports those modules: a module that is not installed simply contributes nothing.
 */
class HomepageComposer
{
    /**
     * @return Collection<int, HomepageSection>
     */
    public function sections(): Collection
    {
        $sections = collect();

        foreach (Modules::providing(ProvidesHomepageSections::class) as $module) {
            $sections = $sections->merge($module->homepageSections());
        }

        return $sections->sortBy('sortOrder')->values();
    }
}
