<?php

namespace Modules\Gallery\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\Gallery\Database\Factories\AlbumFactory;

#[UseFactory(AlbumFactory::class)]
#[Fillable(['title_bn', 'title_en', 'description_bn', 'description_en', 'album_date', 'is_published', 'sort_order', 'cover_photo_id'])]
class Album extends Model
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;

    protected $table = 'gallery_albums';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'album_date' => 'date', 'sort_order' => 'integer'];
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->orderBy('sort_order')->orderBy('id');
    }

    public function title(): string
    {
        return localized($this, 'title') ?? $this->title_bn;
    }

    public function description(): ?string
    {
        return localized($this, 'description');
    }

    /**
     * The chosen cover, else the first photo.
     */
    public function coverUrl(): ?string
    {
        $photo = $this->cover_photo_id ? Photo::query()->find($this->cover_photo_id) : null;
        $photo ??= $this->photos()->first();

        return $photo ? Storage::disk('public')->url($photo->thumbnail) : null;
    }
}
