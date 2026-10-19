<?php

namespace Modules\Fees\Services;

use Modules\Core\Contracts\VerificationHandler;
use Modules\Core\Support\SignedHash;
use Modules\Core\Support\VerificationResult;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\Payment;

class ReceiptVerification implements VerificationHandler
{
    public function verify(string $hash): ?VerificationResult
    {
        $id = SignedHash::parse('receipt', $hash);
        $payment = $id ? Payment::query()->with('student')->find($id) : null;

        if (! $payment || $payment->status !== PaymentStatus::Approved) {
            return null;
        }

        return new VerificationResult(__('fees::pdf.verify.title'), [
            __('fees::pdf.verify.receipt') => (string) $payment->receipt_no,
            __('fees::pdf.verify.student') => $payment->student->displayName(),
            __('fees::pdf.verify.student_id') => $payment->student->student_id,
            __('fees::pdf.verify.amount') => $payment->amount->format(),
            __('fees::pdf.verify.method') => $payment->method->label(),
            __('fees::pdf.verify.date') => bn_date($payment->paid_at),
        ]);
    }
}
