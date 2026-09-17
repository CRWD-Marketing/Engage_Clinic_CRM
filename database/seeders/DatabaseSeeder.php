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
            RoleTemplateSeeder::class,
            ServiceSeeder::class,
            LocationSeeder::class,
            InsuranceSeeder::class,
            PackageSeeder::class,

            LeadSeeder::class,
            LeadActivitySeeder::class,
            PatientSeeder::class,
            PatientTherapistSeeder::class,
            PatientNoteSeeder::class,
            PatientGoalSeeder::class,
            CalendarSessionSeeder::class,
            CalendarExtrasSeeder::class,
            StaffLeaveSeeder::class,
            InvoiceSeeder::class,
            WaitlistSeeder::class,
            ContactSeeder::class,
            WhatsappContactSeeder::class,
            WhatsappMessageSeeder::class,
            KnowledgeBaseSeeder::class,
        ]);
    }
}