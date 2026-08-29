<?php

namespace Database\Seeders;

use App\Models\WhatsappContact;
use App\Models\WhatsappMessage;
use Illuminate\Database\Seeder;

class WhatsappMessageSeeder extends Seeder
{
    public function run(): void
    {
        $threads = [
            '971501234501' => [
                ['in', 'text', 'Hi, I saw your page online. Do you offer ABA therapy for a 5 year old?'],
                ['out', 'text', 'Hello! Yes we do. Could you share a bit about your child so we can guide you to the right programme?'],
                ['in', 'text', 'My son Khalifa is 5, recently diagnosed with autism. We have Daman Enhanced insurance.'],
                ['out', 'text', 'Great, Daman Enhanced covers our ABA programme. We can book an assessment this week if that works for you.'],
                ['in', 'text', 'That would be perfect, thank you!'],
            ],
            '971501234502' => [
                ['in', 'text', 'Salam, what are your working hours?'],
                ['out', 'text', 'Wa alaikum salam! We are open Sunday to Thursday, 8am to 6pm.'],
                ['in', 'like', null],
            ],
            '17920001' => [
                ['in', 'text', 'Hii do you have speech therapy available?'],
                ['out', 'text', 'Hi! Yes, we do. How old is your child and what would you like to work on?'],
                ['in', 'text', 'She is 3, mostly non-verbal right now.'],
                ['out', 'text', 'Understood, early intervention would be a great fit. Would you like to book a free consultation?'],
            ],
            '17920002' => [
                ['in', 'text', 'Hello, I replied to your story about the new sensory room'],
                ['out', 'text', 'Hi! Yes, we just opened it. Would you like to bring your child for a visit?'],
                ['in', 'text', 'Yes please, when is a good time?'],
            ],
            '37920003' => [
                ['in', 'text', 'Hi, do you accept self-pay families?'],
                ['out', 'text', 'Hello Grace, yes we do! Happy to send over our rate card.'],
                ['in', 'text', 'Yes please, thank you.'],
            ],
            '37920004' => [
                ['in', 'text', 'What is the earliest availability for an assessment?'],
                ['out', 'text', 'We currently have openings next week Tuesday and Thursday mornings.'],
            ],
        ];

        foreach ($threads as $waId => $messages) {
            $contact = WhatsappContact::where('wa_id', $waId)->first();

            if (! $contact) {
                continue;
            }

            $sentAt = now()->subDays(2)->setTime(9, 0);
            $lastBody = null;
            $lastType = 'text';
            $unread = 0;

            foreach ($messages as $i => [$direction, $type, $body]) {
                $sentAt = $sentAt->copy()->addMinutes(random_int(3, 40));

                WhatsappMessage::create([
                    'whatsapp_contact_id' => $contact->id,
                    'wa_message_id' => 'seed_'.$waId.'_'.$i,
                    'direction' => $direction === 'in' ? 'inbound' : 'outbound',
                    'type' => $type,
                    'sticker_id' => $type === 'like' ? '369239263222822' : null,
                    'media_url' => null,
                    'body' => $type === 'like' ? '👍' : $body,
                    'status' => $direction === 'in' ? 'received' : 'read',
                    'sent_at' => $sentAt,
                ]);

                $lastBody = $type === 'like' ? '👍' : $body;
                $lastType = $type;
                $unread = $direction === 'in' ? $unread + 1 : 0;
            }

            $contact->update([
                'last_message_preview' => $lastBody ?? WhatsappMessage::fallbackLabel($lastType),
                'last_message_at' => $sentAt,
                // Leave the most recently-seeded inbound run as unread so the
                // inbox's unread badges have something to show.
                'unread_count' => $unread,
            ]);
        }
    }
}
