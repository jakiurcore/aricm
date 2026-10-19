<?php

namespace Modules\Fees\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounts\Models\User;
use Modules\Core\Services\ImageUploader;
use Modules\Core\Services\Sms\SmsManager;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Enums\PaymentChannel;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Notifications\PaymentStatusNotification;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

/**
 * Every way money enters the system. The amount of a payment is always worked out here from the invoices it pays,
 * never taken from the client, and every write that touches balances runs in a transaction with the rows locked.
 */
class PaymentService
{
    public function __construct(
        protected PaymentAllocator $allocator,
        protected DocumentNumbers $numbers,
        protected FeeSettings $settings,
        protected ImageUploader $images,
        protected SmsManager $sms,
    ) {}

    /**
     * Cash or wallet money received at the counter: approved on the spot, receipt issued.
     *
     * @param  list<int>  $invoiceIds  Empty = oldest due first.
     */
    public function collectAtCounter(Student $student, Money $amount, PaymentMethod $method, array $invoiceIds, User $receiver, ?string $transactionId = null, ?string $note = null): Payment
    {
        if (! $amount->isPositive()) {
            throw ValidationException::withMessages(['amount' => __('fees::messages.amount_required')]);
        }

        if ($method === PaymentMethod::Gateway) {
            throw ValidationException::withMessages(['method' => __('fees::messages.method_invalid')]);
        }

        $transactionId = $transactionId !== null && $transactionId !== '' ? TransactionRules::normalize($transactionId) : null;

        if ($method !== PaymentMethod::Cash && ($transactionId === null || ! TransactionRules::isValid($method, $transactionId))) {
            throw ValidationException::withMessages(['transaction_id' => __('fees::messages.txn_invalid')]);
        }

        $this->ensureTransactionUnused($method, $transactionId);

        $payment = DB::transaction(function () use ($student, $amount, $method, $invoiceIds, $receiver, $transactionId, $note): Payment {
            $payment = Payment::query()->create([
                'receipt_no' => $this->numbers->receipt(),
                'student_id' => $student->id, 'amount' => $amount, 'method' => $method, 'channel' => PaymentChannel::Counter,
                'transaction_id' => $transactionId, 'status' => PaymentStatus::Approved, 'invoice_ids' => $invoiceIds ?: null,
                'note' => $note, 'paid_at' => now(), 'received_by' => $receiver->id, 'verified_by' => $receiver->id, 'verified_at' => now(),
            ]);

            $this->allocator->allocate($payment, $invoiceIds ?: null);

            return $payment;
        });

        $this->notify($payment);

        return $payment->load('allocations.invoice');
    }

    /**
     * A guardian says they paid by mobile wallet or bank: record it as pending. Nothing about the invoices changes
     * until someone verifies it.
     *
     * @param  list<int>  $invoiceIds
     */
    public function submitManual(Student $student, array $invoiceIds, PaymentMethod $method, string $transactionId, ?string $senderNumber, ?UploadedFile $screenshot = null): Payment
    {
        if (! in_array($method, PaymentMethod::manual(), true)) {
            throw ValidationException::withMessages(['method' => __('fees::messages.method_invalid')]);
        }

        $transactionId = TransactionRules::normalize($transactionId);

        if (! TransactionRules::isValid($method, $transactionId)) {
            throw ValidationException::withMessages(['transaction_id' => __('fees::messages.txn_invalid')]);
        }

        if (TransactionRules::needsSender($method) && SmsManager::normalize((string) $senderNumber) === null) {
            throw ValidationException::withMessages(['sender_number' => __('fees::messages.sender_invalid')]);
        }

        $invoices = FeeInvoice::query()->where('student_id', $student->id)->whereIn('id', $invoiceIds)->get();

        if ($invoices->isEmpty() || $invoices->count() !== count(array_unique($invoiceIds)) || $invoices->contains(fn (FeeInvoice $invoice): bool => ! $invoice->isOpen())) {
            throw ValidationException::withMessages(['invoice_ids' => __('fees::messages.invoice_not_payable')]);
        }

        // Worked out here, from the invoices: the client's idea of the amount is never used.
        $amount = Money::poisha($invoices->sum(fn (FeeInvoice $invoice): int => $invoice->due->poisha));

        $this->ensureTransactionUnused($method, $transactionId);

        return Payment::query()->create([
            'student_id' => $student->id, 'amount' => $amount, 'method' => $method, 'channel' => PaymentChannel::StudentManual,
            'transaction_id' => $transactionId, 'sender_number' => SmsManager::normalize((string) $senderNumber), 'status' => PaymentStatus::Pending,
            'screenshot_path' => $screenshot ? $this->images->store($screenshot, 'payments', 1600) : null,
            'invoice_ids' => $invoices->pluck('id')->all(), 'idempotency_key' => sha1("{$student->id}|{$method->value}|{$transactionId}"),
        ]);
    }

    /**
     * Approve a pending payment: receipt number, allocation to the invoices the payer chose (the rest to credit).
     * A null verifier means the system approved it after a provider's server-side confirmation (online payments).
     */
    public function approve(Payment $payment, ?User $verifier): Payment
    {
        $approved = DB::transaction(function () use ($payment, $verifier): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['payment' => __('fees::messages.payment_not_pending')]);
            }

            $locked->forceFill([
                'receipt_no' => $this->numbers->receipt(), 'status' => PaymentStatus::Approved,
                'paid_at' => $locked->paid_at ?? now(), 'verified_by' => $verifier?->id, 'verified_at' => now(),
            ])->save();

            $this->allocator->allocate($locked, $locked->invoice_ids);

            return $locked;
        });

        $this->notify($approved);

        return $approved->load('allocations.invoice');
    }

    public function reject(Payment $payment, User $verifier, string $reason): Payment
    {
        $rejected = DB::transaction(function () use ($payment, $verifier, $reason): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['payment' => __('fees::messages.payment_not_pending')]);
            }

            $locked->forceFill(['status' => PaymentStatus::Rejected, 'rejection_reason' => $reason, 'verified_by' => $verifier->id, 'verified_at' => now()])->save();

            return $locked;
        });

        $this->notify($rejected);

        return $rejected;
    }

    /**
     * Waive an invoice entirely (a reason is required and logged).
     */
    public function waive(FeeInvoice $invoice, User $by, string $reason): FeeInvoice
    {
        return DB::transaction(function () use ($invoice, $by, $reason): FeeInvoice {
            $locked = FeeInvoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['invoice' => __('fees::messages.invoice_not_payable')]);
            }

            $locked->forceFill(['status' => InvoiceStatus::Waived, 'waived_by' => $by->id, 'waive_reason' => $reason, 'due' => Money::zero()])->save();
            activity('fees')->performedOn($locked)->causedBy($by)->withProperties(['reason' => $reason])->log('waived');

            return $locked;
        });
    }

    protected function ensureTransactionUnused(PaymentMethod $method, ?string $transactionId): void
    {
        if ($transactionId !== null && Payment::query()->where(['method' => $method->value, 'transaction_id' => $transactionId])->exists()) {
            throw ValidationException::withMessages(['transaction_id' => __('fees::messages.txn_used')]);
        }
    }

    /**
     * Tell the student (in the portal) and, when switched on, the guardian by SMS.
     */
    protected function notify(Payment $payment): void
    {
        $payment->loadMissing('student');
        $notification = new PaymentStatusNotification($payment);
        $payment->student->notify($notification);

        if ($this->settings->smsEnabled()) {
            $this->sms->send($payment->student->guardian_phone, $notification->title().': '.$notification->body());
        }
    }
}
