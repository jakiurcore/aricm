<?php

namespace Modules\Fees\Services;

use Modules\Core\Services\PdfService;
use Modules\Core\Services\QrCodeService;
use Modules\Core\Support\Branding;
use Modules\Core\Support\SignedHash;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\Money;

/**
 * Money receipts as PDF: A5 (portrait) or 80 mm thermal. Amount in words, a QR to the verification page.
 */
class ReceiptService
{
    public const string A5 = 'a5';

    public const string THERMAL = 'thermal';

    public function __construct(protected PdfService $pdf, protected QrCodeService $qr, protected FeeSettings $settings) {}

    public function pdf(Payment $payment, string $format = self::A5): string
    {
        abort_unless($payment->status === PaymentStatus::Approved, 404);

        $payment->load(['student.schoolClass', 'student.section', 'allocations.invoice.items.head', 'receiver', 'verifier']);

        $html = view($format === self::THERMAL ? 'fees::pdf.receipt-thermal' : 'fees::pdf.receipt-a5', [
            'payment' => $payment,
            'brand' => Branding::forPdf(),
            'qr' => $this->qr->forVerification('receipt', SignedHash::make('receipt', $payment->id), 120),
            'footer' => (string) $this->settings->get('receipt_footer'),
            'lines' => $this->lines($payment),
        ])->render();

        $options = $format === self::THERMAL
            ? ['format' => [80, 40 + 14 * max(1, $payment->allocations->count()) + 70], 'margin_left' => 3, 'margin_right' => 3, 'margin_top' => 3, 'margin_bottom' => 3]
            : ['format' => 'A5', 'margin_left' => 8, 'margin_right' => 8, 'margin_top' => 8, 'margin_bottom' => 8];

        return $this->pdf->render($html, [...$options, 'showImageErrors' => false]);
    }

    /**
     * What the money paid for: one line per invoice it went to.
     *
     * @return list<array{invoice_no: string, description: string, amount: Money}>
     */
    protected function lines(Payment $payment): array
    {
        $lines = $payment->allocations->map(fn ($allocation): array => [
            'invoice_no' => $allocation->invoice->invoice_no,
            'description' => collect([$allocation->invoice->billing_month ? bn_date($allocation->invoice->billing_month->startOfMonth(), true) : null, $allocation->invoice->items->map(fn ($item) => $item->head->displayName())->implode(', ')])->filter()->implode(' — '),
            'amount' => $allocation->amount,
        ])->all();

        $credited = $payment->amount->minus(Money::poisha($payment->allocations->sum(fn ($allocation): int => $allocation->amount->poisha)));

        if ($credited->isPositive()) {
            $lines[] = ['invoice_no' => '—', 'description' => __('fees::pdf.advance_credit'), 'amount' => $credited];
        }

        return $lines;
    }
}
