# Engage Clinic — mobile app

This folder (`mobile/`) is an Expo (React Native, TypeScript, Expo Router) mobile app. It is the mobile client for the Laravel 12 clinic web app in the parent folder (the rest of this repo), a CRM and practice-management system for a therapy clinic in Abu Dhabi. It lives on the `mobile-app` branch of the Laravel repo.

Expo SDK 57 ships breaking changes. Read `AGENTS.md` (in this folder) and check the versioned Expo docs before using any Expo or React Native API.

## Hard rules

1. **The Laravel app (everything in this repo outside `mobile/`) is the live website.** On 2026-10-02 the user gave the go-ahead to add the mobile API to it (`routes/api*`, `app/Http/Controllers/Api/V1/`, `tests/Feature/Api/`). Keep Laravel changes to what the API needs, reuse the web controllers rather than duplicating their logic, and don't change web behaviour without asking.
2. **All data access goes through `src/api/`.**
   - Screens and hooks call the `api` client and never `fetch` directly.
   - Two implementations sit behind the same interface: the mock (`src/api/mock/`, the default) and the real one (`src/api/http/`, used when `EXPO_PUBLIC_API_URL` is set). A change to an endpoint's shape must be made in `types.ts`, the mock, and the Laravel API controller together.
3. **Types mirror the Laravel JSON.** Use the same field names (snake_case) and the same serialization: decimals as strings, ISO datetimes, `"HH:mm:ss"` times. Where a Laravel controller already builds a payload (e.g. calendar `sessionPayload`), mirror that payload.
4. **Permissions follow the real Laravel checks**, not UI text. Port `canAccessFeature` / `levelFor` / `canDo` exactly; see `src/auth/permissions.ts`.
5. Run type-check and lint before calling a task done.

## Commands (run in `mobile/`)

```bash
npm install                 # install
npx expo start              # dev server; scan the QR with Expo Go (same Wi-Fi) or press a / w
npx expo start --tunnel     # if the phone can't reach the PC on the LAN
npx tsc --noEmit            # type-check
npx tsc --noEmit -p tsconfig.check.json   # same, ignoring Expo's generated route types (see note below)
npx expo lint               # lint (ESLint, eslint-config-expo)
npx expo install <pkg>      # add packages (never plain npm install <pkg>)
```

Laravel API tests (run in the repo root; needs a MySQL database named `engage_clinic_testing`):

```bash
DB_CONNECTION=mysql DB_DATABASE=engage_clinic_testing php artisan test tests/Feature/Api
```

The app runs on mock data unless `EXPO_PUBLIC_API_URL` is set (see `.env.example` and `docs/api.md`).

Route-type note: with typed routes on, a running `expo start` appends every newly added file to
`.expo/types/router.d.ts`, which makes `tsc` report false errors like `"/inbox" is not assignable…`
with odd `/../features/...` paths. Restart the dev server (it regenerates the file), or type-check
with `tsconfig.check.json`.

Navigation: each tab's stack sets `unstable_settings = { anchor: 'index' }`; when pushing a detail
screen in *another* tab (dashboard → patient, lead → patient…), pass `{ withAnchor: true }` so Back works.

Mock login: any seeded staff email with password `password`, e.g. therapist `alessandra@engagebehavior.com`. Seeded users are listed in `docs/laravel-roles-and-auth.md`.

## Folder structure (`mobile/`)

```
src/app/              Expo Router routes (every file is a screen; keep non-route code out)
  (auth)/             login, forgot-password (signed-out stack)
  (app)/              signed-in stack: (tabs) holds the tab bar (built from the user's modules);
                      pushed screens (e.g. notes-review) sit beside it with a back button
src/api/types.ts      TS types matching the Laravel models and payloads
src/api/client.ts     ApiClient interface + the active implementation export (`api`)
src/api/mock/         mock implementation + seed data (from the Laravel seeders)
src/api/http/         real HTTP implementation (the Laravel mobile API, /api/v1)
src/auth/             session context, secure token storage, permission helpers
src/components/       shared UI: cards, chips, avatars, stat tiles, screen shell
src/features/         screen-level logic per module (dashboard, calendar, …)
src/theme/            design tokens: colours, fonts, spacing, status/activity colours
src/utils/            dates (Asia/Dubai), formatting
docs/                 research on the Laravel app (linked below)
```

## Domain notes and traps

- **Roles** (8) and their default modules:

  | Role | Modules |
  |---|---|
  | THERAPIST | dashboard, patients (own), calendar (own). **This is the primary mobile user.** |
  | COORDINATOR | dashboard, leads, contacts, whatsapp, patients, calendar |
  | FULL_ADMIN | all modules |
  | CLINICAL_SUPERVISOR | dashboard, patients, calendar, therapists, reports |
  | SALES_STAFF | dashboard, leads, contacts, cms_forms, whatsapp, reports |
  | FINANCE_STAFF | dashboard, calendar, billing, reports, packages |
  | HR_STAFF | dashboard, calendar, therapists, careers, users, roles_access, reports |
  | OTHER_STAFF | dashboard, billing |

  A user's own `modules` / `module_levels` / `actions` override the role defaults.
- **`CalendarSession.patient_id` points to a Lead, not a Patient.** The child's name, age, parent and phone live on the Lead. `Patient` has `lead_id`, diagnosis, programme and dates only.
- **User has `first_name` / `last_name`**, and no `name` column. Build the display name.
- **Sessions are never marked `completed` by hand.** A `scheduled` session flips to `completed` once its end time passes.
- **Only a session's own therapist can write its `therapist_note`**, and the UI offers it only on completed sessions.
- **Timezone is Asia/Dubai (UTC+4, no DST).** Session date and time values are clinic-local.
- Money is AED. VAT is 5%.

## Design tokens

Everything visual comes from `src/theme/`:
- brand navy #16436E and pink #C8355F
- fonts Baloo 2 (headings) and Nunito Sans (body)
- activity-type, calendar-category and status colours

These are copied from the web app. Don't hard-code colours in screens.

Dates are picked with `src/components/DateField.tsx` (a JS month calendar in a modal, same on iOS, Android and web; value is `YYYY-MM-DD` or `''`). Don't add typed date inputs.

## Backend notes

- **The mobile API exists** (added 2026-10-02): Sanctum token sign-in and JSON endpoints under `/api/v1`, one API controller per module extending the web controller. Endpoint list, how it is built and how to run it: `docs/api.md`.
- **API-only behaviour (not on the web):** note sign-off and flagging (`patient-notes/*`, Clinical Supervisor + Full Admin, `canReviewNotes`); suspended users can't sign in; `GET calendar/{id}` enforces "own records only".
- **Funding rows come in two shapes.** The intake form saves `approved_hours` / `approval_reference`; seeded and older rows carry a free-text `cover` ("96 h approved") and `approval_ref`, which is what the web's patient Profile tab reads. Read them through `src/features/leads/fundingRows.ts`.
- **Web gaps to decide on (display-only on the web, so display-only on mobile for now):**
  - No endpoint or UI ever sets `calendar_sessions.follow_up_completed_at`, so "No-shows needing follow-up" can never be cleared.
  - No "log intake call" action; a lead leaves the intake queue only when its status leaves `new`.
  - `whatsapp_contacts.assigned_user_id` is accepted by `POST /whatsapp/{id}/ai-state` but no UI sends or shows it.
  - Family details (`child_name`, `interested_in`, `insurance` on a contact) have no edit endpoint.
- **Vendors module** (added on the web 2026-10-02, Full Admin only): in `MODULES` so it lists under Module access as web only; no mobile screens.
- **Mock data notes:** mock invoices and line items hold only the columns the dashboards and reports read; the mock "sends" the contact decision email without sending anything; Laravel's billing never writes `invoice_line_items.setting`, so real data shows "Not specified" where the mock shows Clinic / Home.
- **Known Laravel issues (for later, don't fix):**
  - Patient Payments "Outstanding" is VAT-exclusive billed (`subtotal`) minus VAT-inclusive receipts (`amount_paid`), so it understates what is owed. The mock mirrors it.
  - `Lead::packageHours()` treats a package's `hours_per_week` as its whole hour balance, counted against every non-cancelled session ever booked. A child on a 30 h/wk ABA package is therefore blocked from further ABA bookings after 30 hours in total. The mock mirrors this, so most seeded patients can't be booked for ABA.
  - The booking authorization warning also fires for a "Self-pay" authorization row ("only has 0 hours left on the Self-pay authorization").
  - Admin dashboard "oldest Nd" uses `issue_date->diffInDays(now())`, which is a float in Carbon 3, so the web can print e.g. "oldest 12.43d". The mock returns whole days.
  - Converting a chat to a lead writes `source` as `ucfirst(channel)` ("Whatsapp"), which doesn't match the lead board's "WhatsApp" badge key.
  - `POST /whatsapp/message/{message}/convert-to-lead` exists but nothing in the UI calls it.
  - Suspended users (`is_active = false`) can still log in on the web; `LoginController` never checks. The API and the mock reject them.
  - `last_login_at` is never written, so every invited user shows as "invited".
  - `GET /calendar/{id}` has no ownership check. `PatientDocumentController` has no role or ownership check.
  - `leads/count` and `leads/kanban` are registered after `leads/{lead}`, so they are shadowed.
  - Route files are loaded twice: in `bootstrap/app.php` `web:` and again via `require` in `routes/web.php`.
  - The Calendar capability-strip text says Coordinator is locked, but `canManage()` lets them manage.
  - A stray file named `title)` sits in the Laravel root.
  - `PatientGoal::sessionsInLast(10)` counts the 10 latest sessions *including future booked ones*, so "used in N of the last 10 sessions" reads 0 whenever sessions are booked ahead. The mock mirrors this.
  - `updateGoalsForToday` validates `goal_ids` with `exists:patient_goals,id` only, so a goal belonging to another patient can be linked.
  - Therapist patient scoping (`PatientController::index` / `assertAssignedTherapist`) matches `patient_id` only, ignoring group bookings in `patient_ids`.

## Research docs

Paths in these docs are relative to the Laravel root (the parent of `mobile/`).

- [docs/laravel-roles-and-auth.md](docs/laravel-roles-and-auth.md): auth, User, permissions, dashboards per role, navigation, seeded users
- [docs/laravel-data-models.md](docs/laravel-data-models.md): every model's columns, enums, relations, sample values
- [docs/laravel-modules.md](docs/laravel-modules.md): calendar, patients, leads endpoints and rules; UI colours
- [docs/api.md](docs/api.md): the mobile API — where the code is, how it reuses the web controllers, endpoints, sign-in, pointing the app at it, running its tests

## Milestones

| # | Scope | Status |
|---|---|---|
| 1 | Login (mock auth), therapist dashboard, "My schedule" (week and day list, session detail, therapist note), tab shell from modules, theme, "My profile" (mirrors web profile page: summary + editable personal details via `PUT /profile`, module access, log out) | Done (2026-09-30): tested on the user's phone in Expo Go; profile screen rebuilt afterwards to mirror the web page (verified on web). Type-check, lint, expo-doctor 21/21, Android + iOS bundles pass |
| 2 | Patients: list (own-scoped for therapists, search, "Needs details"/"Active" groups), detail (header, renewal/incomplete banners, chips; Overview: goals worked on today, session notes, insurance & authorization, care team, upcoming; Session history). Dashboard note/plan rows open the patient | Done 2026-10-01: mock rules checked by script, walked through in a browser, tested on the user's phone |
| 2c | Calendar: Week / Month toggle. Month grid like the web (`calendar/index.blade.php`: count badge, up to 3 name chips, +N more, today/weekend/leave), tap a day to list its sessions; data from `GET /calendar/feed`. Session cards show the therapist's own note text; month cells mark days with notes | Done 2026-10-01: walked through in a browser, tested on the user's phone |
| 2b | Patient extras: Payments tab (billed / collected / outstanding, invoice history), Documents tab (add and delete document records; no file upload, as on the web), Profile & intake tab (everything agreed at intake, read-only), and Edit details (`patients/edit`: child, parent, phone, diagnosis, programme, start, insurance / authorized hours / renewal on the primary authorization, optional clinical note). Coordinators' clinical fields are hidden and ignored by the server. The web has endpoints to add / edit / remove extra authorizations but no screen for them, so the app has none either | Built 2026-10-02; rules checked by script, walked through in a browser. **Remaining: test on a phone** |
| 3 | Clinical supervisor: dashboard mirroring `clinical_supervisor.blade.php` (6 stats, today's clinic schedule, notes awaiting sign-off, flagged, plans due, waitlist); **notes review** screen with sign-off (single + multi-select) and flag/unflag (proposed endpoints, user's choice); clinic-wide Week/Month calendar with therapist filter for managers; log/remove supervision on past sessions (real `POST/DELETE /calendar/{id}/supervision` rules); sign-off/flag status on patient notes | Done 2026-10-01: rules checked by script, walked through in a browser (supervisor + therapist regression), tested on the user's phone |
| 4a | Coordinator dashboard mirroring `coordinator.blade.php` (6 stats, clinic schedule, intake pipeline, WhatsApp preview, no-shows needing follow-up) and the **WhatsApp / Instagram / Facebook inbox** for every role with the `whatsapp` module: list (search, channel pills, AI/staff chips, attention dot, unread), conversation (bubbles, date dividers, reply with Sending/Sent/Delivered, AI-state switch, read-only family details, Convert to Lead), 4-second polling | Built 2026-10-01; rules checked by script, walked through in a browser (plus supervisor/therapist regression). **Remaining: test on a phone** |
| 4b | Leads for every role with the `leads` module: board as a stage switcher (New / Contacted / Initial assessment / Enrolled + Terminated history with Restore), lead panel (Success → next stage, Follow-up, notes, assign owner, follow-up date, terminate with reason, Convert to client, intake checklist status, "where this lead came from", assignment log), New lead form; all gated by `canDo` like LeadController | Built 2026-10-01 and pushed; rules checked by script, walked through in a browser. **Remaining: test on a phone** |
| 4c | Lead intake checklist: the 7 step forms (parent contact, child details, intake form, assessment, funding with per-service payer rows, package multi-select, consent), same fields/options/required marks as the web's `ic-modal`s; saving stamps the step (`intake_step`), 7/7 auto-enrols, and Convert to client carries diagnosis, programme and an insurance authorization from the funding rows. Step definitions live in `src/features/leads/intakeSteps.ts` | Built 2026-10-02; rules checked by script, walked through in a browser. **Remaining: test on a phone** |
| 5 | Admin overview dashboard (FULL_ADMIN): 6 stat tiles (new leads, sessions today, attendance, revenue MTD, waitlist, claims pending), today's schedule, lead sources chart, WhatsApp inbox, authorizations expiring, waitlist, + New Lead. Also the shared `DateField` date picker (intake dates, lead follow-up, new lead). HR / Sales / Finance / Other staff dashboards still show the placeholder | Done 2026-10-02 (tested on a phone) |
| 6 | Contacts (website enquiries / booking requests): list with status filter, submission detail, set/move/remove the consultation slot (no past dates, no Fri/Sat), approve / reject, decision email (moves it to Contacted and locks the slot), convert to lead, close, delete. Coordinators can't convert or delete. Opened from the Leads board ("Website enquiries · N new"); screens are `(app)/contacts/*`, not a tab, because seven tabs don't fit a phone | Done 2026-10-02 (tested on a phone) |
| 7 | Therapists & schedules: roster with each therapist's weekly hours and session count, one therapist's week (Mon–Sun, closed and cancelled sessions shown), Close / Reopen a session (managers only), week navigation. Opened from the Calendar tab ("Therapists & schedules →"); screens are `(app)/therapists/*`. **Not included: Add / Edit session** — that is booking (`CalendarController::store/update` with conflict, leave, package and authorization rules) and needs its own milestone | Done 2026-10-02 (tested on a phone) |
| 7b | Book / edit a session (`(app)/session-form`): therapist(s), patient or custom block, type, date, time, duration, room, notes; repeat weekly / every 2 weeks with weekdays and a session count (blank = sized from the patient's package hours). Edit also changes status (cancelled by family / clinic, no-show, closed). Rules from `CalendarController::store/update`: booking needs `book_modify_session`, editing is managers only, double-booked slots are skipped, package hours are a hard stop, the insurance authorization is a warning. Opened from Calendar ("+ Book session"), a therapist's week ("+ Add session", "Edit") and the session screen ("Edit session"). Group bookings (several children) and multiple types per session stay on the web | Done 2026-10-02 (tested on a phone) |
| 8 | Reports & analytics (read-only): VAT return summary, collection rate, revenue summary, revenue by month / service / setting & therapist, lead conversion funnel, lead sources, why leads are lost, therapy hours delivered. Opened from the admin dashboard ("Reports →") and from Profile → Module access, where Contacts, Therapists and Reports now have an "Open →" link. PDF export stays on the web | Done 2026-10-02 (tested on a phone) |
| 9 | Remaining role dashboards: Sales (my leads pipeline, lead sources, inbox), HR (staff counts, today's schedule, staff by role, recently added staff), Finance (revenue, collected, claims pending and aging, revenue by payer), Other staff (invoice / quotation counts, recent invoices). Links into modules that are still web only (User Management, Billing) are left out | Built 2026-10-02; rules checked by script, walked through in a browser. **Remaining: test on a phone** |
| 10 | Laravel mobile API: Sanctum sign-in and JSON endpoints under `/api/v1` for every module the app has, plus the app's real HTTP client (`src/api/http/`) switched on by `EXPO_PUBLIC_API_URL`. 37 API tests; mock and real responses compared field by field; the app smoke-tested in a browser against the real API | Built 2026-10-02. **Remaining: test on a phone against your own server** (run `php artisan migrate` there first) |
| 11 | Notifications bell on every dashboard (unread badge, list, open the item, mark all as read); Staff leave from the Calendar (mark leave with a warning of how many sessions it cancels, upcoming leave, remove); Add patient directly (Patients list, not for coordinators or therapists). From this milestone on, each one adds its API routes, mock, real client and screens together | Done 2026-10-02 (tested on a phone) |
