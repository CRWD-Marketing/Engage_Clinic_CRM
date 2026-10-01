# Engage Clinic — mobile app

This folder (`mobile/`) is an Expo (React Native, TypeScript, Expo Router) mobile app. It is the mobile client for the Laravel 12 clinic web app in the parent folder (the rest of this repo), a CRM and practice-management system for a therapy clinic in Abu Dhabi. It lives on the `mobile-app` branch of the Laravel repo.

Expo SDK 57 ships breaking changes. Read `AGENTS.md` (in this folder) and check the versioned Expo docs before using any Expo or React Native API.

## Hard rules

1. **Never modify the Laravel app (everything in this repo outside `mobile/`) without the user's explicit OK.** Read it for reference only.
2. **All data access goes through `src/api/`.**
   - Screens and hooks call the `api` client and never `fetch` directly.
   - Today the client is the mock implementation (`src/api/mock/`). An `http/` implementation will replace it later behind the same interface.
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
src/api/http/         real HTTP implementation (later; needs a Laravel API)
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

## Backend notes

- **There is no API or Sanctum yet.** Laravel has session + CSRF web routes only, and many pages return HTML only. A real backend will need token auth and JSON endpoints, which requires the user's OK because it means touching the Laravel app.
- **Proposed endpoints (mobile-only features with no Laravel equivalent yet; add with the API):**
  - `GET /patient-notes/review?filter=unsigned|flagged`, `POST /patient-notes/sign-off {note_ids}`,
    `POST /patient-notes/{id}/flag {flag_reason}`, `DELETE /patient-notes/{id}/flag`, and a
    `signed_off_by_name` append on PatientNote. The web only *lists* unsigned/flagged notes; nothing
    ever sets `signed_off_at` or `flagged`. Allowed for CLINICAL_SUPERVISOR + FULL_ADMIN
    (`canReviewNotes` in `src/auth/permissions.ts`).
- **Web gaps to decide on (display-only on the web, so display-only on mobile for now):**
  - No endpoint or UI ever sets `calendar_sessions.follow_up_completed_at`, so "No-shows needing follow-up" can never be cleared.
  - No "log intake call" action; a lead leaves the intake queue only when its status leaves `new`.
  - `whatsapp_contacts.assigned_user_id` is accepted by `POST /whatsapp/{id}/ai-state` but no UI sends or shows it.
  - Family details (`child_name`, `interested_in`, `insurance` on a contact) have no edit endpoint.
- **Inbox JSON**: `GET /whatsapp/poll` and `POST /whatsapp/send` return rendered HTML fragments, and ai-state / convert-to-lead redirect. The mobile API needs JSON equivalents (shapes in `src/api/types.ts`: InboxContact, InboxThread, InboxPoll).
- **Known Laravel issues (for later, don't fix):**
  - Converting a chat to a lead writes `source` as `ucfirst(channel)` ("Whatsapp"), which doesn't match the lead board's "WhatsApp" badge key.
  - `POST /whatsapp/message/{message}/convert-to-lead` exists but nothing in the UI calls it.
  - Suspended users (`is_active = false`) can still log in; `LoginController` never checks. The mock login does reject them.
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

## Milestones

| # | Scope | Status |
|---|---|---|
| 1 | Login (mock auth), therapist dashboard, "My schedule" (week and day list, session detail, therapist note), tab shell from modules, theme, "My profile" (mirrors web profile page: summary + editable personal details via `PUT /profile`, module access, log out) | Done (2026-09-30): tested on the user's phone in Expo Go; profile screen rebuilt afterwards to mirror the web page (verified on web). Type-check, lint, expo-doctor 21/21, Android + iOS bundles pass |
| 2 | Patients: list (own-scoped for therapists, search, "Needs details"/"Active" groups), detail (header, renewal/incomplete banners, chips; Overview: goals worked on today, session notes, insurance & authorization, care team, upcoming; Session history). Dashboard note/plan rows open the patient | Done 2026-10-01: mock rules checked by script, walked through in a browser, tested on the user's phone |
| 2c | Calendar: Week / Month toggle. Month grid like the web (`calendar/index.blade.php`: count badge, up to 3 name chips, +N more, today/weekend/leave), tap a day to list its sessions; data from `GET /calendar/feed`. Session cards show the therapist's own note text; month cells mark days with notes | Done 2026-10-01: walked through in a browser, tested on the user's phone |
| 2b | Patient detail extras: Payments, Documents, Profile & intake tabs; Edit details; add/edit authorizations | Not started |
| 3 | Clinical supervisor: dashboard mirroring `clinical_supervisor.blade.php` (6 stats, today's clinic schedule, notes awaiting sign-off, flagged, plans due, waitlist); **notes review** screen with sign-off (single + multi-select) and flag/unflag (proposed endpoints, user's choice); clinic-wide Week/Month calendar with therapist filter for managers; log/remove supervision on past sessions (real `POST/DELETE /calendar/{id}/supervision` rules); sign-off/flag status on patient notes | Done 2026-10-01: rules checked by script, walked through in a browser (supervisor + therapist regression), tested on the user's phone |
| 4a | Coordinator dashboard mirroring `coordinator.blade.php` (6 stats, clinic schedule, intake pipeline, WhatsApp preview, no-shows needing follow-up) and the **WhatsApp / Instagram / Facebook inbox** for every role with the `whatsapp` module: list (search, channel pills, AI/staff chips, attention dot, unread), conversation (bubbles, date dividers, reply with Sending/Sent/Delivered, AI-state switch, read-only family details, Convert to Lead), 4-second polling | Built 2026-10-01; rules checked by script, walked through in a browser (plus supervisor/therapist regression). **Remaining: test on a phone** |
| 4b | Leads for every role with the `leads` module: board as a stage switcher (New / Contacted / Initial assessment / Enrolled + Terminated history with Restore), lead panel (Success → next stage, Follow-up, notes, assign owner, follow-up date, terminate with reason, Convert to client, intake checklist status, "where this lead came from", assignment log), New lead form; all gated by `canDo` like LeadController | Built 2026-10-01; rules checked by script, walked through in a browser. **Remaining: test on a phone** |
| 4c | Lead intake checklist: the 7 step forms (parent contact, child details, intake form, assessment, funding, package, consent). Today the app shows each step's done/to-do state only | Not started |
| 5 | Admin overview dashboard | Not started |
| 6 | Contacts (website enquiries / booking requests) for coordinator + sales | Not started (user asked for it later) |
| 7 | Therapists page (per-therapist schedule, close/reopen a session) for supervisor | Not started (user asked for it later) |
| 8 | Reports (read-only summary) for supervisor / admin | Not started (user asked for it later) |
| — | Laravel mobile API (Sanctum + JSON endpoints under `/api/mobile/v1`) | Waiting for the user's signal; don't touch the Laravel app until then |
