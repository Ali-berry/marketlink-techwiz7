<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // har hafte ka slot, jaise "Sunday 9:00 - 11:00 at Clifton market"
        Schema::create('pickup_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            // 0 = Sunday ... 6 = Saturday (Carbon ke dayOfWeek jaisa)
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('max_orders')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_windows');
    }
};
