<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The payer has no CRM access - their answer arrives by email, phone or
     * portal and staff record it here: what was decided, when, how it was
     * communicated, who logged it, and (on approval) the client-file
     * authorization it opened.
     */
    public function up(): void
    {
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->date('decided_on')->nullable()->after('submitted_on');
            $table->foreignId('decided_by')->nullable()->after('decided_on')->constrained('users')->nullOnDelete();
            $table->string('decision_channel', 30)->nullable()->after('decided_by');
            $table->unsignedSmallInteger('approved_hours')->nullable()->after('hours');
            $table->unsignedTinyInteger('coverage_percent')->nullable()->after('approved_hours');
            $table->foreignId('patient_authorization_id')->nullable()->after('resubmitted_from_id')->constrained('patient_authorizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->dropForeign(['decided_by']);
            $table->dropForeign(['patient_authorization_id']);
            $table->dropColumn(['decided_on', 'decided_by', 'decision_channel', 'approved_hours', 'coverage_percent', 'patient_authorization_id']);
        });
    }
};
