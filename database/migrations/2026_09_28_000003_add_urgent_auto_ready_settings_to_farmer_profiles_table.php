<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            // sirf tab kaam ka jab ai_auto_confirms_urgent on ho - stall form dono ko saath off karta hai
            $table->boolean('ai_marks_urgent_ready')->default(false)->after('ai_auto_confirms_urgent');
            $table->unsignedTinyInteger('urgent_prep_minutes')->default(10)->after('ai_marks_urgent_ready');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropColumn(['ai_marks_urgent_ready', 'urgent_prep_minutes']);
        });
    }
};
