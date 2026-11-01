<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Website\Support\PosterHeroMigration;

return new class extends Migration
{
    /**
     * Moves the seeded benefits to the poster's wording (only when unedited) and emoji icons to icon keys.
     */
    public function up(): void
    {
        PosterHeroMigration::run();
    }

    public function down(): void {}
};
