<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Fees\Casts\MoneyCast;

#[Fillable(['invoice_id', 'fee_head_id', 'description', 'amount', 'discount', 'net'])]
class FeeInvoiceItem extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['amount' => MoneyCast::class, 'discount' => MoneyCast::class, 'net' => MoneyCast::class];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FeeInvoice::class, 'invoice_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }
}
