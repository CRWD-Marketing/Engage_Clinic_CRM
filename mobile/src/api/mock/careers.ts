/**
 * Mock of the careers module (Career\JobApplicationController and
 * JobPostingController): applications with a status filter, notes, status
 * changes and delete; postings with add, edit and delete. Seeded with a few
 * postings and applications so the screens have something to show. There
 * are no resume files, so a resume download is refused like the PDFs.
 */

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { ApplicationsPage, ApplicationStatus, JobApplication, JobPosting, JobPostingInput } from '../types';
import { laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser } from './server';

const STATUSES: Record<ApplicationStatus, string> = {
  new: 'New',
  reviewed: 'Reviewed',
  interviewing: 'Interviewing',
  hired: 'Hired',
  rejected: 'Rejected',
};

type PostingRow = Omit<JobPosting, 'applications_count'>;
type ApplicationRow = Omit<JobApplication, 'status_label' | 'full_name' | 'notes'> & {
  notes: { id: number; body: string; user_id: number | null; created_at: string }[];
};

const daysAgo = (days: number, hours = 0) => laravelIso(new Date(Date.now() - (days * 24 + hours) * 3600_000).toISOString());
const now = () => laravelIso(new Date().toISOString());
const fail = (field: string, message: string) => new ApiError(422, { message, errors: { [field]: [message] } });

/** Kept beside the main mock database; built on first use. */
let store: { postings: PostingRow[]; applications: ApplicationRow[] } | null = null;
function data() {
  if (store) return store;
  const postings: PostingRow[] = [
    {
      id: 1,
      title: 'Registered Behavior Technician (RBT)',
      employment_type: 'Full-time',
      location: 'Abu Dhabi',
      description: 'Deliver 1:1 ABA sessions in clinic and at home under BCBA supervision.',
      requirements: ['Active RBT certification', 'At least 1 year with children with autism', 'Valid UAE driving licence'],
      status: 'active',
      created_at: daysAgo(40),
    },
    {
      id: 2,
      title: 'Speech-Language Pathologist',
      employment_type: 'Full-time',
      location: 'Abu Dhabi',
      description: 'Assess and treat speech, language and feeding needs; work alongside the ABA team.',
      requirements: ['DOH licence (or eligible)', 'Paediatric caseload experience', 'Arabic is a plus'],
      status: 'active',
      created_at: daysAgo(25),
    },
    {
      id: 3,
      title: 'Occupational Therapist',
      employment_type: 'Part-time',
      location: 'Al Ain',
      description: 'Sensory integration and fine-motor programmes for school-age children.',
      requirements: ['DOH licence', 'Sensory integration training'],
      status: 'inactive',
      created_at: daysAgo(70),
    },
  ];
  const app = (
    id: number,
    posting: PostingRow,
    first: string,
    last: string,
    status: ApplicationStatus,
    years: string | null,
    age: number,
    cover: string | null,
    resume: boolean,
    notes: ApplicationRow['notes'] = [],
  ): ApplicationRow => ({
    id,
    job_posting_id: posting.id,
    job_title: posting.title,
    first_name: first,
    last_name: last,
    email: `${first}.${last}@example.com`.toLowerCase().replace(/\s+/g, ''),
    years_experience: years,
    cover_letter: cover,
    status,
    has_resume: resume,
    resume_name: resume ? `${first} ${last} CV.pdf` : null,
    created_at: daysAgo(age, id),
    notes,
  });
  const [rbt, slp, ot] = postings;
  const applications: ApplicationRow[] = [
    app(1, rbt, 'Sana', 'Iqbal', 'new', '3', 0, 'I have worked as an RBT in Dubai for three years and would love to join your clinic team.', true),
    app(2, rbt, 'Joel', 'Ramirez', 'new', '1', 1, null, true),
    app(3, slp, 'Hala', 'Mansour', 'reviewed', '6', 3, 'Bilingual SLP (Arabic / English) with a paediatric caseload.', true, [
      { id: 1, body: 'Strong CV — book a first call.', user_id: 4, created_at: daysAgo(2) },
    ]),
    app(4, rbt, 'Priya', 'Nair', 'interviewing', '2', 6, 'Currently completing my BCaBA coursework.', true, [
      { id: 2, body: 'Second interview with Indira on Thursday.', user_id: 4, created_at: daysAgo(1) },
      { id: 3, body: 'Good first interview; clear on supervision model.', user_id: 4, created_at: daysAgo(4) },
    ]),
    app(5, ot, 'Omar', 'Haddad', 'hired', '8', 20, 'Experienced OT, sensory integration certified.', true, [
      { id: 4, body: 'Offer accepted, starts next month.', user_id: 4, created_at: daysAgo(10) },
    ]),
    app(6, slp, 'Lena', 'Fischer', 'rejected', null, 12, null, false, [{ id: 5, body: 'No UAE licence route.', user_id: 4, created_at: daysAgo(11) }]),
  ];
  store = { postings, applications };
  return store;
}

function present(a: ApplicationRow): JobApplication {
  const users = getDb().users;
  return {
    ...a,
    full_name: `${a.first_name} ${a.last_name}`.trim(),
    status_label: STATUSES[a.status],
    notes: [...a.notes]
      .sort((x, y) => y.created_at.localeCompare(x.created_at) || y.id - x.id)
      .map((n) => {
        const author = users.find((u) => u.id === n.user_id);
        return { id: n.id, body: n.body, author_name: author ? `${author.first_name} ${author.last_name}`.trim() : 'System', created_at: n.created_at };
      }),
  };
}

function requireCareers(write = false) {
  const user = requireUser();
  requireFeature(user, 'careers', write);
  return user;
}

function findApplication(id: number): ApplicationRow {
  const a = data().applications.find((x) => x.id === id);
  if (!a) throw new ApiError(404, { message: 'Not found.' });
  return a;
}

/** GET /careers/applications */
function list(status?: ApplicationStatus | 'all' | null): ApplicationsPage {
  requireCareers();
  const all = [...data().applications].sort((a, b) => b.created_at.localeCompare(a.created_at));
  const counts: Partial<Record<ApplicationStatus, number>> = {};
  for (const a of all) counts[a.status] = (counts[a.status] ?? 0) + 1;
  return {
    applications: (status && status !== 'all' ? all.filter((a) => a.status === status) : all).map(present),
    statuses: STATUSES,
    status_counts: counts,
    total_count: all.length,
    new_count: counts.new ?? 0,
  };
}

function setStatus(id: number, status: ApplicationStatus) {
  requireCareers(true);
  const a = findApplication(id);
  if (!status) throw fail('status', 'The status field is required.');
  if (!(status in STATUSES)) throw fail('status', 'The selected status is invalid.');
  a.status = status;
  return { message: `Status set to ${STATUSES[status]}.`, application: present(a) };
}

function addNote(id: number, body: string) {
  const user = requireCareers(true);
  const a = findApplication(id);
  if (!body?.trim()) throw fail('body', 'The body field is required.');
  if (body.length > 2000) throw fail('body', 'The body field must not be greater than 2000 characters.');
  a.notes.push({ id: Math.max(0, ...data().applications.flatMap((x) => x.notes.map((n) => n.id))) + 1, body, user_id: user.id, created_at: now() });
  return { message: 'Note added.', application: present(a) };
}

function destroy(id: number) {
  requireCareers(true);
  findApplication(id);
  data().applications = data().applications.filter((a) => a.id !== id);
  return { message: 'Application deleted successfully!' };
}

function postingRow(p: PostingRow): JobPosting {
  return { ...p, applications_count: data().applications.filter((a) => a.job_posting_id === p.id).length };
}

/** JobPostingController::validatePosting() + preparePostingData() */
function validatePosting(input: JobPostingInput) {
  if (!input.title?.trim()) throw fail('title', 'The title field is required.');
  for (const [key, label] of [
    ['title', 'title'],
    ['employment_type', 'employment type'],
    ['location', 'location'],
  ] as const) {
    if ((input[key] ?? '').length > 255) throw fail(key, `The ${label} field must not be greater than 255 characters.`);
  }
  if (!input.status) throw fail('status', 'The status field is required.');
  if (!['active', 'inactive'].includes(input.status)) throw fail('status', 'The selected status is invalid.');
  return {
    title: input.title,
    employment_type: input.employment_type || null,
    location: input.location || null,
    description: input.description || null,
    requirements: (input.requirements ?? []).map((r) => r.trim()).filter(Boolean),
    status: input.status,
  };
}

function postings() {
  requireCareers();
  return {
    postings: [...data().postings].sort((a, b) => b.created_at.localeCompare(a.created_at)).map(postingRow),
    statuses: { active: 'Active', inactive: 'Inactive' } as Record<'active' | 'inactive', string>,
  };
}

function storePosting(input: JobPostingInput) {
  requireCareers(true);
  const posting: PostingRow = { id: Math.max(0, ...data().postings.map((p) => p.id)) + 1, ...validatePosting(input), created_at: now() };
  data().postings.push(posting);
  return { message: 'Job posting created successfully!', posting: postingRow(posting) };
}

function updatePosting(id: number, input: JobPostingInput) {
  requireCareers(true);
  const posting = data().postings.find((p) => p.id === id);
  if (!posting) throw new ApiError(404, { message: 'Not found.' });
  Object.assign(posting, validatePosting(input));
  return { message: 'Job posting updated successfully!', posting: postingRow(posting) };
}

function destroyPosting(id: number) {
  requireCareers(true);
  if (!data().postings.some((p) => p.id === id)) throw new ApiError(404, { message: 'Not found.' });
  data().postings = data().postings.filter((p) => p.id !== id);
  // Applications keep their job title snapshot.
  for (const a of data().applications) if (a.job_posting_id === id) a.job_posting_id = null;
  return { message: 'Job posting deleted successfully!' };
}

export function createCareersApi(): ApiClient['careers'] {
  return {
    applications: (status) => delay(() => list(status)),
    newCount: () => delay(() => ({ success: true, count: list().new_count })),
    setStatus: (id, status) => delay(() => setStatus(id, status)),
    addNote: (id, body) => delay(() => addNote(id, body)),
    destroy: (id) => delay(() => destroy(id)),
    resume: () => Promise.reject(new ApiError(503, { message: 'Resumes are stored on the server. Connect the app to the API to open one.' })),
    postings: () => delay(postings),
    storePosting: (input) => delay(() => storePosting(input)),
    updatePosting: (id, input) => delay(() => updatePosting(id, input)),
    destroyPosting: (id) => delay(() => destroyPosting(id)),
  };
}
