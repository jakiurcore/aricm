<?php

namespace Modules\Fees\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\Academic\Models\SchoolClass;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\FeeHead;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\FeeInvoiceItem;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\Money;
use Modules\Fees\Support\ReportTable;
use Modules\Students\Models\Student;

/**
 * The accountant's reports. Each returns a ReportTable that the UI, PDF and Excel export render alike.
 */
class FeeReports
{
    public function __construct(protected StudentFeeDesk $desk) {}

    /**
     * Every approved payment between two dates, newest last, with the day's totals by method.
     */
    public function cashBook(CarbonInterface $from, CarbonInterface $to): ReportTable
    {
        $payments = Payment::query()->with(['student.schoolClass', 'receiver', 'verifier'])
            ->where('status', PaymentStatus::Approved->value)
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('paid_at')->orderBy('id')->get();

        return new ReportTable(
            __('fees::reports.cash_book'),
            [
                $this->col('date', 'date'), $this->col('receipt_no'), $this->col('student'), $this->col('class'),
                $this->col('method'), $this->col('channel'), $this->col('collector'), $this->col('amount', 'money'),
            ],
            $payments->map(fn (Payment $payment): array => [
                'date' => $payment->paid_at->toDateString(), 'receipt_no' => (string) $payment->receipt_no, 'student' => $payment->student->displayName().' ('.$payment->student->student_id.')',
                'class' => (string) $payment->student->schoolClass?->displayName(), 'method' => $payment->method->label(), 'channel' => $payment->channel->label(),
                'collector' => (string) ($payment->receiver?->name ?? $payment->verifier?->name), 'amount' => $payment->amount->poisha,
            ])->all(),
            ['amount' => $payments->sum(fn (Payment $payment): int => $payment->amount->poisha)],
        );
    }

    /**
     * For one billing month: what was billed to each class, what came in, what is still owed.
     */
    public function monthlyCollection(CarbonInterface $month): ReportTable
    {
        $rows = FeeInvoice::query()
            ->selectRaw('class_id, count(*) as invoices, sum(total) as billed, sum(paid) as collected, sum(due) as outstanding')
            ->where('type', FeeHeadType::Monthly->value)->where('status', '!=', InvoiceStatus::Cancelled->value)
            ->whereDate('billing_month', $month->copy()->startOfMonth()->toDateString())
            ->groupBy('class_id')->get();

        $classes = SchoolClass::query()->whereIn('id', $rows->pluck('class_id'))->get()->keyBy('id');
        $table = $rows->map(fn ($row): array => [
            'class' => $classes[$row->class_id]->displayName(), 'order' => $classes[$row->class_id]->numeric_order,
            'invoices' => (int) $row->invoices, 'billed' => (int) $row->billed, 'collected' => (int) $row->collected, 'outstanding' => (int) $row->outstanding,
        ])->sortBy('order')->values()->map(fn (array $row): array => collect($row)->except('order')->all());

        return new ReportTable(
            __('fees::reports.monthly'), [$this->col('class'), $this->col('invoices', 'int'), $this->col('billed', 'money'), $this->col('collected', 'money'), $this->col('outstanding', 'money')],
            $table->all(), ['invoices' => $table->sum('invoices'), 'billed' => $table->sum('billed'), 'collected' => $table->sum('collected'), 'outstanding' => $table->sum('outstanding')],
        );
    }

    /**
     * Per fee head: billed (net of concessions) and collected. A payment is spread over the heads of an invoice in
     * proportion to their share of it, so "collected" is exact for fully paid invoices and proportional for part-paid.
     */
    public function headWise(CarbonInterface $from, CarbonInterface $to): ReportTable
    {
        $totals = [];

        FeeInvoiceItem::query()->with('invoice')
            ->whereHas('invoice', fn ($query) => $query->where('status', '!=', InvoiceStatus::Cancelled->value)->whereBetween('issued_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))
            ->chunkById(500, function ($items) use (&$totals): void {
                foreach ($items as $item) {
                    $totals[$item->fee_head_id]['billed'] = ($totals[$item->fee_head_id]['billed'] ?? 0) + $item->net->poisha;
                    $invoiceTotal = $item->invoice->subtotal->minus($item->invoice->discount)->poisha;
                    $share = $invoiceTotal > 0 ? (int) floor($item->net->poisha * min($item->invoice->paid->poisha, $invoiceTotal) / $invoiceTotal) : 0;
                    $totals[$item->fee_head_id]['collected'] = ($totals[$item->fee_head_id]['collected'] ?? 0) + $share;
                }
            });

        $heads = FeeHead::query()->whereIn('id', array_keys($totals))->orderBy('sort_order')->get();
        $rows = $heads->map(fn (FeeHead $head): array => ['head' => $head->displayName(), 'billed' => $totals[$head->id]['billed'], 'collected' => $totals[$head->id]['collected'], 'outstanding' => $totals[$head->id]['billed'] - $totals[$head->id]['collected']]);

        return new ReportTable(__('fees::reports.head_wise'), [$this->col('head'), $this->col('billed', 'money'), $this->col('collected', 'money'), $this->col('outstanding', 'money')], $rows->all(), ['billed' => $rows->sum('billed'), 'collected' => $rows->sum('collected'), 'outstanding' => $rows->sum('outstanding')]);
    }

    /**
     * Students who owe money for bills that are due by the given date, biggest due first.
     */
    public function dues(?int $classId = null, ?CarbonInterface $upTo = null): ReportTable
    {
        $upTo ??= now();

        $rows = FeeInvoice::query()
            ->selectRaw('student_id, count(*) as invoices, sum(due) as owed, min(due_date) as oldest')
            ->whereIn('status', array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open()))
            ->whereDate('due_date', '<=', $upTo->toDateString())
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->groupBy('student_id')->orderByDesc('due')->get();

        $students = Student::query()->with('schoolClass')->whereIn('id', $rows->pluck('student_id'))->get()->keyBy('id');
        $table = $rows->map(fn ($row): array => [
            'student' => $students[$row->student_id]->displayName().' ('.$students[$row->student_id]->student_id.')', 'class' => (string) $students[$row->student_id]->schoolClass?->displayName(),
            'roll' => $students[$row->student_id]->roll, 'phone' => (string) $students[$row->student_id]->guardian_phone, 'invoices' => (int) $row->invoices,
            'oldest' => $row->oldest ? Carbon::parse($row->oldest)->toDateString() : null, 'due' => (int) $row->owed,
        ]);

        return new ReportTable(__('fees::reports.dues'), [$this->col('student'), $this->col('class'), $this->col('roll', 'int'), $this->col('phone'), $this->col('invoices', 'int'), $this->col('oldest', 'date'), $this->col('due', 'money')], $table->all(), ['invoices' => $table->sum('invoices'), 'due' => $table->sum('due')]);
    }

    /**
     * Collection per person who took the money: counter staff, or the verifier of a mobile-wallet payment.
     */
    public function collectors(CarbonInterface $from, CarbonInterface $to): ReportTable
    {
        $payments = Payment::query()->with(['receiver', 'verifier'])
            ->where('status', PaymentStatus::Approved->value)
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])->get();

        $rows = $payments->groupBy(fn (Payment $payment): string => (string) ($payment->received_by ?? $payment->verified_by ?? 0))->map(function ($group): array {
            $first = $group->first();

            return ['collector' => (string) ($first->receiver?->name ?? $first->verifier?->name ?? __('fees::reports.online')), 'payments' => $group->count(), 'amount' => $group->sum(fn (Payment $payment): int => $payment->amount->poisha)];
        })->sortByDesc('amount')->values();

        return new ReportTable(__('fees::reports.collectors'), [$this->col('collector'), $this->col('payments', 'int'), $this->col('amount', 'money')], $rows->all(), ['payments' => $rows->sum('payments'), 'amount' => $rows->sum('amount')]);
    }

    public function studentLedger(Student $student): ReportTable
    {
        $entries = $this->desk->ledger($student);

        return new ReportTable(
            __('fees::reports.ledger').' — '.$student->displayName(),
            [$this->col('date', 'date'), $this->col('kind'), $this->col('reference'), $this->col('description'), $this->col('debit', 'money'), $this->col('credit', 'money'), $this->col('balance', 'money')],
            array_map(fn (array $entry): array => [
                'date' => $entry['date']->toDateString(), 'kind' => __('fees::reports.kinds.'.$entry['kind']), 'reference' => $entry['reference'], 'description' => $entry['description'],
                'debit' => $entry['debit']->poisha, 'credit' => $entry['credit']->poisha, 'balance' => $entry['balance']->poisha,
            ], $entries),
            ['debit' => array_sum(array_map(fn (array $entry): int => $entry['debit']->poisha, $entries)), 'credit' => array_sum(array_map(fn (array $entry): int => $entry['credit']->poisha, $entries))],
        );
    }

    /**
     * Today's headline numbers for the admin dashboard.
     *
     * @return array<string, int>
     */
    public function dashboard(?CarbonInterface $today = null): array
    {
        $today = Carbon::instance($today ?? now());
        $approved = PaymentStatus::Approved->value;
        $open = array_map(fn (InvoiceStatus $status): string => $status->value, InvoiceStatus::open());

        return [
            'billed_this_month' => (int) FeeInvoice::query()->where('type', FeeHeadType::Monthly->value)->whereDate('billing_month', $today->copy()->startOfMonth()->toDateString())->where('status', '!=', InvoiceStatus::Cancelled->value)->sum('total'),
            'collected_this_month' => (int) Payment::query()->where('status', $approved)->whereBetween('paid_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])->sum('amount'),
            'collected_today' => (int) Payment::query()->where('status', $approved)->whereBetween('paid_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->sum('amount'),
            'pending_verifications' => Payment::query()->where('status', PaymentStatus::Pending->value)->count(),
            'outstanding' => (int) FeeInvoice::query()->whereIn('status', $open)->sum('due'),
        ];
    }

    /**
     * @return array{key: string, label: string, type: string}
     */
    protected function col(string $key, string $type = 'text'): array
    {
        return ['key' => $key, 'label' => __("fees::reports.columns.{$key}"), 'type' => $type];
    }
}
