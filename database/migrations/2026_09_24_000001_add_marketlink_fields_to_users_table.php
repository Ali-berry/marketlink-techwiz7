<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->after('email')->index();
            $table->string('phone', 30)->nullable()->after('role');
            $table->text('address')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('address');
            $table->string('avatar_path')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'address', 'is_active', 'avatar_path']);
        });
    }
};
