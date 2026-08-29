<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadActivitySeeder extends Seeder
{
    /**
     * Give every lead a short activity trail (an assignment entry plus a
     * couple of follow-up notes) so the lead detail panel isn't empty.
     */
    public function run(): void
    {
        $staff = User::whereIn('role', ['SALES_STAFF', 'COORDINATOR', 'FULL_ADMIN'])->get();

        $noteTemplates = [
            'Called the family, they are interested but need to check insurance coverage first.',
            'Sent WhatsApp follow-up with the assessment booking link.',
            'Parent confirmed availability on weekday mornings.',
            'Discussed programme options and estimated monthly cost.',
            'Family requested a call back next week - added a follow-up reminder.',
        ];

        Lead::all()->each(function (Lead $lead) use ($staff, $noteTemplates) {
            if ($staff->isEmpty()) {
                return;
            }

            $author = $staff->random();

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $author->id,
                'type' => LeadActivity::TYPE_ASSIGNMENT,
                'body' => "Assigned to {$author->first_name} {$author->last_name}.",
                'created_at' => $lead->created_at,
                'updated_at' => $lead->created_at,
            ]);

            $noteCount = $lead->status === Lead::STATUS_NEW ? 0 : random_int(1, 2);

            for ($i = 0; $i < $noteCount; $i++) {
                $createdAt = $lead->created_at->copy()->addDays(random_int(1, 5) + $i);

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $staff->random()->id,
                    'type' => LeadActivity::TYPE_NOTE,
                    'body' => $noteTemplates[array_rand($noteTemplates)],
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        });
    }
}
