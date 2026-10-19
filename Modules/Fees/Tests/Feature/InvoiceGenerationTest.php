<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\ExamFeeAmount;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\StudentConcession;
use Modules\Fees\Models\StudentCredit;
use Modules\Fees\Services\InvoiceGenerator;
use Modules\Students\Models\Student;

function generator(): InvoiceGenerator
{
    return app(InvoiceGenerator::class);
}

it('creates one monthly invoice per student holding every monthly head', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);

    $summary = generator()->generateMonthly(Carbon::parse('2026-03-15'));

    expect($summary->created)->toBe(1)->and($summary->totalAmount->poisha)->toBe(130000);

    $invoice = FeeInvoice::first();
    expect($invoice->invoice_no)->toStartWith('INV-'.now()->format('Y').'-')
        ->and($invoice->billing_month->toDateString())->toBe('2026-03-01')
        ->and($invoice->total->poisha)->toBe(130000)->and($invoice->due->poisha)->toBe(130000)->and($invoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->items)->toHaveCount(2)->and($invoice->student_id)->toBe($student->id)
        ->and($invoice->due_date->toDateString())->toBe('2026-03-10');
});

it('is idempotent: running it again creates nothing', function () {
    $p = placement();
    feeSetup($p);
    enrolled($p);

    generator()->generateMonthly(Carbon::parse('2026-03-01'));
    $second = generator()->generateMonthly(Carbon::parse('2026-03-20'));

    expect($second->created)->toBe(0)->and($second->skippedExisting)->toBe(1)->and(FeeInvoice::count())->toBe(1);
});

it('previews without writing anything', function () {
    $p = placement();
    feeSetup($p);
    enrolled($p);

    $preview = generator()->generateMonthly(Carbon::parse('2026-03-01'), dryRun: true);

    expect($preview->created)->toBe(1)->and($preview->dryRun)->toBeTrue()->and(FeeInvoice::count())->toBe(0);
});

it('skips months before the student was admitted', function () {
    $p = placement();
    feeSetup($p);
    enrolled($p, ['admission_date' => '2026-05-10']);

    $before = generator()->generateMonthly(Carbon::parse('2026-04-01'));
    $during = generator()->generateMonthly(Carbon::parse('2026-05-01'));

    expect($before->skippedPreAdmission)->toBe(1)->and($before->created)->toBe(0)->and($during->created)->toBe(1);
});

it('skips inactive students and classes without a fee', function () {
    $p = placement();
    feeSetup($p);
    enrolled($p)->update(['status' => 'inactive']);
    $other = placement();
    enrolled($other);

    $summary = generator()->generateMonthly(Carbon::parse('2026-03-01'));

    expect($summary->created)->toBe(0)->and($summary->skippedNoFee)->toBe(1);
});

it('limits generation to chosen classes', function () {
    $a = placement();
    $b = placement();
    feeSetup($a);
    feeSetup($b);
    enrolled($a);
    enrolled($b);

    expect(generator()->generateMonthly(Carbon::parse('2026-03-01'), [$a['class']->id])->created)->toBe(1)->and(FeeInvoice::count())->toBe(1);
});

it('applies concessions and residency to the invoice', function () {
    $p = placement();
    $heads = feeSetup($p);
    $student = enrolled($p);
    StudentConcession::create(['student_id' => $student->id, 'fee_head_id' => $heads['tuition']->id, 'kind' => 'percent', 'value' => 50, 'reason_type' => 'orphan', 'valid_from_month' => '2026-01-01', 'valid_to_month' => '2026-03-01']);

    generator()->generateMonthly(Carbon::parse('2026-03-01'));
    generator()->generateMonthly(Carbon::parse('2026-04-01'));

    $march = FeeInvoice::whereDate('billing_month', '2026-03-01')->first();
    $april = FeeInvoice::whereDate('billing_month', '2026-04-01')->first();

    expect($march->subtotal->poisha)->toBe(130000)->and($march->discount->poisha)->toBe(50000)->and($march->total->poisha)->toBe(80000)
        ->and($april->discount->poisha)->toBe(0)->and($april->total->poisha)->toBe(130000);
});

it('numbers invoices sequentially without gaps', function () {
    $p = placement();
    feeSetup($p);
    enrolled($p);
    enrolled($p, ['roll' => 2]);

    generator()->generateMonthly(Carbon::parse('2026-03-01'));

    $numbers = FeeInvoice::orderBy('id')->pluck('invoice_no')->all();
    $year = now()->format('Y');

    expect($numbers)->toBe(["INV-{$year}-000001", "INV-{$year}-000002"]);
});

it('creates exam invoices once per student from the exam fee amounts', function () {
    $p = placement();
    $exam = examFor($p);
    FeeHead::factory()->exam()->create();
    ExamFeeAmount::create(['exam_id' => $exam->id, 'class_id' => $p['class']->id, 'amount' => taka(200), 'due_date' => '2026-05-25']);
    enrolled($p);

    expect(generator()->generateExam($exam)->created)->toBe(1)->and(generator()->generateExam($exam)->skippedExisting)->toBe(1);

    $invoice = FeeInvoice::first();
    expect($invoice->exam_id)->toBe($exam->id)->and($invoice->total->poisha)->toBe(20000)->and($invoice->due_date->toDateString())->toBe('2026-05-25');
});

it('needs an exam fee head to generate exam invoices', function () {
    generator()->generateExam(examFor(placement()));
})->throws(ValidationException::class);

it('creates a one-off invoice using the structure amount or an explicit one', function () {
    $p = placement();
    $heads = feeSetup($p);
    $books = FeeHead::factory()->oneTime()->create(['name_bn' => 'বই-খাতা']);
    $student = enrolled($p);

    $invoice = generator()->generateOneOff($student, [['fee_head_id' => $books->id, 'amount' => taka(450)], ['fee_head_id' => $heads['tuition']->id]]);

    expect($invoice->total->poisha)->toBe(145000)->and($invoice->items)->toHaveCount(2)->and($invoice->billing_month)->toBeNull();

    generator()->generateOneOff($student, [['fee_head_id' => $books->id]]);
})->throws(ValidationException::class);

it('creates advance month invoices inside the session only', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);

    $invoices = generator()->ensureAdvanceMonths($student, [Carbon::parse('2026-06-01'), Carbon::parse('2026-07-15')]);

    expect($invoices)->toHaveCount(2)->and(FeeInvoice::count())->toBe(2)
        ->and(generator()->ensureAdvanceMonths($student, [Carbon::parse('2026-06-01')]))->toHaveCount(1)
        ->and(FeeInvoice::count())->toBe(2);

    expect(fn () => generator()->ensureAdvanceMonths($student, [Carbon::parse('2027-02-01')]))->toThrow(ValidationException::class);
    expect(fn () => generator()->ensureAdvanceMonths($student, [Carbon::parse('2025-12-01')]))->toThrow(ValidationException::class);
});

it('uses the credit a student already holds on a new invoice', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    StudentCredit::create(['student_id' => $student->id, 'balance' => taka(500)]);

    generator()->generateMonthly(Carbon::parse('2026-03-01'));

    $invoice = FeeInvoice::first();
    expect($invoice->paid->poisha)->toBe(50000)->and($invoice->due->poisha)->toBe(80000)->and($invoice->status)->toBe(InvoiceStatus::Partial)
        ->and(StudentCredit::first()->balance->poisha)->toBe(0);
});

it('never double bills even when two runs race on the unique index', function () {
    $p = placement();
    feeSetup($p);
    $student = enrolled($p);
    generator()->generateMonthly(Carbon::parse('2026-03-01'));

    expect(fn () => FeeInvoice::factory()->create(['student_id' => $student->id, 'type' => 'monthly', 'billing_month' => '2026-03-01']))->toThrow(QueryException::class);
    expect(Student::count())->toBe(1);
});
