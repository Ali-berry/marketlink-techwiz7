<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            // urgent pickup farmer ke apne stall / farm pe hota hai, is liye ye hours market schedule se alag hain
            $table->boolean('accepts_urgent_orders')->default(false)->after('order_cutoff_hours');
            $table->boolean('ai_auto_confirms_urgent')->default(false)->after('accepts_urgent_orders');
            $table->time('urgent_pickup_starts_at')->nullable()->after('ai_auto_confirms_urgent');
            $table->time('urgent_pickup_ends_at')->nullable()->after('urgent_pickup_starts_at');
            $table->unsignedSmallInteger('max_urgent_orders_per_hour')->default(5)->after('urgent_pickup_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'accepts_urgent_orders',
                'ai_auto_confirms_urgent',
                'urgent_pickup_starts_at',
                'urgent_pickup_ends_at',
                'max_urgent_orders_per_hour',
            ]);
        });
    }
};
