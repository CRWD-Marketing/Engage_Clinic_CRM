<?php

return [
    // UAE VAT on standard-rated supplies.
    'vat_rate' => 5,

    // Invoice due date = issue date + this many days.
    'due_days' => 30,

    // Hourly rate used when a patient has no package with a rate on file.
    'default_rate' => 300,

    // Cancellation notice policy - drives the attendance charge on the session
    // ledger. Kept in one place so the clinic can change its terms without
    // touching the billing code.
    'cancel_policy' => [
        'notice_hours' => 24, // at or beyond this many hours' notice: free
        'late_pct' => 50,     // inside the notice window: this % of the session
        'no_show_pct' => 100, // did not attend, no cancellation: this %
    ],

    // Receivables aging buckets (days past due, inclusive upper bound).
    'aging_buckets' => [
        ['key' => 'current', 'label' => 'Current — not yet due', 'max' => 0],
        ['key' => 'd1_30', 'label' => '1 – 30 days overdue', 'max' => 30],
        ['key' => 'd31_60', 'label' => '31 – 60 days', 'max' => 60],
        ['key' => 'd61_90', 'label' => '61 – 90 days', 'max' => 90],
        ['key' => 'd90', 'label' => '90+ days', 'max' => null],
    ],

    // What the ledger calls each session type on an invoice line.
    'service_labels' => [
        'ABA' => 'ABA therapy — 1:1 session',
        'Speech' => 'Speech therapy — individual',
        'OT' => 'Occupational therapy — individual',
        'Assessment' => 'Assessment session',
        'Parent training' => 'Parent training session',
        'Social group' => 'Social skills group',
    ],
];
