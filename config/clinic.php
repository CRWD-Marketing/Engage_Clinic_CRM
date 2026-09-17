<?php

// The clinic's legal identity - printed on every tax invoice and statement,
// and used as the payable-to block. Override per environment via .env.
return [
    'name' => env('CLINIC_NAME', 'Engage Behavior Clinic'),
    'legal_name' => env('CLINIC_LEGAL_NAME', 'Engage Behavioral Learning Abilitation Center Sole'),
    'address' => env('CLINIC_ADDRESS', 'Al Bateen Tower, Bainunah St, Abu Dhabi, UAE'),
    'phone' => env('CLINIC_PHONE', '+971 2 445 8890'),
    'billing_email' => env('CLINIC_BILLING_EMAIL', 'billing@engageclinic.ae'),
    'trn' => env('CLINIC_TRN', '100447288910003'),
    'bank' => env('CLINIC_BANK', 'Abu Dhabi Commercial Bank'),
    'account_name' => env('CLINIC_ACCOUNT_NAME', 'Engage Behavioral Learning Abilitation Center Sole'),
    'iban' => env('CLINIC_IBAN', 'AE900030013151276820001'),
    'account_number' => env('CLINIC_ACCOUNT_NUMBER', '13151276820001'),
    'license' => env('CLINIC_LICENSE', 'Licensed by the Department of Health — Abu Dhabi'),
];
