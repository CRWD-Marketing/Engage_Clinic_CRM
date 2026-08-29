<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\WhatsappContact;
use Illuminate\Database\Seeder;

class WhatsappContactSeeder extends Seeder
{
    /**
     * Seeds a handful of WhatsApp/Instagram/Facebook conversations. Message
     * content and last_message_preview/last_message_at are filled in by
     * WhatsappMessageSeeder, which runs right after this one.
     */
    public function run(): void
    {
        $khalifaLead = Lead::where('child_name', 'Khalifa Al Mansoori')->first();

        $contacts = [
            [
                'wa_id' => '971501234501',
                'channel' => 'whatsapp',
                'name' => 'Mohammed Al Mansoori',
                'lead_id' => $khalifaLead?->id,
            ],
            [
                'wa_id' => '971501234502',
                'channel' => 'whatsapp',
                'name' => 'Youssef Hassan',
            ],
            [
                'wa_id' => '17920001',
                'channel' => 'instagram',
                'name' => 'aisha.parent',
                // Manual family-details override, matching what the Family
                // details panel in the WhatsApp inbox now lets you edit.
                'child_name' => 'Aisha Rahman',
                'interested_in' => 'Early intervention',
                'insurance' => 'Thiqa',
            ],
            [
                'wa_id' => '17920002',
                'channel' => 'instagram',
                'name' => 'noora.mom',
            ],
            [
                'wa_id' => '37920003',
                'channel' => 'facebook',
                'name' => 'Grace Okoro',
            ],
            [
                'wa_id' => '37920004',
                'channel' => 'facebook',
                'name' => 'Faisal Al Nuaimi',
            ],
        ];

        foreach ($contacts as $data) {
            WhatsappContact::updateOrCreate(
                ['wa_id' => $data['wa_id']],
                [
                    'channel' => $data['channel'],
                    'name' => $data['name'],
                    'avatar_url' => null,
                    'child_name' => $data['child_name'] ?? null,
                    'interested_in' => $data['interested_in'] ?? null,
                    'insurance' => $data['insurance'] ?? null,
                    'lead_id' => $data['lead_id'] ?? null,
                    'unread_count' => 0,
                ]
            );
        }
    }
}
