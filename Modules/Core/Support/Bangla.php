<?php

namespace Modules\Core\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

final class Bangla
{
    private const array DIGITS = ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯'];

    private const array MONTHS = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

    private const array WEEKDAYS = ['রবিবার', 'সোমবার', 'মঙ্গলবার', 'বুধবার', 'বৃহস্পতিবার', 'শুক্রবার', 'শনিবার'];

    public static function isBangla(): bool
    {
        return app()->getLocale() === 'bn';
    }

    /**
     * Convert Latin digits to Bangla digits. Without $force this only happens in the bn locale.
     */
    public static function digits(string|int|float|null $value, ?bool $force = null): string
    {
        $text = (string) $value;

        return ($force ?? self::isBangla()) ? strtr($text, self::DIGITS) : $text;
    }

    /**
     * Format a number with fixed decimals, localising the digits.
     */
    public static function number(int|float|string|null $value, int $decimals = 0): string
    {
        return self::digits(number_format((float) $value, $decimals, '.', ''));
    }

    /**
     * Bangla long date such as "২১ অক্টোবর ২০২৬"; "21 Oct 2026" in English.
     */
    public static function date(DateTimeInterface|string|null $date, ?bool $force = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $date = Carbon::parse($date);

        if (! ($force ?? self::isBangla())) {
            return $date->format('d M Y');
        }

        return self::digits($date->day, true).' '.self::MONTHS[$date->month - 1].' '.self::digits($date->year, true);
    }

    public static function monthName(CarbonInterface|int $month, ?bool $force = null): string
    {
        $number = $month instanceof CarbonInterface ? $month->month : $month;

        return ($force ?? self::isBangla())
            ? self::MONTHS[$number - 1]
            : Carbon::create(2000, $number, 1)->format('F');
    }

    /**
     * "অক্টোবর ২০২৬" / "October 2026".
     */
    public static function monthYear(CarbonInterface $date, ?bool $force = null): string
    {
        return self::monthName($date, $force).' '.self::digits($date->year, $force);
    }

    public static function weekday(CarbonInterface $date, ?bool $force = null): string
    {
        return ($force ?? self::isBangla()) ? self::WEEKDAYS[$date->dayOfWeek] : $date->format('l');
    }
}
