<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            // pinned posts feed mein upar, naya pin pehle (max 3 - CommunityPostModerator)
            $table->boolean('is_pinned')->default(false)->after('status');
            $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            // author ko notification mein aur moderator ko Rejected tab pe dikhta hai
            $table->text('rejection_reason')->nullable()->after('pinned_at');
            // aakhri approve / reject kab hua - moderation page ke "this week" numbers ke liye
            $table->timestamp('moderated_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropColumn(['is_pinned', 'pinned_at', 'rejection_reason', 'moderated_at']);
        });
    }
};
