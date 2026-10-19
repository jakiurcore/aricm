<?php

namespace Modules\Fees\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\Exams\Models\Exam;
use Modules\Exams\Support\Eligibility;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Students\Models\Student;

/**
 * The fee rules behind admit cards and result viewing. Fees implements the contracts that Exams and Results define;
 * those modules never import this class.
 */
class EligibilityChecker
{
    public function __construct(protected FeeSettings $settings) {}

    /**
     * admit_card_requires: "exam_invoice" (the exam fee must be settled, if there is one), "all_dues_to_exam_month"
     * (every bill up to the exam's month must be settled) or "none".
     */
    public function admitCard(Exam $exam, Student $student): Eligibility
    {
        $open = array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open());

        return match ($this->settings->admitCardRule()) {
            'none' => Eligibility::allow(),
            'exam_invoice' => FeeInvoice::query()->where(['student_id' => $student->id, 'exam_id' => $exam->id, 'type' => FeeHeadType::Exam->value])->whereIn('status', $open)->exists()
                ? Eligibility::deny(__('fees::messages.eligibility.exam_fee_unpaid'), 'portal.fees')
                : Eligibility::allow(),
            'all_dues_to_exam_month' => $this->hasDuesUpTo($student, Carbon::instance($exam->start_date ?? now())->endOfMonth())
                ? Eligibility::deny(__('fees::messages.eligibility.dues_unpaid'), 'portal.fees')
                : Eligibility::allow(),
        };
    }

    /**
     * Only when "hide results until fees are paid" is on: any overdue open bill blocks the result.
     */
    public function result(Exam $exam, Student $student): Eligibility
    {
        if (! $this->settings->resultRequiresNoDues()) {
            return Eligibility::allow();
        }

        return $this->hasDuesUpTo($student, now()->endOfDay())
            ? Eligibility::deny(__('fees::messages.eligibility.result_dues'), 'portal.fees')
            : Eligibility::allow();
    }

    protected function hasDuesUpTo(Student $student, CarbonInterface $date): bool
    {
        return FeeInvoice::query()
            ->where('student_id', $student->id)
            ->whereIn('status', array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open()))
            ->whereDate('due_date', '<=', $date->toDateString())
            ->exists();
    }
}
