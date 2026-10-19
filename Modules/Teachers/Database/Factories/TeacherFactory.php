<?php

namespace Modules\Teachers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Accounts\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Accounts\Models\User;
use Modules\Teachers\Models\Teacher;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name_bn' => fake()->unique()->lexify('শিক্ষক-???'),
            'name_en' => fake()->name(),
            'designation' => 'সহকারী শিক্ষক',
            'phone' => '01'.fake()->numerify('#########'),
            'is_active' => true,
        ];
    }

    /**
     * With a teacher-role login so the teacher can sign in and be scoped.
     */
    public function withLogin(): static
    {
        return $this->afterMaking(function (Teacher $teacher): void {
            $user = User::factory()->create(['name' => $teacher->name_bn]);
            app(RolesAndPermissionsSeeder::class)->run();
            $user->assignRole('teacher');
            $teacher->user_id = $user->id;
        });
    }
}
