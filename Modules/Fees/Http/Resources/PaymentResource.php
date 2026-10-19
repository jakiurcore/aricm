<?php

namespace Modules\Fees\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\Fees\Models\Payment;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receipt_no' => $this->receipt_no,
            'amount' => $this->amount->poisha,
            'method' => $this->method->value,
            'channel' => $this->channel->value,
            'transaction_id' => $this->transaction_id,
            'sender_number' => $this->sender_number,
            'status' => $this->status->value,
            'rejection_reason' => $this->rejection_reason,
            'note' => $this->note,
            'screenshot_url' => $this->screenshot_path ? Storage::disk('public')->url($this->screenshot_path) : null,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'student_id' => $this->student->student_id, 'name' => $this->student->name_bn, 'class' => $this->student->schoolClass?->displayName()]),
            'invoices' => $this->when($this->invoice_ids, fn () => FeeInvoiceLite::forIds($this->invoice_ids)),
            'received_by' => $this->receiver?->name,
            'verified_by' => $this->verifier?->name,
        ];
    }
}
