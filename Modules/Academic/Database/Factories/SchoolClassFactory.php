<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\SchoolClass;

/**
 * @extends Factory<SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_bn' => 'শ্রেণি '.fake()->unique()->numberBetween(1, 999),
            'name_en' => 'Class '.fake()->unique()->numberBetween(1, 999),
            'numeric_order' => fake()->numberBetween(1, 20),
            'division' => null,
            'is_active' => true,
        ];
    }
}
