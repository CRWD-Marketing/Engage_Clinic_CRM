<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            // Tracks whether a coordinator has followed up on a no-show; null means still pending.
            $table->timestamp('follow_up_completed_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->dropColumn('follow_up_completed_at');
        });
    }
};
