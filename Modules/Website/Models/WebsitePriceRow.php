<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Modules\Website\Models\Concerns\FlushesSiteContent;

/**
 * A fee line of the public price table; `prices` maps a division key (kids, junior, ...) to whole taka.
 */
#[Fillable(['label_bn', 'label_en', 'note_bn', 'note_en', 'prices', 'sort_order'])]
class WebsitePriceRow extends Model
{
    use FlushesSiteContent;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['prices' => 'array', 'sort_order' => 'integer'];
    }

    public function label(): string
    {
        return (string) localized($this, 'label');
    }

    public function note(): ?string
    {
        return localized($this, 'note');
    }

    public function price(string $divisionKey): ?int
    {
        $value = ($this->prices ?? [])[$divisionKey] ?? null;

        return $value === null ? null : (int) $value;
    }
}
