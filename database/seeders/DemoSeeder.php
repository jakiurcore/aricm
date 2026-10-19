<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Database\Seeders\DemoAcademicSeeder;
use Modules\Accounts\Models\User;
use Modules\Diary\Database\Seeders\DemoDiarySeeder;
use Modules\Exams\Database\Seeders\DemoExamSeeder;
use Modules\Fees\Database\Seeders\DemoFeesSeeder;
use Modules\Notices\Database\Seeders\DemoNoticeSeeder;
use Modules\Results\Database\Seeders\DemoResultSeeder;
use Modules\Students\Database\Seeders\DemoStudentSeeder;
use Modules\Teachers\Database\Seeders\DemoTeacherSeeder;

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

        $this->call(DemoNoticeSeeder::class);
        $this->call(DemoAcademicSeeder::class);
        $this->call(DemoStudentSeeder::class);
        $this->call(DemoTeacherSeeder::class);
        $this->call(DemoDiarySeeder::class);
        $this->call(DemoExamSeeder::class);
        $this->call(DemoResultSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'accountant@example.com'],
            ['name' => 'হিসাবরক্ষক', 'password' => 'password', 'is_active' => true],
        )->assignRole('accountant');

        $this->call(DemoFeesSeeder::class);
    }
}
