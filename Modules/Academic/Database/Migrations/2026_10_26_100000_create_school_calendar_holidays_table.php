<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_calendar_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_calendar_holidays');
    }
};
