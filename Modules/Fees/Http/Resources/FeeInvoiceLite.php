<?php

namespace Modules\Fees\Http\Resources;

use Modules\Fees\Models\FeeInvoice;

/**
 * The few fields of an invoice a verifier needs next to a pending payment.
 */
final class FeeInvoiceLite
{
    /**
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public static function forIds(array $ids): array
    {
        return FeeInvoice::query()->whereIn('id', $ids)->orderBy('due_date')->get()->map(fn (FeeInvoice $invoice): array => [
            'id' => $invoice->id, 'invoice_no' => $invoice->invoice_no, 'billing_month' => $invoice->billing_month?->format('Y-m'), 'total' => $invoice->total->poisha, 'due' => $invoice->due->poisha, 'status' => $invoice->status->value,
        ])->all();
    }
}
