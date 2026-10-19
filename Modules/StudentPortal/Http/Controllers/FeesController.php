<?php

namespace Modules\StudentPortal\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Controllers\Concerns\ServesPdf;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Services\FeeSettings;
use Modules\Fees\Services\Gateway\GatewayManager;
use Modules\Fees\Services\GatewayPayments;
use Modules\Fees\Services\InvoiceGenerator;
use Modules\Fees\Services\InvoicePdf;
use Modules\Fees\Services\PaymentService;
use Modules\Fees\Services\ReceiptService;
use Modules\Fees\Services\StudentFeeDesk;
use Modules\Fees\Support\Money;
use Modules\StudentPortal\Support\FeeDisplay;
use Modules\Students\Models\Student;

/**
 * The student's fee screens. Every lookup goes through StudentFeeDesk with the student from the session, so a
 * tampered invoice or payment id finds nothing (404), and amounts are always worked out on the server.
 */
class FeesController
{
    use ServesPdf;

    public function index(Request $request, StudentFeeDesk $desk, InvoiceGenerator $generator, FeeSettings $settings): View
    {
        $student = $request->user('student')->load(['schoolClass', 'session']);
        $payments = $desk->payments($student);
        $pendingIds = $this->pendingInvoiceIds($payments);
        $invoices = $desk->invoices($student)->load('exam')->keyBy('id');
        $monthly = $generator->estimateMonth($student, now());
        $grid = $desk->monthGrid($student);
        $current = collect($grid)->first(fn (array $entry): bool => $entry['month']->isSameMonth(now()));
        $currentInvoice = $current && $current['invoice_id'] ? $invoices->get($current['invoice_id']) : null;

        return view('student-portal::pages.fees.index', [
            'student' => $student, 'due' => $desk->totalDue($student), 'credit' => $desk->credit($student),
            'open' => $desk->openInvoices($student), 'monthly' => $monthly, 'dueDay' => (int) $settings->get('due_day'),
            'months' => array_map(fn (array $entry): array => $this->monthTile($entry, $invoices, $pendingIds, $monthly), $grid),
            'thisMonth' => $current ? ['month' => $current['month'], 'status' => $currentInvoice ? FeeDisplay::invoiceStatus($currentInvoice, in_array($currentInvoice->id, $pendingIds, true)) : ($current['status'] === 'none' ? 'none' : 'upcoming')] : null,
            'others' => $invoices->filter(fn (FeeInvoice $invoice): bool => $invoice->type !== FeeHeadType::Monthly && $invoice->status !== InvoiceStatus::Cancelled)->sortBy('due_date')->values(),
            'pendingIds' => $pendingIds,
            'lastPayment' => $payments->first(fn (Payment $payment): bool => $payment->status === PaymentStatus::Approved),
            'pendingAmount' => Money::poisha((int) $payments->filter(fn (Payment $payment): bool => $payment->status === PaymentStatus::Pending)->sum(fn (Payment $payment): int => $payment->amount->poisha)),
            'result' => $this->flashedResult($payments),
        ]);
    }

    public function invoice(Request $request, int $invoice, StudentFeeDesk $desk): View
    {
        $student = $request->user('student');
        $model = $desk->invoice($student, $invoice)->load('exam');

        return view('student-portal::pages.fees.invoice', [
            'student' => $student, 'invoice' => $model,
            'pending' => in_array($model->id, $this->pendingInvoiceIds($desk->payments($student)), true),
        ]);
    }

    public function invoicePdf(Request $request, int $invoice, StudentFeeDesk $desk, InvoicePdf $pdf): Response
    {
        $model = $desk->invoice($request->user('student'), $invoice);

        return $this->pdf($pdf->render($model), "invoice-{$model->invoice_no}.pdf");
    }

    public function payments(Request $request, StudentFeeDesk $desk): View
    {
        $payments = $desk->payments($request->user('student'));
        $invoices = FeeInvoice::query()->with(['items.head', 'exam'])->whereIn('id', $payments->flatMap(fn (Payment $payment): array => $payment->invoice_ids ?? [])->unique()->all())->get()->keyBy('id');

        return view('student-portal::pages.fees.payments', ['payments' => $payments, 'invoices' => $invoices, 'result' => $this->flashedResult($payments)]);
    }

    public function receipt(Request $request, int $payment, StudentFeeDesk $desk, ReceiptService $receipts): Response
    {
        $model = $desk->payment($request->user('student'), $payment);

        return $this->pdf($receipts->pdf($model), "receipt-{$model->receipt_no}.pdf");
    }

    public function ledger(Request $request, StudentFeeDesk $desk): View
    {
        $student = $request->user('student');

        return view('student-portal::pages.fees.ledger', ['entries' => $desk->ledger($student), 'due' => $desk->totalDue($student), 'credit' => $desk->credit($student)]);
    }

    /**
     * Step 1–3 of paying: pick what to pay (bills, or months in advance), choose how, then confirm. One page, driven
     * by Alpine; the server decides every amount.
     */
    public function pay(Request $request, StudentFeeDesk $desk, InvoiceGenerator $generator, FeeSettings $settings, GatewayManager $gateways): View
    {
        $student = $request->user('student');

        return view('student-portal::pages.fees.pay', [
            'open' => $desk->openInvoices($student),
            'advance' => $this->advanceMonths($student, $generator),
            'manualMethods' => collect((array) $settings->get('manual_methods')),
            'gateway' => $gateways->active()?->name(),
            'preselected' => $request->integer('invoice') ?: null,
            'student' => $student, 'dueDay' => (int) $settings->get('due_day'),
        ]);
    }

    public function payStore(Request $request, PaymentService $payments, GatewayPayments $gatewayPayments, InvoiceGenerator $generator, FeeSettings $settings, GatewayManager $gateways): RedirectResponse
    {
        $student = $request->user('student');
        $manual = collect((array) $settings->get('manual_methods'))->pluck('method')->all();

        $data = $request->validate([
            'invoice_ids' => ['array', 'max:24'], 'invoice_ids.*' => ['integer'],
            'advance_months' => ['array', 'max:6'], 'advance_months.*' => ['date_format:Y-m'],
            'method' => ['required', Rule::in([...$manual, ...($gateways->active() ? ['gateway'] : [])])],
            'transaction_id' => ['nullable', 'string', 'max:60'], 'sender_number' => ['nullable', 'string', 'max:20'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $ids = array_map('intval', $data['invoice_ids'] ?? []);

        foreach ($data['advance_months'] ?? [] as $month) {
            abort_unless(Carbon::createFromFormat('Y-m', $month)->startOfMonth()->gt(now()->startOfMonth()), 422);
        }

        $months = array_map(fn (string $month): Carbon => Carbon::createFromFormat('Y-m', $month)->startOfMonth(), $data['advance_months'] ?? []);

        if ($months !== []) {
            $ids = [...$ids, ...$generator->ensureAdvanceMonths($student, $months)->pluck('id')->all()];
        }

        $ids = array_values(array_unique($ids));

        if ($data['method'] === 'gateway') {
            return redirect()->away($gatewayPayments->start($student, $ids));
        }

        $payments->submitManual($student, $ids, PaymentMethod::from($data['method']), (string) ($data['transaction_id'] ?? ''), $data['sender_number'] ?? null, $request->file('screenshot'));

        return redirect()->route('portal.fees.payments')->with('payment_submitted', true);
    }

    /**
     * Ids of the bills that have a payment waiting to be checked.
     *
     * @param  Collection<int, Payment>  $payments
     * @return list<int>
     */
    protected function pendingInvoiceIds(Collection $payments): array
    {
        return $payments->filter(fn (Payment $payment): bool => $payment->status === PaymentStatus::Pending)
            ->flatMap(fn (Payment $payment): array => $payment->invoice_ids ?? [])->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }

    /**
     * What the "payment result" card shows after a payment was just submitted or an online checkout returned.
     *
     * @param  Collection<int, Payment>  $payments
     * @return array{state: string, payment: Payment|null}|null
     */
    protected function flashedResult(Collection $payments): ?array
    {
        $state = session('payment_status') ?? (session('payment_submitted') ? 'pending' : null);

        if ($state === null) {
            return null;
        }

        return ['state' => $state === 'approved' ? 'approved' : ($state === 'pending' ? 'pending' : 'failed'), 'payment' => $payments->first()];
    }

    /**
     * One tile of the month grid.
     *
     * @param  array{month: Carbon, status: string, invoice_id: int|null, due: Money}  $entry
     * @param  Collection<int, FeeInvoice>  $invoices
     * @param  list<int>  $pendingIds
     * @return array{month: Carbon, status: string, invoice_id: int|null, amount: string, current: bool}
     */
    protected function monthTile(array $entry, Collection $invoices, array $pendingIds, Money $estimate): array
    {
        $invoice = $entry['invoice_id'] ? $invoices->get($entry['invoice_id']) : null;
        $status = $invoice ? FeeDisplay::invoiceStatus($invoice, in_array($invoice->id, $pendingIds, true)) : ($entry['status'] === 'none' ? 'none' : 'upcoming');

        $amount = match (true) {
            $status === 'none' => '—',
            $status === 'waived' => Money::zero()->format(),
            $status === 'partial' => __('student-portal::messages.remaining').' '.$entry['due']->format(),
            $invoice !== null => $invoice->total->format(),
            default => $estimate->format(),
        };

        return ['month' => $entry['month'], 'status' => $status, 'invoice_id' => $entry['invoice_id'], 'amount' => $amount, 'current' => $entry['month']->isSameMonth(now())];
    }

    /**
     * The next few months with no bill yet (inside the session), each with an estimate of its price.
     *
     * @return list<array{value: string, label: Carbon, estimate: Money}>
     */
    protected function advanceMonths(Student $student, InvoiceGenerator $generator): array
    {
        $session = $student->session;
        $billed = FeeInvoice::query()->where(['student_id' => $student->id, 'type' => FeeHeadType::Monthly->value])->pluck('billing_month')->map(fn ($month) => $month->format('Y-m'))->all();
        $months = [];

        for ($i = 1; $i <= 6; $i++) {
            $month = now()->startOfMonth()->addMonths($i);

            if ($session?->end_date && $month->gt($session->end_date) || in_array($month->format('Y-m'), $billed, true)) {
                continue;
            }

            $estimate = $generator->estimateMonth($student, $month);

            if ($estimate->isPositive()) {
                $months[] = ['value' => $month->format('Y-m'), 'label' => Carbon::instance($month), 'estimate' => $estimate];
            }
        }

        return $months;
    }
}
