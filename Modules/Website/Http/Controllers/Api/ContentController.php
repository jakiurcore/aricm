<?php

namespace Modules\Website\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\ImageUploader;
use Modules\Core\Support\SafeHtml;
use Modules\Website\Http\Requests\ImageRequest;
use Modules\Website\Http\Requests\UpdateContentRequest;
use Modules\Website\Http\Resources\ContentEntryResource;
use Modules\Website\Models\WebsiteContent;
use Modules\Website\Services\ContentSchema;
use Modules\Website\Services\SiteContent;

class ContentController
{
    public function show(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('website.view'), 403);

        return response()->json(['schema' => $this->entries()]);
    }

    public function update(UpdateContentRequest $request, SiteContent $content): JsonResponse
    {
        foreach ((array) $request->input('values') as $key => $value) {
            $row = WebsiteContent::query()->firstOrNew(['key' => $key]);

            foreach (['bn', 'en'] as $locale) {
                $text = SafeHtml::clean(is_array($value) ? ($value[$locale] ?? null) : null);
                $row->{'value_'.$locale} = $text === '' ? null : $text;
            }

            $this->saveOrForget($row);
        }

        $content->flush();

        return response()->json(['schema' => $this->entries()]);
    }

    public function uploadImage(ImageRequest $request, ImageUploader $uploader, SiteContent $content, string $key): JsonResponse
    {
        $this->imageEntryOrFail($key);

        $row = WebsiteContent::query()->firstOrNew(['key' => $key]);
        $old = $row->image;
        $row->image = $uploader->store($request->file('image'), 'website', 1800);
        $row->save();
        $uploader->delete($old);
        $content->flush();

        return response()->json(['schema' => $this->entries()]);
    }

    public function resetImage(Request $request, ImageUploader $uploader, SiteContent $content, string $key): JsonResponse
    {
        abort_unless($request->user()->can('website.manage'), 403);
        $this->imageEntryOrFail($key);

        if ($row = WebsiteContent::query()->where('key', $key)->first()) {
            $uploader->delete($row->image);
            $row->image = null;
            $this->saveOrForget($row);
        }

        $content->flush();

        return response()->json(['schema' => $this->entries()]);
    }

    /**
     * Drops a row that no longer overrides anything, so the default shows again.
     */
    protected function saveOrForget(WebsiteContent $row): void
    {
        if ($row->value_bn === null && $row->value_en === null && $row->image === null) {
            $row->exists && $row->delete();

            return;
        }

        $row->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function imageEntryOrFail(string $key): array
    {
        $entry = ContentSchema::find($key);
        abort_unless($entry !== null && $entry['type'] === 'image', 404);

        return $entry;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function entries(): array
    {
        $rows = WebsiteContent::query()->get()->keyBy('key');

        return array_map(fn (array $entry): array => (new ContentEntryResource($entry, $rows->get($entry['key'])))->resolve(), ContentSchema::all());
    }
}
