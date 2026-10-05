# Mobile API (`/api/v1`)

The Laravel app serves the mobile app's JSON API under `/api/v1`. It was added on 2026-10-02.

## Where the code is (Laravel side)

| What | Where |
|---|---|
| Routes | `routes/api.php` (sign-in, profile) and `routes/api/modules.php` (modules) |
| Controllers | `app/Http/Controllers/Api/V1/` |
| Tests | `tests/Feature/Api/` |
| Token auth | Laravel Sanctum (`config/sanctum.php`, `personal_access_tokens` table, `HasApiTokens` on `User`) |
| JSON for every request | `app/Http/Middleware/ForceJsonResponse.php` |

## How it is built

Each API controller **extends the web controller for the same module** and reuses its logic, so the app and the website can't drift apart:

- Where the web action already answers JSON (booking, notes, lead updates…), the API route points at that same action.
- Where the web renders a page, the API calls the web action, takes the data the page is rendered with (`->getData()`), and returns it as JSON.
- Where the web redirects (contact status, convert to lead, AI state…), the API runs the web action and returns the changed record.

To make that possible, the `private` helper methods of five web controllers became `protected` (Calendar, Dashboard, Patient, Report, and `extractLeadHints` in Whatsapp). No web behaviour changed.

Module routes use the same `feature:<module>` middleware as the web routes, so module access and "view only" rules are identical.

## Sign-in

`POST /api/v1/login` with `{email, password}` returns `{token, user}`. The app sends the token as `Authorization: Bearer <token>`.

- Same rules as the web login: 5 failed attempts per email + IP locks sign-in for 60 seconds.
- A suspended user (`is_active = false`) cannot sign in. The web login still lets them in.
- Tokens don't expire. `POST /api/v1/logout` deletes the token in use.

## Endpoints

| Area | Endpoints |
|---|---|
| Auth | `POST login`, `POST forgot-password`, `GET me`, `POST logout`, `PUT profile` |
| Dashboard | `GET dashboard` — returns the signed-in role's dashboard |
| Notifications | `GET notifications`, `POST notifications/{id}/read`, `POST notifications/read-all` |
| Calendar | `GET calendar/my-week`, `GET calendar/feed`, `GET calendar/booking-options`, `GET calendar/leave/impact`, `POST calendar/leave`, `DELETE calendar/leave/{id}`, `POST calendar`, `GET/PUT calendar/{id}`, `POST/DELETE calendar/{id}/supervision`, `PATCH calendar/{id}/therapist-note` |
| Patients | `GET/POST patients`, `GET patients/create-options`, `GET/PUT patients/{id}`, `POST patients/{id}/notes`, `POST patients/{id}/goals/today`, `POST patients/{id}/documents`, `DELETE patients/{id}/documents/{doc}` |
| Note review | `GET patient-notes/review`, `POST patient-notes/sign-off`, `POST/DELETE patient-notes/{id}/flag` |
| Leads | `GET/POST leads`, `GET/PUT leads/{id}`, `PATCH leads/{id}/status`, `POST leads/{id}/notes`, `POST leads/{id}/restore`, `POST leads/{id}/convert-to-patient` |
| Inbox | `GET inbox`, `GET inbox/poll`, `POST inbox/send`, `GET inbox/{id}`, `POST inbox/{id}/ai-state`, `POST inbox/{id}/convert-to-lead` |
| Contacts | `GET contacts`, `GET contacts/count`, `PATCH contacts/{id}`, `PATCH contacts/{id}/status`, `POST contacts/{id}/send-email`, `POST contacts/{id}/convert-to-lead`, `DELETE contacts/{id}` |
| Therapists | `GET therapists` |
| Reports | `GET reports` |
| Billing | `GET billing`, `GET billing/invoices/{id}`, `POST billing/invoices/{id}/payments`, `POST billing/invoices/{id}/credit`, `POST billing/invoices/{id}/void`, `POST billing/invoices/{id}/send`, `GET billing/patients/{id}/ledger`, `POST billing/invoices/preview`, `POST billing/invoices`, `POST billing/patients/{id}/top-up`, `GET billing/patients/{id}/statement`, `PATCH billing/claims/{id}`, `POST billing/pre-authorizations`, `PATCH billing/pre-authorizations/{id}`, `GET/POST billing/bulk-run`, `GET billing/invoices/{id}/pdf`, `GET billing/patients/{id}/statement/pdf` |
| Users | `GET users` (search, department, role, status, page), `POST users`, `GET users/{public_id}`, `PUT users/{public_id}`, `DELETE users/{public_id}` |
| Roles & access | `GET roles-access`, `POST roles-access/templates`, `PUT/DELETE roles-access/templates/{id}`, `POST roles-access/users`, `PUT/DELETE roles-access/users/{public_id}`, `PUT roles-access/users/{public_id}/template`, `PUT roles-access/users/{public_id}/access`, `PUT roles-access/users/{public_id}/suspend` |
| Careers | `GET careers/applications` (status), `GET careers/applications/count`, `GET careers/applications/{id}/resume` (the file), `PATCH careers/applications/{id}/status`, `POST careers/applications/{id}/notes`, `DELETE careers/applications/{id}`, `GET/POST careers/postings`, `PUT/DELETE careers/postings/{id}` |
| Packages | `GET packages`, `POST packages`, `PUT packages/{id}`, `DELETE packages/{id}` |

Response shapes are the TypeScript types in `src/api/types.ts`. Errors are `{message, errors}` with the usual status codes (401, 403, 404, 422, 429).

The two `…/pdf` routes answer with the PDF file itself (`application/pdf`), not JSON; the app downloads them with the bearer token.

**New behaviour that the web does not have:** note sign-off and flagging (`patient-notes/*`), open to the Clinical Supervisor and Full Admin. And `GET calendar/{id}` refuses a session that isn't the caller's own when their calendar access is "own records only" (the web route has no such check).

## Pointing the app at the API

The app uses mock data unless `EXPO_PUBLIC_API_URL` is set. Copy `.env.example` to `.env.local` and set the address of the Laravel server **as the phone sees it**:

```bash
# On the PC, serve Laravel so other devices on the Wi-Fi can reach it:
php artisan serve --host=0.0.0.0 --port=8000

# mobile/.env.local  (use the PC's Wi-Fi IP address, not 127.0.0.1)
EXPO_PUBLIC_API_URL=http://192.168.1.20:8000
```

Restart `npx expo start` after changing it. An ngrok address works too.

## Running the API tests

The migrations use MySQL-only SQL, so the tests need a MySQL test database (not phpunit.xml's in-memory SQLite):

```bash
# once: create an empty database named engage_clinic_testing
DB_CONNECTION=mysql DB_DATABASE=engage_clinic_testing php artisan test tests/Feature/Api
```

The API tests seed the demo clinic into that database and never touch `engage_clinic`.
