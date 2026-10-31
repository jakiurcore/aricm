<?php

namespace Modules\Website\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\Website\Models\WebsiteContent;

/**
 * A ContentSchema entry together with the client's stored override (the resource is the schema entry array).
 *
 * @property array{key: string, page: string, group: string, type: string, label: string, default: string, default_lang: string|null} $resource
 */
class ContentEntryResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $resource
     */
    public function __construct($resource, protected ?WebsiteContent $row = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $entry = $this->resource;
        $isImage = $entry['type'] === 'image';
        $image = $this->row?->image;

        return [
            'key' => $entry['key'], 'page' => $entry['page'], 'group' => $entry['group'], 'type' => $entry['type'],
            'label' => $entry['label'],
            'value_bn' => $this->row?->value_bn, 'value_en' => $this->row?->value_en,
            'image_url' => $isImage ? ($image ? Storage::disk('public')->url($image) : asset($entry['default'])) : null,
            'has_image_override' => $isImage && filled($image),
            'default_bn' => $isImage ? null : (string) __($entry['default_lang'], [], 'bn'),
            'default_en' => $isImage ? null : (string) __($entry['default_lang'], [], 'en'),
        ];
    }
}
