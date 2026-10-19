<?php

namespace Modules\Notices\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Notices\Models\Notice;

/**
 * @mixin Notice
 */
class NoticeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_bn' => $this->title_bn, 'title_en' => $this->title_en,
            'body_bn' => $this->body_bn, 'body_en' => $this->body_en,
            'category' => $this->category->value,
            'attachment_url' => $this->attachment_url, 'attachment_name' => $this->attachment_name,
            'is_pinned' => $this->is_pinned, 'show_in_ticker' => $this->show_in_ticker, 'is_published' => $this->is_published,
            'publish_at' => $this->publish_at?->format('Y-m-d\TH:i'),
            'visible' => $this->isVisible(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
