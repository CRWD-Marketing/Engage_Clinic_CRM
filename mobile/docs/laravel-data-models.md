# Laravel research: data models

Source: `Engage-Clinic/app/Models` + `database/migrations` (final state after all alters), read on 2026-09-30.
`?` = nullable.

## JSON serialization rules (how real endpoints will look)

- `date` / `datetime` casts → ISO string `"2026-09-30T00:00:00.000000Z"`.
  - A `date` cast is still midnight UTC.
- Uncast `date` → `"YYYY-MM-DD"`. Uncast `timestamp` → `"YYYY-MM-DD HH:MM:SS"`.
- `time` columns → `"09:30:00"`.
- Decimals (cast or not) → strings, e.g. `"1100.00"`.
- JSON columns come back as arrays only when they have an `array` cast.
- Relations appear only when eager-loaded. Accessors appear only when listed in `$appends`.
- No API Resources exist. Where a controller already builds a JSON payload (e.g. calendar `sessionPayload`), mirror that shape instead of the raw model.

## CalendarSession (`calendar_sessions`)

### Columns

- **People**
  - `id`
  - `therapist_id` (FK users, required)
  - `cover_for_user_id?` (FK users)
  - `created_by?` (FK users)
- **Who the session is for**
  - `patient_id?` → **FK `leads.id`, NOT patients**
  - `patient_name?` — denormalised `lead.child_name`, or the block label for non-patient blocks
  - `patient_ids?` — json array of lead ids (group bookings)
- **Activity**
  - `activity_label?`
  - `activity_type` string(50), free text
  - `activity_types?` json
- **Timing**
  - `session_date` date
  - `start_time` time
  - `duration_minutes` smallint
  - `end_time` time (auto = start + duration)
  - `room?`
- **Status**
  - `status` string, default `scheduled`
  - `cancel_reason?` (`family` | `clinic`)
  - `cancel_notice_hours?` decimal
  - `cancelled_at?`
  - `follow_up_completed_at?`
- **Notes and supervision**
  - `notes?` — scheduling note
  - `recurrence_group?` uuid
  - `supervised_by?` (FK users)
  - `supervised_at?`
  - `supervision_notes?`
  - `therapist_note?` — the therapist's private session note
- **Billing**: `invoice_id?`
- `created_at`, `updated_at`

### Constants

| Constant | Values |
|---|---|
| `THERAPY_TYPES` | ABA, Speech, OT, Assessment, Parent training |
| `NON_THERAPY_TYPES` | Supervision, Observation, Admin time, Training, Meeting |
| `STATUSES` | scheduled, completed, cancelled, no_show, closed |
| `MANUAL_STATUSES` | scheduled, cancelled, no_show, closed (**`completed` is automatic only**) |
| `INACTIVE_STATUSES` | cancelled, closed |
| `DURATIONS` (controller) | 30, 45, 60, 90, 120, 150, 180 |

### Derived values

- `statusLabel()`:
  - "Cancelled — clinic"
  - "Cancelled — with notice" (family, notice ≥ 24h or unknown)
  - "Cancelled — late" (family, < 24h)
  - "No-show", "Closed", "Scheduled", "Completed"
- `category()`: cancelled | covered | supervision | observation | admin | therapy

### Relations

- `therapist`, `coverFor`, `supervisor`, `creator` → User
- `child` → **Lead**
- `invoice` → Invoice
- `goals` → PatientGoal via `session_goals`

### Controller JSON payload (`CalendarController::sessionPayload`)

- **Identity**: `id`, `therapist_id`, `therapist_name`, `cover_for_user_id`, `cover_for_name`, `patient_id`, `patient_ids`, `patient_name`, `is_custom_patient`
- **Activity**: `activity_label`, `activity_type`, `activity_types[]`
- **Timing**: `session_date`, `start_time` (HH:mm), `end_time`, `duration_minutes`, `room`
- **Status**: `status`, `cancel_reason`, `cancel_notice_hours`, `status_label`, `category`, `is_past`
- **Other**: `notes`, `recurrence_group`
- **Supervision**: `can_supervise`, `supervised`, `supervised_by_name`, `supervised_at`, `supervision_notes`

### Seed values

- Rooms: Room 1, Room 2, Room 3, Sensory gym.
- Start times: 08:00–16:00.
- Durations: 45 / 60 / 90 / 120 (assessment = 90).
- Activity type weighting: ABA ×3, Speech, OT.
- Past sessions are mostly completed, ~10% no_show, some cancelled.

### StaffLeave (`staff_leaves`)

- Columns: `user_id`, `leave_date`, `leave_type`, `reason`, `created_by?`.
- Types: Annual leave, Sick leave, Public holiday, Emergency leave, Unpaid leave.

## Patient (`patients`)

- **Columns**
  - `id`
  - `lead_id` (unique FK leads)
  - `diagnosis?`
  - `programme?`
  - `treatment_plan_review_due_at?` date
  - `enrolled_at` datetime
  - timestamps
- **The child's name, age, parent and phone live on the Lead.** There are no insurance columns (they were moved to authorizations).
- **Relations**
  - `lead`
  - `notes` (PatientNote, latest first)
  - `goals`
  - `invoices`
  - `authorizations` (by `sort_order`; lowest = primary)
  - `documents`
- **Derived**
  - `careTeam()`: users with any session for this lead
  - `calendarSessions()`: `patient_id = lead_id` OR `patient_ids` contains `lead_id`
  - `attendanceRate()`: completed / (completed + no_show + family-cancelled)
  - `isProfileIncomplete()`: missing diagnosis, programme, authorization or phone
- **Seed diagnoses**: Autism Spectrum Disorder (Level 1/2/3), Global Developmental Delay, Speech & Language Delay.
- **Seed programmes**: "ABA 20h/wk + Speech 2h", "ABA 15h/wk + OT 1h", "ABA 25h/wk", "Combined ABA + Speech + OT", "Speech 3h/wk + OT 2h/wk".

### PatientNote (`patient_notes`)

- Columns: `patient_id`, `user_id?`, `body`, `flagged` bool, `flag_reason?`, `signed_off_at?`, `signed_off_by?`, timestamps.
- Appends: `author_name` (first + last, or "System").
- Example body: "Worked on requesting preferred items using PECS cards; 8/10 independent trials."

### PatientGoal (`patient_goals`)

- Columns: `patient_id`, `title`, `progress_percent` (0–100).
- `sessions` via `session_goals`.
- Example: "Increase eye contact during structured play to 80% of trials".

### PatientAuthorization (`patient_authorizations`)

- Columns:
  - `patient_id`, `payer_name`, `coverage_percent`
  - `covers_services` (array, e.g. `["ABA","Speech"]`)
  - `policy_number?`, `approval_reference?`
  - `authorized_hours_total?`, `renews_at?` date, `sort_order`
- Example: `{payer_name:"Daman Enhanced", coverage_percent:85, covers_services:["ABA","Speech"], policy_number:"DA-42-518273", authorized_hours_total:40}`.

### PatientDocument (`patient_documents`)

- Columns: `patient_id`, `uploaded_by?`, `name`, `type?`, `file_path?`, `original_filename?`, `mime_type?`, `file_size?`, `expires_at?`.
- Types: Assessment report, Progress review, Authorization, Consent, Identity, Invoice / receipt, Correspondence.
- Renewal warning window: 45 days.

## Lead (`leads`) (file `app/Models/lead.php`, class `Lead`)

### Core columns

- **Child**: `child_name?`, `child_age?` (string), `child_age_band?`
- **Parent**: `parent_guardian_name?`, `phone?`, `email?`
- **Origin**: `source?`, `campaign?`, `ad_name?`, `lead_form_name?`, `city?`
- **Interest and value**: `interested_in?`, `insurance?`, `estimated_value?` (varchar, cast decimal:2 → `"24000.00"`), `notes?`
- **Status**: `status` (default `new`)
- **Termination**: `termination_reason?`, `termination_note?`, `terminated_at?`, `status_before_termination?`
- **Ownership**: `assigned_to?` (FK users), `follow_up_due_at?`

### Intake steps

Each step has a `*_completed_at` stamp plus these fields:

1. **parent_contact**: relationship, alternate phone, preferred_language
2. **child_details**: DOB, gender, Emirates ID + expiry, diagnosis_suspected, nursery_school, main_concern
3. **intake_form**: received_on, received_via, allergies, medical_history
4. **assessment**: date, `assessment_clinician_id`, tool, report reference, report summary
5. **funding**: type, insurer, policy number, approval_valid_until, `funding_services_needed` (json `[{service, payer, hours_per_week, cover, approval_ref}]`), notes
6. **package**: `package_location_id`, `package_ids` (json), start date, sessions/week, agreed_by, scheduling notes
7. **consent**: signed date, signed by, data/photo consent, signature method, notes

### Other details

- **Statuses**: new | contacted | assessment_booked | assessment_done | enrolled | terminated.
- **Appends**: `assigned_to_name`, `intake_steps_complete` (0–7).
- **Relations**: `owner` (assigned_to), `patient` (hasOne), `calendarSessions` (by `patient_id`), `activities`.
- **Sample**: Layla Hassan (4), parent Youssef Hassan, +971531234567, Facebook, Abu Dhabi, Speech therapy, Daman, enrolled.
- **Seed child/parent pairs**:
  - Khalifa/Mohammed Al Mansoori
  - Layla/Youssef Hassan
  - Omar/Bilal Farooq
  - Mariam/Faisal Al Shamsi
  - Fatima/Ahmed Al Zaabi
  - Hessa/Marwan Al Nuaimi
  - Saeed/Khalfan Al Mazrouei
  - Amina/Fatima Al Rashidi

### LeadActivity

- Columns: `lead_id`, `user_id?`, `type` (`note` | `assignment`), `body?`.
- Appends: `author_name`.

## Contact (`contacts`)

- Columns:
  - `name?`, `child_name?`, `child_age?`, `email?`, `phone?`
  - `interested_in?`, `insurance?`, `message?`
  - `booking_date?`, `booking_time?` ("10:00 AM"), `booking_decision?`
  - `status`: new | approved | rejected | contacted | converted | closed
  - `converted_lead_id?`, `converted_at?`

## Catalogue

- **Service**
  - Columns: `name`, `cpt_code?`, `description?`, `default_rate`, `is_active`.
  - Seeds: ABA therapy session 97153 AED 1100, Speech & language therapy 92507 AED 450, Occupational therapy 97530 AED 475, Initial assessment 97151 AED 800, Parent training 97156 AED 600 (inactive).
- **Package**
  - Columns: `name`, `service_id?`, `location_id?`, `funding_type?` (Insurance | Self pay), `delivery_mode?` (Home base | Clinic), `hours_per_week?`, `rate?`, `is_active`.
- **Location** (service zones, not branches): Inside Abu Dhabi, Abu Dhabi around city boundary, Abu Dhabi remote…, Clinic direct visit.
- **Insurance** (`name`, `default_coverage_percent`, `is_active`): Daman 80, Daman Enhanced 100, Thiqa 100, ADNIC 80, AXA / GIG 70, Daman Basic 50, NAS (Neuron) 70, MetLife 60.

## Billing (brief)

- **Invoice**
  - Columns: `invoice_number` ("INV-2026-0412"), `patient_id`, `payer`, `coverage_percent`, `period*`, `issue_date`, `due_date?`, money decimals (`subtotal`, `vat_amount` 5%, `total`, `amount_paid`, …), `payer_splits?`.
  - `status`: draft | issued | submitted | pending_info | paid | rejected.
  - Derived `billingStatus`: voided | paid | partly_paid | outstanding.
- **InvoiceLineItem**: per-session lines with `rate`, `amount`, `insurer_amount`, `family_amount`.
- **Payment**: `receipt_number` ("RCT-001"), `amount`, `method` (Bank transfer | Card | Cash | Cheque | Insurance remittance), `received_on`.
- **InsuranceClaim**: `reference` ("CLM-2001"), `insurer`, `amount`, `status` (draft | submitted | pending_info | rejected | settled).
- **PreAuthorization**: `reference`, `payer`, `service`, `hours`, `valid_from`, `valid_to`, `status` (requested | approved | denied).

## Messaging (brief)

- **WhatsappContact**
  - Columns: `wa_id`, `channel` (whatsapp | instagram | facebook | voice), `name?`, `child_name?`, `lead_id?`, `last_message_preview?`, `last_message_at?`, `unread_count`.
  - `ai_state`: ai_active | human_assigned | human_takeover | closed.
  - Also `needs_human_attention`.
- **WhatsappMessage**
  - Columns: `direction` (inbound | outbound), `type`, `body?`, `media_url?`, `is_ai_generated`, `sent_by_user_id?`, `sent_at`.
  - `status`: pending | sent | delivered | read | failed | received.

## Notifications & activity

- **`notifications`**: `id` uuid, `type` (PHP class), `notifiable_id`, `data` JSON `{icon, title, subtitle, url}`, `read_at?`.
- **`activities`**: `user_id?`, `type`, `title`, `url?`, appends `actor_name`.
  - Types: session_moved, bulk_run, claim_status, credit_note, invoice_voided, form_*.
