<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Support\WebsiteDefaults;

class WebsiteContentSeeder extends Seeder
{
    /**
     * Writes the default lists and prices once; tables the client has already edited are left alone.
     */
    public function run(): void
    {
        WebsiteDefaults::seed();
    }
}
