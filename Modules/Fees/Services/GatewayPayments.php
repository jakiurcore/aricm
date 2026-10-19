<?php

namespace Modules\Fees\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Fees\Enums\PaymentChannel;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Models\Payment;
use Modules\Fees\Services\Gateway\GatewayManager;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

/**
 * Online payments. A payment row is created pending with our own reference and an amount worked out from the invoices;
 * it becomes approved only when the driver's verify() says the provider confirmed exactly that.
 */
class GatewayPayments
{
    public function __construct(protected GatewayManager $gateways, protected PaymentService $payments) {}

    /**
     * Start a checkout for the chosen invoices; returns the provider URL to send the payer to.
     *
     * @param  list<int>  $invoiceIds
     */
    public function start(Student $student, array $invoiceIds): string
    {
        $gateway = $this->gateways->active() ?? throw ValidationException::withMessages(['gateway' => __('fees::messages.gateway_unavailable')]);

        $invoices = FeeInvoice::query()->where('student_id', $student->id)->whereIn('id', $invoiceIds)->get();

        if ($invoices->isEmpty() || $invoices->count() !== count(array_unique($invoiceIds)) || $invoices->contains(fn (FeeInvoice $invoice): bool => ! $invoice->isOpen())) {
            throw ValidationException::withMessages(['invoice_ids' => __('fees::messages.invoice_not_payable')]);
        }

        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'amount' => Money::poisha($invoices->sum(fn (FeeInvoice $invoice): int => $invoice->due->poisha)),
            'method' => PaymentMethod::Gateway, 'channel' => PaymentChannel::Gateway, 'status' => PaymentStatus::Pending,
            'gateway' => $gateway->name(), 'invoice_ids' => $invoices->pluck('id')->all(),
            'idempotency_key' => 'FEE-'.strtoupper(Str::random(20)),
        ]);

        return $gateway->initiate($payment);
    }

    /**
     * Settle a payment after a callback or IPN. Safe to call any number of times (providers do retry): an approved or
     * rejected payment is left alone, and approval itself is locked and idempotent.
     */
    public function settle(Payment $payment): Payment
    {
        $fresh = Payment::query()->find($payment->id);

        if (! $fresh->isPending()) {
            return $fresh;
        }

        if ($this->gateways->driver((string) $fresh->gateway)->verify($fresh->refresh())) {
            return $this->payments->approve($fresh->refresh(), null);
        }

        return $fresh->refresh();
    }

    /**
     * The payer cancelled or the provider reported failure: close the attempt without touching any invoice.
     */
    public function fail(Payment $payment, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $reason): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPending()) {
                $locked->forceFill(['status' => PaymentStatus::Failed, 'rejection_reason' => $reason])->save();
            }

            return $locked;
        });
    }
}
