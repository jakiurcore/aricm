<?php

namespace Modules\Fees\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Fees\Enums\PaymentChannel;
use Modules\Fees\Enums\PaymentMethod;
use Modules\Fees\Enums\PaymentStatus;
use Modules\Fees\Models\Payment;
use Modules\Fees\Support\Money;
use Modules\Students\Models\Student;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'amount' => Money::taka(1000),
            'method' => PaymentMethod::Bkash,
            'channel' => PaymentChannel::StudentManual,
            'transaction_id' => strtoupper(fake()->unique()->bothify('??########')),
            'sender_number' => '01711111111',
            'status' => PaymentStatus::Pending,
        ];
    }
}
