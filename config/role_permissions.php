<?php

// config/role_permissions.php
// Maps the `role` enum on users to sidebar/feature keys.
// Keys must match the enum values exactly (uppercase, as defined in the users migration).

return [

    'FULL_ADMIN' => [
        'dashboard', 'leads', 'whatsapp', 'patients', 'calendar', 'therapists', 'users', 'billing', 'reports',
    ],

    'HR_STAFF' => [
        // Staff/HR profiles, internal scheduling — no billing or clinical data
        'dashboard', 'therapists',
    ],

    'SALES_STAFF' => [
        // Leads, deals, pipeline, client intake — no clinical records
        'dashboard', 'leads', 'whatsapp',
    ],

    'COORDINATOR' => [
        // Appointment calendars, client communication, intake coordination
        'dashboard', 'calendar', 'whatsapp', 'patients',
    ],

    'FINANCE_STAFF' => [
        // Invoicing, payments, financial reporting — no clinical records
        'dashboard', 'billing', 'reports',
    ],

    'CLINICAL_SUPERVISOR' => [
        // Full clinical records, treatment plans, oversight of therapist accounts/notes
        'dashboard', 'patients', 'calendar', 'therapists', 'reports',
    ],

    'THERAPIST' => [
        // Session notes/records — scope to own assigned clients in the controller query, not just nav
        'dashboard', 'patients', 'calendar',
    ],

    'OTHER_STAFF' => [
        // Basic/View-Only per Notes column. Cindy & Ryan's "Invoice and Quotation Only" -> billing.
        'dashboard', 'billing',
    ],

];