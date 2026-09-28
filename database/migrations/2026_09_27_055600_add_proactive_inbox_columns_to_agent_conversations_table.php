<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // proactive inbox: agent khud message bhejta hai (kind), us ka reference (context),
        // quick buttons (actions), reply thread (reply_to_message_id) aur unread badge (read_at)
        Schema::table('agent_conversations', function (Blueprint $table) {
            $table->string('kind', 20)->default('normal')->after('role');
            $table->json('context')->nullable()->after('content');
            $table->json('actions')->nullable()->after('context');
            $table->foreignId('reply_to_message_id')->nullable()->after('actions')
                ->constrained('agent_conversations')->nullOnDelete();
            $table->timestamp('read_at')->nullable()->after('reply_to_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reply_to_message_id');
            $table->dropColumn(['kind', 'context', 'actions', 'read_at']);
        });
    }
};
