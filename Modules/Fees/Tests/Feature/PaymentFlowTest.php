<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\Payment;
use Modules\Fees\Models\StudentCredit;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Services\TransactionRules;

it('records a manual payment as pending and leaves the invoice alone', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    $payment = submit($student, [$invoice->id]);

    expect($payment->status)->toBe(PaymentStatus::Pending)->and($payment->receipt_no)->toBeNull()->and($payment->amount->poisha)->toBe(130000)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)->and($invoice->fresh()->paid->poisha)->toBe(0)
        ->and(StudentCredit::count())->toBe(0);
});

it('works the amount out from the invoices, never from the client', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01']);

    $payment = submit($student, $invoices->pluck('id')->all());

    expect($payment->amount->poisha)->toBe(260000);
});

it('approving allocates, issues a receipt and notifies the student', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    $payment = submit($student, [$invoice->id]);

    $approved = app(PaymentService::class)->approve($payment, userWithRole('accountant'));

    expect($approved->status)->toBe(PaymentStatus::Approved)
        ->and($approved->receipt_no)->toStartWith('RCPT-'.now()->format('Y').'-')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($student->notifications()->count())->toBe(1)
        ->and($student->notifications()->first()->data['status'])->toBe('approved');
});

it('approves only once, even when asked twice', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    $payment = submit($student, [$invoice->id]);
    $service = app(PaymentService::class);

    $service->approve($payment, userWithRole('accountant'));

    expect(fn () => $service->approve($payment->fresh(), userWithRole('accountant')))->toThrow(ValidationException::class);
    expect($invoice->fresh()->paid->poisha)->toBe(130000);
});

it('rejecting changes nothing about the invoice and records the reason', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    $payment = submit($student, [$invoice->id]);

    $rejected = app(PaymentService::class)->reject($payment, userWithRole('accountant'), 'ট্রানজেকশন পাওয়া যায়নি');

    expect($rejected->status)->toBe(PaymentStatus::Rejected)->and($rejected->rejection_reason)->toBe('ট্রানজেকশন পাওয়া যায়নি')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Unpaid)->and($invoice->fresh()->paid->poisha)->toBe(0)
        ->and($student->notifications()->first()->data['status'])->toBe('rejected');
});

it('lets the same transaction id be used only once per method', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01']);

    submit($student, [$invoices[0]->id], 'TXN00000AA');

    expect(fn () => submit($student, [$invoices[1]->id], 'txn00000aa'))->toThrow(ValidationException::class);

    // A different method may reuse the number: transaction ids are only unique within a provider.
    expect(submit($student, [$invoices[1]->id], 'TXN00000AA', PaymentMethod::Nagad)->status)->toBe(PaymentStatus::Pending);
});

it('rejects a reused transaction id even if the first payment was rejected', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    app(PaymentService::class)->reject(submit($student, [$invoice->id], 'TXN00000BB'), userWithRole('accountant'), 'ভুল');

    expect(fn () => submit($student, [$invoice->id], 'TXN00000BB'))->toThrow(ValidationException::class);
});

it('blocks a transaction id already used at the counter', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    app(PaymentService::class)->collectAtCounter($student, taka(100), PaymentMethod::Bkash, [], userWithRole('accountant'), 'COUNTER001');

    expect(fn () => submit($student, [$invoice->id], 'COUNTER001'))->toThrow(ValidationException::class);
});

it('checks the transaction id format and the sender number', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    expect(fn () => submit($student, [$invoice->id], 'x'))->toThrow(ValidationException::class);
    expect(fn () => submit($student, [$invoice->id], 'ABC12345XY', PaymentMethod::Bkash, '12345'))->toThrow(ValidationException::class);
    expect(submit($student, [$invoice->id], 'BANK-REF-77', PaymentMethod::Bank, null)->status)->toBe(PaymentStatus::Pending);

    expect(TransactionRules::isValid(PaymentMethod::Bkash, ' abc12345xy '))->toBeTrue()->and(TransactionRules::isValid(PaymentMethod::Cash, ''))->toBeTrue();
});

it('refuses invoices that belong to someone else, are already paid, or do not exist', function () {
    $p = placement();
    feeSetup($p);
    $a = enrolled($p, ['roll' => 1]);
    $b = enrolled($p, ['roll' => 2]);
    $aInvoice = invoicesFor($a, ['2026-01-01'])[0];
    $bInvoice = invoicesFor($b, ['2026-01-01'])[0];
    app(PaymentService::class)->collectAtCounter($b, taka(1300), PaymentMethod::Cash, [$bInvoice->id], userWithRole('accountant'));

    expect(fn () => submit($a, [$bInvoice->id]))->toThrow(ValidationException::class);
    expect(fn () => submit($a, [999999]))->toThrow(ValidationException::class);
    expect(fn () => submit($a, []))->toThrow(ValidationException::class);
    expect(fn () => submit($b, [$bInvoice->id], 'PAID0000ZZ'))->toThrow(ValidationException::class);
    expect(submit($a, [$aInvoice->id])->student_id)->toBe($a->id);
});

it('cannot submit by the counter-only methods', function () {
    $student = enrolled(placement());

    expect(fn () => submit($student, [1], 'CASH00000A', PaymentMethod::Cash))->toThrow(ValidationException::class);
    expect(fn () => submit($student, [1], 'GATE00000A', PaymentMethod::Gateway))->toThrow(ValidationException::class);
});

it('stores a screenshot', function () {
    Storage::fake('public');
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    $payment = submit($student, [$invoice->id], screenshot: tinyPng('proof.png'));

    Storage::disk('public')->assertExists($payment->screenshot_path);
});

it('collects at the counter with an immediate receipt', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];
    $accountant = userWithRole('accountant');

    $payment = app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [$invoice->id], $accountant, note: 'নগদ');

    expect($payment->status)->toBe(PaymentStatus::Approved)->and($payment->receipt_no)->not->toBeNull()->and($payment->received_by)->toBe($accountant->id)
        ->and($payment->allocations)->toHaveCount(1)->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

it('requires a positive amount and, for non-cash, a valid transaction id', function () {
    $student = enrolled(placement());
    $service = app(PaymentService::class);
    $accountant = userWithRole('accountant');

    expect(fn () => $service->collectAtCounter($student, taka(0), PaymentMethod::Cash, [], $accountant))->toThrow(ValidationException::class);
    expect(fn () => $service->collectAtCounter($student, taka(100), PaymentMethod::Bkash, [], $accountant))->toThrow(ValidationException::class);
    expect(fn () => $service->collectAtCounter($student, taka(100), PaymentMethod::Gateway, [], $accountant))->toThrow(ValidationException::class);
});

it('texts the guardian only when SMS is switched on', function () {
    Log::spy();
    $p = placement();
    feeSetup($p);
    $student = enrolled($p, ['guardian_phone' => '01712345678']);
    $invoices = invoicesFor($student, ['2026-01-01', '2026-02-01']);
    $service = app(PaymentService::class);

    $service->collectAtCounter($student, taka(100), PaymentMethod::Cash, [$invoices[0]->id], userWithRole('accountant'));
    Log::shouldNotHaveReceived('info');

    app(FeeSettings::class)->update(['sms_enabled' => true]);
    $service->collectAtCounter($student, taka(100), PaymentMethod::Cash, [$invoices[1]->id], userWithRole('accountant'));
    Log::shouldHaveReceived('info')->once();
});

it('waives an open invoice with a reason', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    $invoice = invoicesFor($student, ['2026-01-01'])[0];

    app(PaymentService::class)->waive($invoice, userWithRole('admin'), 'এতিম ছাত্র');

    $fresh = $invoice->fresh();
    expect($fresh->status)->toBe(InvoiceStatus::Waived)->and($fresh->due->poisha)->toBe(0)->and($fresh->waive_reason)->toBe('এতিম ছাত্র');
    expect(fn () => app(PaymentService::class)->waive($fresh, userWithRole('admin'), 'আবার'))->toThrow(ValidationException::class);
    expect(Payment::count())->toBe(0);
});
