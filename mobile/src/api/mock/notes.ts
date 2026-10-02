/**
 * Patient-note presentation shared by the mock handlers, plus the PROPOSED
 * note-review endpoints (sign-off / flag). Laravel has no equivalent yet —
 * the supervisor dashboard only lists unsigned and flagged notes — so these
 * define the contract the mobile API must add.
 */

import { canReviewNotes } from '@/auth/permissions';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type { NoteReviewFilter, PatientNote, SignOffResponse } from '../types';
import type { MockDb, PatientNoteRow, UserRow } from './rows';
import { laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser, staffDisplayName, toApiUser } from './server';

const FLAG_REASON_MAX = 255;

function nameOf(db: MockDb, userId: number | null): string | null {
  const user = db.users.find((u) => u.id === userId);
  return user ? staffDisplayName(user) : null;
}

/** PatientNote with `author_name` (and the proposed `signed_off_by_name`). */
export function presentNote(db: MockDb, note: PatientNoteRow): PatientNote {
  return {
    ...note,
    author_name: nameOf(db, note.user_id) ?? 'System',
    signed_off_by_name: nameOf(db, note.signed_off_by),
  };
}

/** presentNote() with `patient.lead` loaded, as the dashboards eager-load it. */
export function presentNoteWithPatient(db: MockDb, note: PatientNoteRow): PatientNote {
  const patient = db.patients.find((p) => p.id === note.patient_id);
  return {
    ...presentNote(db, note),
    patient: patient ? { ...patient, lead: db.leads.find((l) => l.id === patient.lead_id) } : undefined,
  };
}

function requireReviewer(write: boolean): UserRow {
  const user = requireUser();
  requireFeature(user, 'patients', write);
  if (!canReviewNotes(toApiUser(user))) {
    throw new ApiError(403, { message: 'Only the Clinical Supervisor can review session notes.' });
  }
  return user;
}

function findNoteOr404(db: MockDb, id: number): PatientNoteRow {
  const note = db.patientNotes.find((n) => n.id === id);
  if (!note) throw new ApiError(404, { message: 'Not found.' });
  return note;
}

function review(filter: NoteReviewFilter): PatientNote[] {
  requireReviewer(false);
  const db = getDb();
  const notes =
    filter === 'flagged'
      ? db.patientNotes.filter((n) => n.flagged).sort((a, b) => b.created_at.localeCompare(a.created_at))
      : db.patientNotes.filter((n) => n.signed_off_at === null).sort((a, b) => a.created_at.localeCompare(b.created_at));
  return notes.map((n) => presentNoteWithPatient(db, n));
}

function signOff(noteIds: number[]): SignOffResponse {
  const user = requireReviewer(true);
  const db = getDb();

  if (noteIds.length === 0) {
    throw new ApiError(422, { message: 'Select at least one note.', errors: { note_ids: ['Select at least one note.'] } });
  }
  const errors: Record<string, string[]> = {};
  noteIds.forEach((id, i) => {
    if (!db.patientNotes.some((n) => n.id === id)) errors[`note_ids.${i}`] = [`The selected note_ids.${i} is invalid.`];
  });
  if (Object.keys(errors).length > 0) {
    throw new ApiError(422, { message: Object.values(errors)[0][0], errors });
  }

  const stamp = laravelIso(new Date().toISOString());
  let count = 0;
  for (const note of db.patientNotes) {
    if (noteIds.includes(note.id) && note.signed_off_at === null) {
      note.signed_off_at = stamp;
      note.signed_off_by = user.id;
      note.updated_at = stamp;
      count++;
    }
  }
  return { success: true, message: `${count} ${count === 1 ? 'note' : 'notes'} signed off.`, signed_off_count: count };
}

function flag(id: number, reason: string): { success: true; note: PatientNote } {
  requireReviewer(true);
  const db = getDb();
  const note = findNoteOr404(db, id);
  const text = reason.trim();
  if (!text) {
    throw new ApiError(422, {
      message: 'Add a reason for flagging this note.',
      errors: { flag_reason: ['Add a reason for flagging this note.'] },
    });
  }
  if (text.length > FLAG_REASON_MAX) {
    const message = `The flag reason field must not be greater than ${FLAG_REASON_MAX} characters.`;
    throw new ApiError(422, { message, errors: { flag_reason: [message] } });
  }
  note.flagged = true;
  note.flag_reason = text;
  note.updated_at = laravelIso(new Date().toISOString());
  return { success: true, note: presentNoteWithPatient(db, note) };
}

function unflag(id: number): { success: true; note: PatientNote } {
  requireReviewer(true);
  const db = getDb();
  const note = findNoteOr404(db, id);
  note.flagged = false;
  note.flag_reason = null;
  note.updated_at = laravelIso(new Date().toISOString());
  return { success: true, note: presentNoteWithPatient(db, note) };
}

export function createNotesApi(): ApiClient['notes'] {
  return {
    review: (filter) => delay(() => review(filter)),
    signOff: (ids) => delay(() => signOff(ids)),
    flag: (id, reason) => delay(() => flag(id, reason)),
    unflag: (id) => delay(() => unflag(id)),
  };
}
