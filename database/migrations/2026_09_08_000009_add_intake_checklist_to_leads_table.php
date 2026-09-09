<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 7-step intake checklist a lead goes through before conversion, plus
     * the ad-attribution fields shown in "Where this lead came from" (only
     * ever populated for leads that arrive via a tracked ad/lead-form source -
     * blank/dash for manual, walk-in, or plain WhatsApp-converted leads).
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');

            // Where this lead came from (ad attribution - optional, source-dependent)
            $table->string('campaign')->nullable()->after('source');
            $table->string('ad_name')->nullable()->after('campaign');
            $table->string('lead_form_name')->nullable()->after('ad_name');
            $table->string('city')->nullable()->after('lead_form_name');
            $table->string('child_age_band')->nullable()->after('city');

            // Step 1: Parent contact verified
            $table->timestamp('parent_contact_completed_at')->nullable();
            $table->string('parent_relationship')->nullable();
            $table->string('parent_alternate_phone')->nullable();
            $table->string('preferred_language')->nullable();

            // Step 2: Child details complete
            $table->timestamp('child_details_completed_at')->nullable();
            $table->date('child_date_of_birth')->nullable();
            $table->string('child_gender')->nullable();
            $table->string('child_emirates_id')->nullable();
            $table->date('child_emirates_id_expiry')->nullable();
            $table->string('diagnosis_suspected')->nullable();
            $table->string('nursery_school')->nullable();
            $table->string('main_concern')->nullable();

            // Step 3: Intake form received
            $table->timestamp('intake_form_completed_at')->nullable();
            $table->date('intake_form_received_on')->nullable();
            $table->string('intake_form_received_via')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_history')->nullable();

            // Step 4: Consultation / assessment done
            $table->timestamp('assessment_completed_at')->nullable();
            $table->date('assessment_date')->nullable();
            $table->foreignId('assessment_clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assessment_tool')->nullable();
            $table->string('assessment_report_reference')->nullable();
            $table->text('assessment_report_summary')->nullable();

            // Step 5: Funding confirmed
            $table->timestamp('funding_completed_at')->nullable();
            $table->string('funding_type')->nullable();
            $table->string('funding_insurer')->nullable();
            $table->string('funding_policy_number')->nullable();
            $table->date('funding_approval_valid_until')->nullable();
            $table->json('funding_services_needed')->nullable(); // [{service, payer, hours_per_week}, ...]
            $table->text('funding_notes')->nullable();

            // Step 6: Package agreed
            $table->timestamp('package_completed_at')->nullable();
            $table->foreignId('package_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->json('package_ids')->nullable(); // array of packages.id
            $table->date('package_start_date')->nullable();
            $table->unsignedTinyInteger('package_sessions_per_week')->nullable();
            $table->string('package_agreed_by')->nullable();
            $table->text('package_scheduling_notes')->nullable();

            // Step 7: Consent & terms signed
            $table->timestamp('consent_completed_at')->nullable();
            $table->date('consent_signed_date')->nullable();
            $table->string('consent_signed_by')->nullable();
            $table->string('consent_data_photo')->nullable();
            $table->string('consent_signature_method')->nullable();
            $table->text('consent_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['assessment_clinician_id']);
            $table->dropForeign(['package_location_id']);

            $table->dropColumn([
                'email',
                'campaign', 'ad_name', 'lead_form_name', 'city', 'child_age_band',
                'parent_contact_completed_at', 'parent_relationship', 'parent_alternate_phone', 'preferred_language',
                'child_details_completed_at', 'child_date_of_birth', 'child_gender', 'child_emirates_id',
                'child_emirates_id_expiry', 'diagnosis_suspected', 'nursery_school', 'main_concern',
                'intake_form_completed_at', 'intake_form_received_on', 'intake_form_received_via', 'allergies', 'medical_history',
                'assessment_completed_at', 'assessment_date', 'assessment_clinician_id', 'assessment_tool',
                'assessment_report_reference', 'assessment_report_summary',
                'funding_completed_at', 'funding_type', 'funding_insurer', 'funding_policy_number',
                'funding_approval_valid_until', 'funding_services_needed', 'funding_notes',
                'package_completed_at', 'package_location_id', 'package_ids', 'package_start_date',
                'package_sessions_per_week', 'package_agreed_by', 'package_scheduling_notes',
                'consent_completed_at', 'consent_signed_date', 'consent_signed_by', 'consent_data_photo',
                'consent_signature_method', 'consent_notes',
            ]);
        });
    }
};
