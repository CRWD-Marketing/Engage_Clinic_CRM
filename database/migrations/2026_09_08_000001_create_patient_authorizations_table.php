<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('payer_name'); // e.g. "Daman Enhanced", "Self-pay"
            $table->unsignedTinyInteger('coverage_percent')->default(0);
            // Which services this specific payer covers, e.g. ["ABA","Speech"] -
            // structured (not freeform text) so hours-used can be computed by
            // matching against calendar_sessions.activity_type.
            $table->json('covers_services')->nullable();
            $table->string('policy_number')->nullable();
            $table->unsignedInteger('authorized_hours_total')->nullable();
            $table->date('renews_at')->nullable();
            // First (lowest) sort_order is the "primary" authorization shown
            // on the patient list page.
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('patient_id');
            $table->index('renews_at');
        });

        // Carry forward each patient's existing single insurance/authorization
        // fields as their first (primary) authorization row, so nothing on
        // record is lost when those columns are dropped in the next migration.
        DB::table('patients')
            ->whereNotNull('insurance_provider')
            ->get(['id', 'insurance_provider', 'authorized_sessions_total', 'authorization_renews_at', 'programme', 'created_at'])
            ->each(function ($patient) {
                DB::table('patient_authorizations')->insert([
                    'patient_id' => $patient->id,
                    'payer_name' => $patient->insurance_provider,
                    'coverage_percent' => $patient->insurance_provider === 'Self-pay' ? 0 : 80,
                    'covers_services' => json_encode(array_values(array_filter([
                        str_contains((string) $patient->programme, 'ABA') ? 'ABA' : null,
                        str_contains((string) $patient->programme, 'Speech') ? 'Speech' : null,
                        str_contains((string) $patient->programme, 'OT') ? 'OT' : null,
                    ])) ?: ['ABA']),
                    'authorized_hours_total' => $patient->authorized_sessions_total,
                    'renews_at' => $patient->authorization_renews_at,
                    'sort_order' => 0,
                    'created_at' => $patient->created_at,
                    'updated_at' => $patient->created_at,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_authorizations');
    }
};
