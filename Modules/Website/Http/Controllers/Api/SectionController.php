<?php

namespace Modules\Website\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Website\Http\Requests\UpdateSectionsRequest;
use Modules\Website\Models\WebsiteSection;
use Modules\Website\Services\HomepageComposer;
use Modules\Website\Services\SiteContent;

class SectionController
{
    public function show(Request $request, HomepageComposer $composer, SiteContent $content): JsonResponse
    {
        abort_unless($request->user()->can('website.view'), 403);

        return response()->json(['data' => $this->payload($composer, $content)]);
    }

    public function update(UpdateSectionsRequest $request, HomepageComposer $composer, SiteContent $content): JsonResponse
    {
        foreach ($request->validated('sections') as $section) {
            WebsiteSection::query()->updateOrCreate(['key' => $section['key']], ['is_enabled' => $section['is_enabled'], 'sort_order' => $section['sort_order'] ?? null]);
        }

        $content->flush();

        return response()->json(['data' => $this->payload($composer, $content)]);
    }

    /**
     * @return list<array{key: string, view: string, label: string, default_order: int, is_enabled: bool, sort_order: int|null}>
     */
    protected function payload(HomepageComposer $composer, SiteContent $content): array
    {
        $state = $content->sectionState();

        return array_map(fn (array $section): array => [
            ...$section,
            'label' => trans()->has($section['label']) ? __($section['label']) : $section['view'],
            'is_enabled' => $state[$section['key']]['is_enabled'] ?? true,
            'sort_order' => $state[$section['key']]['sort_order'] ?? null,
        ], $composer->catalog());
    }
}
