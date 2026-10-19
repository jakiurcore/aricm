<?php

use Illuminate\Support\Carbon;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\LateFineApplier;
use Modules\Fees\Services\PaymentService;

function lateFineSetup(array $settings): array
{
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-03-01'])[0];     // due 10 March
    app(FeeSettings::class)->update($settings);

    return [$student, $invoice];
}

it('does nothing while the fine is zero', function () {
    [, $invoice] = lateFineSetup(['late_fine_amount' => 0]);

    expect(app(LateFineApplier::class)->apply(Carbon::parse('2026-04-20')))->toBe(0)->and($invoice->fresh()->late_fine->poisha)->toBe(0);
});

it('adds the fine once to an overdue invoice and not before the due date', function () {
    [, $invoice] = lateFineSetup(['late_fine_amount' => 5000, 'late_fine_mode' => 'once']);
    $applier = app(LateFineApplier::class);

    expect($applier->apply(Carbon::parse('2026-03-10')))->toBe(0);
    expect($applier->apply(Carbon::parse('2026-03-12')))->toBe(1);

    $fresh = $invoice->fresh();
    expect($fresh->late_fine->poisha)->toBe(5000)->and($fresh->total->poisha)->toBe(135000)->and($fresh->due->poisha)->toBe(135000);
});

it('is idempotent: running again the same day or later changes nothing in once mode', function () {
    [, $invoice] = lateFineSetup(['late_fine_amount' => 5000, 'late_fine_mode' => 'once']);
    $applier = app(LateFineApplier::class);

    $applier->apply(Carbon::parse('2026-03-12'));

    expect($applier->apply(Carbon::parse('2026-03-12')))->toBe(0)->and($applier->apply(Carbon::parse('2026-06-01')))->toBe(0)->and($invoice->fresh()->late_fine->poisha)->toBe(5000);
});

it('waits for the grace days', function () {
    [, $invoice] = lateFineSetup(['late_fine_amount' => 5000, 'late_fine_after_days' => 5]);
    $applier = app(LateFineApplier::class);

    expect($applier->apply(Carbon::parse('2026-03-15')))->toBe(0)->and($applier->apply(Carbon::parse('2026-03-16')))->toBe(1);
});

it('grows by one fine per month in per_month mode, once per period', function () {
    [, $invoice] = lateFineSetup(['late_fine_amount' => 5000, 'late_fine_mode' => 'per_month']);
    $applier = app(LateFineApplier::class);

    $applier->apply(Carbon::parse('2026-03-12'));
    expect($invoice->fresh()->late_fine->poisha)->toBe(5000);

    $applier->apply(Carbon::parse('2026-03-12'));
    expect($invoice->fresh()->late_fine->poisha)->toBe(5000);

    $applier->apply(Carbon::parse('2026-04-15'));    // 36 days late → second period
    expect($invoice->fresh()->late_fine->poisha)->toBe(10000);

    $applier->apply(Carbon::parse('2026-04-16'));
    expect($invoice->fresh()->late_fine->poisha)->toBe(10000);
});

it('never fines paid, waived or cancelled invoices', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01', '2026-03-01']);
    app(FeeSettings::class)->update(['late_fine_amount' => 5000]);

    app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [$invoices[0]->id], userWithRole('accountant'));
    app(PaymentService::class)->waive($invoices[1], userWithRole('admin'), 'এতিম');

    app(LateFineApplier::class)->apply(Carbon::parse('2026-06-01'));

    expect($invoices[0]->fresh()->late_fine->poisha)->toBe(0)->and($invoices[1]->fresh()->late_fine->poisha)->toBe(0)->and($invoices[1]->fresh()->status)->toBe(InvoiceStatus::Waived)
        ->and($invoices[2]->fresh()->late_fine->poisha)->toBe(5000);
});

it('fines a partly paid invoice', function () {
    [$student, $invoice] = lateFineSetup(['late_fine_amount' => 5000]);
    app(PaymentService::class)->collectAtCounter($student, taka(300), PaymentMethod::Cash, [$invoice->id], userWithRole('accountant'));

    app(LateFineApplier::class)->apply(Carbon::parse('2026-03-20'));

    $fresh = $invoice->fresh();
    expect($fresh->late_fine->poisha)->toBe(5000)->and($fresh->due->poisha)->toBe(105000)->and($fresh->status)->toBe(InvoiceStatus::Partial);
});
