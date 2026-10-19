<?php

namespace Modules\Core\Support;

use NumberFormatter;

/**
 * Money amounts as words for receipts: 150000 poisha → "এক হাজার পাঁচশত টাকা মাত্র".
 */
final class BanglaWords
{
    private const array BELOW_HUNDRED = [
        'শূন্য', 'এক', 'দুই', 'তিন', 'চার', 'পাঁচ', 'ছয়', 'সাত', 'আট', 'নয়',
        'দশ', 'এগারো', 'বারো', 'তেরো', 'চৌদ্দ', 'পনেরো', 'ষোলো', 'সতেরো', 'আঠারো', 'উনিশ',
        'বিশ', 'একুশ', 'বাইশ', 'তেইশ', 'চব্বিশ', 'পঁচিশ', 'ছাব্বিশ', 'সাতাশ', 'আটাশ', 'ঊনত্রিশ',
        'ত্রিশ', 'একত্রিশ', 'বত্রিশ', 'তেত্রিশ', 'চৌত্রিশ', 'পঁয়ত্রিশ', 'ছত্রিশ', 'সাঁইত্রিশ', 'আটত্রিশ', 'ঊনচল্লিশ',
        'চল্লিশ', 'একচল্লিশ', 'বিয়াল্লিশ', 'তেতাল্লিশ', 'চুয়াল্লিশ', 'পঁয়তাল্লিশ', 'ছেচল্লিশ', 'সাতচল্লিশ', 'আটচল্লিশ', 'ঊনপঞ্চাশ',
        'পঞ্চাশ', 'একান্ন', 'বাহান্ন', 'তিপ্পান্ন', 'চুয়ান্ন', 'পঞ্চান্ন', 'ছাপ্পান্ন', 'সাতান্ন', 'আটান্ন', 'ঊনষাট',
        'ষাট', 'একষট্টি', 'বাষট্টি', 'তেষট্টি', 'চৌষট্টি', 'পঁয়ষট্টি', 'ছেষট্টি', 'সাতষট্টি', 'আটষট্টি', 'ঊনসত্তর',
        'সত্তর', 'একাত্তর', 'বাহাত্তর', 'তিয়াত্তর', 'চুয়াত্তর', 'পঁচাত্তর', 'ছিয়াত্তর', 'সাতাত্তর', 'আটাত্তর', 'ঊনআশি',
        'আশি', 'একাশি', 'বিরাশি', 'তিরাশি', 'চুরাশি', 'পঁচাশি', 'ছিয়াশি', 'সাতাশি', 'অষ্টাশি', 'ঊননব্বই',
        'নব্বই', 'একানব্বই', 'বিরানব্বই', 'তিরানব্বই', 'চুরানব্বই', 'পঁচানব্বই', 'ছিয়ানব্বই', 'সাতানব্বই', 'আটানব্বই', 'নিরানব্বই',
    ];

    /**
     * A whole number in Bangla words, using the south-Asian scale (হাজার, লক্ষ, কোটি).
     */
    public static function integer(int $number): string
    {
        if ($number === 0) {
            return self::BELOW_HUNDRED[0];
        }

        $parts = [];

        foreach ([[10_000_000, 'কোটি'], [100_000, 'লক্ষ'], [1_000, 'হাজার']] as [$size, $name]) {
            if ($number >= $size) {
                $parts[] = self::integer(intdiv($number, $size)).' '.$name;
                $number %= $size;
            }
        }

        if ($number >= 100) {
            $parts[] = self::BELOW_HUNDRED[intdiv($number, 100)].'শত';
            $number %= 100;
        }

        if ($number > 0) {
            $parts[] = self::BELOW_HUNDRED[$number];
        }

        return implode(' ', $parts);
    }

    /**
     * "এক হাজার পাঁচশত টাকা মাত্র", with paisa when there is any: "… টাকা পঁচাশি পয়সা মাত্র".
     * In English it is "One thousand five hundred Taka Only".
     */
    public static function money(int $poisha, ?bool $bangla = null): string
    {
        $taka = intdiv(abs($poisha), 100);
        $paisa = abs($poisha) % 100;

        if (! ($bangla ?? Bangla::isBangla())) {
            $speller = new NumberFormatter('en', NumberFormatter::SPELLOUT);
            $words = ucfirst((string) $speller->format($taka)).' Taka';

            return $words.($paisa > 0 ? ' '.$speller->format($paisa).' Paisa' : '').' Only';
        }

        return self::integer($taka).' টাকা'.($paisa > 0 ? ' '.self::integer($paisa).' পয়সা' : '').' মাত্র';
    }
}
