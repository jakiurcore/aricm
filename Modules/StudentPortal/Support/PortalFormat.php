<?php

namespace Modules\StudentPortal\Support;

use Carbon\CarbonInterface;
use Modules\Core\Support\Bangla;

/**
 * Small display formats the portal pages share, localised like the rest of the app (Bangla digits and names in bn).
 */
final class PortalFormat
{
    private const array WEEKDAYS_SHORT = ['রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহঃ', 'শুক্র', 'শনি'];

    private const array ORDINALS = [1 => 'ম', 2 => 'য়', 3 => 'য়', 4 => 'র্থ', 5 => 'ম', 6 => 'ষ্ঠ', 7 => 'ম', 8 => 'ম', 9 => 'ম', 10 => 'ম'];

    /**
     * "১৮ অক্টোবর" / "18 Oct".
     */
    public static function dayMonth(CarbonInterface $date): string
    {
        return Bangla::isBangla() ? Bangla::digits($date->day).' '.Bangla::monthName($date) : $date->format('j M');
    }

    /**
     * "রবি" / "Sun".
     */
    public static function weekdayShort(CarbonInterface $date): string
    {
        return Bangla::isBangla() ? self::WEEKDAYS_SHORT[$date->dayOfWeek] : $date->format('D');
    }

    /**
     * "২য়" / "2nd".
     */
    public static function ordinal(int $number): string
    {
        if (Bangla::isBangla()) {
            return Bangla::digits($number).(self::ORDINALS[$number] ?? 'তম');
        }

        $suffix = in_array($number % 100, [11, 12, 13], true) ? 'th' : ([1 => 'st', 2 => 'nd', 3 => 'rd'][$number % 10] ?? 'th');

        return $number.$suffix;
    }

    /**
     * "৩:৩০ PM" / "3:30 PM".
     */
    public static function time(CarbonInterface $time): string
    {
        return Bangla::digits($time->format('g:i')).' '.$time->format('A');
    }
}
