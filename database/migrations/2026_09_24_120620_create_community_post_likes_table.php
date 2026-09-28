<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // har banda har post pe ek like - toggle route bas ye row add / remove karta hai
            $table->unique(['post_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_post_likes');
    }
};
