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
npx expo lint               # lint (ESLint, eslint-config-expo)
npx expo install <pkg>      # add packages (never plain npm install <pkg>)
```

Mock login: any seeded staff email with password `password`, e.g. therapist `alessandra@engagebehavior.com`. Seeded users are listed in `docs/laravel-roles-and-auth.md`.

## Folder structure (`mobile/`)

```
src/app/              Expo Router routes (every file is a screen; keep non-route code out)
  (auth)/             login, forgot-password (signed-out stack)
  (app)/              signed-in tabs; the tab set is built from the user's modules
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
- **Known Laravel issues (for later, don't fix):**
  - Suspended users (`is_active = false`) can still log in; `LoginController` never checks. The mock login does reject them.
  - `last_login_at` is never written, so every invited user shows as "invited".
  - `GET /calendar/{id}` has no ownership check. `PatientDocumentController` has no role or ownership check.
  - `leads/count` and `leads/kanban` are registered after `leads/{lead}`, so they are shadowed.
  - Route files are loaded twice: in `bootstrap/app.php` `web:` and again via `require` in `routes/web.php`.
  - The Calendar capability-strip text says Coordinator is locked, but `canManage()` lets them manage.
  - A stray file named `title)` sits in the Laravel root.

## Research docs

Paths in these docs are relative to the Laravel root (the parent of `mobile/`).

- [docs/laravel-roles-and-auth.md](docs/laravel-roles-and-auth.md): auth, User, permissions, dashboards per role, navigation, seeded users
- [docs/laravel-data-models.md](docs/laravel-data-models.md): every model's columns, enums, relations, sample values
- [docs/laravel-modules.md](docs/laravel-modules.md): calendar, patients, leads endpoints and rules; UI colours

## Milestones

| # | Scope | Status |
|---|---|---|
| 1 | Login (mock auth), therapist dashboard, "My schedule" (week and day list, session detail, therapist note), tab shell from modules, theme, "My profile" (mirrors web profile page: summary + editable personal details via `PUT /profile`, module access, log out) | Done (2026-09-30): tested on the user's phone in Expo Go; profile screen rebuilt afterwards to mirror the web page (verified on web). Type-check, lint, expo-doctor 21/21, Android + iOS bundles pass |
| 2 | Patients: list (own-scoped for therapists), detail (overview, notes, goals, authorizations, session history) | Not started |
