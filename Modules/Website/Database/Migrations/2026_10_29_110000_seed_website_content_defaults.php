<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Website\Support\WebsiteDefaults;

return new class extends Migration
{
    /**
     * Gives the client today's lists and prices to edit. Idempotent: each table is only filled while it is empty.
     */
    public function up(): void
    {
        WebsiteDefaults::seed();
    }

    public function down(): void {}
};
