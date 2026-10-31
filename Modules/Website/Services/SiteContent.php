<?php

namespace Modules\Website\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Modules\Website\Models\WebsiteContent;
use Modules\Website\Models\WebsiteItem;
use Modules\Website\Models\WebsitePriceRow;
use Modules\Website\Models\WebsiteSection;

/**
 * What the public views print: an editable value from the database when the client set one, else the lang default
 * (images: the committed file). Every row is cached forever under one key and busted by flush() on any write.
 */
#[Singleton]
class SiteContent
{
    private const string CACHE_KEY = 'website.content';

    /** @var array{contents: array<string, array{value_bn: ?string, value_en: ?string, image: ?string}>, items: Collection<int, WebsiteItem>, price_rows: Collection<int, WebsitePriceRow>, sections: array<string, array{is_enabled: bool, sort_order: ?int}>}|null */
    private ?array $memo = null;

    public function __construct(protected Cache $cache) {}

    /**
     * Text for the current locale: the database value for the locale, else the Bangla one, else the lang default.
     * `:placeholder` tokens are replaced from $replace in either source.
     *
     * @param  array<string, scalar|null>  $replace
     */
    public function text(string $key, array $replace = []): string
    {
        $row = $this->data()['contents'][$key] ?? null;

        if ($row !== null) {
            $value = $row['value_'.app()->getLocale()] ?? null;
            $value = filled($value) ? $value : ($row['value_bn'] ?? null);

            if (filled($value)) {
                return $this->replacePlaceholders((string) $value, $replace);
            }
        }

        $lang = ContentSchema::find($key)['default_lang'] ?? 'website::messages.'.$key;

        return (string) __($lang, $replace);
    }

    /**
     * Public URL of an image: the uploaded file, else the committed default.
     */
    public function image(string $key): string
    {
        $path = $this->data()['contents'][$key]['image'] ?? null;

        if (filled($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset(ContentSchema::find($key)['default'] ?? $key);
    }

    /**
     * Active rows of a list, in display order.
     *
     * @return Collection<int, WebsiteItem>
     */
    public function items(string $collection): Collection
    {
        return $this->data()['items']->where('collection', $collection)->values();
    }

    /**
     * @return Collection<int, WebsitePriceRow>
     */
    public function priceRows(): Collection
    {
        return $this->data()['price_rows'];
    }

    /**
     * The price table's columns: the active divisions, keyed by their meta key.
     *
     * @return Collection<string, WebsiteItem>
     */
    public function priceColumns(): Collection
    {
        return $this->items('divisions')->filter(fn (WebsiteItem $item): bool => filled($item->meta['key'] ?? null))->keyBy(fn (WebsiteItem $item): string => (string) $item->meta['key']);
    }

    /**
     * Home section overrides keyed by view name.
     *
     * @return array<string, array{is_enabled: bool, sort_order: ?int}>
     */
    public function sectionState(): array
    {
        return $this->data()['sections'];
    }

    public function flush(): void
    {
        $this->memo = null;
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, scalar|null>  $replace
     */
    protected function replacePlaceholders(string $value, array $replace): string
    {
        $pairs = [];

        foreach ($replace as $name => $replacement) {
            $pairs[':'.$name] = (string) $replacement;
            $pairs[':'.strtoupper((string) $name)] = strtoupper((string) $replacement);
            $pairs[':'.ucfirst((string) $name)] = ucfirst((string) $replacement);
        }

        return strtr($value, $pairs);
    }

    /**
     * @return array{contents: array<string, array{value_bn: ?string, value_en: ?string, image: ?string}>, items: Collection<int, WebsiteItem>, price_rows: Collection<int, WebsitePriceRow>, sections: array<string, array{is_enabled: bool, sort_order: ?int}>}
     */
    protected function data(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $raw = $this->cache->rememberForever(self::CACHE_KEY, fn (): array => [
            'contents' => WebsiteContent::query()->get()->mapWithKeys(fn (WebsiteContent $row): array => [$row->key => ['value_bn' => $row->value_bn, 'value_en' => $row->value_en, 'image' => $row->image]])->all(),
            'items' => WebsiteItem::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()->map(fn (WebsiteItem $row): array => $row->getAttributes())->all(),
            'price_rows' => WebsitePriceRow::query()->orderBy('sort_order')->orderBy('id')->get()->map(fn (WebsitePriceRow $row): array => $row->getAttributes())->all(),
            'sections' => WebsiteSection::query()->get()->mapWithKeys(fn (WebsiteSection $row): array => [$row->key => ['is_enabled' => $row->is_enabled, 'sort_order' => $row->sort_order]])->all(),
        ]);

        // Only plain arrays are cached: the file store refuses to unserialize model objects (cache.serializable_classes).
        return $this->memo = [
            'contents' => $raw['contents'],
            'items' => WebsiteItem::hydrate($raw['items'])->toBase(),
            'price_rows' => WebsitePriceRow::hydrate($raw['price_rows'])->toBase(),
            'sections' => $raw['sections'],
        ];
    }
}
