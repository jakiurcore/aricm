<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value_bn')->nullable();
            $table->text('value_en')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('website_items', function (Blueprint $table) {
            $table->id();
            $table->string('collection', 40);
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->text('body_bn')->nullable();
            $table->text('body_en')->nullable();
            $table->string('icon', 40)->nullable();
            $table->json('meta')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['collection', 'is_active', 'sort_order']);
        });

        Schema::create('website_price_rows', function (Blueprint $table) {
            $table->id();
            $table->string('label_bn');
            $table->string('label_en')->nullable();
            $table->string('note_bn')->nullable();
            $table->string('note_en')->nullable();
            $table->json('prices')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('website_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_sections');
        Schema::dropIfExists('website_price_rows');
        Schema::dropIfExists('website_items');
        Schema::dropIfExists('website_contents');
    }
};
