<?php

namespace Modules\Website\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Website\Models\WebsiteItem;

/**
 * @mixin WebsiteItem
 */
class WebsiteItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'collection' => $this->collection,
            'title_bn' => $this->title_bn, 'title_en' => $this->title_en,
            'body_bn' => $this->body_bn, 'body_en' => $this->body_en,
            'icon' => $this->icon, 'meta' => $this->meta ?? (object) [],
            'image_url' => $this->imageUrl(),
            'sort_order' => $this->sort_order, 'is_active' => $this->is_active,
        ];
    }
}
