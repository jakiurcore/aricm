<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->text('body_bn');
            $table->text('body_en')->nullable();
            $table->string('category', 20)->default('general')->index();
            $table->string('attachment')->nullable();
            $table->string('attachment_name')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('show_in_ticker')->default(false);
            $table->timestamp('publish_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['is_published', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
