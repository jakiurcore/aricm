<?php

namespace Modules\Fees\Services;

use Modules\Core\Services\PdfService;
use Modules\Core\Support\Branding;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\Payment;

/**
 * Money receipts as PDF: A5 landscape or 80 mm thermal. Amount in words, a QR to the verification page.
 */
class ReceiptService
{
    public const string A5 = 'a5';

    public const string THERMAL = 'thermal';

    public function __construct(protected PdfService $pdf, protected PrintDocumentFactory $documents) {}

    public function pdf(Payment $payment, string $format = self::A5): string
    {
        abort_unless($payment->status === PaymentStatus::Approved, 404);

        $payment->load(['student.schoolClass', 'student.section', 'allocations.invoice.items.head', 'receiver', 'verifier']);

        if ($format === self::THERMAL) {
            $doc = $this->documents->thermal($payment, $this->documents->receiptLines($payment));

            return $this->pdf->render(
                view('fees::pdf.receipt-thermal', ['doc' => $doc, 'brand' => Branding::forPdf()])->render(),
                ['format' => [80, $this->documents->thermalHeight($doc)], 'showImageErrors' => false],
            );
        }

        $html = view('fees::pdf.receipt-a5', ['doc' => $this->documents->receipt($payment), 'brand' => Branding::forPdf()])->render();

        return $this->pdf->render($html, ['format' => 'A5-L', 'showImageErrors' => false]);
    }
}
