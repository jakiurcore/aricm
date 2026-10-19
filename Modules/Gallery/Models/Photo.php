<?php

namespace Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['album_id', 'image', 'thumbnail', 'caption_bn', 'caption_en', 'sort_order'])]
class Photo extends Model
{
    protected $table = 'gallery_photos';

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->image);
    }

    public function thumbnailUrl(): string
    {
        return Storage::disk('public')->url($this->thumbnail);
    }

    public function caption(): ?string
    {
        return localized($this, 'caption');
    }
}
