<?php

use Illuminate\Support\Carbon;
use Modules\Fees\Enums\ConcessionKind;
use Modules\Fees\Models\FeeStructure;
use Modules\Fees\Services\FeeCalculator;
use Modules\Fees\Support\ConcessionRule;
use Modules\Fees\Support\Money;
use Modules\Students\Enums\ResidencyType;

function rule(ConcessionKind $kind, int $value, string $from = '2026-01-01', ?string $to = null, ?int $head = null): ConcessionRule
{
    return new ConcessionRule($kind, $value, Carbon::parse($from), $to ? Carbon::parse($to) : null, $head);
}

function row(int $head, ?ResidencyType $residency, int $taka): FeeStructure
{
    return (new FeeStructure)->forceFill(['fee_head_id' => $head, 'residency_type' => $residency, 'amount' => Money::taka($taka)]);
}

it('uses the residency specific amount before the generic one', function () {
    $book = [row(1, null, 1000), row(1, ResidencyType::Residential, 2500), row(2, null, 300)];
    $calculator = new FeeCalculator;

    expect($calculator->structureAmount($book, 1, ResidencyType::Residential)->poisha)->toBe(250000)
        ->and($calculator->structureAmount($book, 1, ResidencyType::NonResidential)->poisha)->toBe(100000)
        ->and($calculator->structureAmount($book, 1, null)->poisha)->toBe(100000)
        ->and($calculator->structureAmount($book, 3, null))->toBeNull();
});

it('applies a percent concession', function () {
    $discount = (new FeeCalculator)->discount(Money::taka(1000), [rule(ConcessionKind::Percent, 25)], 1, Carbon::parse('2026-03-01'));

    expect($discount->poisha)->toBe(25000);
});

it('applies a fixed concession but never beyond the amount', function () {
    $calculator = new FeeCalculator;

    expect($calculator->discount(Money::taka(1000), [rule(ConcessionKind::Fixed, 20000)], 1, Carbon::parse('2026-03-01'))->poisha)->toBe(20000)
        ->and($calculator->discount(Money::taka(100), [rule(ConcessionKind::Fixed, 50000)], 1, Carbon::parse('2026-03-01'))->poisha)->toBe(10000);
});

it('caps stacked concessions at the gross amount', function () {
    $discount = (new FeeCalculator)->discount(Money::taka(1000), [rule(ConcessionKind::Percent, 70), rule(ConcessionKind::Percent, 50)], 1, Carbon::parse('2026-03-01'));

    expect($discount->poisha)->toBe(100000);
});

it('respects the validity months of a concession, both ends inclusive', function () {
    $calculator = new FeeCalculator;
    $rules = [rule(ConcessionKind::Percent, 50, '2026-03-01', '2026-05-31')];

    $at = fn (string $month) => $calculator->discount(Money::taka(1000), $rules, 1, Carbon::parse($month))->poisha;

    expect($at('2026-02-15'))->toBe(0)->and($at('2026-03-01'))->toBe(50000)->and($at('2026-05-20'))->toBe(50000)->and($at('2026-06-01'))->toBe(0);
});

it('keeps an open-ended concession running', function () {
    $discount = (new FeeCalculator)->discount(Money::taka(1000), [rule(ConcessionKind::Percent, 10, '2026-01-01', null)], 1, Carbon::parse('2030-12-01'));

    expect($discount->poisha)->toBe(10000);
});

it('only applies a concession to its own head unless it is for all heads', function () {
    $calculator = new FeeCalculator;
    $month = Carbon::parse('2026-03-01');

    expect($calculator->discount(Money::taka(1000), [rule(ConcessionKind::Percent, 50, head: 2)], 1, $month)->poisha)->toBe(0)
        ->and($calculator->discount(Money::taka(1000), [rule(ConcessionKind::Percent, 50, head: 1)], 1, $month)->poisha)->toBe(50000)
        ->and($calculator->discount(Money::taka(1000), [rule(ConcessionKind::Percent, 50, head: null)], 1, $month)->poisha)->toBe(50000);
});
