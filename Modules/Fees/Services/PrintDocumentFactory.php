<?php

namespace Modules\Fees\Services;

use Illuminate\Support\Collection;
use Modules\Core\Services\QrCodeService;
use Modules\Core\Support\Bangla;
use Modules\Core\Support\Branding;
use Modules\Core\Support\SignedHash;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

/**
 * Builds the data behind the three A5 landscape fee documents (money receipt, invoice, due notice), which share one
 * layout (`fees::pdf.partials.a5-sheet`), and the thermal receipt.
 */
class PrintDocumentFactory
{
    public const string GREEN = '#0B6B3A';

    public const string BLUE = '#16307A';

    public const string RED = '#D6202A';

    public function __construct(protected QrCodeService $qr, protected FeeSettings $settings) {}

    /**
     * @return array<string, mixed>
     */
    public function receipt(Payment $payment): array
    {
        $lines = $this->receiptLines($payment);
        $verifyHash = SignedHash::make('receipt', $payment->id);
        $receiver = $payment->receiver?->name ?? $payment->verifier?->name;

        return [
            'kind' => 'receipt',
            'title' => __('fees::pdf.receipt'),
            'titleColor' => self::GREEN,
            'number' => $payment->receipt_no,
            'date' => bn_date($payment->paid_at),
            'student' => $this->studentFields($payment->student),
            'tableLabel' => __('fees::pdf.paid_invoices'),
            'rows' => array_map(fn (array $line): array => ['label' => $line['description'] ?: $line['invoice_no'], 'amount' => $line['amount']->format()], $lines),
            'sums' => [['label' => __('fees::pdf.total_paid'), 'value' => $payment->amount->format(), 'weight' => 'bold', 'size' => 3.7, 'color' => self::GREEN, 'rule' => 0.5]],
            'boxLabel' => __('fees::pdf.amount_received'),
            'boxAmount' => $payment->amount->format(),
            'boxWords' => $payment->amount->inWords(),
            'pay' => array_values(array_filter([
                [__('fees::pdf.method'), $payment->method->label()],
                $payment->transaction_id ? [__('fees::pdf.transaction_id'), $payment->transaction_id] : null,
                [__('fees::pdf.received_by'), $receiver ?? '—'],
            ])),
            'stamp' => ['text' => 'PAID', 'color' => '#15A04A'],
            'qr' => $this->qr->forVerification('receipt', $verifyHash, 120),
            'verifyUrl' => $this->verifyHost(),
            'rightWidth' => 58.7,
            'tableAmountWidth' => 38.1,
            'footer' => (string) $this->settings->get('receipt_footer'),
            'signLeft' => [(string) $receiver, __('fees::pdf.receiver_signature')],
            'signRight' => [Branding::forPdf()['principal'], __('fees::pdf.principal')],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function invoice(FeeInvoice $invoice): array
    {
        $invoice->loadMissing(['student.schoolClass', 'student.section', 'items.head']);
        $subtotal = Money::poisha((int) $invoice->items->sum(fn ($item): int => $item->amount->poisha));
        $sums = [['label' => __('fees::pdf.subtotal'), 'value' => $subtotal->format(), 'weight' => 'normal', 'size' => 3.31, 'color' => '#17212B', 'rule' => 0.26]];

        if ($invoice->discount->isPositive()) {
            $sums[] = ['label' => __('fees::pdf.discount'), 'value' => '− '.$invoice->discount->format(), 'weight' => 'normal', 'size' => 3.31, 'color' => self::GREEN, 'rule' => null];
        }

        if ($invoice->late_fine->isPositive()) {
            $sums[] = ['label' => __('fees::pdf.late_fine'), 'value' => '+ '.$invoice->late_fine->format(), 'weight' => 'normal', 'size' => 3.31, 'color' => self::RED, 'rule' => null];
        }

        $due = $invoice->status === InvoiceStatus::Waived ? Money::zero() : $invoice->due;
        $sums[] = ['label' => __('fees::pdf.total'), 'value' => $invoice->total->format(), 'weight' => 'bold', 'size' => 3.57, 'color' => '#17212B', 'rule' => 0.26];
        $sums[] = ['label' => __('fees::pdf.paid'), 'value' => $invoice->paid->format(), 'weight' => 'normal', 'size' => 3.31, 'color' => self::GREEN, 'rule' => null];
        $sums[] = ['label' => __('fees::pdf.due'), 'value' => $due->format(), 'weight' => 'bold', 'size' => 3.7, 'color' => $due->isPositive() ? self::RED : '#17212B', 'rule' => 0.5];

        $stamp = match ($invoice->status) {
            InvoiceStatus::Paid => ['text' => 'PAID', 'color' => '#15A04A'],
            InvoiceStatus::Partial => ['text' => 'PARTIAL', 'color' => '#B7791F'],
            InvoiceStatus::Unpaid => ['text' => 'DUE', 'color' => self::RED],
            InvoiceStatus::Waived => ['text' => 'WAIVED', 'color' => self::BLUE],
            default => null,
        };

        $fineAfter = $this->settings->dueDay() + $this->settings->lateFineAfterDays();
        $phone = $this->phones()[0] ?? '';

        return [
            'kind' => 'invoice',
            'title' => __('fees::pdf.invoice_title'),
            'titleColor' => self::BLUE,
            'number' => $invoice->invoice_no,
            'date' => bn_date($invoice->issued_at ?? $invoice->created_at),
            'student' => $this->studentFields($invoice->student),
            'tableLabel' => $this->invoiceTitle($invoice),
            'rows' => $invoice->items->map(fn ($item): array => ['label' => $item->head->displayName().($item->description ? ' — '.$item->description : ''), 'amount' => $item->amount->format()])->all(),
            'sums' => $sums,
            'boxLabel' => __('fees::pdf.payable'),
            'boxAmount' => $due->format(),
            'boxWords' => $due->inWords(),
            'pay' => [
                [__('fees::pdf.due_date_label'), $invoice->due_date ? bn_date($invoice->due_date) : '—'],
                [__('fees::pdf.late_fee'), $this->settings->lateFine()->format().' '.__('fees::pdf.late_fee_after', ['day' => bn_digits($fineAfter)])],
                [__('fees::pdf.bkash_nagad'), $phone],
            ],
            'stamp' => $stamp,
            'qr' => $this->qr->forVerification('invoice', SignedHash::make('invoice', $invoice->id), 120),
            'verifyUrl' => $this->verifyHost(),
            'rightWidth' => 56.3,
            'tableAmountWidth' => 30.5,
            'footer' => (string) $this->settings->get('receipt_footer'),
            'signLeft' => ['', __('fees::pdf.accountant')],
            'signRight' => [Branding::forPdf()['principal'], __('fees::pdf.principal')],
        ];
    }

    /**
     * @param  Collection<int, FeeInvoice>  $invoices  The student's open invoices.
     * @return array<string, mixed>
     */
    public function dueNotice(Student $student, Collection $invoices): array
    {
        $student->loadMissing(['schoolClass', 'section']);
        $total = Money::poisha((int) $invoices->sum(fn (FeeInvoice $invoice): int => $invoice->due->poisha));
        $phones = $this->phones();
        $deadline = now()->endOfMonth();

        return [
            'kind' => 'notice',
            'title' => __('fees::pdf.notice_title'),
            'titleColor' => self::RED,
            'number' => 'DN-'.now()->format('y-m').'-'.str_pad((string) ($student->roll ?? 0), 2, '0', STR_PAD_LEFT),
            'date' => bn_date(now()),
            'student' => $this->studentFields($student),
            'deadline' => bn_date($deadline),
            'rows' => $invoices->map(fn (FeeInvoice $invoice): array => ['label' => $this->invoiceTitle($invoice), 'date' => $invoice->due_date ? bn_date($invoice->due_date) : '—', 'amount' => $invoice->due->format()])->all(),
            'total' => $total->format(),
            'totalWords' => $total->inWords(),
            'bkash' => $phones[0] ?? '',
            'nagad' => $phones[1] ?? ($phones[0] ?? ''),
            'footer' => (string) $this->settings->get('receipt_footer'),
            'signLeft' => ['', __('fees::pdf.class_teacher')],
            'signRight' => [Branding::forPdf()['principal'], __('fees::pdf.principal')],
            'stamp' => null,
        ];
    }

    /**
     * @param  list<array{invoice_no: string, description: string, amount: Money}>  $lines
     * @return array<string, mixed>
     */
    public function thermal(Payment $payment, array $lines): array
    {
        $student = $payment->student;
        $receiver = $payment->receiver?->name ?? $payment->verifier?->name;

        return [
            'number' => $payment->receipt_no,
            'date' => bn_date($payment->paid_at),
            'meta' => [
                [__('fees::pdf.receipt_no'), $payment->receipt_no],
                [__('fees::pdf.date'), bn_date($payment->paid_at)],
                [__('fees::pdf.name'), $student->displayName()],
                [__('fees::pdf.id_short'), $student->student_id],
                [__('fees::pdf.class_roll'), trim(($student->schoolClass?->displayName() ?? '').($student->section ? '–'.$student->section->displayName() : '').' / '.($student->roll ? bn_digits($student->roll) : '—'))],
            ],
            'lines' => array_map(fn (array $line): array => [$line['description'] ?: $line['invoice_no'], $line['amount']->format()], $lines),
            'amount' => $payment->amount->format(),
            'words' => $payment->amount->inWords(),
            'method' => $payment->method->label(),
            'transaction' => $payment->transaction_id,
            'receiver' => $receiver ?? '—',
            'qr' => $this->qr->forVerification('receipt', SignedHash::make('receipt', $payment->id), 120),
            'verifyUrl' => $this->verifyHost(),
            'footer' => (string) $this->settings->get('receipt_footer'),
        ];
    }

    /**
     * Height of the thermal roll slip: the design's height for one line and a transaction id, plus a line each.
     *
     * @param  array<string, mixed>  $doc
     */
    public function thermalHeight(array $doc): float
    {
        return 157.8 + (count($doc['lines']) - 1) * 5.46 + ($doc['transaction'] ? 0 : -5.46) + 8;
    }

    /**
     * The rotated rubber-stamp ("PAID", "DUE"…) as an SVG image sized to its rotated bounding box (mPDF has no CSS rotate).
     */
    public static function stamp(string $text, string $color, float $width, float $height): string
    {
        $angle = deg2rad(14);
        $boxWidth = (cos($angle) * $width + sin($angle) * $height) * 10;
        $boxHeight = (sin($angle) * $width + cos($angle) * $height) * 10;
        $centerX = $boxWidth / 2;
        $centerY = $boxHeight / 2;
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$.1f" height="%2$.1f" viewBox="0 0 %1$.1f %2$.1f"><g transform="rotate(-14 %3$.1f %4$.1f)" opacity="0.8"><rect x="%5$.1f" y="%6$.1f" width="%7$.1f" height="%8$.1f" rx="16" fill="none" stroke="%9$s" stroke-width="8"/><text x="%3$.1f" y="%10$.1f" text-anchor="middle" font-family="Helvetica" font-weight="bold" font-size="58" letter-spacing="3.5" fill="%9$s">%11$s</text></g></svg>',
            $boxWidth, $boxHeight, $centerX, $centerY, $centerX - $width * 5 + 4, $centerY - $height * 5 + 4, $width * 10 - 8, $height * 10 - 8, $color, $centerY + 20, e($text),
        );

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    protected function studentFields(Student $student): array
    {
        $class = $student->schoolClass?->displayName() ?? '—';

        if ($student->section) {
            $class = __('fees::pdf.class_section', ['class' => $class, 'section' => $student->section->displayName()]);
        }

        return [
            [__('fees::pdf.name'), $student->displayName()],
            [__('fees::pdf.student_id'), $student->student_id],
            [__('fees::pdf.class'), $class],
            [__('fees::pdf.roll'), $student->roll ? bn_digits(str_pad((string) $student->roll, 2, '0', STR_PAD_LEFT)) : '—'],
            [__('fees::pdf.father'), $student->father_name ?: '—'],
            [__('fees::pdf.mobile'), $student->guardian_phone ? bn_digits($student->guardian_phone) : '—'],
        ];
    }

    protected function invoiceTitle(FeeInvoice $invoice): string
    {
        $invoice->loadMissing('items.head');

        return collect([$invoice->billing_month ? Bangla::monthYear($invoice->billing_month) : null, $invoice->items->map(fn ($item) => $item->head->displayName())->unique()->implode(', ')])->filter()->implode(' — ');
    }

    /**
     * What the money paid for: one line per invoice it went to.
     *
     * @return list<array{invoice_no: string, description: string, amount: Money}>
     */
    public function receiptLines(Payment $payment): array
    {
        $lines = $payment->allocations->map(fn ($allocation): array => [
            'invoice_no' => $allocation->invoice->invoice_no,
            'description' => $this->invoiceTitle($allocation->invoice),
            'amount' => $allocation->amount,
        ])->all();

        $credited = $payment->amount->minus(Money::poisha($payment->allocations->sum(fn ($allocation): int => $allocation->amount->poisha)));

        if ($credited->isPositive()) {
            $lines[] = ['invoice_no' => '—', 'description' => __('fees::pdf.advance_credit'), 'amount' => $credited];
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    protected function phones(): array
    {
        return array_values(array_map(fn (string $phone): string => bn_digits($phone), (array) setting('phones', [])));
    }

    protected function verifyHost(): string
    {
        return (string) parse_url((string) config('app.url'), PHP_URL_HOST).'/verify';
    }
}
