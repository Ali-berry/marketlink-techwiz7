<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ek order hamesha ek farmer ka - cart mein do farmers ho to checkout do orders banata hai
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('farmer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->restrictOnDelete();
            $table->foreignId('pickup_window_id')->nullable()->constrained()->nullOnDelete();
            $table->date('pickup_date');
            $table->string('status', 30)->default('placed')->index();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('customer_note')->nullable();
            $table->string('decline_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // order ke waqt copy, taake product badle to bhi purana order sahi dikhe
            $table->string('product_name', 100);
            $table->string('unit', 20);
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });

        // har status change yahan save hota hai (audit trail)
        Schema::create('order_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_changes');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
