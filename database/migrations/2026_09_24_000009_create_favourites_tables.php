<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favourite_farmers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('farmer_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'farmer_profile_id']);
        });

        Schema::create('favourite_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // customer ko batana hai jab ye product wapas stock mein aaye
            $table->boolean('wants_restock_alert')->default(true);
            $table->timestamps();
            $table->unique(['customer_id', 'product_id']);
        });

        Schema::create('saved_markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_markets');
        Schema::dropIfExists('favourite_products');
        Schema::dropIfExists('favourite_farmers');
    }
};
