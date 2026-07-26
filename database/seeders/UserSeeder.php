<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // CEO
        $hong = User::create([
            'first_name' => 'Hong',
            'middle_name' => null,
            'last_name' => 'Tan',
            'email' => 'admin@gmail.com',
            'phone_number' => '0588556108',
            'password' => Hash::make('admin123'),

            'department' => 'EXECUTIVE',
            'role' => 'FULL_ADMIN',
            'manager_id' => null,

            'start_date' => '2026-07-24',
            'notes' => 'CEO',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // General Manager
        $cherry = User::create([
            'first_name' => 'Cherry Ann',
            'middle_name' => null,
            'last_name' => 'Amoroso',
            'email' => 'cherry@engagebehavior.com',
            'phone_number' => '0509766801',
            'password' => Hash::make('password'),

            'department' => 'EXECUTIVE',
            'role' => 'FULL_ADMIN',
            'manager_id' => $hong->id,

            'start_date' => '2026-07-24',
            'notes' => 'General Manager',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Clinical Supervisor
        $indira = User::create([
            'first_name' => 'Indira',
            'middle_name' => null,
            'last_name' => 'Banarjee',
            'email' => 'indira@engagebehavior.com',
            'phone_number' => '0558092636',
            'password' => Hash::make('password'),

            'department' => 'CLINICAL',
            'role' => 'CLINICAL_SUPERVISOR',
            'manager_id' => $cherry->id,

            'start_date' => '2026-07-02',
            'notes' => null,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $users = [
            // HR
            [
                'first_name' => 'HR',
                'last_name' => 'Staff',
                'email' => 'hr@engagebehavior.com',
                'phone' => '0500000001',
                'department' => 'HUMAN_RESOURCES',
                'role' => 'HR_STAFF',
                'manager_id' => $hong->id,
            ],

            // Sales
            [
                'first_name' => 'Cindy Marie',
                'last_name' => 'Gealan',
                'email' => 'info@engagebehavior.com',
                'phone' => '0508846801',
                'department' => 'SALES',
                'role' => 'SALES_STAFF',
                'manager_id' => $cherry->id,
            ],
            [
                'first_name' => 'Ryan',
                'last_name' => 'Flores',
                'email' => 'ryan@engagebehavior.com',
                'phone' => '0543116801',
                'department' => 'SALES',
                'role' => 'SALES_STAFF',
                'manager_id' => $cherry->id,
            ],

            // Finance
            [
                'first_name' => 'Kavitha',
                'last_name' => 'Venkatesan',
                'email' => 'kavitha@engagebehavior.com',
                'phone' => '0501815654',
                'department' => 'FINANCE',
                'role' => 'FINANCE_STAFF',
                'manager_id' => $cherry->id,
            ],

            // Therapists
            [
                'first_name' => 'Alessandra',
                'last_name' => 'Yukimi',
                'email' => 'alessandra@engagebehavior.com',
                'phone' => '0501019522',
                'department' => 'CLINICAL',
                'role' => 'THERAPIST',
                'manager_id' => $indira->id,
            ],
            [
                'first_name' => 'Claudine',
                'last_name' => 'Tadeo',
                'email' => 'claudine@engagebehavior.com',
                'phone' => '0523757097',
                'department' => 'CLINICAL',
                'role' => 'THERAPIST',
                'manager_id' => $indira->id,
            ],
            [
                'first_name' => 'Sheryl',
                'last_name' => 'Estrella',
                'email' => 'sheryl@engagebehavior.com',
                'phone' => '0564081884',
                'department' => 'CLINICAL',
                'role' => 'THERAPIST',
                'manager_id' => $indira->id,
            ],
            [
                'first_name' => 'May Ann',
                'last_name' => 'Momo',
                'email' => 'may@engagebehavior.com',
                'phone' => '0543337210',
                'department' => 'CLINICAL',
                'role' => 'THERAPIST',
                'manager_id' => $indira->id,
            ],
            [
                'first_name' => 'Fatima',
                'last_name' => 'Vinoythimy',
                'email' => 'fatima@engagebehavior.com',
                'phone' => '0525977634',
                'department' => 'CLINICAL',
                'role' => 'THERAPIST',
                'manager_id' => $indira->id,
            ],
            [
                'first_name' => 'Lubna',
                'last_name' => 'Sherina',
                'email' => 'lubna@engagebehavior.com',
                'phone' => '0585836797',
                'department' => 'CLINICAL',
                'role' => 'THERAPIST',
                'manager_id' => $indira->id,
            ],
            [
                'first_name' => 'Amalu',
                'last_name' => 'Jacob',
                'email' => 'amalu@engagebehavior.com',
                'phone' => '0544296275',
                'department' => 'CLINICAL',
                'role' => 'THERAPIST',
                'manager_id' => $indira->id,
            ],

            // Other Staff
            [
                'first_name' => 'Cindy Marie',
                'last_name' => 'Gealan',
                'email' => 'cindy.billing@engagebehavior.com',
                'phone' => '0508846801',
                'department' => 'OTHER',
                'role' => 'OTHER_STAFF',
                'manager_id' => $cherry->id,
                'notes' => 'Invoice and Quotation Only',
            ],
            [
                'first_name' => 'Ryan',
                'last_name' => 'Flores',
                'email' => 'ryan.billing@engagebehavior.com',
                'phone' => '0543116801',
                'department' => 'OTHER',
                'role' => 'OTHER_STAFF',
                'manager_id' => $cherry->id,
                'notes' => 'Invoice and Quotation Only',
            ],
        ];

        foreach ($users as $user) {
            User::create([
                'first_name' => $user['first_name'],
                'middle_name' => null,
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'phone_number' => $user['phone'],
                'password' => Hash::make('password'),

                'department' => $user['department'],
                'role' => $user['role'],
                'manager_id' => $user['manager_id'],

                'start_date' => now()->toDateString(),
                'notes' => $user['notes'] ?? null,

                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        }
    }
}