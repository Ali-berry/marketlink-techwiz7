<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // har customer-farmer pair ka sirf ek thread, taake purani baat doosre thread mein gum na ho
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('farmer_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['customer_id', 'farmer_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
