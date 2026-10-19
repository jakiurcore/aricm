<?php

use Illuminate\Support\Carbon;
use Modules\Core\Support\Bangla;

it('converts digits only in the bn locale', function () {
    app()->setLocale('bn');
    expect(bn_digits('2026-10-21'))->toBe('২০২৬-১০-২১');

    app()->setLocale('en');
    expect(bn_digits('2026'))->toBe('2026')
        ->and(bn_digits('2026', true))->toBe('২০২৬');
});

it('formats a Bangla date like ২১ অক্টোবর ২০২৬', function () {
    app()->setLocale('bn');

    expect(bn_date('2026-10-21'))->toBe('২১ অক্টোবর ২০২৬')
        ->and(bn_date(null))->toBe('');
});

it('falls back to a latin date in English', function () {
    app()->setLocale('en');

    expect(bn_date('2026-10-21'))->toBe('21 Oct 2026');
});

it('localises numbers with decimals', function () {
    app()->setLocale('bn');

    expect(bn_number(4.5, 2))->toBe('৪.৫০')
        ->and(Bangla::weekday(Carbon::parse('2026-10-23')))->toBe('শুক্রবার');
});
