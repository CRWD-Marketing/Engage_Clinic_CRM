/**
 * Builds the in-memory mock database. People, names, rooms, durations,
 * diagnoses and programmes come from the Laravel seeders (UserSeeder,
 * LeadSeeder, PatientSeeder, CalendarSessionSeeder, PatientNoteSeeder…).
 * Dates are generated relative to "today" in Asia/Dubai so the schedule
 * always looks current.
 */

import { SYSTEM_TEMPLATES, type Department, type Role } from '@/auth/roles';
import { addDays, clinicToIso, todayYmd, weekdayIndex, type Ymd } from '@/utils/dates';

import { blankLead, INTAKE_STEP_COLUMNS } from './leadDefaults';
import { authorizationHoursUsed, sessionsForLead } from './presenters';
import { createRandom, type Random } from './random';
import type {
  CalendarSessionRow,
  LeadRow,
  MockDb,
  PatientAuthorizationRow,
  PatientGoalRow,
  PatientNoteRow,
  PatientRow,
  SessionGoalRow,
  RoleTemplateRow,
  StaffLeaveRow,
  UserRow,
} from './rows';
import type { WhatsappContact } from '../types';

// ---------------------------------------------------------------------------
// Serialization helpers (match Laravel's JSON output)
// ---------------------------------------------------------------------------

/** Laravel serializes Carbon with microseconds: "2026-09-30T05:00:00.000000Z". */
export function laravelIso(iso: string): string {
  return iso.replace(/\.(\d{3})Z$/, '.$1000Z');
}

/** A `date`-cast column: clinic-local midnight, serialized in UTC. */
export function dateCast(ymd: Ymd): string {
  return laravelIso(clinicToIso(ymd, '00:00'));
}

export function isoAt(ymd: Ymd, time: string): string {
  return laravelIso(clinicToIso(ymd, time));
}

export function hoursAgoIso(hours: number, now: Date = new Date()): string {
  return laravelIso(new Date(now.getTime() - hours * 3600_000).toISOString());
}

export function addMinutes(time: string, minutes: number): string {
  const [h, m] = time.split(':').map(Number);
  const total = h * 60 + m + minutes;
  return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}:00`;
}

// ---------------------------------------------------------------------------
// Staff (database/seeders/UserSeeder.php)
// ---------------------------------------------------------------------------

type StaffSeed = {
  first: string;
  middle?: string;
  last: string;
  email: string;
  password?: string;
  role: Role;
  department: Department;
  job_title?: string;
  notes?: string;
  manager?: number;
  phone: string;
  is_active?: boolean;
};

const STAFF: StaffSeed[] = [
  { first: 'Hong', last: 'Tan', email: 'admin@gmail.com', password: 'admin123', role: 'FULL_ADMIN', department: 'EXECUTIVE', notes: 'CEO', phone: '0501234501' },
  { first: 'Cherry', middle: 'Ann', last: 'Amoroso', email: 'cherry@engagebehavior.com', role: 'FULL_ADMIN', department: 'EXECUTIVE', notes: 'General Manager', manager: 1, phone: '0501234502' },
  { first: 'Indira', last: 'Banarjee', email: 'indira@engagebehavior.com', role: 'CLINICAL_SUPERVISOR', department: 'CLINICAL', job_title: 'BCBA Supervisor', manager: 2, phone: '0501234503' },
  { first: 'HR', last: 'Staff', email: 'hr@engagebehavior.com', role: 'HR_STAFF', department: 'HUMAN_RESOURCES', manager: 1, phone: '0501234504' },
  { first: 'Coordinator', last: 'Staff', email: 'coordinator@engagebehavior.com', role: 'COORDINATOR', department: 'COORDINATOR', notes: 'Scheduling, client communication, intake coordination', manager: 2, phone: '0501234505' },
  { first: 'Cindy', middle: 'Marie', last: 'Gealan', email: 'info@engagebehavior.com', role: 'SALES_STAFF', department: 'SALES', manager: 2, phone: '0501234506' },
  { first: 'Ryan', last: 'Flores', email: 'ryan@engagebehavior.com', role: 'SALES_STAFF', department: 'SALES', manager: 2, phone: '0501234507' },
  { first: 'Kavitha', last: 'Venkatesan', email: 'kavitha@engagebehavior.com', role: 'FINANCE_STAFF', department: 'FINANCE', manager: 2, phone: '0501234508' },
  { first: 'Alessandra', last: 'Yukimi', email: 'alessandra@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'RBT', manager: 3, phone: '0501234509' },
  { first: 'Claudine', last: 'Tadeo', email: 'claudine@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'RBT', manager: 3, phone: '0501234510' },
  { first: 'Sheryl', last: 'Estrella', email: 'sheryl@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'Speech-Language Pathologist', manager: 3, phone: '0501234511' },
  { first: 'May', middle: 'Ann', last: 'Momo', email: 'may@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'Occupational Therapist', manager: 3, phone: '0501234512' },
  { first: 'Fatima', last: 'Vinoythimy', email: 'fatima@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'RBT', manager: 3, phone: '0501234513' },
  { first: 'Lubna', last: 'Sherina', email: 'lubna@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'Speech-Language Pathologist', manager: 3, phone: '0501234514' },
  { first: 'Amalu', last: 'Jacob', email: 'amalu@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'Occupational Therapist', manager: 3, phone: '0501234515' },
  { first: 'Cindy', middle: 'Marie', last: 'Gealan', email: 'cindy.billing@engagebehavior.com', role: 'OTHER_STAFF', department: 'OTHER', notes: 'Invoice and Quotation Only', manager: 2, phone: '0501234516' },
  { first: 'Ryan', last: 'Flores', email: 'ryan.billing@engagebehavior.com', role: 'OTHER_STAFF', department: 'OTHER', notes: 'Invoice and Quotation Only', manager: 2, phone: '0501234517' },
  // Mock-only: exercises the suspended-account check the mobile login enforces.
  { first: 'Suspended', last: 'Demo', email: 'suspended.demo@engagebehavior.com', role: 'THERAPIST', department: 'CLINICAL', job_title: 'RBT', manager: 3, phone: '0501234518', is_active: false },
];

const SUPERVISOR_ID = 3;
const COORDINATOR_ID = 5;
const SALES_IDS = [6, 7];

type TherapistKind = 'ABA' | 'Speech' | 'OT';
const THERAPIST_KIND: Record<number, TherapistKind> = {
  9: 'ABA',
  10: 'ABA',
  11: 'Speech',
  12: 'OT',
  13: 'ABA',
  14: 'Speech',
  15: 'OT',
};

// ---------------------------------------------------------------------------
// Families (LeadSeeder / PatientSeeder vocabularies)
// ---------------------------------------------------------------------------

type FamilySeed = {
  child: string;
  parent: string;
  age: number;
  status: LeadRow['status'];
  diagnosis?: string;
  programme?: string;
  /** therapist id → service delivered */
  care?: number[];
  interested_in: string;
  insurance: string;
  source: string;
  planDueInDays?: number;
  /** Intake step 4 assessment summary (the "Converted from lead" banner). */
  summary?: string;
  /** Enquiry note shown on the lead card. */
  note?: string;
  /** follow_up_due_at relative to today (negative = overdue). */
  followUpInDays?: number;
};

const FAMILIES: FamilySeed[] = [
  { child: 'Khalifa Al Mansoori', parent: 'Mohammed Al Mansoori', age: 5, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 1)', programme: 'ABA 20h/wk + Speech 2h', care: [9, 11], interested_in: 'ABA therapy', insurance: 'Daman Enhanced', source: 'WhatsApp', planDueInDays: 3, summary: 'ADOS-2 consistent with ASD Level 1. Strong visual learner; limited expressive language. Recommend 20h ABA + 2h speech.' },
  { child: 'Layla Hassan', parent: 'Youssef Hassan', age: 4, status: 'enrolled', diagnosis: 'Speech & Language Delay', programme: 'Speech 3h/wk + OT 2h/wk', care: [14, 12], interested_in: 'Speech therapy', insurance: 'Daman', source: 'Facebook', planDueInDays: 41 },
  { child: 'Omar Farooq', parent: 'Bilal Farooq', age: 6, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 2)', programme: 'ABA 25h/wk', care: [10], interested_in: 'ABA therapy', insurance: 'Thiqa', source: 'Referral', planDueInDays: 5 },
  { child: 'Amina Al Rashidi', parent: 'Fatima Al Rashidi', age: 3, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 1)', programme: 'Combined ABA + Speech + OT', care: [9, 11, 15], interested_in: 'Early intervention', insurance: 'Daman', source: 'Instagram', planDueInDays: 30, summary: 'M-CHAT-R high risk; ADOS-2 confirms ASD Level 1. Sensory seeking, so OT input advised alongside ABA and speech.' },
  { child: 'Zayed Al Hammadi', parent: 'Hamad Al Hammadi', age: 5, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 2)', programme: 'ABA 15h/wk + OT 1h', care: [13, 12], interested_in: 'Combined program', insurance: 'ADNIC', source: 'Google', planDueInDays: 55 },
  { child: 'Noor Al Ketbi', parent: 'Aisha Al Ketbi', age: 4, status: 'enrolled', diagnosis: 'Global Developmental Delay', programme: 'ABA 15h/wk + OT 1h', care: [9, 15], interested_in: 'Early intervention', insurance: 'Self-pay', source: 'Website', planDueInDays: 62 },
  { child: 'Yousef Rahman', parent: 'Imran Rahman', age: 7, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 1)', programme: 'ABA 20h/wk + Speech 2h', care: [10, 14], interested_in: 'ABA therapy', insurance: 'AXA / GIG', source: 'Walk-in', planDueInDays: -4 },
  { child: 'Sara Al Dhaheri', parent: 'Mariam Al Dhaheri', age: 3, status: 'enrolled', diagnosis: 'Speech & Language Delay', programme: 'Speech 3h/wk + OT 2h/wk', care: [11, 12], interested_in: 'Speech therapy', insurance: 'Thiqa', source: 'Instagram', planDueInDays: 18 },
  { child: 'Rashid Al Suwaidi', parent: 'Ali Al Suwaidi', age: 6, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 3)', programme: 'ABA 25h/wk', care: [13], interested_in: 'ABA therapy', insurance: 'Daman Enhanced', source: 'Referral', planDueInDays: 6 },
  { child: 'Hind Al Falasi', parent: 'Salama Al Falasi', age: 5, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 2)', programme: 'ABA 20h/wk + Speech 2h', care: [9, 14], interested_in: 'ABA therapy', insurance: 'Daman', source: 'Event', planDueInDays: -2 },
  { child: 'Adam Qureshi', parent: 'Sana Qureshi', age: 4, status: 'enrolled', diagnosis: 'Global Developmental Delay', programme: 'Combined ABA + Speech + OT', care: [13, 11, 15], interested_in: 'Combined program', insurance: 'Self-pay', source: 'Facebook', planDueInDays: 27 },
  { child: 'Maryam Al Blooshi', parent: 'Khalid Al Blooshi', age: 5, status: 'enrolled', diagnosis: 'Autism Spectrum Disorder (Level 1)', programme: 'ABA 15h/wk + OT 1h', care: [10, 15], interested_in: 'ABA therapy', insurance: 'ADNIC', source: 'Google', planDueInDays: 74 },
  { child: 'Mariam Al Shamsi', parent: 'Faisal Al Shamsi', age: 4, status: 'assessment_booked', interested_in: 'Diagnostic assessment', insurance: 'Not sure yet', source: 'Instagram', note: 'Assessment booked for next week; parent asked about ADOS-2.', followUpInDays: -1 },
  { child: 'Fatima Al Zaabi', parent: 'Ahmed Al Zaabi', age: 3, status: 'contacted', interested_in: 'Speech therapy', insurance: 'Daman', source: 'WhatsApp', note: 'Called back, interested in speech therapy. Waiting on insurance card.', followUpInDays: 2 },
  { child: 'Hessa Al Nuaimi', parent: 'Marwan Al Nuaimi', age: 6, status: 'new', interested_in: 'Occupational therapy', insurance: 'Thiqa', source: 'Website', note: 'Submitted the website form asking about OT availability.' },
  { child: 'Saeed Al Mazrouei', parent: 'Khalfan Al Mazrouei', age: 7, status: 'terminated', interested_in: 'ABA therapy', insurance: 'Self-pay', source: 'Google' },
  { child: 'Rayan Al Marri', parent: 'Noura Al Marri', age: 3, status: 'new', interested_in: 'Speech therapy', insurance: 'Daman', source: 'Instagram' },
  { child: 'Ali Haddad', parent: 'Rania Haddad', age: 5, status: 'new', interested_in: 'ABA therapy', insurance: 'Not sure yet', source: 'Website' },
  { child: 'Yara Al Hosani', parent: 'Salem Al Hosani', age: 4, status: 'assessment_done', interested_in: 'Combined program', insurance: 'Daman Enhanced', source: 'Referral', note: 'Assessment complete; waiting on consent form.' },
  // Enrolled with 7/7 intake steps but not converted yet (no diagnosis given here, so no patient row).
  { child: 'Dana Al Kaabi', parent: 'Hamdan Al Kaabi', age: 5, status: 'enrolled', interested_in: 'ABA therapy', insurance: 'Thiqa', source: 'Walk-in', note: 'All intake steps done. Ready to convert to client.' },
];

const AGE_BAND = (age: number) => (age <= 4 ? '3-4' : age <= 6 ? '5-6' : '7-8');
const STEPS_FOR_STATUS: Record<LeadRow['status'], number> = {
  new: 0,
  contacted: 1,
  assessment_booked: 3,
  assessment_done: 6,
  enrolled: 7,
  terminated: 0,
};

// ---------------------------------------------------------------------------
// Session vocabulary (CalendarSessionSeeder / PatientNoteSeeder)
// ---------------------------------------------------------------------------

const ROOMS = ['Room 1', 'Room 2', 'Room 3'];
const ABA_STARTS = ['08:00', '10:30', '13:00', '15:00'];
const SHORT_STARTS = ['08:30', '09:30', '10:30', '11:30', '13:30', '14:30', '15:30'];

const SCHEDULING_NOTES = [
  'Parent to join the last 15 min',
  'Bring visual schedule',
  'Work on transitions between activities',
  null,
  null,
  null,
];

const THERAPIST_NOTES = [
  'Good engagement today. Completed 8/10 mand trials independently.',
  'Tired in the first half; improved after a movement break.',
  'Worked on turn-taking with a peer game — needed 2 prompts.',
  'Tolerated the new sensory brush well. Continue next session.',
  'Great progress on 2-step instructions today.',
  'Some elopement early on; used first/then board successfully.',
];

const SUPERVISION_NOTES = [
  'Observed 45 min. Prompt fading on target; mand training to continue at current level.',
  'Good pacing and reinforcement. Tighten data collection on the matching program.',
  'Session well structured. Consider adding a visual timer for transitions.',
];

/** PatientNoteSeeder flag reasons. */
const FLAG_REASONS = [
  'Increase in self-injurious behaviour observed, flagging for BCBA review.',
  'Family requested a change in session times - needs scheduling follow-up.',
];

const PATIENT_NOTE_BODIES = [
  'Worked on requesting preferred items using PECS cards; 8/10 independent trials.',
  'Session focused on joint attention tasks - responded well to name call 7/10 trials.',
  'Practised 2-step instructions during play; needed gestural prompts on 3 of 10.',
  'Fine motor: pencil grip improving, completed tracing task with minimal support.',
  'Parent reports better sleep this week; fewer transitions meltdowns in session.',
  'Introduced a 5-step visual schedule; completed 3 steps independently.',
  'Expressive vocabulary: 4 new spontaneous words observed (ball, more, open, car).',
];

// ---------------------------------------------------------------------------
// Builder
// ---------------------------------------------------------------------------

export function buildSeed(now: Date = new Date()): MockDb {
  const rnd = createRandom(20260930);
  const today = todayYmd(now);
  const stamp = laravelIso(now.toISOString());

  // --- role templates + users (RoleTemplateSeeder applies them to users) ---
  const roleTemplates: RoleTemplateRow[] = Object.entries(SYSTEM_TEMPLATES).map(([key, t], i) => ({
    id: i + 1,
    key,
    name: t.name,
    description: null,
    base_role: t.base_role,
    modules: [...t.modules],
    module_levels: t.module_levels ? { ...t.module_levels } : null,
    actions: [...t.actions],
    is_system: true,
    sort_order: i + 1,
  }));

  const users: UserRow[] = STAFF.map((s, i) => {
    const template = roleTemplates.find((t) => t.base_role === s.role)!;
    return {
      id: i + 1,
      public_id: rnd.uuid(),
      first_name: s.first,
      middle_name: s.middle ?? null,
      last_name: s.last,
      job_title: s.job_title ?? null,
      email: s.email,
      phone_number: s.phone,
      timezone: 'Asia/Dubai (GST)',
      message_signature: null,
      email_verified_at: stamp,
      department: s.department,
      manager_id: s.manager ?? null,
      role: s.role,
      role_template_id: template.id,
      modules: template.modules,
      module_levels: template.module_levels,
      actions: template.actions,
      start_date: dateCast(i < 2 ? '2026-07-24' : '2026-07-02'),
      notes: s.notes ?? null,
      is_active: s.is_active ?? true,
      invited_at: null,
      last_login_at: null,
      created_at: stamp,
      updated_at: stamp,
      password: s.password ?? 'password',
    };
  });

  const userName = (id: number) => {
    const u = users.find((x) => x.id === id);
    return u ? `${u.first_name} ${u.last_name}` : null;
  };

  // --- leads + patients ---
  const leads: LeadRow[] = [];
  const patients: PatientRow[] = [];

  FAMILIES.forEach((f, i) => {
    const id = i + 1;
    // Waitlist ("new") enquiries are recent; everyone else enquired months ago.
    const createdYmd = addDays(today, f.status === 'new' ? -rnd.int(5, 40) : -rnd.int(60, 140));
    // New enquiries have no owner yet ("Unassigned — open to assign").
    const owner = f.status === 'new' ? null : SALES_IDS[i % 2];
    const email = `${f.parent.split(' ')[0].toLowerCase()}.${f.parent.split(' ').slice(-1)[0].toLowerCase()}@gmail.com`;
    const createdAt = isoAt(createdYmd, '10:15');
    // LeadSeeder: steps completed by status (enrolled 7, assessment_done 6, booked 3, contacted 1).
    const stepStamps = Object.fromEntries(
      Object.values(INTAKE_STEP_COLUMNS)
        .slice(0, STEPS_FOR_STATUS[f.status])
        .map((column, step) => [column, isoAt(addDays(createdYmd, step + 1), '11:00')]),
    );
    const terminated = f.status === 'terminated';
    leads.push(
      blankLead({
        id,
        child_name: f.child,
        child_age: String(f.age),
        parent_guardian_name: f.parent,
        phone: `+9715${rnd.int(0, 8)}${String(rnd.int(1000000, 9999999))}`,
        email,
        source: f.source,
        city: 'Abu Dhabi',
        child_age_band: AGE_BAND(f.age),
        interested_in: f.interested_in,
        insurance: f.insurance,
        estimated_value: `${rnd.int(8, 40) * 1000}.00`,
        notes: f.note ?? (f.status === 'enrolled' ? 'Enrolled and attending regular sessions.' : null),
        status: f.status,
        assigned_to: owner,
        follow_up_due_at: f.followUpInDays === undefined ? null : isoAt(addDays(today, f.followUpInDays), '00:00'),
        assessment_report_summary: f.summary ?? null,
        diagnosis_suspected: f.diagnosis ?? null,
        ...intakeValues(f, STEPS_FOR_STATUS[f.status], createdYmd, today, i),
        termination_reason: terminated ? 'Distance / relocated' : null,
        termination_note: terminated ? 'Family moved to Al Ain.' : null,
        terminated_at: terminated ? isoAt(addDays(today, -12), '15:30') : null,
        status_before_termination: terminated ? 'contacted' : null,
        ...stepStamps,
        created_at: createdAt,
        assigned_to_name: owner === null ? null : userName(owner),
        intake_steps_complete: STEPS_FOR_STATUS[f.status],
      }),
    );

    if (f.status === 'enrolled' && f.diagnosis) {
      const enrolledYmd = addDays(createdYmd, rnd.int(7, 21));
      patients.push({
        id: patients.length + 1,
        lead_id: id,
        diagnosis: f.diagnosis,
        programme: f.programme ?? null,
        treatment_plan_review_due_at: f.planDueInDays === undefined ? null : dateCast(addDays(today, f.planDueInDays)),
        enrolled_at: isoAt(enrolledYmd, '09:00'),
        created_at: isoAt(enrolledYmd, '09:00'),
        updated_at: isoAt(enrolledYmd, '09:00'),
      });
    }
  });

  // --- staff leave (StaffLeaveSeeder) ---
  const staffLeaves: StaffLeaveRow[] = [
    { id: 1, user_id: 9, leave_date: addDays(today, 7 - weekdayIndex(today) + 1), leave_type: 'Annual leave', reason: 'Approved annual leave.', created_by: SUPERVISOR_ID },
    { id: 2, user_id: 10, leave_date: nearestWeekday(addDays(today, -9)), leave_type: 'Sick leave', reason: 'Called in sick — cover arranged with the RBT team.', created_by: SUPERVISOR_ID },
  ];

  // --- calendar sessions ---
  const sessions = buildSessions({ rnd, today, leads, staffLeaves });

  // --- patient notes ---
  const patientNotes = buildPatientNotes({ rnd, now, patients, sessions });

  const db: MockDb = {
    roleTemplates,
    users,
    leads,
    patients,
    sessions,
    staffLeaves,
    patientNotes,
    leadActivities: [],
    patientGoals: [],
    sessionGoals: [],
    authorizations: [],
    whatsappContacts: [],
    whatsappMessages: [],
  };
  buildLeadActivities(db);
  buildGoals({ rnd, db, stamp });
  buildAuthorizations({ rnd, db, today, stamp });
  buildInbox({ rnd, db, today, stamp });
  return db;
}

function nearestWeekday(ymd: Ymd): Ymd {
  const w = weekdayIndex(ymd);
  return w === 5 ? addDays(ymd, -1) : w === 6 ? addDays(ymd, -2) : ymd;
}

function buildSessions({
  rnd,
  today,
  leads,
  staffLeaves,
}: {
  rnd: Random;
  today: Ymd;
  leads: LeadRow[];
  staffLeaves: StaffLeaveRow[];
}): CalendarSessionRow[] {
  const rows: CalendarSessionRow[] = [];

  // Which children each therapist sees (from FAMILIES.care).
  const caseload = new Map<number, number[]>();
  FAMILIES.forEach((f, i) => {
    for (const therapistId of f.care ?? []) {
      caseload.set(therapistId, [...(caseload.get(therapistId) ?? []), i + 1]);
    }
  });

  const recurrence = new Map<string, string>();
  const groupFor = (therapistId: number, leadId: number) => {
    const key = `${therapistId}:${leadId}`;
    if (!recurrence.has(key)) recurrence.set(key, rnd.uuid());
    return recurrence.get(key)!;
  };

  const push = (row: Omit<CalendarSessionRow, 'id' | 'created_at' | 'updated_at' | 'end_time'>) => {
    rows.push({
      ...row,
      id: rows.length + 1,
      end_time: addMinutes(row.start_time, row.duration_minutes),
      created_at: isoAt(addDays(row.session_date, -21), '09:00'),
      updated_at: isoAt(addDays(row.session_date, -21), '09:00'),
    });
  };

  const base = {
    cover_for_user_id: null,
    patient_ids: null,
    activity_types: null,
    cancelled_at: null,
    follow_up_completed_at: null,
    supervised_by: null,
    supervised_at: null,
    supervision_notes: null,
    therapist_note: null,
    invoice_id: null,
    created_by: COORDINATOR_ID,
  } as const;

  for (let offset = -45; offset <= 28; offset++) {
    const date = addDays(today, offset);
    const weekday = weekdayIndex(date);
    if (weekday >= 5) continue; // Sat/Sun off

    for (const [therapistId, kind] of Object.entries(THERAPIST_KIND).map(([k, v]) => [Number(k), v] as const)) {
      const children = caseload.get(therapistId) ?? [];
      if (children.length === 0) continue;

      const isAba = kind === 'ABA';
      const starts = isAba ? ABA_STARTS.slice(0, rnd.int(2, 3)) : SHORT_STARTS.filter(() => rnd.chance(0.55));
      const onLeave = staffLeaves.some((l) => l.user_id === therapistId && l.leave_date === date);

      starts.forEach((start, slot) => {
        const n = children.length;
        const leadId = children[(((slot + offset + therapistId) % n) + n) % n];
        const lead = leads.find((l) => l.id === leadId)!;
        // Assessments run 90 min, so only in slots with a free hour after them.
        const isAssessment = !isAba && (start === '11:30' || start === '15:30') && rnd.chance(0.15);
        const isParentTraining = isAba && slot === 0 && weekday === 3 && rnd.chance(0.5);
        const activityType = isAssessment ? 'Assessment' : isParentTraining ? 'Parent training' : kind;
        const duration = isAssessment ? 90 : isParentTraining ? 60 : isAba ? 120 : rnd.pick([45, 60]);
        const startTime = `${start}:00`;

        const past = offset < 0;
        let status: CalendarSessionRow['status'] = 'scheduled';
        let cancelReason: CalendarSessionRow['cancel_reason'] = null;
        let noticeHours: number | null = null;

        if (onLeave) {
          status = 'cancelled';
          cancelReason = 'clinic';
        } else if (past) {
          const roll = rnd.next();
          if (roll < 0.08) status = 'no_show';
          else if (roll < 0.14) {
            status = 'cancelled';
            cancelReason = 'family';
            noticeHours = rnd.pick([2, 6, 12, 30, 48, 72]);
          } else if (roll < 0.17) {
            status = 'cancelled';
            cancelReason = 'clinic';
          } else status = 'completed';
        } else if (offset > 0 && rnd.chance(0.03)) {
          status = 'cancelled';
          cancelReason = 'family';
          noticeHours = rnd.pick([12, 48]);
        }

        const completedRecently = status === 'completed' && offset >= -14;
        const supervised = status === 'completed' && offset < -1 && rnd.chance(0.12);

        push({
          ...base,
          therapist_id: therapistId,
          patient_id: leadId,
          patient_name: lead.child_name,
          activity_label: null,
          activity_type: activityType,
          session_date: date,
          start_time: startTime,
          duration_minutes: duration,
          room: kind === 'OT' ? (rnd.chance(0.6) ? 'Sensory gym' : rnd.pick(ROOMS)) : rnd.pick(ROOMS),
          status,
          cancel_reason: cancelReason,
          cancel_notice_hours: noticeHours,
          cancelled_at: status === 'cancelled' ? isoAt(addDays(date, -1), '18:00') : null,
          notes: onLeave ? 'Therapist on leave — family informed.' : rnd.pick(SCHEDULING_NOTES),
          recurrence_group: groupFor(therapistId, leadId),
          therapist_note: completedRecently && rnd.chance(0.5) ? rnd.pick(THERAPIST_NOTES) : null,
          supervised_by: supervised ? SUPERVISOR_ID : null,
          supervised_at: supervised ? isoAt(addDays(date, 1), '17:00') : null,
          supervision_notes: supervised ? rnd.pick(SUPERVISION_NOTES) : null,
        });
      });

      // Weekly non-therapy block for RBTs (CalendarExtrasSeeder "Data collection training").
      if (isAba && weekday === 3 && !onLeave) {
        push({
          ...base,
          therapist_id: therapistId,
          patient_id: null,
          patient_name: 'Data collection training',
          activity_label: 'Data collection training',
          activity_type: 'Training',
          session_date: date,
          start_time: '17:15:00',
          duration_minutes: 45,
          room: 'Room 3',
          status: offset < 0 ? 'completed' : 'scheduled',
          cancel_reason: null,
          cancel_notice_hours: null,
          notes: null,
          recurrence_group: groupFor(therapistId, 0),
        });
      }
    }
  }

  return rows;
}

function buildPatientNotes({
  rnd,
  now,
  patients,
  sessions,
}: {
  rnd: Random;
  now: Date;
  patients: PatientRow[];
  sessions: CalendarSessionRow[];
}): PatientNoteRow[] {
  const notes: PatientNoteRow[] = [];
  const push = (row: Omit<PatientNoteRow, 'id' | 'updated_at'>) =>
    notes.push({ ...row, id: notes.length + 1, updated_at: row.created_at });

  for (const patient of patients) {
    const therapists = [...new Set(sessions.filter((s) => s.patient_id === patient.lead_id).map((s) => s.therapist_id))];
    const count = rnd.int(3, 6);
    for (let i = 0; i < count; i++) {
      const ageHours = rnd.int(4 * 24, 60 * 24);
      const createdAt = hoursAgoIso(ageHours, now);
      const signed = ageHours > 7 * 24 || rnd.chance(0.5);
      const flagged = rnd.chance(0.08);
      push({
        patient_id: patient.id,
        user_id: rnd.pick(therapists),
        body: rnd.pick(PATIENT_NOTE_BODIES),
        flagged,
        flag_reason: flagged ? rnd.pick(FLAG_REASONS) : null,
        signed_off_at: signed ? hoursAgoIso(Math.max(1, ageHours - 30), now) : null,
        signed_off_by: signed ? SUPERVISOR_ID : null,
        created_at: createdAt,
      });
    }
  }

  // A couple of guaranteed flags for the supervisor's "Flagged for review" list.
  notes
    .filter((n) => n.signed_off_at === null && n.user_id !== 9 && !n.flagged)
    .slice(0, FLAG_REASONS.length)
    .forEach((n, i) => {
      n.flagged = true;
      n.flag_reason = FLAG_REASONS[i];
    });

  // Alessandra (id 9): a known mix of pending/overdue notes for the dashboard.
  const alessandraPatients = patients.filter((p) =>
    sessions.some((s) => s.therapist_id === 9 && s.patient_id === p.lead_id),
  );
  [6, 30, 3 * 24, 5 * 24].forEach((hours, i) => {
    const patient = alessandraPatients[i % alessandraPatients.length];
    push({
      patient_id: patient.id,
      user_id: 9,
      body: PATIENT_NOTE_BODIES[(i + 2) % PATIENT_NOTE_BODIES.length],
      flagged: false,
      flag_reason: null,
      signed_off_at: null,
      signed_off_by: null,
      created_at: hoursAgoIso(hours, now),
    });
  });

  return notes;
}

// ---------------------------------------------------------------------------
// Lead activity log (LeadActivitySeeder): assignment entries + a few notes
// ---------------------------------------------------------------------------

function buildLeadActivities(db: MockDb) {
  const add = (leadId: number, userId: number, type: 'note' | 'assignment', body: string, at: string) =>
    db.leadActivities.push({
      id: db.leadActivities.length + 1,
      lead_id: leadId,
      user_id: userId,
      type,
      body,
      created_at: at,
      updated_at: at,
    });

  for (const lead of db.leads) {
    if (lead.assigned_to === null) continue;
    const owner = db.users.find((u) => u.id === lead.assigned_to);
    if (!owner) continue;
    const day = new Date(lead.created_at).getTime();
    add(lead.id, COORDINATOR_ID, 'assignment', `Assigned to ${owner.first_name} ${owner.last_name}`, laravelIso(new Date(day + 3600_000).toISOString()));
    if (lead.status !== 'enrolled') {
      add(lead.id, owner.id, 'note', 'Sent WhatsApp follow-up with the assessment booking link.', laravelIso(new Date(day + 26 * 3600_000).toISOString()));
    }
  }
}

// ---------------------------------------------------------------------------
// Goals + session_goals (PatientGoalSeeder) and authorizations
// ---------------------------------------------------------------------------

const GOALS_BY_KIND: Record<TherapistKind, string[]> = {
  ABA: [
    'Increase eye contact during structured play to 80% of trials',
    'Independently request preferred items using PECS',
    'Follow 2-step instructions independently',
    'Independently complete a 5-step visual schedule',
    'Reduce elopement during transitions',
  ],
  Speech: [
    'Expand expressive vocabulary to 50 spontaneous words',
    'Use 2-word phrases to request',
    'Answer simple "what" questions',
  ],
  OT: [
    'Improve fine motor skills for pencil grip',
    'Tolerate tactile input during messy play',
    'Use scissors to cut along a straight line',
  ],
};

function careKinds(family: FamilySeed): TherapistKind[] {
  return [...new Set((family.care ?? []).map((id) => THERAPIST_KIND[id]))];
}

/** Lead ids follow FAMILIES order (lead id = index + 1). */
function familyFor(patient: PatientRow): FamilySeed {
  return FAMILIES[patient.lead_id - 1];
}

function buildGoals({ rnd, db, stamp }: { rnd: Random; db: MockDb; stamp: string }) {
  for (const patient of db.patients) {
    const kinds = careKinds(familyFor(patient));
    const goalsByKind = new Map<string, PatientGoalRow[]>();

    kinds.forEach((kind, k) => {
      const pool = GOALS_BY_KIND[kind];
      const count = kind === 'ABA' ? rnd.int(2, 3) : k === 0 ? 2 : 1;
      const start = (patient.id + k) % pool.length;
      const titles = Array.from({ length: Math.min(count, pool.length) }, (_, i) => pool[(start + i) % pool.length]);
      goalsByKind.set(
        kind,
        titles.map((title) => {
          const goal: PatientGoalRow = {
            id: db.patientGoals.length + 1,
            patient_id: patient.id,
            title,
            progress_percent: rnd.int(1, 9) * 10,
            created_at: patient.created_at,
            updated_at: stamp,
          };
          db.patientGoals.push(goal);
          return goal;
        }),
      );
    });

    // Link goals to past completed sessions: the first goal of a kind is
    // worked on most often, so "used in N of the last 10" ranks differ.
    for (const session of sessionsForLead(db, patient.lead_id)) {
      if (session.status !== 'completed') continue;
      const goals = goalsByKind.get(session.activity_type) ?? [];
      goals.forEach((goal, i) => {
        if (rnd.chance(i === 0 ? 0.85 : i === 1 ? 0.5 : 0.25)) {
          const link: SessionGoalRow = {
            id: db.sessionGoals.length + 1,
            calendar_session_id: session.id,
            patient_goal_id: goal.id,
          };
          db.sessionGoals.push(link);
        }
      });
    }
  }
}

/** InsuranceSeeder default coverage per payer. */
const DEFAULT_COVERAGE: Record<string, number> = {
  Daman: 80,
  'Daman Enhanced': 100,
  Thiqa: 100,
  ADNIC: 80,
  'AXA / GIG': 70,
};

const POLICY_PREFIX: Record<string, string> = {
  Daman: 'DA',
  'Daman Enhanced': 'DA',
  Thiqa: 'TH',
  ADNIC: 'AD',
  'AXA / GIG': 'AX',
};

type AuthOptions = { tightHours?: boolean; renewInDays?: number };

function buildAuthorizations({ rnd, db, today, stamp }: { rnd: Random; db: MockDb; today: Ymd; stamp: string }) {
  const add = (patient: PatientRow, payer: string, covers: string[], opts: AuthOptions = {}) => {
    const used = authorizationHoursUsed(db, patient.lead_id, covers, today);
    const total = opts.tightHours ? used + 4 : Math.ceil((used + rnd.int(8, 40)) / 10) * 10;
    const selfPay = payer === 'Self-pay';
    const row: PatientAuthorizationRow = {
      id: db.authorizations.length + 1,
      patient_id: patient.id,
      payer_name: payer,
      coverage_percent: selfPay ? 0 : (DEFAULT_COVERAGE[payer] ?? 80),
      covers_services: covers,
      policy_number: selfPay ? null : `${POLICY_PREFIX[payer] ?? 'PL'}-${rnd.int(10, 99)}-${rnd.int(100000, 999999)}`,
      approval_reference: null,
      authorized_hours_total: total,
      renews_at: dateCast(addDays(today, opts.renewInDays ?? rnd.int(60, 200))),
      sort_order: db.authorizations.filter((a) => a.patient_id === patient.id).length,
      created_at: patient.created_at,
      updated_at: stamp,
    };
    db.authorizations.push(row);
  };

  for (const patient of db.patients) {
    const family = familyFor(patient);
    const kinds = careKinds(family);

    switch (family.child) {
      case 'Noor Al Ketbi':
        // No authorization on file → "Needs details" / profile incomplete.
        break;
      case 'Amina Al Rashidi':
        // Two payers, each covering different services.
        add(patient, 'Daman', ['ABA', 'Speech']);
        add(patient, 'ADNIC', ['OT']);
        break;
      case 'Khalifa Al Mansoori':
        // Renews within 45 days → the red "Attention" banner.
        add(patient, family.insurance, kinds, { renewInDays: 30 });
        break;
      case 'Hind Al Falasi':
        // Almost out of hours → red progress bar (≤ 5h left).
        add(patient, family.insurance, kinds, { tightHours: true });
        break;
      default:
        add(patient, family.insurance, kinds);
    }
  }
}

// ---------------------------------------------------------------------------
// Inbox (WhatsappContactSeeder + WhatsappMessageSeeder)
// ---------------------------------------------------------------------------

type ThreadSeed = {
  wa_id: string;
  channel: WhatsappContact['channel'];
  name: string;
  child?: string;
  interested_in?: string;
  insurance?: string;
  /** Lead (by child name) the conversation is linked to. */
  leadChild?: string;
  /** MOCK variety (the Laravel seeder leaves these at defaults): who sent the outbound replies. */
  outboundBy?: 'ai' | number;
  aiState?: WhatsappContact['ai_state'];
  attentionReason?: string;
  messages: ['in' | 'out', string, string | null][];
};

const THREADS: ThreadSeed[] = [
  {
    wa_id: '971501234501',
    channel: 'whatsapp',
    name: 'Mohammed Al Mansoori',
    leadChild: 'Khalifa Al Mansoori',
    messages: [
      ['in', 'text', 'Hi, I saw your page online. Do you offer ABA therapy for a 5 year old?'],
      ['out', 'text', 'Hello! Yes we do. Could you share a bit about your child so we can guide you to the right programme?'],
      ['in', 'text', 'My son Khalifa is 5, recently diagnosed with autism. We have Daman Enhanced insurance.'],
      ['out', 'text', 'Great, Daman Enhanced covers our ABA programme. We can book an assessment this week if that works for you.'],
      ['in', 'text', 'That would be perfect, thank you!'],
    ],
  },
  {
    wa_id: '971501234502',
    channel: 'whatsapp',
    name: 'Youssef Hassan',
    outboundBy: 5,
    messages: [
      ['in', 'text', 'Salam, what are your working hours?'],
      ['out', 'text', 'Wa alaikum salam! We are open Sunday to Thursday, 8am to 6pm.'],
      ['in', 'like', null],
    ],
  },
  {
    wa_id: '17920001',
    channel: 'instagram',
    name: 'aisha.parent',
    child: 'Aisha Rahman',
    interested_in: 'Early intervention',
    insurance: 'Thiqa',
    outboundBy: 'ai',
    messages: [
      ['in', 'text', 'Hii do you have speech therapy available?'],
      ['out', 'text', 'Hi! Yes, we do. How old is your child and what would you like to work on?'],
      ['in', 'text', 'She is 3, mostly non-verbal right now.'],
      ['out', 'text', 'Understood, early intervention would be a great fit. Would you like to book a free consultation?'],
    ],
  },
  {
    wa_id: '17920002',
    channel: 'instagram',
    name: 'noora.mom',
    outboundBy: 'ai',
    attentionReason: 'Model determined this needs human review.',
    messages: [
      ['in', 'text', 'Hello, I replied to your story about the new sensory room'],
      ['out', 'text', 'Hi! Yes, we just opened it. Would you like to bring your child for a visit?'],
      ['in', 'text', 'Yes please, when is a good time?'],
    ],
  },
  {
    wa_id: '37920003',
    channel: 'facebook',
    name: 'Grace Okoro',
    outboundBy: 6,
    aiState: 'human_takeover',
    messages: [
      ['in', 'text', 'Hi, do you accept self-pay families?'],
      ['out', 'text', 'Hello Grace, yes we do! Happy to send over our rate card.'],
      ['in', 'text', 'Yes please, thank you.'],
    ],
  },
  {
    wa_id: '37920004',
    channel: 'facebook',
    name: 'Faisal Al Nuaimi',
    messages: [
      ['in', 'text', 'What is the earliest availability for an assessment?'],
      ['out', 'text', 'We currently have openings next week Tuesday and Thursday mornings.'],
    ],
  },
];

function buildInbox({ rnd, db, today, stamp }: { rnd: Random; db: MockDb; today: Ymd; stamp: string }) {
  for (const thread of THREADS) {
    const contactId = db.whatsappContacts.length + 1;
    // Seeder: threads start two days ago at 09:00, each message 3–40 minutes later.
    let at = new Date(clinicToIso(addDays(today, -2), '09:00'));
    let lastBody: string | null = null;
    let unread = 0;

    for (const [i, [direction, type, body]] of thread.messages.entries()) {
      at = new Date(at.getTime() + rnd.int(3, 40) * 60_000);
      const sentAt = laravelIso(at.toISOString());
      const text = type === 'like' ? '👍' : body;
      db.whatsappMessages.push({
        id: db.whatsappMessages.length + 1,
        whatsapp_contact_id: contactId,
        wa_message_id: `seed_${thread.wa_id}_${i}`,
        direction: direction === 'in' ? 'inbound' : 'outbound',
        type,
        sticker_id: type === 'like' ? '369239263222822' : null,
        media_url: null,
        body: text,
        status: direction === 'in' ? 'received' : 'read',
        send_error: null,
        is_ai_generated: direction === 'out' && thread.outboundBy === 'ai',
        sent_by_user_id: direction === 'out' && typeof thread.outboundBy === 'number' ? thread.outboundBy : null,
        ai_processing_status: direction === 'out' && thread.outboundBy === 'ai' ? 'completed' : null,
        triggered_by_message_id: null,
        ai_error: null,
        voice_call_session_id: null,
        sent_at: sentAt,
        created_at: sentAt,
        updated_at: sentAt,
      });
      lastBody = text;
      unread = direction === 'in' ? unread + 1 : 0;
    }

    const lead = thread.leadChild ? db.leads.find((l) => l.child_name === thread.leadChild) : undefined;
    db.whatsappContacts.push({
      id: contactId,
      wa_id: thread.wa_id,
      channel: thread.channel,
      name: thread.name,
      avatar_url: null,
      child_name: thread.child ?? null,
      interested_in: thread.interested_in ?? null,
      insurance: thread.insurance ?? null,
      lead_id: lead?.id ?? null,
      last_message_preview: lastBody,
      last_message_at: laravelIso(at.toISOString()),
      unread_count: unread,
      ai_state: thread.aiState ?? 'ai_active',
      assigned_user_id: null,
      needs_human_attention: !!thread.attentionReason,
      needs_human_reason: thread.attentionReason ?? null,
      ai_state_changed_by: thread.aiState ? 5 : null,
      ai_state_changed_at: thread.aiState ? stamp : null,
      created_at: stamp,
      updated_at: stamp,
    });
  }
}

// ---------------------------------------------------------------------------
// Intake step values (LeadSeeder fills these in for the steps a lead has done)
// ---------------------------------------------------------------------------

/** Field values for the first `stepsDone` intake steps of a family's lead. */
function intakeValues(f: FamilySeed, stepsDone: number, createdYmd: Ymd, today: Ymd, index: number): Partial<LeadRow> {
  const insured = !['Self-pay', 'Not sure yet'].includes(f.insurance);
  const steps: Partial<LeadRow>[] = [
    {
      parent_relationship: index % 2 === 0 ? 'Father' : 'Mother',
      preferred_language: index % 3 === 0 ? 'Arabic' : 'English',
    },
    {
      child_date_of_birth: dateCast(addDays(today, -(f.age * 365 + 40 + index * 9))),
      child_gender: index % 2 === 0 ? 'Male' : 'Female',
      child_emirates_id: `784-${2026 - f.age}-${1234567 + index * 311}-${(index % 9) + 1}`,
      diagnosis_suspected: f.diagnosis ?? 'Suspected ASD — awaiting assessment',
      main_concern: 'Limited expressive language and difficulty with transitions.',
    },
    {
      intake_form_received_on: dateCast(addDays(createdYmd, 3)),
      intake_form_received_via: ['Email', 'WhatsApp', 'In person'][index % 3],
      medical_history: 'No significant medical history reported.',
    },
    {
      assessment_date: dateCast(addDays(createdYmd, 5)),
      assessment_clinician_id: 9 + (index % 7),
      assessment_tool: ['ADOS-2', 'VB-MAPP', 'PLS-5'][index % 3],
      assessment_report_reference: `ASM-2026-${String(100 + index).padStart(3, '0')}`,
      assessment_report_summary: f.summary ?? 'Assessment completed; a therapy programme was recommended.',
    },
    {
      funding_type: insured ? 'Insurance' : 'Self pay',
      funding_insurer: insured ? f.insurance : null,
      funding_policy_number: insured ? `PN-${24 + (index % 3)}-${556677 + index}` : null,
      funding_approval_valid_until: insured ? dateCast(addDays(today, 120)) : null,
      funding_services_needed: [
        {
          service: 'ABA therapy session',
          payer: insured ? 'Insurance' : 'Self pay',
          hours_per_week: 20,
          approved_hours: insured ? 96 : null,
          approval_reference: insured ? `PA-2026-${77341 + index}` : null,
        },
      ],
    },
    {
      package_location_id: 1,
      package_ids: [1],
      package_start_date: dateCast(addDays(createdYmd, 10)),
      package_sessions_per_week: 5,
      package_agreed_by: f.parent.split(' ')[0],
    },
    {
      consent_signed_date: dateCast(addDays(createdYmd, 11)),
      consent_signed_by: index % 2 === 0 ? 'Father' : 'Mother',
      consent_data_photo: 'Yes',
      consent_signature_method: 'In person',
    },
  ];
  return Object.assign({}, ...steps.slice(0, stepsDone));
}
