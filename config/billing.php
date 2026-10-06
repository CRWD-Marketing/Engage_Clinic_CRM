<?php

return [
    // UAE VAT on standard-rated supplies.
    'vat_rate' => 5,

    // Invoice due date = issue date + this many days.
    'due_days' => 30,

    // Payment confirmation is the trigger for session scheduling. "warn" lets
    // a booking through with a warning when the client has no cleared
    // quotation; "block" refuses it; "off" skips the check. Clients who were
    // already being seen before quotations existed have none on file, so
    // switch to "block" only once theirs are entered.
    'scheduling_gate' => env('BILLING_SCHEDULING_GATE', 'warn'),

    // Card POS fee: this % of the service value, plus VAT on the fee.
    'pos_fee_pct' => 2,

    // How long each record is kept (years), per the Finance & Billing SOP.
    'retention_years' => [
        'quotations' => 2,
        'receipts' => 5,
        'rota' => 1,
        'invoices' => 5,
    ],

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
