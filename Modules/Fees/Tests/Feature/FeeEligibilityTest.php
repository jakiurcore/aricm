<?php

use Illuminate\Support\Carbon;
use Modules\Exams\Services\AdmitCardService;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Models\ExamFeeAmount;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\InvoiceGenerator;
use Modules\Fees\Services\PaymentService;
use Modules\Results\Services\ResultDirectory;
use Modules\Results\Services\ResultProcessor;

function examWithFee(array $p, string $rule = 'exam_invoice'): array
{
    FeeHead::factory()->exam()->create();
    $exam = examFor($p);
    ExamFeeAmount::create(['exam_id' => $exam->id, 'class_id' => $p['class']->id, 'amount' => taka(200)]);
    $student = enrolled($p);
    app(InvoiceGenerator::class)->generateExam($exam);
    app(FeeSettings::class)->update(['admit_card_requires' => $rule]);

    return [$exam, $student];
}

it('holds back the admit card until the exam fee is paid (exam_invoice)', function () {
    [$exam, $student] = examWithFee(placement());
    $service = app(AdmitCardService::class);

    $denied = $service->eligibility($exam, $student);
    expect($denied->allowed)->toBeFalse()->and($denied->reason)->toBe(__('fees::messages.eligibility.exam_fee_unpaid'));

    app(PaymentService::class)->collectAtCounter($student, taka(200), PaymentMethod::Cash, [], userWithRole('accountant'));
    expect($service->eligibility($exam, $student)->allowed)->toBeTrue();
});

it('lets a student with no exam invoice through under exam_invoice', function () {
    $p = placement();
    $exam = examFor($p);
    $student = enrolled($p);

    expect(app(AdmitCardService::class)->eligibility($exam, $student)->allowed)->toBeTrue();
});

it('lets a waived exam fee through', function () {
    [$exam, $student] = examWithFee(placement());
    app(PaymentService::class)->waive($student->fresh()->load([])->hasMany(FeeInvoice::class)->first() ?? FeeInvoice::first(), userWithRole('admin'), 'মওকুফ');

    expect(app(AdmitCardService::class)->eligibility($exam, $student)->allowed)->toBeTrue();
});

it('demands every due up to the exam month under all_dues_to_exam_month', function () {
    $p = placement();
    feeSetup($p);
    $exam = examFor($p);                       // exam in June 2026
    $student = enrolled($p);
    invoicesFor($student, ['2026-05-01', '2026-08-01']);   // May is due before June ends, August is not
    app(FeeSettings::class)->update(['admit_card_requires' => 'all_dues_to_exam_month']);
    $service = app(AdmitCardService::class);

    expect($service->eligibility($exam, $student)->allowed)->toBeFalse();

    app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [FeeInvoice::orderBy('billing_month')->first()->id], userWithRole('accountant'));
    expect($service->eligibility($exam, $student)->allowed)->toBeTrue();
});

it('ignores fees entirely under none', function () {
    [$exam, $student] = examWithFee(placement(), 'none');

    expect(app(AdmitCardService::class)->eligibility($exam, $student)->allowed)->toBeTrue();
});

it('hides a published result for a student with overdue fees only when the setting is on', function () {
    $p = placement();
    feeSetup($p);
    $setup = gradedExam($p);
    $student = enrolled($p);
    enterMarks($setup['exam'], $p['class']->id, $student, [$setup['first']->id => 90, $setup['second']->id => 90]);
    app(ResultProcessor::class)->process($setup['exam'], $p['class']->id);
    $setup['exam']->forceFill(['result_published' => true])->save();
    invoicesFor($student, ['2026-01-01']);       // due 10 January: long overdue
    $directory = app(ResultDirectory::class);

    expect($directory->eligibility($setup['exam'], $student)->allowed)->toBeTrue();

    app(FeeSettings::class)->update(['result_requires_no_dues' => true]);
    $blocked = $directory->eligibility($setup['exam'], $student);
    expect($blocked->allowed)->toBeFalse()->and($blocked->reason)->toBe(__('fees::messages.eligibility.result_dues'));

    app(PaymentService::class)->collectAtCounter($student, taka(1300), PaymentMethod::Cash, [], userWithRole('accountant'));
    expect($directory->eligibility($setup['exam'], $student)->allowed)->toBeTrue();
});

it('does not block a result for a bill that is not due yet', function () {
    $p = placement();
    feeSetup($p);
    $setup = gradedExam($p);
    $student = enrolled($p);
    invoicesFor($student, [Carbon::now()->addMonths(2)->startOfMonth()->toDateString()]);
    app(FeeSettings::class)->update(['result_requires_no_dues' => true]);

    expect(app(ResultDirectory::class)->eligibility($setup['exam'], $student)->allowed)->toBeTrue();
});
