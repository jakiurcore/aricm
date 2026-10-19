<?php

namespace Modules\Admission\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Admission\Models\Inquiry;

/**
 * @extends Factory<Inquiry>
 */
class InquiryFactory extends Factory
{
    protected $model = Inquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['student_name' => fake()->name(), 'guardian_name' => fake()->name('male'), 'phone' => '017'.fake()->numerify('########'), 'desired_class' => 'প্লে', 'message' => null, 'status' => 'new'];
    }
}
