<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Lead;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Seeds a handful of "Contact us" landing-page submissions - most still
     * unconverted, one or two already turned into leads.
     */
    public function run(): void
    {
        $entries = [
            ['Huda Al Kaabi', 5, 'huda.alkaabi@example.com', '+971501112233', 'ABA therapy', 'Looking for an ABA centre near Al Reem Island, does insurance cover it?'],
            ['James Whitfield', 4, 'j.whitfield@example.com', '+971521114455', 'Speech therapy', 'My son was recently diagnosed and we are looking for speech therapy options.'],
            ['Mona Al Suwaidi', 6, 'mona.s@example.com', '+971561117788', 'Diagnostic assessment', 'Need a diagnostic assessment appointment as soon as possible.'],
            ['Sara Thompson', 3, 'sara.t@example.com', '+971581119900', 'Occupational therapy', 'Interested in OT for sensory processing concerns.'],
            ['Faisal Al Nuaimi', 7, 'faisal.n@example.com', '+971509998877', 'Early intervention', 'Can you share pricing for early intervention programmes?'],
            ['Aaliyah Rahim', 5, 'aaliyah.r@example.com', '+971523334455', 'Combined program', 'Would like a call back to discuss a combined ABA + Speech programme.'],
        ];

        foreach ($entries as $i => [$name, $age, $email, $phone, $interest, $message]) {
            $status = match (true) {
                $i === 0 => Contact::STATUS_CONVERTED,
                $i === 1 => Contact::STATUS_CONTACTED,
                $i === 2 => Contact::STATUS_CLOSED,
                default => Contact::STATUS_NEW,
            };

            $convertedLead = null;

            if ($status === Contact::STATUS_CONVERTED) {
                $convertedLead = Lead::where('child_name', 'Khalifa Al Mansoori')->first();
            }

            Contact::create([
                'name' => $name,
                'child_age' => (string) $age,
                'email' => $email,
                'phone' => $phone,
                'interested_in' => $interest,
                'message' => $message,
                'status' => $status,
                'converted_lead_id' => $convertedLead?->id,
                'converted_at' => $convertedLead ? now()->subDays(3) : null,
                'created_at' => now()->subDays(random_int(1, 20)),
            ]);
        }
    }
}
