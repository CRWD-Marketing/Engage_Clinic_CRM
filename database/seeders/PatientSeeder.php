<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Patient;
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

            Patient::updateOrCreate(
                ['lead_id' => $lead->id],
                [
                    'diagnosis' => $diagnoses[$i % count($diagnoses)],
                    'programme' => $programmes[$i % count($programmes)],
                    'treatment_plan_review_due_at' => now()->addDays(random_int(20, 90)),
                    'insurance_provider' => $insurers[$i % count($insurers)],
                    'authorized_sessions_total' => [20, 30, 40, 60][$i % 4],
                    'authorization_renews_at' => now()->addMonths(random_int(2, 6)),
                    'enrolled_at' => $enrolledAt,
                ]
            );
        });
    }
}
