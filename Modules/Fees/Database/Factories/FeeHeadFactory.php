<?php

namespace Modules\Fees\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Fees\Enums\FeeHeadType;
use Modules\Fees\Models\FeeHead;

/**
 * @extends Factory<FeeHead>
 */
class FeeHeadFactory extends Factory
{
    protected $model = FeeHead::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_bn' => 'ফি '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => null,
            'type' => FeeHeadType::Monthly,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function monthly(): static
    {
        return $this->state(['type' => FeeHeadType::Monthly]);
    }

    public function oneTime(): static
    {
        return $this->state(['type' => FeeHeadType::OneTime]);
    }

    public function exam(): static
    {
        return $this->state(['type' => FeeHeadType::Exam]);
    }
}
