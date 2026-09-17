<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The insurer's pre-authorization/approval number (e.g. "PA-2026-00000")
     * captured per service in the intake Funding step - distinct from the
     * policy number, which identifies the family's policy, not this specific
     * approved course of therapy.
     */
    public function up(): void
    {
        Schema::table('patient_authorizations', function (Blueprint $table) {
            $table->string('approval_reference', 60)->nullable()->after('policy_number');
        });
    }

    public function down(): void
    {
        Schema::table('patient_authorizations', function (Blueprint $table) {
            $table->dropColumn('approval_reference');
        });
    }
};
