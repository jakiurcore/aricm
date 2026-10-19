<?php

use Modules\Fees\Support\Money;

it('keeps whole poisha and never floats', function () {
    expect(Money::taka('1250.50')->poisha)->toBe(125050)
        ->and(Money::taka(0.1)->plus(Money::taka(0.2))->poisha)->toBe(30)
        ->and(Money::poisha(150)->minus(Money::poisha(50))->poisha)->toBe(100);
});

it('takes a percentage rounded down', function () {
    expect(Money::taka(1000)->percent(25)->poisha)->toBe(25000)
        ->and(Money::poisha(999)->percent(10)->poisha)->toBe(99);
});

it('compares and picks the smaller', function () {
    expect(Money::taka(5)->min(Money::taka(3))->poisha)->toBe(300)
        ->and(Money::taka(5)->max(Money::taka(3))->poisha)->toBe(500)
        ->and(Money::zero()->isZero())->toBeTrue()
        ->and(Money::taka(1)->equals(Money::poisha(100)))->toBeTrue();
});

it('formats with Bangla digits and lakh grouping', function () {
    expect(Money::taka(1200)->format(true))->toBe('৳১,২০০')
        ->and(Money::taka(1250000)->format(true))->toBe('৳১২,৫০,০০০')
        ->and(Money::poisha(125050)->format(true))->toBe('৳১,২৫০.৫০')
        ->and(Money::taka(50)->format(false))->toBe('৳50')
        ->and(Money::taka(-300)->format(true))->toBe('-৳৩০০');
});

it('writes the amount in words', function () {
    expect(Money::taka(1500)->inWords(true))->toBe('এক হাজার পাঁচশত টাকা মাত্র');
});

it('refuses non-numeric input', function () {
    Money::taka('abc');
})->throws(InvalidArgumentException::class);
