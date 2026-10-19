<?php

namespace Modules\Students\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Academic\Models\AcademicSession;
use Modules\Academic\Models\SchoolClass;
use Modules\Students\Enums\Gender;
use Modules\Students\Enums\ResidencyType;
use Modules\Students\Enums\StudentStatus;
use Modules\Students\Models\Student;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Creates the class/session it needs when none is given. Tests normally pass them explicitly.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dob = fake()->dateTimeBetween('-14 years', '-5 years');

        return [
            'student_id' => 'ARICM-2026-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'session_id' => AcademicSession::factory(),
            'class_id' => SchoolClass::factory(),
            'section_id' => null,
            'roll' => null,
            'name_bn' => fake()->name(),
            'name_en' => fake()->name(),
            'father_name' => fake()->name('male'),
            'mother_name' => fake()->name('female'),
            'guardian_name' => fake()->name('male'),
            'guardian_phone' => '01'.fake()->numerify('#########'),
            'guardian_relation' => 'পিতা',
            'date_of_birth' => $dob->format('Y-m-d'),
            'gender' => fake()->randomElement(Gender::cases()),
            'blood_group' => fake()->randomElement(['A+', 'B+', 'O+', 'AB+']),
            'religion' => 'ইসলাম',
            'present_address' => 'গাইবান্ধা সদর',
            'permanent_address' => 'গাইবান্ধা সদর',
            'admission_date' => '2026-01-10',
            'status' => StudentStatus::Active,
            'residency_type' => ResidencyType::NonResidential,
            'password' => $dob->format('dmY'),
            'must_change_password' => true,
        ];
    }
}
