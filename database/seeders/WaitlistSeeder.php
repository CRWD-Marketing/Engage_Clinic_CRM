<?php

namespace Database\Seeders;

use App\Models\Waitlist;
use Illuminate\Database\Seeder;

class WaitlistSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            ['Yousef Al Kaabi', 5, 'ABA', 20, 3],
            ['Amna Al Hosani', 4, 'ABA', 25, 5],
            ['Marwan Al Zahmi', 6, 'Speech', 4, 2],
            ['Reem Al Mansoori', 3, 'ABA', 15, 6],
            ['Ahmed Al Dhaheri', 7, 'OT', 6, 4],
            ['Latifa Al Neyadi', 4, 'ABA', 20, 8],
            ['Hamad Al Junaibi', 5, 'Combined ABA + Speech', 22, 2],
            ['Alyazia Al Shehhi', 4, 'ABA', 18, 3],
            ['Sultan Al Marzooqi', 6, 'Speech', 3, 5],
        ];

        foreach ($entries as [$childName, $age, $programme, $hours, $weeksAgo]) {
            Waitlist::create([
                'lead_id' => null,
                'child_name' => $childName,
                'child_age' => $age,
                'programme' => $programme,
                'hours_per_week' => $hours,
                'status' => 'waiting',
                'joined_at' => now()->subWeeks($weeksAgo),
                'notes' => 'Awaiting therapist availability.',
            ]);
        }
    }
}
