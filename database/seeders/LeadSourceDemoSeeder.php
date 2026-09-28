<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\WhatsappContact;
use Illuminate\Database\Seeder;

class LeadSourceDemoSeeder extends Seeder
{
    /**
     * Tops up this week's WhatsApp/Instagram/Facebook chats, website
     * submissions, and Referral/Google leads with an uneven, realistic
     * spread, so the Dashboard's "Lead sources · this week" bar chart has
     * something meaningful to show instead of one or two flat channels.
     * Everything else (pipeline stages, the ~2.5-month lead history) is
     * already covered by LeadSeeder/ContactSeeder/WhatsappContactSeeder -
     * this only adds volume dated within the current week.
     */
    public function run(): void
    {
        $this->seedChats();
        $this->seedWebsiteContacts();
        $this->seedOtherLeads();
    }

    /**
     * A day/hour sometime between the start of this week and now, so re-runs
     * land in "this week" no matter which day of the week it's actually run.
     */
    private function thisWeekTimestamp(): \Illuminate\Support\Carbon
    {
        $weekStart = now()->startOfWeek();
        $maxOffsetMinutes = max(1, now()->diffInMinutes($weekStart));

        return $weekStart->copy()->addMinutes(random_int(0, $maxOffsetMinutes));
    }

    private function seedChats(): void
    {
        $chats = [
            ['wa_id' => '971502223301', 'channel' => 'whatsapp', 'name' => 'Latifa Al Neyadi'],
            ['wa_id' => '971502223302', 'channel' => 'whatsapp', 'name' => 'Reem Al Mansoori'],
            ['wa_id' => '971502223303', 'channel' => 'whatsapp', 'name' => 'Amna Al Hosani'],
            ['wa_id' => '971502223304', 'channel' => 'whatsapp', 'name' => 'Khalid Al Ameri'],
            ['wa_id' => '971502223305', 'channel' => 'whatsapp', 'name' => 'Salama Al Kaabi'],
            ['wa_id' => '27920011', 'channel' => 'instagram', 'name' => 'mariam.mom_ae'],
            ['wa_id' => '27920012', 'channel' => 'instagram', 'name' => 'rashid.dad'],
            ['wa_id' => '47920021', 'channel' => 'facebook', 'name' => 'Hessa Al Marzouqi'],
        ];

        foreach ($chats as $data) {
            $contact = WhatsappContact::updateOrCreate(
                ['wa_id' => $data['wa_id']],
                [
                    'channel' => $data['channel'],
                    'name' => $data['name'],
                    'unread_count' => 0,
                ]
            );

            // updateOrCreate() stamps created_at with the moment it runs -
            // backdate it into this week's spread same as LeadSeeder does.
            $contact->created_at = $this->thisWeekTimestamp();
            $contact->save();
        }
    }

    private function seedWebsiteContacts(): void
    {
        $entries = [
            ['Noura Al Dhaheri', 4, 'noura.aldhaheri@example.com', '+971501230011', 'ABA therapy', 'Would like to book a free consultation for my son.'],
            ['Tariq Al Blooshi', 6, 'tariq.b@example.com', '+971501230022', 'Speech therapy', 'Looking for speech therapy availability this month.'],
            ['Ayesha Farooqi', 5, 'ayesha.f@example.com', '+971501230033', 'Occupational therapy', 'Referred by our paediatrician for OT screening.'],
        ];

        foreach ($entries as [$name, $age, $email, $phone, $interest, $message]) {
            $contact = Contact::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'child_age' => (string) $age,
                    'phone' => $phone,
                    'interested_in' => $interest,
                    'message' => $message,
                    'status' => Contact::STATUS_NEW,
                ]
            );

            $contact->created_at = $this->thisWeekTimestamp();
            $contact->save();
        }
    }

    private function seedOtherLeads(): void
    {
        // [child_name, parent_guardian_name, age, source]
        $leads = [
            ['Zainab Al Suwaidi', 'Rashed Al Suwaidi', 4, 'Referral'],
            ['Yousef Al Mheiri', 'Ahmed Al Mheiri', 5, 'Referral'],
            ['Fatima Al Nuaimi', 'Saeed Al Nuaimi', 6, 'Referral'],
            ['Hamdan Al Falasi', 'Rashid Al Falasi', 4, 'Google'],
            ['Alya Al Zaabi', 'Khalfan Al Zaabi', 3, 'Google'],
        ];

        foreach ($leads as [$childName, $parentName, $age, $source]) {
            $lead = Lead::updateOrCreate(
                ['child_name' => $childName],
                [
                    'child_age' => (string) $age,
                    'parent_guardian_name' => $parentName,
                    'phone' => '+9715'.random_int(0, 9).random_int(1000000, 9999999),
                    'source' => $source,
                    'interested_in' => 'ABA therapy',
                    'status' => Lead::STATUS_NEW,
                    'notes' => 'Parent reached out asking about availability and pricing.',
                ]
            );

            $lead->created_at = $this->thisWeekTimestamp();
            $lead->save();
        }
    }
}
