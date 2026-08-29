<?php

namespace Database\Seeders;

use App\Models\Lead;
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

        foreach ($children as $i => [$childName, $parentName, $age, $status]) {
            $daysAgo = 60 - ($i * 3);
            $createdAt = now()->subDays(max($daysAgo, 1));

            $lead = Lead::updateOrCreate(
                ['child_name' => $childName],
                [
                    'child_age' => (string) $age,
                    'parent_guardian_name' => $parentName,
                    'phone' => '+9715'.random_int(0, 9).random_int(1000000, 9999999),
                    'source' => $sources[$i % count($sources)],
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
                ]
            );

            // updateOrCreate() overwrites timestamps() managed automatically, so
            // backdate created_at after the fact to spread the pipeline out.
            $lead->created_at = $createdAt;
            $lead->save();
        }
    }
}
