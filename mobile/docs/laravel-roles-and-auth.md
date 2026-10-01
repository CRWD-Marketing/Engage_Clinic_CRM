# Laravel research: auth, roles, dashboards, navigation

Source: `Engage-Clinic/` (Laravel 12), read on 2026-09-30. Paths are relative to `Engage-Clinic/`.
Reference only. Do not edit the Laravel app without explicit OK.

## Auth

- Session auth only (`config/auth.php` `web` guard). No `routes/api.php`, no Sanctum/Passport.
- `POST /login` (`app/Http/Controllers/Auth/LoginController.php`):
  - Fields: `email` (required, email), `password`, `remember` (boolean).
  - Throttle: 5 failures per email+IP → 60s lockout. Message: "Too many login attempts. Please try again in {n} seconds."
  - Failure message: "Invalid email or password."
  - Success → redirect to `dashboard` for every role.
  - **No status check**: `is_active`, `invited_at`, `email_verified_at` are ignored.
- Login view copy (`resources/views/auth/login.blade.php`):
  - Heading "Sign in", subtitle "Enter your credentials to reach the dashboard."
  - Field label "Work email", placeholder `you@engageclinic.ae`.
  - Password with show/hide, "Remember me", "Forgot password?", button "Sign in".
  - Footer notice: "Authorized access only… All activity is logged."
- Forgot password: always replies "If an account exists for that email, a password reset link has been sent." Reset needs `token`, `email`, `password` + confirmation (min 8), then shows "Your password has been reset. You can now sign in."
- No self-registration. Accounts are created by staff (`CreateAccount`, `RoleController`).
- Logout: `POST /logout`.

## User (`users`)

| Field | Type / notes |
|---|---|
| `id` | bigint |
| `public_id` | uuid, route key |
| `first_name`, `middle_name?`, `last_name` | **no `name` column**; `full_name` accessor joins first + middle + last |
| `job_title?`, `email`, `phone_number?` | |
| `timezone?` | default `'Asia/Dubai (GST)'` |
| `message_signature?` | |
| `email_verified_at?` | |
| `department` | enum: EXECUTIVE, HUMAN_RESOURCES, SALES, COORDINATOR, FINANCE, CLINICAL, CONTENT_MANAGEMENT, OTHER |
| `manager_id?` | FK users |
| `role` | enum: FULL_ADMIN, HR_STAFF, SALES_STAFF, COORDINATOR, FINANCE_STAFF, CLINICAL_SUPERVISOR, THERAPIST, OTHER_STAFF |
| `role_template_id?` | |
| `modules?` | json array of module keys |
| `module_levels?` | json object `{module: level}` |
| `actions?` | json array of action keys |
| `start_date` | date |
| `notes?` | |
| `is_active` | bool, default true |
| `invited_at?`, `last_login_at?` | `last_login_at` is never written anywhere |
| `created_at`, `updated_at` | |

## Permission logic (`app/Models/User.php`, `app/Models/RoleTemplate.php`)

- **`canAccessFeature(f)`**: if the user has `modules` and `f` is a known module key → `f ∈ modules`. Otherwise fall back to `config/role_permissions.php[role]`.
- **`levelFor(m)`** = `module_levels[m] ?? 'full'`. Levels: `full | own | view | edit`.
  - `view` blocks every non-GET request (`FeatureMiddleware`).
- **`canDo(action)`**: the user's `actions`, else the system template actions for the user's base role.
- **`accessStatus()`**:
  - `!is_active` → `suspended`
  - `invited_at` set and no `last_login_at` → `invited`
  - otherwise `active`
- **`RoleTemplate::MODULES`** (key → label):
  - dashboard → Dashboard, leads → Leads, contacts → Contacts, cms_forms → CMS Forms
  - whatsapp → WhatsApp, knowledge_base → AI Employee, patients → Patients, calendar → Calendar
  - therapists → Therapists, careers → Job Applications, users → User Management, roles_access → Roles & access
  - billing → Billing, reports → Reports, packages → Packages, settings → Settings
- **`RoleTemplate::ACTIONS`**:
  - `assign_lead_owner`, `reassign_lead_owner`, `terminate_lead`, `add_lead_notes`, `convert_to_client`
  - `book_modify_session`, `assign_change_schedule`, `assign_multiple_therapists`
  - `view_terminated_history`, `create_invoice`, `manage_users_roles`

### System templates (the effective defaults per role)

| Role | Modules | Actions / levels |
|---|---|---|
| FULL_ADMIN | all 16 | all 11 |
| HR_STAFF | dashboard, calendar, therapists, careers, users, roles_access, reports | assign_change_schedule, manage_users_roles |
| SALES_STAFF | dashboard, leads, contacts, cms_forms, whatsapp, reports | assign_lead_owner, terminate_lead, add_lead_notes, convert_to_client, view_terminated_history |
| COORDINATOR | dashboard, leads, contacts, whatsapp, patients, calendar | assign_lead_owner, add_lead_notes, convert_to_client, book_modify_session, assign_change_schedule, assign_multiple_therapists |
| FINANCE_STAFF | dashboard, calendar, billing, reports, packages | view_terminated_history, create_invoice |
| CLINICAL_SUPERVISOR | dashboard, patients, calendar, therapists, reports | add_lead_notes, book_modify_session, assign_change_schedule, assign_multiple_therapists, view_terminated_history |
| THERAPIST | dashboard, patients, calendar | none; `module_levels = {patients: own, calendar: own}` |
| OTHER_STAFF | dashboard, billing | create_invoice |

`config/role_permissions.php` is the legacy fallback and differs slightly (e.g. FINANCE there has no calendar or packages).

### Role rules enforced in controllers

- **THERAPIST**
  - Patients limited to those with any calendar session with `therapist_id = me`.
  - Calendar feed forced to own sessions.
  - Search results scoped the same way.
- **COORDINATOR**
  - Cannot delete leads, forms or contacts.
  - Cannot add clinical notes; clinical fields are dropped on patient update.
- **COORDINATOR and THERAPIST** cannot create patients.
- **Calendar `canManage()`** = `canDo('assign_change_schedule') || role ∈ {CLINICAL_SUPERVISOR, FULL_ADMIN}`.
  - The hard-coded capability strip text says Coordinator is "locked", but the real check lets Coordinator manage. **Follow the check, not the text.**

## Dashboards (`DashboardController`, `resources/views/dashboard/*`)

`GET /dashboard` renders a different view per role. The shared header shows a greeting with `full_name`, the date and the location.

### THERAPIST (primary mobile persona)

- **Data**: `scheduleMetrics(me)` + `therapistMetrics`.
- **Stat tiles**:
  - My sessions today ("n rooms")
  - My attendance · 30d: completed / (completed + no_show) over 30 days, ± delta
  - My active patients ("assigned to you"): distinct `patient_id` from my sessions
  - My notes pending ("awaiting sign-off" / "All caught up"): my unsigned `PatientNote`s
- **My schedule today**: time, child, room, activity chip, status chip; "Open calendar".
  - Status chip values: Completed, Cancelled, No-show, In session, Awaiting update, Upcoming.
- **My notes awaiting sign-off** (3): child, created date, "Pending Nh", or "Overdue Nd" once ≥ 48h old.
- **My treatment plans due for review**: patients with `treatment_plan_review_due_at` within 7 days, labelled "due" or "overdue".
- Header button "My Patients →". No leads, WhatsApp or billing content.

### COORDINATOR

- **Stat tiles**:
  - New leads · week
  - Intake calls pending (Leads with status `new`; overdue if `follow_up_due_at` is past or > 2 days old)
  - No-shows · week (n need follow-up: `follow_up_completed_at` null)
  - Waitlist (openings this week ≈ rooms × 8 × 5 − booked)
  - Sessions today
  - Attendance · 30d
- **Panels**: today's schedule (clinic-wide), intake pipeline, WhatsApp inbox (3), no-shows needing follow-up.

### FULL_ADMIN

- **Stat tiles**: New leads · 7d, Sessions today, Attendance · 30d, Revenue · MTD (AED), Waitlist, Claims pending.
- **Panels**: today's schedule, lead sources (7d), WhatsApp inbox, authorizations expiring (≤ 45 days), waitlist next up.

### Other roles

- **CLINICAL_SUPERVISOR**: notes awaiting sign-off (all therapists), flagged notes, treatment plans due, caseload.
- **FINANCE**: revenue MTD, collected, claims pending, aging.
- **HR**: staff counts, staff by role.
- **SALES**: my leads pipeline, sources, WhatsApp.
- **OTHER_STAFF**: draft quotations, awaiting payment, recent invoices.

## Navigation (`resources/views/layouts/admin-sidebar.blade.php`)

Each item is shown only when `canAccessFeature(key)` is true:

| Label | Feature key | Notes |
|---|---|---|
| Dashboard | `dashboard` | |
| Leads | `leads` | badge = new leads |
| Contacts | `contacts` | badge = new contacts |
| CMS Forms | `cms_forms` | |
| WhatsApp | `whatsapp` | badge = unread |
| AI Employee | `knowledge_base` | |
| Patients | `patients` | |
| Calendar | `calendar` | label is "**My calendar**" when `levelFor('calendar') === 'own'` |
| Therapists | `therapists` | |
| Roles & access | `roles_access` | |
| Billing | `billing` | |
| Reports | `reports` | |
| Packages | `packages` | |
| Settings | `settings` | |

Profile menu: avatar initial, `full_name`, role label, "My Profile", "Logout".

### Topbar (`routes/topbar.php`, prefix `/admin`)

- **`GET /admin/search?q=`** (≥ 2 chars) → `{results:[{type,icon,title,subtitle,url}]}`.
  - Types: Lead, Patient, Contact, WhatsApp, Therapist, Session. Max 20 results, 6 per type.
- **`GET /admin/notifications`** → `{count, items:[{id?,icon,title,subtitle,url,read,created_at}]}`, max 15.
  - `created_at` is diffForHumans text.
  - Derived feeds (new leads, new contacts, unread WhatsApp) have no `id` and `read: true`.
  - Database notifications (`SessionAssigned`, `LeadAssigned`) have `id` and a real read state.
- **`POST /admin/notifications/{id}/read`** → `{ok:true}`.
- **`GET /admin/activity-feed`** → `{items:[{icon,title,actor,url,created_at}]}`.
- **SessionAssigned**: title "You've been scheduled — {name}" or "Session reassigned to you — {name}", subtitle "Tue 30 Sep · 09:30".

## Seeded users (`database/seeders/UserSeeder.php`)

Password is `password` for all users except admin@gmail.com (`admin123`). All are active.

| Name | Email | Role | Job title |
|---|---|---|---|
| Hong Tan | admin@gmail.com | FULL_ADMIN | (CEO) |
| Cherry Ann Amoroso | cherry@engagebehavior.com | FULL_ADMIN | (General Manager) |
| Indira Banarjee | indira@engagebehavior.com | CLINICAL_SUPERVISOR | BCBA Supervisor |
| HR Staff | hr@engagebehavior.com | HR_STAFF | |
| Coordinator Staff | coordinator@engagebehavior.com | COORDINATOR | |
| Cindy Marie Gealan | info@engagebehavior.com | SALES_STAFF | |
| Ryan Flores | ryan@engagebehavior.com | SALES_STAFF | |
| Kavitha Venkatesan | kavitha@engagebehavior.com | FINANCE_STAFF | |
| Alessandra Yukimi | alessandra@engagebehavior.com | THERAPIST | RBT |
| Claudine Tadeo | claudine@engagebehavior.com | THERAPIST | RBT |
| Sheryl Estrella | sheryl@engagebehavior.com | THERAPIST | Speech-Language Pathologist |
| May Ann Momo | may@engagebehavior.com | THERAPIST | Occupational Therapist |
| Fatima Vinoythimy | fatima@engagebehavior.com | THERAPIST | RBT |
| Lubna Sherina | lubna@engagebehavior.com | THERAPIST | Speech-Language Pathologist |
| Amalu Jacob | amalu@engagebehavior.com | THERAPIST | Occupational Therapist |
| Cindy Marie Gealan | cindy.billing@engagebehavior.com | OTHER_STAFF | Invoice and Quotation Only |
| Ryan Flores | ryan.billing@engagebehavior.com | OTHER_STAFF | Invoice and Quotation Only |
