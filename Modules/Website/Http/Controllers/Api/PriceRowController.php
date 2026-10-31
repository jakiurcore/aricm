<?php

namespace Modules\Website\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Website\Http\Requests\PriceRowRequest;
use Modules\Website\Http\Requests\ReorderRequest;
use Modules\Website\Http\Resources\WebsiteItemResource;
use Modules\Website\Http\Resources\WebsitePriceRowResource;
use Modules\Website\Models\WebsitePriceRow;
use Modules\Website\Services\SiteContent;

class PriceRowController
{
    public function index(Request $request, SiteContent $content): JsonResponse
    {
        abort_unless($request->user()->can('website.view'), 403);

        return response()->json($this->payload($content));
    }

    public function store(PriceRowRequest $request): JsonResponse
    {
        $data = $this->attributes($request);
        $data['sort_order'] = (int) WebsitePriceRow::query()->max('sort_order') + 1;

        return (new WebsitePriceRowResource(WebsitePriceRow::create($data)))->response()->setStatusCode(201);
    }

    public function update(PriceRowRequest $request, WebsitePriceRow $priceRow): WebsitePriceRowResource
    {
        $priceRow->update($this->attributes($request));

        return new WebsitePriceRowResource($priceRow);
    }

    public function destroy(Request $request, WebsitePriceRow $priceRow): JsonResponse
    {
        abort_unless($request->user()->can('website.manage'), 403);

        $priceRow->delete();

        return response()->json(null, 204);
    }

    public function reorder(ReorderRequest $request, SiteContent $content): JsonResponse
    {
        foreach (array_values($request->input('ids')) as $position => $id) {
            WebsitePriceRow::query()->whereKey($id)->update(['sort_order' => $position + 1]);
        }

        $content->flush();

        return response()->json($this->payload($content));
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(SiteContent $content): array
    {
        return [
            'data' => WebsitePriceRowResource::collection(WebsitePriceRow::query()->orderBy('sort_order')->orderBy('id')->get())->resolve(),
            'columns' => WebsiteItemResource::collection($content->priceColumns()->values())->resolve(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(PriceRowRequest $request): array
    {
        $data = $request->safe()->except('prices');
        $data['prices'] = array_map('intval', array_filter((array) $request->input('prices'), fn (mixed $price): bool => $price !== null && $price !== ''));

        return $data;
    }
}
