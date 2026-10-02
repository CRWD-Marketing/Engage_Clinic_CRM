# Laravel research: module behaviour (calendar, patients, therapists, leads) + UI patterns

Source: `Engage-Clinic/routes/*`, controllers and Blade views, read on 2026-09-30.

## Calendar (`routes/calendar.php`, `CalendarController`, middleware `feature:calendar`)

| Method | URI | Action |
|---|---|---|
| GET | /calendar | HTML. `own` level (THERAPIST) → read-only `calendar/my.blade.php` |
| GET | /calendar/feed?start=&end= | JSON `{sessions:[sessionPayload], leaves:[{id,user_id,user_name,leave_date,leave_type,reason}]}`. THERAPIST is forced to own sessions. Auto-completes past sessions and excludes `closed` |
| GET | /calendar/{id} | JSON sessionPayload. **No ownership check** |
| POST | /calendar | book. Needs `book_modify_session` |
| PUT | /calendar/{id} | update. Needs `canManage()` |
| DELETE | /calendar/{id} | Needs `canManage()` |
| POST/DELETE | /calendar/{id}/supervision | supervise (`notes` required, max 2000; only past, non-cancelled/closed sessions) |
| PATCH | /calendar/{id}/therapist-note | body `note` (nullable, max 2000) → `{message, therapist_note}`. **Only the session's own therapist (else 403).** The UI offers it only on `completed` sessions |
| POST/DELETE | /calendar/leave | mark or remove leave. Marking leave auto-cancels that day's sessions with reason `clinic` |
| GET | /calendar/utilisation, /calendar/leads, /calendar/leave/impact | manager tools |

- **Status flow**
  - `scheduled` → `completed` happens automatically once the end time has passed (a cron every 15 minutes, plus on feed load).
  - Managers may set scheduled, cancelled (family/clinic, plus notice hours), no_show or closed.
- **Cancel policy** (`config/billing.php`): notice 24h, late 50%, no-show 100%.
- **Booking rules**
  - Conflict = same therapist, same date, overlapping times, excluding cancelled/closed. Conflicting slots are skipped and reported.
  - Recurrence can be weekly or biweekly. Booked therapists get a `SessionAssigned` notification.
- **Therapist "My calendar" page (`calendar/my.blade.php`)**
  - Read-only Mon–Sun week with prev/next.
  - Subtitle: therapy hours this week, plus "🔒 Your schedule is set by your supervisor".
  - Cards show time, duration, name, type · notes, a "★ Supervised" badge with the supervision note, and a status tag.
  - Completed cards get "+ Add session note" or "Edit note".
- **Dashboard "today" status chip** (admin/therapist): Completed, Cancelled, No-show, In session (now within start–end), Awaiting update (past end, still scheduled), Upcoming.

## Patients (`routes/patient.php`, `PatientController`)

- **Routes**
  - `GET patient` (`?search=`, `?period=`)
  - `GET patient/{id}`
  - `POST patient`, `PUT patient/{id}`
  - `POST patient/{id}/notes` (`body` max 2000)
  - `POST patient/{id}/goals/today`
  - authorizations CRUD
  - documents (metadata only)
- **Scoping**
  - THERAPIST: list = patients whose lead has any session with me; show/update/notes → 403 unless assigned.
  - COORDINATOR: cannot add notes; clinical fields are ignored on update.
  - COORDINATOR and THERAPIST cannot create patients.
  - `PatientDocumentController` has no role check.
- **List columns**
  - Patient (initials avatar, age · diagnosis)
  - Parent · phone
  - Programme
  - Insurance (primary payer)
  - Authorization ("X / Y h left" or "No auth")
  - Attendance %
  - Status: **Needs details** vs **Active**
- **Detail**
  - Header: name, "Age · diagnosis · enrolled M Y", parent, phone.
  - Banners: authorization renewal ≤ 45 days (red); profile incomplete (amber).
  - Tabs:
    1. **Overview**: session goals worked on today, session notes, insurance & authorization cards with an hours-left bar (red when ≤ 5h), care team, upcoming sessions (5)
    2. **Session history**: date, time, type, length, therapist, status label, note; "N of M attended"
    3. **Payments**: billed, collected, outstanding (AED) + invoice table
    4. **Documents**: type badge, expiry badge
    5. **Profile & intake**: read-only lead intake data

## Therapists (`TherapistController`)

- Only `index` is implemented: a weekly or monthly schedule per therapist, with a Close/Reopen toggle (closed ↔ scheduled).
- THERAPIST role has no `therapists` module by default.

## Leads (`routes/lead.php`, prefix `/admin`)

- **Board**
  - Statuses: new → contacted → assessment_booked → assessment_done → enrolled; `terminated` sits off-board.
  - Kanban columns: New | Contacted / Follow-up | Initial assessment | Enrolled.
- **Rules**
  - `contacted` requires an owner.
  - Terminating needs `terminate_lead` + a reason. Restore returns the lead to `status_before_termination`.
  - Notes need `add_lead_notes`.
- **Conversion**
  - The 7-step intake checklist auto-sets `enrolled` when done.
  - `convertToPatient` needs `convert_to_client`, enrolled status, 7/7 steps and no existing patient.
- **Termination reasons**: Fees / budget, No insurance coverage, Chose another provider, Unreachable — no response, Distance / relocated, Not a fit for our services, Duplicate enquiry, Other.

## UI patterns / design tokens (web app)

### Brand

| Token | Value |
|---|---|
| navy | #16436E (titles, active tab) |
| primary pink | #C8355F (hover #A82348) |
| text | #2B3A4C, secondary #5A6B7E, muted #98897A |
| cards | white, border #EBE4DA, radius 14 |
| page background | cream #FFFDFA / #F6F3EE |
| fonts | **Baloo 2** (headings), **Nunito Sans** (body) |

### Activity type chips (bg / fg)

| Type | bg | fg |
|---|---|---|
| ABA | #F9E7EC | #C8355F |
| Speech | #E7EFF7 | #24619C |
| OT | #F7EEDD | #B97F24 |
| Assessment | #EDE7F5 | #6E4FA8 |
| Parent training | #E3F1E9 | #2E7D5B |
| neutral | #F6F3EE | #5A6B7E |

### Calendar categories (bg / fg)

| Category | bg | fg |
|---|---|---|
| covered | #DDF3E4 | #1E7A46 |
| leave | #FDF3B0 | #7A5C00 |
| cancelled | #FBE1E1 | #B3261E (strikethrough) |
| admin | #FBF3C7 | #8A6D00 |
| observation | #F3E4F7 | #8E3B9C |
| supervision | #E4E0F7 | #4B3F9E |
| off | #F5E3C0 | #8A6A2B |

### Other status colours

- **My-calendar status tags**: Completed #2E7D5B, Upcoming #B97F24, Cancelled #98897A (strikethrough), No-show #C8355F.
- **Lead status (bg / fg)**:
  - new #E7EFF7 / #24619C
  - contacted #FBF0DC / #8A5A10
  - assessment_* #EDE7F5 / #6E4FA8
  - enrolled #E3F1E9 / #2E7D5B
  - terminated #F9E4E2 / #B3261E
- **Patient badges**: Active #E4F6EB / #1E8A4C; Needs details #FDF6E9 / #8A5A10.
- **Avatar colours**: `crc32(id) % 7` over [#C8355F, #24619C, #B97F24, #6E4FA8, #1F8FA8, #A8461F, #2E7D5B].
