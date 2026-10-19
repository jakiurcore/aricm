<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Laramod\Facades\Modules;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Services\StudentFeeDesk;

it('only ever returns the student\'s own invoices and payments', function () {
    $p = placement();
    feeSetup($p);
    $a = enrolled($p, ['roll' => 1]);
    $b = enrolled($p, ['roll' => 2]);
    $aInvoice = invoicesFor($a, ['2026-01-01'])[0];
    $bInvoice = invoicesFor($b, ['2026-01-01'])[0];
    $bPayment = app(PaymentService::class)->collectAtCounter($b, taka(100), PaymentMethod::Cash, [$bInvoice->id], userWithRole('accountant'));
    $desk = app(StudentFeeDesk::class);

    expect($desk->invoice($a, $aInvoice->id)->id)->toBe($aInvoice->id)
        ->and($desk->invoices($a))->toHaveCount(1);

    expect(fn () => $desk->invoice($a, $bInvoice->id))->toThrow(ModelNotFoundException::class);
    expect(fn () => $desk->payment($a, $bPayment->id))->toThrow(ModelNotFoundException::class);
});

it('totals what is due and holds credit', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    invoicesFor($student, ['2026-01-01', '2026-02-01']);
    $desk = app(StudentFeeDesk::class);

    expect($desk->totalDue($student)->poisha)->toBe(260000)->and($desk->openInvoices($student))->toHaveCount(2);

    app(PaymentService::class)->collectAtCounter($student, taka(3000), PaymentMethod::Cash, [], userWithRole('accountant'));
    expect($desk->totalDue($student)->poisha)->toBe(0)->and($desk->credit($student)->poisha)->toBe(40000);
});

it('draws a twelve month grid with the right status for each month', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    invoicesFor($student, ['2026-01-01', '2026-02-01', '2026-03-01', '2026-04-01']);
    $invoices = FeeInvoice::orderBy('billing_month')->get();
    $service = app(PaymentService::class);
    $service->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [$invoices[0]->id], userWithRole('accountant'));
    $service->collectAtCounter($student, taka(500), PaymentMethod::Cash, [$invoices[1]->id], userWithRole('accountant'));
    $service->waive($invoices[2], userWithRole('admin'), 'মওকুফ');

    $grid = collect(app(StudentFeeDesk::class)->monthGrid($student, Carbon::parse('2026-05-12')))->pluck('status')->all();

    expect($grid)->toHaveCount(12)
        ->and(array_slice($grid, 0, 4))->toBe(['paid', 'overdue', 'waived', 'overdue'])   // February is part paid but past its due date
        ->and($grid[4])->toBe('none')                                                     // May: no bill yet, month has begun
        ->and($grid[11])->toBe('upcoming');
});

it('shows an unpaid bill that is not yet due as unpaid and a part-paid one as partial', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    invoicesFor($student, ['2026-05-01']);
    app(PaymentService::class)->collectAtCounter($student, taka(100), PaymentMethod::Cash, [], userWithRole('accountant'));

    expect(app(StudentFeeDesk::class)->monthGrid($student, Carbon::parse('2026-05-05'))[4]['status'])->toBe('partial');
});

it('keeps a ledger with a running balance', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01']);
    app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [$invoices[0]->id], userWithRole('accountant'));

    $ledger = app(StudentFeeDesk::class)->ledger($student);

    expect($ledger)->toHaveCount(3)
        ->and(collect($ledger)->pluck('kind')->sort()->values()->all())->toBe(['invoice', 'invoice', 'payment'])
        ->and(end($ledger)['balance']->poisha)->toBe(130000);
});

it('menu and summary come from Fees through the core contracts', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    invoicesFor($student, ['2026-01-01']);
    $module = Modules::find('fees');

    $menu = $module->portalMenu($student)[0];
    expect($menu->badgeFor($student))->toBe(1)->and($menu->route)->toBe('portal.fees');

    $labels = collect($module->studentSummary($student))->pluck('value')->first();
    expect($labels)->toContain('১,৩০০');
});

it('keeps the fee commands harmless when switched off, and idempotent when on', function () {
    $p = placement();
    feeSetup($p);
    enrolled($p);

    $this->artisan('fees:generate-monthly', ['--month' => '2026-03'])->assertSuccessful();
    expect(FeeInvoice::count())->toBe(0);

    $this->artisan('fees:generate-monthly', ['--month' => '2026-03', '--force' => true])->assertSuccessful();
    $this->artisan('fees:generate-monthly', ['--month' => '2026-03', '--force' => true])->assertSuccessful();
    expect(FeeInvoice::count())->toBe(1);

    app(FeeSettings::class)->update(['auto_generate_monthly' => true]);
    $this->artisan('fees:generate-monthly', ['--month' => '2026-04'])->assertSuccessful();
    expect(FeeInvoice::count())->toBe(2);

    $this->artisan('fees:apply-late-fines')->assertSuccessful();
    $this->artisan('fees:due-reminders')->assertSuccessful();
});
