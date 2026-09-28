<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // koi bhi logged in account post kar sakta hai, feed mein aane se pehle moderator dekhta hai
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_posts');
    }
};
