<?php

namespace Modules\Website\Support;

use Modules\Website\Models\WebsiteItem;
use Modules\Website\Models\WebsitePriceRow;

/**
 * The starting lists and prices, built from the committed lang files in both languages so the site looks the same
 * before and after the client's first edit.
 */
final class WebsiteDefaults
{
    private const array WHY_ICONS = ['📖', '🏫', '🎖', '🔤', '🧮', '🛡'];

    /**
     * Fills the item and price tables, each only while it is empty. Safe to run on every deploy.
     */
    public static function seed(): void
    {
        if (! WebsiteItem::query()->exists()) {
            foreach (self::items() as $row) {
                WebsiteItem::create($row);
            }
        }

        if (! WebsitePriceRow::query()->exists()) {
            foreach (self::priceRows() as $row) {
                WebsitePriceRow::create($row);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function items(): array
    {
        $rows = [];
        $add = function (string $collection, int $order, string $titleBn, string $titleEn, ?string $bodyBn = null, ?string $bodyEn = null, ?string $icon = null, ?array $meta = null) use (&$rows): void {
            $rows[] = [
                'collection' => $collection, 'title_bn' => $titleBn, 'title_en' => $titleEn, 'body_bn' => $bodyBn, 'body_en' => $bodyEn,
                'icon' => $icon, 'meta' => $meta, 'sort_order' => $order + 1, 'is_active' => true,
            ];
        };

        foreach (['programs' => 'program', 'phonetics' => 'phonetics'] as $collection => $group) {
            foreach (self::lang("website::messages.{$group}.benefits", 'bn') as $index => $title) {
                $add($collection, $index, $title, self::lang("website::messages.{$group}.benefits", 'en')[$index] ?? $title);
            }
        }

        foreach (self::divisions() as $index => [$key, $min, $max]) {
            $add('divisions', $index, self::lang("website::messages.divisions.{$key}", 'bn'), self::lang("website::messages.divisions.{$key}", 'en'),
                self::lang("website::messages.divisions.{$key}_body", 'bn'), self::lang("website::messages.divisions.{$key}_body", 'en'), null,
                ['key' => $key, 'age_bn' => self::lang("website::messages.divisions.{$key}_age", 'bn'), 'age_en' => self::lang("website::messages.divisions.{$key}_age", 'en'), 'min' => $min, 'max' => $max]);
        }

        foreach (self::lang('website::messages.why.items', 'bn') as $index => [$title, $body]) {
            [$titleEn, $bodyEn] = self::lang('website::messages.why.items', 'en')[$index] ?? [$title, $body];
            $add('why', $index, $title, $titleEn, $body, $bodyEn, self::WHY_ICONS[$index] ?? null);
        }

        foreach (self::lang('website::messages.about.values', 'bn') as $index => [$title, $body]) {
            [$titleEn, $bodyEn] = self::lang('website::messages.about.values', 'en')[$index] ?? [$title, $body];
            $add('about_values', $index, $title, $titleEn, $body, $bodyEn);
        }

        foreach (self::lang('website::messages.academics.schedule', 'bn') as $index => [$time, $what]) {
            [$timeEn, $whatEn] = self::lang('website::messages.academics.schedule', 'en')[$index] ?? [$time, $what];
            $add('schedule', $index, $what, $whatEn, null, null, null, ['time_bn' => $time, 'time_en' => $timeEn]);
        }

        foreach (self::lang('website::messages.academics.programs', 'bn') as $index => [$icon, $title, $body]) {
            [, $titleEn, $bodyEn] = self::lang('website::messages.academics.programs', 'en')[$index] ?? [$icon, $title, $body];
            $add('academics_programs', $index, $title, $titleEn, $body, $bodyEn, $icon);
        }

        foreach (self::lang('admission::messages.docs', 'bn') as $index => $title) {
            $add('admission_docs', $index, $title, self::lang('admission::messages.docs', 'en')[$index] ?? $title);
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function priceRows(): array
    {
        $rows = [];

        foreach (self::lang('admission::messages.fee_rows', 'bn') as $index => [$label, $kids, $junior]) {
            $rows[] = [
                'label_bn' => $label, 'label_en' => self::lang('admission::messages.fee_rows', 'en')[$index][0] ?? $label,
                'prices' => ['kids' => (int) $kids, 'junior' => (int) $junior], 'sort_order' => $index + 1,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private static function divisions(): array
    {
        return [['kids', 5, 11], ['junior', 12, 15]];
    }

    /**
     * @return array<mixed>|string
     */
    private static function lang(string $key, string $locale): array|string
    {
        return trans($key, [], $locale);
    }
}
