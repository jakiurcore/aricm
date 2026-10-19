<?php

namespace Modules\TeacherPanel\Support;

use Carbon\CarbonInterface;
use Modules\Core\Support\Bangla;

/**
 * Small display helpers shared by the panel pages: times, "class – section" labels and the day/month date tile.
 */
final class PanelFormat
{
    /**
     * "১২:১৫ PM" in Bangla, "12:15 PM" in English.
     */
    public static function time(?CarbonInterface $time): string
    {
        return $time === null ? '' : Bangla::digits($time->format('g:i')).' '.$time->format('A');
    }

    /**
     * "৫ম শ্রেণি – ক শাখা": the class and section as one label.
     */
    public static function section(string $className, string $sectionName): string
    {
        return __('teacher-panel::messages.section_label', ['class' => $className, 'section' => $sectionName]);
    }

    /**
     * @return array{day: string, month: string}
     */
    public static function dayMonth(CarbonInterface $date): array
    {
        return ['day' => Bangla::digits($date->day), 'month' => Bangla::monthName($date)];
    }

    /**
     * "রবিবার, ১৮ অক্টোবর ২০২৬".
     */
    public static function fullDate(CarbonInterface $date): string
    {
        return Bangla::weekday($date).', '.Bangla::date($date);
    }

    /**
     * "১৮ অক্টোবর".
     */
    public static function shortDate(CarbonInterface $date): string
    {
        return Bangla::digits($date->day).' '.Bangla::monthName($date);
    }

    /**
     * The first letter of a name, for the round avatars.
     */
    public static function initial(string $name): string
    {
        return mb_strtoupper(mb_substr(trim($name), 0, 1));
    }
}
