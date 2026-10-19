<?php

namespace Modules\Fees\Services;

use Modules\Core\Contracts\VerificationHandler;
use Modules\Core\Support\SignedHash;
use Modules\Core\Support\VerificationResult;
use Modules\Fees\Models\FeeInvoice;

class InvoiceVerification implements VerificationHandler
{
    public function verify(string $hash): ?VerificationResult
    {
        $id = SignedHash::parse('invoice', $hash);
        $invoice = $id ? FeeInvoice::query()->with('student')->find($id) : null;

        if (! $invoice) {
            return null;
        }

        return new VerificationResult(__('fees::pdf.verify.invoice_title'), [
            __('fees::pdf.verify.invoice') => (string) $invoice->invoice_no,
            __('fees::pdf.verify.student') => $invoice->student->displayName(),
            __('fees::pdf.verify.student_id') => $invoice->student->student_id,
            __('fees::pdf.verify.total') => $invoice->total->format(),
            __('fees::pdf.verify.paid') => $invoice->paid->format(),
            __('fees::pdf.verify.due') => $invoice->due->format(),
            __('fees::pdf.verify.status') => $invoice->status->label(),
        ]);
    }
}
