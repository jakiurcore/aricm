<?php

namespace Modules\Fees\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Models\PaymentAllocation;
use Modules\Fees\Models\StudentCredit;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

/**
 * Spreads an approved payment over invoices and keeps whatever is left as the student's credit. Invoices are locked
 * for the duration, so two payments approved at the same moment can never pay the same due twice.
 */
class PaymentAllocator
{
    /**
     * Allocate a payment to the chosen invoices (or oldest-due-first when none were chosen). Anything the invoices
     * cannot take becomes credit. Always call inside a transaction.
     *
     * @param  list<int>|null  $invoiceIds
     * @return Collection<int, PaymentAllocation>
     */
    public function allocate(Payment $payment, ?array $invoiceIds = null): Collection
    {
        return DB::transaction(function () use ($payment, $invoiceIds): Collection {
            $remaining = $payment->amount;
            $allocations = collect();

            foreach ($this->openInvoices($payment->student_id, $invoiceIds) as $invoice) {
                if (! $remaining->isPositive()) {
                    break;
                }

                $take = $remaining->min($invoice->due);

                if (! $take->isPositive()) {
                    continue;
                }

                $allocations->push(PaymentAllocation::query()->create(['payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount' => $take, 'source' => PaymentAllocation::FROM_PAYMENT]));
                $invoice->recalculate();
                $remaining = $remaining->minus($take);
            }

            if ($remaining->isPositive()) {
                $this->addCredit($payment->student_id, $remaining);
            }

            return $allocations;
        });
    }

    /**
     * Use the student's credit on their oldest open invoices. Returns the amount applied.
     */
    public function applyCredit(Student $student): Money
    {
        return DB::transaction(function () use ($student): Money {
            $credit = StudentCredit::query()->where('student_id', $student->id)->lockForUpdate()->first();

            if (! $credit || ! $credit->balance->isPositive()) {
                return Money::zero();
            }

            $available = $credit->balance;

            foreach ($this->openInvoices($student->id, null) as $invoice) {
                if (! $available->isPositive()) {
                    break;
                }

                $take = $available->min($invoice->due);

                if (! $take->isPositive()) {
                    continue;
                }

                PaymentAllocation::query()->create(['payment_id' => null, 'invoice_id' => $invoice->id, 'amount' => $take, 'source' => PaymentAllocation::FROM_CREDIT]);
                $invoice->recalculate();
                $available = $available->minus($take);
            }

            $applied = $credit->balance->minus($available);
            $credit->update(['balance' => $available]);

            return $applied;
        });
    }

    /**
     * @param  list<int>|null  $invoiceIds
     * @return Collection<int, FeeInvoice>
     */
    protected function openInvoices(int $studentId, ?array $invoiceIds): Collection
    {
        return FeeInvoice::query()
            ->where('student_id', $studentId)
            ->whereIn('status', array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open()))
            ->when($invoiceIds, fn ($query) => $query->whereIn('id', $invoiceIds))
            ->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    protected function addCredit(int $studentId, Money $amount): void
    {
        $credit = StudentCredit::query()->where('student_id', $studentId)->lockForUpdate()->first()
            ?? StudentCredit::query()->create(['student_id' => $studentId, 'balance' => Money::zero()]);

        $credit->update(['balance' => $credit->balance->plus($amount)]);
    }
}
