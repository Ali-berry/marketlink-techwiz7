<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('slug', 80)->unique();
            // Iconify icon ka naam, jaise "tabler:carrot"
            $table->string('icon', 60)->default('tabler:basket');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
