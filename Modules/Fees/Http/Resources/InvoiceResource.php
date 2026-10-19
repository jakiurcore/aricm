<?php

namespace Modules\Fees\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Fees\Models\FeeInvoice;

/**
 * @mixin FeeInvoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * Money is always whole poisha; the screens format it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'billing_month' => $this->billing_month?->format('Y-m'),
            'due_date' => $this->due_date?->toDateString(),
            'issued_at' => $this->issued_at?->toIso8601String(),
            'subtotal' => $this->subtotal->poisha, 'discount' => $this->discount->poisha, 'late_fine' => $this->late_fine->poisha,
            'total' => $this->total->poisha, 'paid' => $this->paid->poisha, 'due' => $this->due->poisha,
            'waive_reason' => $this->waive_reason,
            'student' => $this->whenLoaded('student', fn () => ['id' => $this->student->id, 'student_id' => $this->student->student_id, 'name' => $this->student->name_bn, 'roll' => $this->student->roll]),
            'class_name' => $this->whenLoaded('schoolClass', fn () => $this->schoolClass->displayName()),
            'exam' => $this->whenLoaded('exam', fn () => $this->exam?->displayName()),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => ['head' => $item->head->displayName(), 'description' => $item->description, 'amount' => $item->amount->poisha, 'discount' => $item->discount->poisha, 'net' => $item->net->poisha])->values()),
        ];
    }
}
