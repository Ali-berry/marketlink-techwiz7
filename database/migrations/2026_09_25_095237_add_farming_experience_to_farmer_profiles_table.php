<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            // farmer khud likhta hai (jaise "5 years growing organic vegetables") - MarketLink pe kitna time
            // wo created_at se aata hai, ye alag hai. Optional
            $table->string('farming_experience', 255)->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropColumn('farming_experience');
        });
    }
};
