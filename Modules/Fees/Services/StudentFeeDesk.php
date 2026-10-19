<?php

namespace Modules\Fees\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Academic\Models\AcademicSession;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Models\StudentCredit;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

/**
 * Everything the student portal shows about fees. Every query is scoped to the student passed in, so a tampered id can
 * only ever find the student's own records.
 */
class StudentFeeDesk
{
    public function totalDue(Student $student): Money
    {
        return Money::poisha((int) FeeInvoice::query()->where('student_id', $student->id)->whereIn('status', $this->openStatuses())->sum('due'));
    }

    public function credit(Student $student): Money
    {
        return StudentCredit::query()->where('student_id', $student->id)->first()?->balance ?? Money::zero();
    }

    /**
     * @return Collection<int, FeeInvoice>
     */
    public function openInvoices(Student $student): Collection
    {
        return FeeInvoice::query()->with('items.head')->where('student_id', $student->id)->whereIn('status', $this->openStatuses())->orderByRaw('due_date is null')->orderBy('due_date')->orderBy('id')->get();
    }

    public function invoice(Student $student, int $invoiceId): FeeInvoice
    {
        return FeeInvoice::query()->with(['items.head', 'allocations.payment'])->where('student_id', $student->id)->findOrFail($invoiceId);
    }

    /**
     * @return Collection<int, FeeInvoice>
     */
    public function invoices(Student $student): Collection
    {
        return FeeInvoice::query()->with('items.head')->where('student_id', $student->id)->orderByDesc('issued_at')->orderByDesc('id')->get();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function payments(Student $student): Collection
    {
        return Payment::query()->where('student_id', $student->id)->orderByDesc('created_at')->orderByDesc('id')->get();
    }

    public function payment(Student $student, int $paymentId): Payment
    {
        return Payment::query()->with('allocations.invoice')->where('student_id', $student->id)->findOrFail($paymentId);
    }

    /**
     * Twelve months of the student's session with how each stands: paid, partial, unpaid, overdue, waived, upcoming
     * (no bill yet) or none.
     *
     * @return list<array{month: Carbon, status: string, invoice_id: int|null, due: Money}>
     */
    public function monthGrid(Student $student, ?CarbonInterface $today = null): array
    {
        $today = Carbon::instance($today ?? now());
        $session = AcademicSession::query()->find($student->session_id);
        $start = ($session?->start_date ?? Carbon::create($today->year, 1, 1))->copy()->startOfMonth();
        $invoices = FeeInvoice::query()->where(['student_id' => $student->id, 'type' => FeeHeadType::Monthly->value])->get()->keyBy(fn (FeeInvoice $invoice): string => $invoice->billing_month->format('Y-m'));
        $grid = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $invoice = $invoices->get($month->format('Y-m'));

            $status = match (true) {
                $invoice === null => $month->gt($today) ? 'upcoming' : 'none',
                $invoice->status === InvoiceStatus::Paid => 'paid',
                $invoice->status === InvoiceStatus::Waived => 'waived',
                $invoice->status === InvoiceStatus::Cancelled => 'none',
                $invoice->due_date && $invoice->due_date->lt($today->copy()->startOfDay()) => 'overdue',
                $invoice->status === InvoiceStatus::Partial => 'partial',
                default => 'unpaid',
            };

            $grid[] = ['month' => $month, 'status' => $status, 'invoice_id' => $invoice?->id, 'due' => $invoice?->due ?? Money::zero()];
        }

        return $grid;
    }

    /**
     * The statement: bills (debit) and approved payments or waivers (credit) in date order with a running balance.
     * A positive balance is what the student owes.
     *
     * @return list<array{date: Carbon, kind: string, reference: string, description: string, debit: Money, credit: Money, balance: Money}>
     */
    public function ledger(Student $student): array
    {
        $entries = [];

        foreach (FeeInvoice::query()->with('items.head')->where('student_id', $student->id)->where('status', '!=', InvoiceStatus::Cancelled->value)->get() as $invoice) {
            $entries[] = ['date' => $invoice->issued_at ?? $invoice->created_at, 'kind' => 'invoice', 'reference' => $invoice->invoice_no, 'description' => $invoice->items->map(fn ($item) => $item->head->displayName())->implode(', '), 'debit' => $invoice->total, 'credit' => Money::zero()];

            if ($invoice->status === InvoiceStatus::Waived) {
                $entries[] = ['date' => $invoice->updated_at, 'kind' => 'waiver', 'reference' => $invoice->invoice_no, 'description' => (string) $invoice->waive_reason, 'debit' => Money::zero(), 'credit' => $invoice->total->minus($invoice->paid)];
            }
        }

        foreach (Payment::query()->where(['student_id' => $student->id, 'status' => PaymentStatus::Approved->value])->get() as $payment) {
            $entries[] = ['date' => $payment->paid_at ?? $payment->created_at, 'kind' => 'payment', 'reference' => (string) $payment->receipt_no, 'description' => $payment->method->label(), 'debit' => Money::zero(), 'credit' => $payment->amount];
        }

        usort($entries, fn (array $a, array $b): int => [$a['date']->timestamp, $a['kind'] === 'invoice' ? 0 : 1] <=> [$b['date']->timestamp, $b['kind'] === 'invoice' ? 0 : 1]);

        $balance = Money::zero();

        return array_map(function (array $entry) use (&$balance): array {
            $balance = $balance->plus($entry['debit'])->minus($entry['credit']);

            return [...$entry, 'balance' => $balance];
        }, $entries);
    }

    /**
     * @return list<string>
     */
    protected function openStatuses(): array
    {
        return array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open());
    }
}
