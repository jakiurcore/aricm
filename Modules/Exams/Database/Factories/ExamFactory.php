<?php

namespace Modules\Exams\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\AcademicSession;
use Modules\Exams\Enums\ExamStatus;
use Modules\Exams\Models\Exam;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_bn' => 'অর্ধবার্ষিক পরীক্ষা '.fake()->unique()->numberBetween(1, 9999),
            'name_en' => null,
            'session_id' => AcademicSession::factory(),
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
            'status' => ExamStatus::Draft,
            'admit_card_released' => false,
        ];
    }

    public function released(): static
    {
        return $this->state(['admit_card_released' => true]);
    }
}
