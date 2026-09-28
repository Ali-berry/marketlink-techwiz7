<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // hide karte waqt admin ka note, farmer ko dikhta hai taake wajah pata ho
        Schema::table('products', function (Blueprint $table) {
            $table->string('hidden_reason', 255)->nullable()->after('is_hidden_by_admin');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('hidden_reason', 255)->nullable()->after('is_hidden_by_admin');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('hidden_reason');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('hidden_reason');
        });
    }
};
