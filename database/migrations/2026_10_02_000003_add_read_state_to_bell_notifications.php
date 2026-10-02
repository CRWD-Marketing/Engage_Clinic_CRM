<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user read state for the topbar bell's derived feeds (new leads,
     * new contact messages, unread WhatsApp threads). Those rows aren't
     * database notifications, so they had no way to be marked read:
     * - notification_reads: one row per item a user clicked
     * - users.notifications_cleared_at: "Mark all as read" - everything up
     *   to this moment counts as read for that user
     */
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_key', 100); // lead:12, contact:7, whatsapp:3:1790000000
            $table->timestamps();

            $table->unique(['user_id', 'item_key']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('notifications_cleared_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notifications_cleared_at');
        });

        Schema::dropIfExists('notification_reads');
    }
};
