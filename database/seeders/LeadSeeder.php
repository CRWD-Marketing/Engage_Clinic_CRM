<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Location;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Seed leads across every pipeline stage (including a couple of terminated
     * ones), spread over the last ~2 months so the pipeline/dashboard views
     * have something realistic to show.
     */
    public function run(): void
    {
        $owners = User::whereIn('role', ['SALES_STAFF', 'COORDINATOR', 'FULL_ADMIN'])->pluck('id')->all();
        $clinicians = User::where('role', 'THERAPIST')->pluck('id')->all();
        $packages = Package::orderBy('id')->get();
        $locations = Location::orderBy('id')->pluck('id')->all();

        $sources = ['Instagram', 'Facebook', 'WhatsApp', 'Walk-in', 'Referral', 'Google', 'Website', 'Event'];
        $interests = ['ABA therapy', 'Speech therapy', 'Occupational therapy', 'Diagnostic assessment', 'Early intervention', 'Combined program'];
        $insurances = ['Not sure yet', 'Daman', 'Daman Enhanced', 'Thiqa', 'ADNIC', 'AXA / GIG', 'Self-pay'];

        // [child_name, parent_guardian_name, age, status]
        $children = [
            ['Khalifa Al Mansoori', 'Mohammed Al Mansoori', 5, Lead::STATUS_ENROLLED],
            ['Layla Hassan', 'Youssef Hassan', 4, Lead::STATUS_ENROLLED],
            ['Sara Al Hammadi', 'Khalid Al Hammadi', 6, Lead::STATUS_ENROLLED],
            ['Zayed Al Nuaimi', 'Rashed Al Nuaimi', 4, Lead::STATUS_ENROLLED],
            ['Ali Al Falasi', 'Hamad Al Falasi', 7, Lead::STATUS_ENROLLED],
            ['Aisha Rahman', 'Imran Rahman', 3, Lead::STATUS_ENROLLED],
            ['Omar Farooq', 'Bilal Farooq', 5, Lead::STATUS_ASSESSMENT_DONE],
            ['Hamdan Al Ketbi', 'Sultan Al Ketbi', 6, Lead::STATUS_ASSESSMENT_DONE],
            ['Mariam Al Shamsi', 'Faisal Al Shamsi', 4, Lead::STATUS_ASSESSMENT_BOOKED],
            ['Noora Umm Rashid', 'Rashid Al Otaiba', 4, Lead::STATUS_ASSESSMENT_BOOKED],
            ['Fatima Al Zaabi', 'Ahmed Al Zaabi', 5, Lead::STATUS_CONTACTED],
            ['Rashid Al Suwaidi', 'Obaid Al Suwaidi', 8, Lead::STATUS_CONTACTED],
            ['Grace Okoro', 'Chidi Okoro', 3, Lead::STATUS_CONTACTED],
            ['Hessa Al Nuaimi', 'Marwan Al Nuaimi', 4, Lead::STATUS_NEW],
            ['Sultan Al Ameri', 'Nasser Al Ameri', 7, Lead::STATUS_NEW],
            ['Maitha Al Qubaisi', 'Saeed Al Qubaisi', 5, Lead::STATUS_NEW],
            ['Abdullah Al Marri', 'Juma Al Marri', 6, Lead::STATUS_NEW],
            ['Shamma Al Dhaheri', 'Butti Al Dhaheri', 4, Lead::STATUS_NEW],
            ['Saeed Al Mazrouei', 'Khalfan Al Mazrouei', 6, Lead::STATUS_TERMINATED],
            ['Noora Al Blooshi', 'Salem Al Blooshi', 3, Lead::STATUS_TERMINATED],
        ];

        // [reason, note] for each terminated lead above, in order - feeds the
        // "Why leads are lost" report card, which groups on termination_reason.
        $terminationDetails = [
            'Saeed Al Mazrouei' => ['reason' => 'Distance / relocated', 'note' => 'Family relocated outside Abu Dhabi.'],
            'Noora Al Blooshi' => ['reason' => 'Chose another provider', 'note' => 'Enrolled at a centre closer to Al Reem — asked to stay on our mailing list.'],
        ];

        // How many of the 7 intake-checklist steps are done by pipeline stage.
        // Assessment-done leads are deliberately left at 6/7 (missing consent)
        // so there's always at least one lead ready to test "finish the last
        // step -> auto-advance to Enrolled".
        $stepsByStatus = [
            Lead::STATUS_ENROLLED => 7,
            Lead::STATUS_ASSESSMENT_DONE => 6,
            Lead::STATUS_ASSESSMENT_BOOKED => 3,
            Lead::STATUS_CONTACTED => 1,
            Lead::STATUS_NEW => 0,
            Lead::STATUS_TERMINATED => 0,
        ];

        $diagnoses = ['Autism Spectrum Disorder', 'Global Developmental Delay', 'Speech & Language Delay', 'ADHD', null];
        $mainConcerns = [
            'Very few words, points instead of speaking.',
            'Limited eye contact, delayed speech milestones.',
            'Repetitive behaviours, sensory sensitivities.',
            'Difficulty following instructions at nursery.',
            'Expressive language behind same-age peers.',
        ];
        $nurseries = ['Home', 'Kids World Nursery', 'Blossom Nursery', 'Home', 'Little Falcons Nursery'];
        $assessmentTools = ['ADOS-2', 'PLS-5', 'Vineland-3', 'M-CHAT-R'];
        $fundingTypes = ['Insurance', 'Self pay'];
        $consentSignedBy = ['Parent', 'Mother', 'Father', 'Guardian'];
        $consentMethods = ['In person', 'Email', 'WhatsApp'];

        foreach ($children as $i => [$childName, $parentName, $age, $status]) {
            $daysAgo = 60 - ($i * 3);
            $createdAt = now()->subDays(max($daysAgo, 1));

            $stepsComplete = $stepsByStatus[$status];
            $intakeData = $this->intakeChecklistData(
                $stepsComplete, $i, $age, $createdAt, $clinicians, $packages, $locations,
                $diagnoses, $mainConcerns, $nurseries, $assessmentTools, $fundingTypes,
                $consentSignedBy, $consentMethods
            );

            $lead = Lead::updateOrCreate(
                ['child_name' => $childName],
                array_merge([
                    'child_age' => (string) $age,
                    'parent_guardian_name' => $parentName,
                    'phone' => '+9715'.random_int(0, 9).random_int(1000000, 9999999),
                    'email' => strtolower(str_replace(' ', '.', $parentName)).'@gmail.com',
                    'source' => $sources[$i % count($sources)],
                    'campaign' => 'Home Based Speech Therapy',
                    'city' => 'Abu Dhabi',
                    'child_age_band' => $age <= 4 ? '3-4' : ($age <= 6 ? '5-6' : '7-8'),
                    'interested_in' => $interests[$i % count($interests)],
                    'insurance' => $insurances[$i % count($insurances)],
                    'estimated_value' => (string) random_int(8, 40) * 1000,
                    'notes' => match ($status) {
                        Lead::STATUS_ENROLLED => 'Enrolled and attending regular sessions.',
                        Lead::STATUS_TERMINATED => 'Family relocated outside Abu Dhabi.',
                        default => 'Parent reached out asking about availability and pricing.',
                    },
                    'status' => $status,
                    'assigned_to' => $owners ? $owners[$i % count($owners)] : null,
                    'follow_up_due_at' => in_array($status, [Lead::STATUS_CONTACTED, Lead::STATUS_ASSESSMENT_BOOKED], true)
                        ? now()->addDays(random_int(1, 7))
                        : null,
                    'termination_reason' => $status === Lead::STATUS_TERMINATED ? $terminationDetails[$childName]['reason'] : null,
                    'termination_note' => $status === Lead::STATUS_TERMINATED ? $terminationDetails[$childName]['note'] : null,
                    'terminated_at' => $status === Lead::STATUS_TERMINATED ? $createdAt->copy()->addDays(5) : null,
                    'status_before_termination' => $status === Lead::STATUS_TERMINATED ? Lead::STATUS_CONTACTED : null,
                ], $intakeData)
            );

            // updateOrCreate() overwrites timestamps() managed automatically, so
            // backdate created_at after the fact to spread the pipeline out.
            $lead->created_at = $createdAt;
            $lead->save();
        }

        $this->fullyFilledShowcaseLead($owners, $clinicians, $packages, $locations);
    }

    /**
     * One lead with every single fillable field populated - including the ones
     * the loop above deliberately leaves blank (ad_name/lead_form_name for
     * non-ad sources, package_scheduling_notes, consent_notes, and the
     * insurer/policy pair on self-pay leads). Gives the lead detail view one
     * record to render where every section is filled in, nothing dashed out.
     */
    private function fullyFilledShowcaseLead(array $owners, array $clinicians, \Illuminate\Support\Collection $packages, array $locations): void
    {
        $createdAt = now()->subDays(70);
        $packageIds = $packages->isNotEmpty() ? $packages->take(2)->pluck('id')->all() : null;

        $lead = Lead::updateOrCreate(
            ['child_name' => 'Amina Al Rashidi'],
            [
                'child_age' => '5',
                'parent_guardian_name' => 'Fatima Al Rashidi',
                'phone' => '+971501234567',
                'email' => 'fatima.alrashidi@gmail.com',
                'source' => 'Facebook',
                'campaign' => 'Autism Early Intervention - Abu Dhabi Q3',
                'ad_name' => 'ABA Therapy Free Consultation Ad',
                'lead_form_name' => 'Book a Free Consultation',
                'city' => 'Abu Dhabi',
                'child_age_band' => '5-6',
                'interested_in' => 'ABA therapy',
                'insurance' => 'Daman Enhanced',
                'estimated_value' => '18000',
                'notes' => 'Fully onboarded lead - every intake field filled in for UI/QA reference.',
                'status' => Lead::STATUS_ENROLLED,
                'assigned_to' => $owners[0] ?? null,
                'follow_up_due_at' => now()->addDays(3),

                // Step 1: Parent contact verified
                'parent_contact_completed_at' => $createdAt->copy()->addDay(),
                'parent_relationship' => 'Mother',
                'parent_alternate_phone' => '+971501112222',
                'preferred_language' => 'English',

                // Step 2: Child details complete
                'child_details_completed_at' => $createdAt->copy()->addDays(2),
                'child_date_of_birth' => now()->subYears(5),
                'child_gender' => 'Female',
                'child_emirates_id' => '784-2020-1234567-8',
                'child_emirates_id_expiry' => now()->addYears(3),
                'diagnosis_suspected' => 'Autism Spectrum Disorder',
                'nursery_school' => 'Blossom Nursery',
                'main_concern' => 'Limited eye contact, delayed speech milestones.',

                // Step 3: Intake form received
                'intake_form_completed_at' => $createdAt->copy()->addDays(4),
                'intake_form_received_on' => $createdAt->copy()->addDays(4),
                'intake_form_received_via' => 'WhatsApp',
                'allergies' => 'No known allergies',
                'medical_history' => 'No significant medical history.',

                // Step 4: Consultation / assessment done
                'assessment_completed_at' => $createdAt->copy()->addDays(7),
                'assessment_date' => $createdAt->copy()->addDays(7),
                'assessment_clinician_id' => $clinicians[0] ?? null,
                'assessment_tool' => 'ADOS-2',
                'assessment_report_reference' => 'ASM-'.$createdAt->format('Y').'-999',
                'assessment_report_summary' => 'Expressive language below age-expected range; ABA + speech therapy recommended.',

                // Step 5: Funding confirmed
                'funding_completed_at' => $createdAt->copy()->addDays(9),
                'funding_type' => 'Insurance',
                'funding_insurer' => 'Daman',
                'funding_policy_number' => 'DA-24-556677',
                'funding_approval_valid_until' => now()->addMonths(12),
                'funding_services_needed' => [
                    [
                        'service' => 'ABA therapy session',
                        'payer' => 'Insurance',
                        'hours_per_week' => 20,
                        'cover' => '96 h approved',
                        'approval_ref' => 'PA-'.$createdAt->format('Y').'-99999',
                    ],
                    [
                        'service' => 'Speech & language therapy',
                        'payer' => 'Self pay',
                        'hours_per_week' => 10,
                        'cover' => 'Billed to family',
                        'approval_ref' => null,
                    ],
                ],
                'funding_notes' => 'Daman covers ABA only - speech billed to the family monthly.',

                // Step 6: Package agreed
                'package_completed_at' => $createdAt->copy()->addDays(11),
                'package_location_id' => $locations[0] ?? null,
                'package_ids' => $packageIds,
                'package_start_date' => $createdAt->copy()->addDays(14),
                'package_sessions_per_week' => 5,
                'package_agreed_by' => 'Sara',
                'package_scheduling_notes' => 'Prefers mornings, Sun/Tue/Thu; Room 2 for consistency.',

                // Step 7: Consent & terms signed
                'consent_completed_at' => $createdAt->copy()->addDays(13),
                'consent_signed_date' => $createdAt->copy()->addDays(13),
                'consent_signed_by' => 'Mother',
                'consent_data_photo' => 'Yes',
                'consent_signature_method' => 'In person',
                'consent_notes' => 'Signed original on file plus a digital copy emailed to the family.',
            ]
        );

        $lead->created_at = $createdAt;
        $lead->save();
    }

    /**
     * Build the intake-checklist columns for a lead, filling in the first
     * $stepsComplete of the 7 steps (in order) with plausible data and a
     * completed_at timestamp spaced out after $createdAt.
     */
    private function intakeChecklistData(
        int $stepsComplete,
        int $i,
        int $age,
        \Illuminate\Support\Carbon $createdAt,
        array $clinicians,
        \Illuminate\Support\Collection $packages,
        array $locations,
        array $diagnoses,
        array $mainConcerns,
        array $nurseries,
        array $assessmentTools,
        array $fundingTypes,
        array $consentSignedBy,
        array $consentMethods
    ): array {
        $data = [];
        $stampAt = fn (int $daysAfter) => $createdAt->copy()->addDays($daysAfter)->min(now());

        if ($stepsComplete >= 1) {
            $data['parent_contact_completed_at'] = $stampAt(1);
            $data['parent_relationship'] = ['Mother', 'Father', 'Guardian'][$i % 3];
            $data['parent_alternate_phone'] = '+9714'.random_int(1000000, 9999999);
            $data['preferred_language'] = $i % 4 === 0 ? 'Arabic' : 'English';
        }

        if ($stepsComplete >= 2) {
            $data['child_details_completed_at'] = $stampAt(2);
            $data['child_date_of_birth'] = now()->subYears($age)->subMonths($i % 12);
            $data['child_gender'] = $i % 2 === 0 ? 'Male' : 'Female';
            $data['child_emirates_id'] = '784-'.(2018 + ($i % 6)).'-'.random_int(1000000, 9999999).'-'.random_int(0, 9);
            $data['child_emirates_id_expiry'] = now()->addYears(2);
            $data['diagnosis_suspected'] = $diagnoses[$i % count($diagnoses)];
            $data['nursery_school'] = $nurseries[$i % count($nurseries)];
            $data['main_concern'] = $mainConcerns[$i % count($mainConcerns)];
        }

        if ($stepsComplete >= 3) {
            $data['intake_form_completed_at'] = $stampAt(4);
            $data['intake_form_received_on'] = $stampAt(4);
            $data['intake_form_received_via'] = ['Email', 'WhatsApp', 'In person'][$i % 3];
            $data['allergies'] = $i % 5 === 0 ? 'None known' : 'No known allergies';
            $data['medical_history'] = $i % 4 === 0 ? 'Premature birth at 34 weeks.' : 'No significant history.';
        }

        if ($stepsComplete >= 4) {
            $data['assessment_completed_at'] = $stampAt(7);
            $data['assessment_date'] = $stampAt(7);
            $data['assessment_clinician_id'] = $clinicians ? $clinicians[$i % count($clinicians)] : null;
            $data['assessment_tool'] = $assessmentTools[$i % count($assessmentTools)];
            $data['assessment_report_reference'] = 'ASM-'.$createdAt->format('Y').'-'.(100 + $i);
            $data['assessment_report_summary'] = 'Expressive language below age-expected range. Therapy recommended.';
        }

        if ($stepsComplete >= 5) {
            $insuranceHours = [60, 80, 96, 120][$i % 4];
            $insurer = ['Daman', 'Thiqa', 'ADNIC'][$i % 3];
            $policyNumber = strtoupper(substr($insurer, 0, 2)).'-'.random_int(10, 99).'-'.random_int(100000, 999999);

            $data['funding_completed_at'] = $stampAt(9);
            $data['funding_type'] = $fundingTypes[$i % count($fundingTypes)];
            $data['funding_insurer'] = $data['funding_type'] === 'Insurance' ? $insurer : null;
            $data['funding_policy_number'] = $data['funding_type'] === 'Insurance' ? $policyNumber : null;
            $data['funding_approval_valid_until'] = now()->addMonths(random_int(6, 12));
            // ABA is insurance-covered, speech is billed to the family - a
            // realistic "several therapies, different payers" split rather
            // than everything under the single funding_type picked above.
            $data['funding_services_needed'] = [
                [
                    'service' => 'ABA therapy session',
                    'payer' => 'Insurance',
                    'hours_per_week' => 20,
                    'cover' => $insuranceHours.' h approved',
                    'approval_ref' => 'PA-'.$createdAt->format('Y').'-'.random_int(10000, 99999),
                ],
                [
                    'service' => 'Speech & language therapy',
                    'payer' => 'Self pay',
                    'hours_per_week' => 10,
                    'cover' => 'Billed to family',
                    'approval_ref' => null,
                ],
            ];
            $data['funding_notes'] = $data['funding_type'] === 'Insurance'
                ? $insurer.' covers ABA only — speech billed to the family monthly.'
                : null;
        }

        if ($stepsComplete >= 6) {
            $data['package_completed_at'] = $stampAt(11);
            $data['package_location_id'] = $locations ? $locations[$i % count($locations)] : null;
            $data['package_ids'] = $packages->isNotEmpty() ? [$packages[$i % $packages->count()]->id] : null;
            $data['package_start_date'] = $stampAt(14);
            $data['package_sessions_per_week'] = [2, 3, 4, 5][$i % 4];
            $data['package_agreed_by'] = ['Emily', 'Sara', 'Noor'][$i % 3];
            $data['package_scheduling_notes'] = null;
        }

        if ($stepsComplete >= 7) {
            $data['consent_completed_at'] = $stampAt(13);
            $data['consent_signed_date'] = $stampAt(13);
            $data['consent_signed_by'] = $consentSignedBy[$i % count($consentSignedBy)];
            $data['consent_data_photo'] = 'Yes';
            $data['consent_signature_method'] = $consentMethods[$i % count($consentMethods)];
            $data['consent_notes'] = null;
        }

        return $data;
    }
}
