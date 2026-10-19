<?php

namespace Modules\Fees\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Enums\InvoiceStatus;
use Modules\Fees\Models\FeeInvoice;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

/**
 * @extends Factory<FeeInvoice>
 */
class FeeInvoiceFactory extends Factory
{
    protected $model = FeeInvoice::class;

    /**
     * A bare invoice with no items; tests that need items create them through the InvoiceGenerator.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_no' => 'INV-2026-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'student_id' => Student::factory(),
            'session_id' => fn (array $attributes) => Student::query()->find($attributes['student_id'])->session_id,
            'class_id' => fn (array $attributes) => Student::query()->find($attributes['student_id'])->class_id,
            'billing_month' => null,
            'type' => FeeHeadType::OneTime,
            'subtotal' => Money::taka(1000),
            'discount' => Money::zero(),
            'late_fine' => Money::zero(),
            'total' => Money::taka(1000),
            'paid' => Money::zero(),
            'due' => Money::taka(1000),
            'status' => InvoiceStatus::Unpaid,
            'due_date' => now()->addDays(10)->toDateString(),
            'issued_at' => now(),
        ];
    }
}
