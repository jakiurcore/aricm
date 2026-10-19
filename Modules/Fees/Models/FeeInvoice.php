<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Academic\Models\SchoolClass;
use Modules\Exams\Models\Exam;
use Modules\Fees\Casts\MoneyCast;
use Modules\Fees\Database\Factories\FeeInvoiceFactory;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[UseFactory(FeeInvoiceFactory::class)]
#[Fillable(['invoice_no', 'student_id', 'session_id', 'class_id', 'billing_month', 'exam_id', 'type', 'subtotal', 'discount', 'late_fine', 'total', 'paid', 'due', 'status', 'due_date', 'issued_at', 'waived_by', 'waive_reason'])]
class FeeInvoice extends Model
{
    /** @use HasFactory<FeeInvoiceFactory> */
    use HasFactory, LogsActivity;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'billing_month' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'type' => FeeHeadType::class,
            'status' => InvoiceStatus::class,
            'subtotal' => MoneyCast::class, 'discount' => MoneyCast::class, 'late_fine' => MoneyCast::class,
            'total' => MoneyCast::class, 'paid' => MoneyCast::class, 'due' => MoneyCast::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(FeeInvoiceItem::class, 'invoice_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'invoice_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, InvoiceStatus::open(), true);
    }

    /**
     * Rebuild total, paid, due and status from the items and allocations. Call it inside the transaction that
     * changed them; it keeps `due` stored (and so queryable) without ever drifting.
     */
    public function recalculate(): static
    {
        $total = $this->subtotal->minus($this->discount)->plus($this->late_fine);
        $paid = Money::poisha((int) $this->allocations()->sum('amount'));
        $due = $total->minus($paid)->max(Money::zero());

        $status = $this->status;
        if (! in_array($status, [InvoiceStatus::Waived, InvoiceStatus::Cancelled], true)) {
            $status = $due->isZero() ? InvoiceStatus::Paid : ($paid->isPositive() ? InvoiceStatus::Partial : InvoiceStatus::Unpaid);
        }

        $this->forceFill(['total' => $total, 'paid' => $paid, 'due' => $status === InvoiceStatus::Waived || $status === InvoiceStatus::Cancelled ? Money::zero() : $due, 'status' => $status])->save();

        return $this;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('fees')->logOnly(['status', 'late_fine', 'waive_reason'])->logOnlyDirty()->dontLogEmptyChanges();
    }
}
