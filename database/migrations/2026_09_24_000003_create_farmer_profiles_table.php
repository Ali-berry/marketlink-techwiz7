<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('stall_name', 100);
            $table->string('slug', 120)->unique();
            $table->string('contact_person', 100);
            $table->text('bio')->nullable();
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            // pickup se kitne ghante pehle farmer changes lena band karta hai
            $table->unsignedSmallInteger('order_cutoff_hours')->default(12);
            $table->string('approval_status', 20)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->timestamps();
        });

        // farmer kin markets mein bechta hai
        Schema::create('farmer_market', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->string('stall_number', 20)->nullable();
            $table->timestamps();

            $table->unique(['farmer_profile_id', 'market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_market');
        Schema::dropIfExists('farmer_profiles');
    }
};
