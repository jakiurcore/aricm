<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Database\Seeders\DemoAcademicSeeder;
use Modules\Accounts\Models\User;
use Modules\Exams\Database\Seeders\DemoExamSeeder;
use Modules\Students\Database\Seeders\DemoStudentSeeder;

class DemoSeeder extends Seeder
{
    /**
     * Demo data for local development. Not run in production deployments (use --class to seed modules only).
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'সুপার অ্যাডমিন', 'password' => 'password', 'is_active' => true],
        )->assignRole('super_admin');

        $this->call(DemoAcademicSeeder::class);
        $this->call(DemoStudentSeeder::class);
        $this->call(DemoExamSeeder::class);
    }
}
