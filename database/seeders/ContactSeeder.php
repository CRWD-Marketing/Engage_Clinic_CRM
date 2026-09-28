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
        // [parent/guardian, child, age, email, phone, service, message] - the
        // child's name is what the pipeline card leads with once a submission
        // is converted, so every seeded contact carries one.
        $entries = [
            ['Huda Al Kaabi', 'Khalifa Al Mansoori', 5, 'huda.alkaabi@example.com', '+971501112233', 'ABA therapy', 'Looking for an ABA centre near Al Reem Island, does insurance cover it?'],
            ['James Whitfield', 'Oliver Whitfield', 4, 'j.whitfield@example.com', '+971521114455', 'Speech therapy', 'My son was recently diagnosed and we are looking for speech therapy options.'],
            ['Mona Al Suwaidi', 'Latifa Al Suwaidi', 6, 'mona.s@example.com', '+971561117788', 'Diagnostic assessment', 'Need a diagnostic assessment appointment as soon as possible.'],
            ['Sara Thompson', 'Ethan Thompson', 3, 'sara.t@example.com', '+971581119900', 'Occupational therapy', 'Interested in OT for sensory processing concerns.'],
            ['Faisal Al Nuaimi', 'Rashid Al Nuaimi', 7, 'faisal.n@example.com', '+971509998877', 'Early intervention', 'Can you share pricing for early intervention programmes?'],
            ['Aaliyah Rahim', 'Zayd Rahim', 5, 'aaliyah.r@example.com', '+971523334455', 'Combined program', 'Would like a call back to discuss a combined ABA + Speech programme.'],
        ];

        // Indices that came through the booking widget (a requested consultation
        // slot attached) rather than the plain contact form - see
        // Contact::hasBookingSlot(). Times match the landing page's slot format.
        $bookingSlots = [
            1 => ['date' => now()->addDays(4)->toDateString(), 'time' => '10:00 AM'],
            2 => ['date' => now()->subDays(2)->toDateString(), 'time' => '2:30 PM'],
            3 => ['date' => now()->addDays(6)->toDateString(), 'time' => '11:30 AM'],
        ];

        foreach ($entries as $i => [$name, $childName, $age, $email, $phone, $interest, $message]) {
            $status = match (true) {
                $i === 0 => Contact::STATUS_CONVERTED,
                $i === 1 => Contact::STATUS_CONTACTED,
                $i === 2 => Contact::STATUS_CLOSED,
                default => Contact::STATUS_NEW,
            };

            $convertedLead = null;

            if ($status === Contact::STATUS_CONVERTED) {
                $convertedLead = Lead::where('child_name', $childName)->first();
            }

            Contact::create([
                'name' => $name,
                'child_name' => $childName,
                'child_age' => (string) $age,
                'email' => $email,
                'phone' => $phone,
                'interested_in' => $interest,
                'message' => $message,
                'status' => $status,
                'booking_date' => $bookingSlots[$i]['date'] ?? null,
                'booking_time' => $bookingSlots[$i]['time'] ?? null,
                'booking_decision' => $status === Contact::STATUS_CLOSED ? Contact::STATUS_CLOSED : null,
                'converted_lead_id' => $convertedLead?->id,
                'converted_at' => $convertedLead ? now()->subDays(3) : null,
                'created_at' => now()->subDays(random_int(1, 20)),
            ]);
        }
    }
}
