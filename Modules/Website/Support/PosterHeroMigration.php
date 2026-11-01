<?php

namespace Modules\Website\Support;

use Modules\Website\Models\WebsiteItem;

/**
 * The data side of the poster-hero change, kept out of the migration file so it can be tested: the `programs`
 * benefits move to the poster's wording only while they still equal the rows the site was first seeded with (so a
 * client's edits survive), and item icons stored as emoji become icon keys.
 */
final class PosterHeroMigration
{
    /**
     * The benefits seeded before the poster change: [title_bn, title_en] in order.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const array OLD_PROGRAMS = [
        ['মেধাশক্তি বৃদ্ধি', 'Boosts brain power'],
        ['মনোযোগ ও একাগ্রতা বৃদ্ধি', 'Improves focus and concentration'],
        ['দ্রুত গণনা ও সমস্যা সমাধানের দক্ষতা', 'Fast calculation and problem-solving'],
        ['আত্মবিশ্বাস ও সফলতালাভ বৃদ্ধি', 'Builds confidence and success'],
    ];

    /**
     * Runs both steps; returns what changed.
     *
     * @return array{programs_rewritten: bool, icons_converted: int}
     */
    public static function run(): array
    {
        return ['programs_rewritten' => self::rewritePrograms(), 'icons_converted' => self::convertIcons()];
    }

    /**
     * Replaces the four seeded benefits with the poster's, only when the collection is exactly the old defaults.
     */
    public static function rewritePrograms(): bool
    {
        $rows = WebsiteItem::query()->where('collection', 'programs')->orderBy('sort_order')->orderBy('id')->get();

        if ($rows->count() !== count(self::OLD_PROGRAMS)) {
            return false;
        }

        foreach ($rows as $index => $row) {
            [$bn, $en] = self::OLD_PROGRAMS[$index];

            if ($row->title_bn !== $bn || $row->title_en !== $en || filled($row->body_bn) || filled($row->body_en)) {
                return false;
            }
        }

        $new = ['bn' => (array) trans('website::messages.program.benefits', [], 'bn'), 'en' => (array) trans('website::messages.program.benefits', [], 'en')];

        foreach ($rows as $index => $row) {
            $row->update(['title_bn' => $new['bn'][$index], 'title_en' => $new['en'][$index]]);
        }

        return true;
    }

    /**
     * Turns a legacy emoji `icon` into its icon key on every row; keys and unknown values are left as they are.
     */
    public static function convertIcons(): int
    {
        $converted = 0;

        WebsiteItem::query()->whereNotNull('icon')->where('icon', '!=', '')->get()->each(function (WebsiteItem $item) use (&$converted): void {
            $key = core_icon_key($item->icon);

            if ($key !== null && $key !== $item->icon) {
                $item->update(['icon' => $key]);
                $converted++;
            }
        });

        return $converted;
    }
}
