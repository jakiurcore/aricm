<?php

namespace Modules\Fees\Services;

use Modules\Core\Services\PdfService;
use Modules\Core\Support\Branding;
use Modules\Fees\Models\FeeInvoice;
use Modules\Students\Models\Student;

/**
 * A bill and a due notice as A5 landscape PDFs.
 */
class InvoicePdf
{
    public function __construct(protected PdfService $pdf, protected PrintDocumentFactory $documents, protected StudentFeeDesk $desk) {}

    public function render(FeeInvoice $invoice): string
    {
        $invoice->load(['student.schoolClass', 'student.section', 'items.head']);

        $html = view('fees::pdf.invoice', ['doc' => $this->documents->invoice($invoice), 'brand' => Branding::forPdf()])->render();

        return $this->pdf->render($html, ['format' => 'A5-L', 'showImageErrors' => false]);
    }

    /**
     * The "your child's fees are overdue" letter listing every open invoice of the student.
     */
    public function dueNotice(Student $student): string
    {
        $html = view('fees::pdf.due-notice', ['doc' => $this->documents->dueNotice($student, $this->desk->openInvoices($student)), 'brand' => Branding::forPdf()])->render();

        return $this->pdf->render($html, ['format' => 'A5-L', 'showImageErrors' => false]);
    }
}
