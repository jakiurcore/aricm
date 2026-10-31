<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounts\Models\User;
use Modules\Fees\Casts\MoneyCast;
use Modules\Fees\Database\Factories\PaymentFactory;
use Modules\Fees\Enums\PaymentChannel;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Students\Models\Student;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[UseFactory(PaymentFactory::class)]
#[Fillable(['receipt_no', 'student_id', 'amount', 'method', 'channel', 'transaction_id', 'sender_number', 'screenshot_path', 'status', 'rejection_reason', 'gateway', 'gateway_payload', 'invoice_ids', 'note', 'paid_at', 'received_by', 'verified_by', 'verified_at', 'idempotency_key'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'method' => PaymentMethod::class,
            'channel' => PaymentChannel::class,
            'status' => PaymentStatus::class,
            'gateway_payload' => 'array',
            'invoice_ids' => 'array',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::Pending;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('fees')->logOnly(['status', 'amount', 'method', 'receipt_no', 'rejection_reason'])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
