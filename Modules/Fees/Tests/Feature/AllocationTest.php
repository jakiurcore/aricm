<?php

use Modules\Accounts\Models\User;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Models\PaymentAllocation;
use Modules\Fees\Models\StudentCredit;
use Modules\Fees\Services\PaymentService;
use Modules\Students\Models\Student;

function collect_(Student $student, int|float $amount, array $invoiceIds = [], ?User $by = null): Payment
{
    return app(PaymentService::class)->collectAtCounter($student, taka($amount), PaymentMethod::Cash, $invoiceIds, $by ?? userWithRole('accountant'));
}

it('pays the oldest invoice first when none is chosen', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01', '2026-03-01']);

    collect_($student, 2000);

    $fresh = $invoices->map->fresh();
    expect($fresh[0]->status)->toBe(InvoiceStatus::Paid)
        ->and($fresh[1]->status)->toBe(InvoiceStatus::Partial)->and($fresh[1]->paid->poisha)->toBe(70000)->and($fresh[1]->due->poisha)->toBe(60000)
        ->and($fresh[2]->status)->toBe(InvoiceStatus::Unpaid);
});

it('allocates one payment across several chosen invoices', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01', '2026-03-01']);

    $payment = collect_($student, 2600, [$invoices[0]->id, $invoices[2]->id]);

    expect($payment->allocations)->toHaveCount(2)
        ->and($invoices[0]->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoices[1]->fresh()->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoices[2]->fresh()->status)->toBe(InvoiceStatus::Paid);
});

it('keeps an overpayment as credit', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01']);

    collect_($student, 2000, [$invoices[0]->id]);

    expect($invoices[0]->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and(StudentCredit::where('student_id', $student->id)->first()->balance->poisha)->toBe(70000);
});

it('applies stored credit to the next invoice automatically', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $first = invoicesFor($student, ['2026-01-01']);
    collect_($student, 2000, [$first[0]->id]);       // ৳700 credit

    $second = invoicesFor($student, ['2026-02-01'])->last();

    expect($second->paid->poisha)->toBe(70000)->and($second->due->poisha)->toBe(60000)->and($second->status)->toBe(InvoiceStatus::Partial)
        ->and(StudentCredit::first()->balance->poisha)->toBe(0)
        ->and(PaymentAllocation::where('source', 'credit')->count())->toBe(1);
});

it('puts a payment that matches nothing open entirely into credit', function () {
    $student = enrolled(placement());

    collect_($student, 500);

    expect(StudentCredit::first()->balance->poisha)->toBe(50000);
});

it('never lets allocations exceed what an invoice owes', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    collect_($student, 1300, [$invoice->id]);
    collect_($student, 1300, [$invoice->id]);   // already paid: all of it becomes credit

    expect($invoice->fresh()->paid->poisha)->toBe(130000)->and(StudentCredit::first()->balance->poisha)->toBe(130000);
});

it('keeps due, paid and status in step after every change', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    collect_($student, 300, [$invoice->id]);
    $invoice->refresh();
    expect([$invoice->paid->poisha, $invoice->due->poisha, $invoice->status])->toBe([30000, 100000, InvoiceStatus::Partial]);

    collect_($student, 1000, [$invoice->id]);
    $invoice->refresh();
    expect([$invoice->paid->poisha, $invoice->due->poisha, $invoice->status])->toBe([130000, 0, InvoiceStatus::Paid]);
    expect(FeeInvoice::where('status', 'paid')->where('due', '>', 0)->count())->toBe(0);
});

it('does not touch other students\' invoices', function () {
    $p = placement();
    feeSetup($p);
    $a = enrolled($p, ['roll' => 1]);
    $b = enrolled($p, ['roll' => 2]);
    $bInvoice = invoicesFor($b, ['2026-01-01'])[0];
    invoicesFor($a, ['2026-01-01']);

    collect_($a, 1300, [$bInvoice->id]);   // a tampered invoice id: it is not a's

    expect($bInvoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)->and(StudentCredit::where('student_id', $a->id)->first()->balance->poisha)->toBe(130000);
});
