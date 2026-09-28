<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // urgent order pe bhi market_id zaroori hai - farmer ki home market (jis ke timezone mein urgent hours hain),
            // taake reports aur filters chalte rahen. Asal pickup farm ke address pe hota hai
            $table->boolean('is_urgent')->default(false)->after('pickup_date')->index();
            $table->dateTime('urgent_pickup_at')->nullable()->after('is_urgent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_urgent', 'urgent_pickup_at']);
        });
    }
};
