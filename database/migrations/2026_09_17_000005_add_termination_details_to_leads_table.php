<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Termination detail columns for the "Terminated leads - history" panel:
     * why a lead was lost, an optional free-text note, when, and which
     * pipeline stage it was lost from (status itself becomes "terminated",
     * so the stage it was in has to be captured separately to show/restore it).
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('termination_reason')->nullable()->after('status');
            $table->text('termination_note')->nullable()->after('termination_reason');
            $table->timestamp('terminated_at')->nullable()->after('termination_note');
            $table->string('status_before_termination')->nullable()->after('terminated_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'termination_reason',
                'termination_note',
                'terminated_at',
                'status_before_termination',
            ]);
        });
    }
};
