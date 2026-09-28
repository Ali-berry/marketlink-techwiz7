<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // har chat message ki ek row (user ka sawal ya assistant ka jawab), insert ke baad edit nahi
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // message kis agent ka hai - user ka ek hi role hota hai, bas history query simple rehti hai
            $table->string('user_type', 20);
            $table->string('role', 20);
            $table->text('content');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'user_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_conversations');
    }
};
