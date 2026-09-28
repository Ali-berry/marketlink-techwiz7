<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            // kg, dozen, bunch, piece, litre...
            $table->string('unit', 20);
            $table->unsignedInteger('stock_quantity')->default(0);
            // farmer ka normal weekly stock, har hafte refill isi se
            $table->unsignedInteger('weekly_default_quantity')->default(0);
            $table->string('availability', 30)->default('available');
            $table->string('image_path')->nullable();
            // admin rule todne wali listing delete kiye baghair hide kar sakta hai
            $table->boolean('is_hidden_by_admin')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
