<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ServiceSeeder::class,

            LeadSeeder::class,
            LeadActivitySeeder::class,
            PatientSeeder::class,
            PatientTherapistSeeder::class,
            PatientNoteSeeder::class,
            PatientGoalSeeder::class,
            CalendarSessionSeeder::class,
            InvoiceSeeder::class,
            WaitlistSeeder::class,
            ContactSeeder::class,
            WhatsappContactSeeder::class,
            WhatsappMessageSeeder::class,
            KnowledgeBaseSeeder::class,
        ]);
    }
}