<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Website\Database\Factories\WebsiteItemFactory;
use Modules\Website\Models\Concerns\FlushesSiteContent;

/**
 * A row of an editable list (programs, why-us points, schedule, ...), told apart by its collection.
 */
#[UseFactory(WebsiteItemFactory::class)]
#[Fillable(['collection', 'title_bn', 'title_en', 'body_bn', 'body_en', 'icon', 'meta', 'image', 'sort_order', 'is_active'])]
class WebsiteItem extends Model
{
    /** @use HasFactory<WebsiteItemFactory> */
    use FlushesSiteContent, HasFactory;

    /** @var list<string> */
    public const array COLLECTIONS = ['programs', 'phonetics', 'divisions', 'why', 'about_values', 'schedule', 'academics_programs', 'admission_docs'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['meta' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function title(): string
    {
        return (string) localized($this, 'title');
    }

    public function body(): string
    {
        return (string) localized($this, 'body');
    }

    /**
     * A meta value for the current locale (`{name}_{locale}`, then `{name}_bn`, then plain `{name}`).
     */
    public function metaText(string $name): ?string
    {
        $meta = (array) $this->meta;
        $primary = $meta[$name.'_'.app()->getLocale()] ?? null;

        if (filled($primary)) {
            return (string) $primary;
        }

        return (string) ($meta[$name.'_bn'] ?? $meta[$name] ?? '') ?: null;
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
