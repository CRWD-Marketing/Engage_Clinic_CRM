<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A therapist's own quick post-session note ("GOOD", "Sample Note") on
     * their "My calendar" view - private to them, separate from the
     * scheduling `notes` field a supervisor/admin sets when booking, and
     * separate from `supervision_notes` (the supervisor's observation).
     */
    public function up(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->text('therapist_note')->nullable()->after('supervision_notes');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->dropColumn('therapist_note');
        });
    }
};
