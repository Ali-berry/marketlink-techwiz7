<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->text('address');
            $table->string('city', 60);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            // jaise ["saturday", "sunday"]
            $table->json('operating_days');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->string('cover_image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
