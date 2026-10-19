<?php

use Modules\Core\Support\BanglaWords;

it('spells whole numbers in Bangla', function (int $number, string $words) {
    expect(BanglaWords::integer($number))->toBe($words);
})->with([
    [0, 'শূন্য'],
    [7, 'সাত'],
    [11, 'এগারো'],
    [21, 'একুশ'],
    [99, 'নিরানব্বই'],
    [100, 'একশত'],
    [250, 'দুইশত পঞ্চাশ'],
    [1000, 'এক হাজার'],
    [1500, 'এক হাজার পাঁচশত'],
    [12345, 'বারো হাজার তিনশত পঁয়তাল্লিশ'],
    [100000, 'এক লক্ষ'],
    [123456, 'এক লক্ষ তেইশ হাজার চারশত ছাপ্পান্ন'],
    [10000000, 'এক কোটি'],
    [25000000, 'দুই কোটি পঞ্চাশ লক্ষ'],
]);

it('writes a money amount the way a receipt does', function () {
    expect(BanglaWords::money(150000, true))->toBe('এক হাজার পাঁচশত টাকা মাত্র')
        ->and(BanglaWords::money(80000, true))->toBe('আটশত টাকা মাত্র')
        ->and(BanglaWords::money(0, true))->toBe('শূন্য টাকা মাত্র');
});

it('adds paisa when there is any', function () {
    expect(BanglaWords::money(10085, true))->toBe('একশত টাকা পঁচাশি পয়সা মাত্র');
});

it('follows the locale and has an English form', function () {
    app()->setLocale('bn');
    expect(BanglaWords::money(150000))->toBe('এক হাজার পাঁচশত টাকা মাত্র');

    app()->setLocale('en');
    expect(BanglaWords::money(150000))->toBe('One thousand five hundred Taka Only')
        ->and(BanglaWords::money(10050))->toBe('One hundred Taka fifty Paisa Only');
});
