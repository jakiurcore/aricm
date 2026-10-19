<?php

namespace Modules\Fees\Services;

use Modules\Core\Services\PdfService;
use Modules\Core\Support\Branding;
use Modules\Fees\Models\FeeInvoice;

/**
 * A bill as an A5 PDF: items, discount, fine, what is paid and what is due.
 */
class InvoicePdf
{
    public function __construct(protected PdfService $pdf) {}

    public function render(FeeInvoice $invoice): string
    {
        $invoice->load(['student.schoolClass', 'student.section', 'items.head']);

        $html = view('fees::pdf.invoice', ['invoice' => $invoice, 'brand' => Branding::forPdf()])->render();

        return $this->pdf->render($html, ['format' => 'A5', 'margin_left' => 8, 'margin_right' => 8, 'margin_top' => 8, 'margin_bottom' => 8, 'showImageErrors' => false]);
    }
}
