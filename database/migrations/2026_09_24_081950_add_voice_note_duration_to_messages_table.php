<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chrome ki webm file mein duration nahi hoti, <audio> "0:00 / 0:00" dikhata hai -
        // is liye recording ke waqt length khud save karte hain
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedSmallInteger('voice_note_duration_seconds')->nullable()->after('voice_note_path');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('voice_note_duration_seconds');
        });
    }
};
