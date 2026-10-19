<?php

namespace Modules\Academic\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\Subject;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_bn' => 'বিষয় '.fake()->unique()->numberBetween(1, 999),
            'name_en' => null,
            'code' => strtoupper(fake()->unique()->lexify('???')),
        ];
    }
}
