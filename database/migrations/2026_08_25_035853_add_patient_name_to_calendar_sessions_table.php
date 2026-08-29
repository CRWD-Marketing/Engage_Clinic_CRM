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
            // Denormalized snapshot of the linked Lead's child_name, kept in sync by
            // CalendarSession::booted()'s saving() hook. That hook has unconditionally
            // written to this column since the model was written; it was simply never
            // migrated, which breaks every session create/update with a "column not
            // found" error the moment it runs.
            $table->string('patient_name')->nullable()->after('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->dropColumn('patient_name');
        });
    }
};
