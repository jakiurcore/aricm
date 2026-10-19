<?php

namespace Modules\Notices\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Notices\Database\Factories\NoticeFactory;
use Modules\Notices\Enums\NoticeCategory;

#[UseFactory(NoticeFactory::class)]
#[Fillable(['title_bn', 'title_en', 'body_bn', 'body_en', 'category', 'attachment', 'attachment_name', 'is_pinned', 'show_in_ticker', 'publish_at', 'is_published'])]
class Notice extends Model
{
    /** @use HasFactory<NoticeFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['category' => NoticeCategory::class, 'is_pinned' => 'boolean', 'show_in_ticker' => 'boolean', 'is_published' => 'boolean', 'publish_at' => 'datetime'];
    }

    /**
     * What the public may see: published, and not scheduled for later.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_published', true)->where(fn (Builder $inner) => $inner->whereNull('publish_at')->orWhere('publish_at', '<=', now()));
    }

    protected function attachmentUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->attachment ? Storage::disk('public')->url($this->attachment) : null);
    }

    public function isVisible(): bool
    {
        return $this->is_published && ($this->publish_at === null || $this->publish_at->lte(now()));
    }

    public function title(): string
    {
        return localized($this, 'title') ?? $this->title_bn;
    }

    public function body(): string
    {
        return localized($this, 'body') ?? $this->body_bn;
    }

    public function shownAt(): CarbonInterface
    {
        return $this->publish_at ?? $this->created_at;
    }
}
