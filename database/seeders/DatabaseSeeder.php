<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laramod\Facades\Seeder as ModuleSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: first what every module needs to work, then demo data.
     */
    public function run(): void
    {
        ModuleSeeder::runSeeders();

        $this->call(DemoSeeder::class);
    }
}
