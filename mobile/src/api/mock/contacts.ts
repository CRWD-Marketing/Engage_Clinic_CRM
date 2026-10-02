/**
 * Mock of ContactController (`feature:contacts`): website contact-form and
 * booking-widget submissions, the approve/reject decision, the decision
 * email and conversion into a lead.
 */

import { todayYmd, weekdayIndex } from '@/utils/dates';

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  ContactEmailRequest,
  ContactItem,
  ContactsIndex,
  ContactSlotRequest,
  ContactStatus,
} from '../types';
import { blankLead } from './leadDefaults';
import type { ContactRow, MockDb } from './rows';
import { dateCast, laravelIso } from './seed';
import { delay, EMAIL_PATTERN, getDb, requireFeature, requireUser, validationError } from './server';

/** Contact::CONSULTATION_TIMES */
export const CONSULTATION_TIMES = [
  '9:00 AM', '9:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM',
  '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM', '3:00 PM', '3:30 PM', '4:00 PM', '4:30 PM',
];

/** Contact::getStatuses() */
const STATUSES: Record<ContactStatus, string> = {
  new: 'New',
  approved: 'Approved',
  rejected: 'Rejected',
  contacted: 'Contacted',
  converted: 'Converted',
  closed: 'Closed',
};

function now(): string {
  return laravelIso(new Date().toISOString());
}

/** Contact::isSlotEditable() */
function isSlotEditable(c: ContactRow): boolean {
  return c.status === 'new' || c.status === 'approved' || c.status === 'rejected';
}

/** Contact::canSendStatusEmail() */
function canSendStatusEmail(c: ContactRow): boolean {
  return c.status === 'approved' || c.status === 'rejected';
}

/** Contact::canConvertToLead() */
function canConvertToLead(c: ContactRow): boolean {
  return (c.status === 'approved' || c.status === 'contacted') && !c.converted_lead_id;
}

function present(c: ContactRow): ContactItem {
  return {
    ...c,
    can_edit_slot: isSlotEditable(c),
    can_send_email: canSendStatusEmail(c),
    can_convert: canConvertToLead(c),
  };
}

function findOr404(db: MockDb, id: number): ContactRow {
  const contact = db.contacts.find((c) => c.id === id);
  if (!contact) throw new ApiError(404, { message: 'Contact not found.' });
  return contact;
}

/** GET /admin/contacts — newest first; the status filter is applied on the client. */
function index(): ContactsIndex {
  const user = requireUser();
  requireFeature(user, 'contacts');
  const contacts = [...getDb().contacts].sort((a, b) => b.created_at.localeCompare(a.created_at));
  return {
    contacts: contacts.map(present),
    statuses: STATUSES,
    new_count: contacts.filter((c) => c.status === 'new').length,
    consultation_times: CONSULTATION_TIMES,
  };
}

/** GET /admin/contacts/count */
function count(): { success: true; count: number } {
  const user = requireUser();
  requireFeature(user, 'contacts');
  return { success: true, count: getDb().contacts.filter((c) => c.status === 'new').length };
}

/** PATCH /admin/contacts/{contact} — the app only sends the consultation slot. */
function updateSlot(id: number, input: ContactSlotRequest) {
  const user = requireUser();
  requireFeature(user, 'contacts', true);
  const contact = findOr404(getDb(), id);
  const date = input.booking_date || null;
  const time = input.booking_time || null;

  if (date && !/^\d{4}-\d{2}-\d{2}$/.test(date)) throw validationError('booking_date', 'The booking date field must be a valid date.');
  if (date && date < todayYmd()) throw validationError('booking_date', 'Pick today or a later date.');
  if (date && !time) throw validationError('booking_time', 'Choose a time for the consultation.');
  if (time && !CONSULTATION_TIMES.includes(time)) throw validationError('booking_time', 'Choose one of the consultation times.');
  if (!isSlotEditable(contact)) {
    throw validationError('booking_date', 'The decision has already been emailed, so this slot can no longer be changed.');
  }
  // Carbon FRIDAY / SATURDAY (weekdayIndex is Monday-based).
  if (date && [4, 5].includes(weekdayIndex(date))) {
    throw validationError('booking_date', 'The clinic is closed on Fridays and Saturdays.');
  }

  contact.booking_date = date ? dateCast(date) : null;
  // Clearing the date clears the slot.
  contact.booking_time = date ? time : null;
  contact.updated_at = now();
  return { success: true as const, message: 'Contact updated successfully!', contact: present(contact) };
}

/** PATCH /admin/contacts/{contact}/status — every status except "converted". */
function updateStatus(id: number, status: ContactStatus): ContactItem {
  const user = requireUser();
  requireFeature(user, 'contacts', true);
  const contact = findOr404(getDb(), id);
  if (!(status in STATUSES) || status === 'converted') throw validationError('status', 'The selected status is invalid.');

  contact.status = status;
  // Kept separately so the outcome survives the later move to "contacted".
  if (status === 'approved' || status === 'rejected') contact.booking_decision = status;
  contact.updated_at = now();
  return present(contact);
}

/** POST /admin/contacts/{contact}/send-email — sending is what moves the status to "contacted". */
function sendEmail(id: number, input: ContactEmailRequest) {
  const user = requireUser();
  requireFeature(user, 'contacts', true);
  const contact = findOr404(getDb(), id);
  if (!canSendStatusEmail(contact)) {
    throw new ApiError(422, { message: 'Approve or reject this booking before emailing the family.' });
  }

  const to = input.to.trim();
  if (!to) throw validationError('to', 'The to field is required.');
  if (!EMAIL_PATTERN.test(to)) throw validationError('to', 'The to field must be a valid email address.');
  if (!input.subject.trim()) throw validationError('subject', 'The subject field is required.');
  if (input.subject.length > 200) throw validationError('subject', 'The subject field must not be greater than 200 characters.');
  if (!input.message.trim()) throw validationError('message', 'The message field is required.');
  if (input.message.length > 5000) throw validationError('message', 'The message field must not be greater than 5000 characters.');

  contact.status = 'contacted';
  contact.status_email_sent_at = now();
  contact.updated_at = now();
  return { message: `Decision emailed to ${to}.`, contact: present(contact) };
}

/** POST /admin/contacts/{contact}/convert-to-lead */
function convertToLead(id: number): ContactItem {
  const user = requireUser();
  requireFeature(user, 'contacts', true);
  if (user.role === 'COORDINATOR') {
    throw new ApiError(403, { message: 'Coordinators cannot convert contacts to leads.' });
  }
  const db = getDb();
  const contact = findOr404(db, id);
  // Not eligible: Laravel just redirects back without changing anything.
  if (!canConvertToLead(contact)) return present(contact);

  const stamp = now();
  const lead = blankLead({
    id: Math.max(0, ...db.leads.map((l) => l.id)) + 1,
    created_at: stamp,
    updated_at: stamp,
    child_name: contact.child_name,
    child_age: contact.child_age,
    parent_guardian_name: contact.name,
    phone: contact.phone,
    email: contact.email,
    source: 'Contact Us',
    interested_in: contact.interested_in,
    insurance: contact.insurance,
    notes: contact.message || '',
  });
  db.leads.push(lead);

  contact.status = 'converted';
  contact.converted_lead_id = lead.id;
  contact.converted_at = stamp;
  contact.updated_at = stamp;
  return present(contact);
}

/** DELETE /admin/contacts/{contact} */
function destroy(id: number): { message: string } {
  const user = requireUser();
  requireFeature(user, 'contacts', true);
  if (user.role === 'COORDINATOR') {
    throw new ApiError(403, { message: 'Coordinators cannot delete contact submissions.' });
  }
  const db = getDb();
  const contact = findOr404(db, id);
  db.contacts.splice(db.contacts.indexOf(contact), 1);
  return { message: 'Contact deleted successfully!' };
}

export function createContactsApi(): ApiClient['contacts'] {
  return {
    list: () => delay(index),
    newCount: () => delay(count),
    updateSlot: (id, input) => delay(() => updateSlot(id, input)),
    updateStatus: (id, status) => delay(() => updateStatus(id, status)),
    sendEmail: (id, input) => delay(() => sendEmail(id, input)),
    convertToLead: (id) => delay(() => convertToLead(id)),
    destroy: (id) => delay(() => destroy(id)),
  };
}
