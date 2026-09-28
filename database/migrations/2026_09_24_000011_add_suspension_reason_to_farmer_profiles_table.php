<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            // suspend pe farmer ko dikhta hai, dobara approve pe clear
            $table->text('suspension_reason')->nullable()->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropColumn('suspension_reason');
        });
    }
};
