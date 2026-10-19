<?php

namespace Modules\Fees\Notifications;

use Illuminate\Notifications\Notification;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\Payment;

/**
 * The in-portal notice a student gets when a payment is approved or rejected.
 */
class PaymentStatusNotification extends Notification
{
    public function __construct(public Payment $payment) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'payment',
            'payment_id' => $this->payment->id,
            'status' => $this->payment->status->value,
            'amount' => $this->payment->amount->poisha,
            'title' => $this->title(),
            'body' => $this->body(),
        ];
    }

    public function title(): string
    {
        return $this->payment->status === PaymentStatus::Approved ? __('fees::messages.notify.approved_title') : __('fees::messages.notify.rejected_title');
    }

    public function body(): string
    {
        return $this->payment->status === PaymentStatus::Approved
            ? __('fees::messages.notify.approved_body', ['amount' => $this->payment->amount->format(), 'receipt' => $this->payment->receipt_no])
            : __('fees::messages.notify.rejected_body', ['amount' => $this->payment->amount->format(), 'reason' => $this->payment->rejection_reason]);
    }
}
