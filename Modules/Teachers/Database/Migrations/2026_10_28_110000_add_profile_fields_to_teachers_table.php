<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->date('joined_on')->nullable()->after('email');
            $table->string('status', 10)->default('active')->after('joined_on');
            $table->string('photo')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['email', 'joined_on', 'status', 'photo']);
        });
    }
};
