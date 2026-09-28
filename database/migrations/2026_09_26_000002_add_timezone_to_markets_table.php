<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            // Texas mein do timezones - El Paso Mountain, baqi Central. Hours aur cutoff market ke zone mein
            $table->string('timezone', 40)->default('America/Chicago')->after('closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
