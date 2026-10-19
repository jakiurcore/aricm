<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laramod\Facades\Seeder as ModuleSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: first what every module needs to work, then (outside production) demo data.
     */
    public function run(): void
    {
        ModuleSeeder::runSeeders();

        // Demo people, classes and fees never land in a production database, even by accident.
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
