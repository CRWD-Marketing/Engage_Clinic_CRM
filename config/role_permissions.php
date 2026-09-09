<?php

// config/role_permissions.php
// Maps the `role` enum on users to sidebar/feature keys.
// Keys must match the enum values exactly (uppercase, as defined in the users migration).
//
// This is the single source of truth for module-level access: it drives both
// the sidebar (User::canAccessFeature(), used in admin-sidebar.blade.php) and
// the actual route gate (the 'feature' middleware, applied per route file).
// A role missing a key here cannot reach that module's routes at all, not
// just hidden from the nav.
//
// Finer-grained restrictions within an allowed module (Coordinator's "limited"
// leads/patients access, a therapist's assigned-only patient list, Other
// Staff's invoice-only billing) are enforced in the controllers themselves -
// see LeadController, PatientController, TherapistController.

return [

    'FULL_ADMIN' => [
        // Everything in the CRM, including user/role management and system-level access.
        'dashboard', 'leads', 'contacts', 'whatsapp', 'voice', 'patients', 'calendar', 'therapists', 'users', 'billing', 'reports', 'knowledge_base', 'careers', 'settings', 'packages',
    ],

    'HR_STAFF' => [
        // Employee/staff profiles, staff records, internal scheduling, user/staff
        // information. No clinical records, no billing.
        'dashboard', 'users', 'calendar', 'careers',
    ],

    'SALES_STAFF' => [
        // Leads, lead details, lead conversion, sales pipeline/deals, client intake
        // forms, WhatsApp/client communication. No patient clinical records.
        'dashboard', 'leads', 'contacts', 'whatsapp', 'voice',
    ],

    'COORDINATOR' => [
        // Calendar, appointments, client communication, intake coordination, plus
        // limited (view-mostly, non-clinical) lead/patient information.
        'dashboard', 'calendar', 'whatsapp', 'voice', 'leads', 'contacts', 'patients',
    ],

    'FINANCE_STAFF' => [
        // Billing, invoices, payments, financial reports. No clinical records.
        'dashboard', 'billing', 'reports',
    ],

    'CLINICAL_SUPERVISOR' => [
        // Patients, full clinical records, treatment plans, therapists, session
        // notes, clinical reports, therapist oversight.
        'dashboard', 'patients', 'calendar', 'therapists', 'reports',
    ],

    'THERAPIST' => [
        // Assigned patients only - their clinical records, session notes, treatment
        // plans, and their own schedule/calendar. No user management, no billing.
        // Scoped down to "assigned only" in PatientController/CalendarController,
        // not just gated here.
        'dashboard', 'patients', 'calendar',
    ],

    'OTHER_STAFF' => [
        // Only the features specifically approved for this person. Default here
        // covers the seeded example (Cindy & Ryan: "Invoice and Quotation Only")
        // - billing access itself is further scoped down in BillingController.
        'dashboard', 'billing',
    ],

];
