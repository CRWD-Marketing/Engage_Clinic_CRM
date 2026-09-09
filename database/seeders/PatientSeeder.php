<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    /**
     * Convert every enrolled lead into a Patient clinical/enrollment record,
     * mirroring what LeadController::convertToPatient() does in the app.
     */
    public function run(): void
    {
        $diagnoses = [
            'Autism Spectrum Disorder (Level 1)',
            'Autism Spectrum Disorder (Level 2)',
            'Autism Spectrum Disorder (Level 3)',
            'Global Developmental Delay',
            'Speech & Language Delay',
        ];

        $programmes = [
            'ABA 20h/wk + Speech 2h',
            'ABA 15h/wk + OT 1h',
            'ABA 25h/wk',
            'Combined ABA + Speech + OT',
            'Speech 3h/wk + OT 2h/wk',
        ];

        $insurers = ['Daman', 'Daman Enhanced', 'Thiqa', 'ADNIC', 'AXA / GIG', 'Self-pay'];

        Lead::where('status', Lead::STATUS_ENROLLED)->get()->each(function (Lead $lead, int $i) use ($diagnoses, $programmes, $insurers) {
            $enrolledAt = $lead->created_at->copy()->addDays(random_int(7, 21));
            $payer = $insurers[$i % count($insurers)];

            $patient = Patient::updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'diagnosis' => $diagnoses[$i % count($diagnoses)],
                    'programme' => $programmes[$i % count($programmes)],
                    'treatment_plan_review_due_at' => now()->addDays(random_int(20, 90)),
                    'enrolled_at' => $enrolledAt,
                ]
            );

            PatientAuthorization::updateOrCreate(
                ['patient_id' => $patient->id, 'sort_order' => 0],
                [
                    'payer_name' => $payer,
                    'coverage_percent' => $payer === 'Self-pay' ? 0 : [80, 85, 90][$i % 3],
                    'covers_services' => ['ABA', 'Speech'],
                    'policy_number' => $payer === 'Self-pay' ? null : strtoupper(substr($payer, 0, 2)).'-'.random_int(10, 99).'-'.random_int(100000, 999999),
                    'authorized_hours_total' => [20, 30, 40, 60][$i % 4],
                    'renews_at' => now()->addMonths(random_int(2, 6)),
                ]
            );
        });
    }
}
