<?php

namespace Modules\Website\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Core\Services\ImageUploader;
use Modules\Core\Support\SafeHtml;
use Modules\Website\Http\Requests\ItemRequest;
use Modules\Website\Http\Requests\ReorderRequest;
use Modules\Website\Http\Resources\WebsiteItemResource;
use Modules\Website\Models\WebsiteItem;
use Modules\Website\Services\SiteContent;

class ItemController
{
    public function index(Request $request, string $collection): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('website.view'), 403);

        return WebsiteItemResource::collection(WebsiteItem::query()->where('collection', $collection)->orderBy('sort_order')->orderBy('id')->get());
    }

    public function store(ItemRequest $request, ImageUploader $uploader, string $collection): JsonResponse
    {
        $data = $this->attributes($request, $uploader);
        $data['collection'] = $collection;
        $data['sort_order'] = (int) WebsiteItem::query()->where('collection', $collection)->max('sort_order') + 1;

        return (new WebsiteItemResource(WebsiteItem::create($data)))->response()->setStatusCode(201);
    }

    public function update(ItemRequest $request, ImageUploader $uploader, string $collection, string $item): WebsiteItemResource
    {
        $model = $this->find($collection, $item);
        $data = $this->attributes($request, $uploader);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $uploader->delete($model->image);
        } else {
            unset($data['image']);
        }

        $model->update($data);

        return new WebsiteItemResource($model);
    }

    public function destroy(Request $request, ImageUploader $uploader, string $collection, string $item): JsonResponse
    {
        abort_unless($request->user()->can('website.manage'), 403);

        $model = $this->find($collection, $item);
        $uploader->delete($model->image);
        $model->delete();

        return response()->json(null, 204);
    }

    public function reorder(ReorderRequest $request, SiteContent $content, string $collection): JsonResponse
    {
        foreach (array_values($request->input('ids')) as $position => $id) {
            WebsiteItem::query()->where('collection', $collection)->whereKey($id)->update(['sort_order' => $position + 1]);
        }

        $content->flush();

        return $this->index($request, $collection)->response();
    }

    protected function find(string $collection, string $id): WebsiteItem
    {
        return WebsiteItem::query()->where('collection', $collection)->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(ItemRequest $request, ImageUploader $uploader): array
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        foreach (['body_bn', 'body_en'] as $field) {
            $data[$field] = filled($data[$field] ?? null) ? SafeHtml::clean($data[$field]) : null;
        }

        $meta = (array) ($data['meta'] ?? []);

        foreach (['min', 'max'] as $number) {
            if (isset($meta[$number]) && is_numeric($meta[$number])) {
                $meta[$number] = (int) $meta[$number];
            }
        }

        $data['meta'] = $meta === [] ? null : $meta;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['image'] = $request->hasFile('image') ? $uploader->store($request->file('image'), 'website', 1800) : null;

        return $data;
    }
}
