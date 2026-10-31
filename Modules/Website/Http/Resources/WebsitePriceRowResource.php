<?php

namespace Modules\Website\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Website\Models\WebsitePriceRow;

/**
 * @mixin WebsitePriceRow
 */
class WebsitePriceRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label_bn' => $this->label_bn, 'label_en' => $this->label_en,
            'note_bn' => $this->note_bn, 'note_en' => $this->note_en,
            'prices' => $this->prices ?? (object) [],
            'sort_order' => $this->sort_order,
        ];
    }
}
