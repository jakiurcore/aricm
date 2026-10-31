<?php

namespace Modules\Website\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laramod\Facades\Modules;
use Modules\Core\Contracts\ProvidesHomepageSections;
use Modules\Core\Support\HomepageSection;

/**
 * Builds the home page from whatever modules contribute through ProvidesHomepageSections. Website never imports those
 * modules: a module that is not installed simply contributes nothing. The client's overrides (website_sections) switch
 * a block off or move it; without an override a block keeps its own sort order.
 */
class HomepageComposer
{
    public function __construct(protected SiteContent $content) {}

    /**
     * The blocks to render, hidden ones removed, in display order.
     *
     * @return Collection<int, HomepageSection>
     */
    public function sections(): Collection
    {
        $state = $this->content->sectionState();

        return $this->all()
            ->reject(fn (HomepageSection $section): bool => ($state[$section->view]['is_enabled'] ?? true) === false)
            ->sortBy(fn (HomepageSection $section): int => $state[$section->view]['sort_order'] ?? $section->sortOrder)
            ->values();
    }

    /**
     * Every block the site can show, for the admin's section list.
     *
     * @return list<array{key: string, view: string, label: string, default_order: int}>
     */
    public function catalog(): array
    {
        return $this->all()->sortBy('sortOrder')->map(fn (HomepageSection $section): array => [
            'key' => $section->view,
            'view' => $section->view,
            'label' => 'website::admin.sections.'.self::slug($section->view),
            'default_order' => $section->sortOrder,
        ])->values()->all();
    }

    /**
     * "website::sections.hero" becomes "website_hero", "admission::home.banner" becomes "admission_banner".
     */
    public static function slug(string $view): string
    {
        [$namespace, $path] = str_contains($view, '::') ? explode('::', $view, 2) : ['', $view];

        return trim($namespace.'_'.Str::afterLast($path, '.'), '_');
    }

    /**
     * @return Collection<int, HomepageSection>
     */
    protected function all(): Collection
    {
        $sections = collect();

        foreach (Modules::providing(ProvidesHomepageSections::class) as $module) {
            $sections = $sections->merge($module->homepageSections());
        }

        return $sections->values();
    }
}
