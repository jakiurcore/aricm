<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Fees\Casts\MoneyCast;

#[Fillable(['payment_id', 'invoice_id', 'amount', 'source'])]
class PaymentAllocation extends Model
{
    public const string FROM_PAYMENT = 'payment';

    public const string FROM_CREDIT = 'credit';

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }
}
