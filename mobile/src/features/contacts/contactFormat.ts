import type { Contact, ContactStatus } from '@/api/types';
import { formatDayMonthYearShort, isoToYmd } from '@/utils/dates';

/** Filter order (Contact::getStatuses()). */
export const STATUS_ORDER: ContactStatus[] = ['new', 'approved', 'rejected', 'contacted', 'converted', 'closed'];

/** Clinic details used in the decision email (contact/index.blade.php `CT_CLINIC_*`). */
const CLINIC_NAME = 'Engage Behavior Clinic';
const CLINIC_PHONE = '+971 50 884 6801';
const CLINIC_EMAIL = 'info@engagebehavior.com';
const CLINIC_ADDRESS = 'Office No. 1203, ADCP Commercial Tower-C, Electra Street, Abu Dhabi, UAE';

export function hasSlot(contact: Pick<Contact, 'booking_date' | 'booking_time'>): boolean {
  return contact.booking_date !== null && contact.booking_time !== null;
}

/** "6 Oct 2026 · 10:00 AM", or null when no slot was requested. */
export function slotLabel(contact: Pick<Contact, 'booking_date' | 'booking_time'>): string | null {
  if (!contact.booking_date || !contact.booking_time) return null;
  return `${formatDayMonthYearShort(isoToYmd(contact.booking_date))} · ${contact.booking_time}`;
}

/** "Khalifa · Age 5 · ABA therapy · Daman" line on a submission card. */
export function enquiryLine(contact: Contact): string {
  return [
    contact.child_name || 'Name not provided',
    contact.child_age ? `Age ${contact.child_age}` : null,
    contact.interested_in || 'No service selected',
    contact.insurance,
  ]
    .filter(Boolean)
    .join(' · ');
}

/** The pre-filled approval / rejection email (openEmailModalFor() on the web). */
export function decisionEmail(contact: Contact): { to: string; subject: string; message: string; kind: string } {
  const approved = contact.status === 'approved';
  const slot =
    contact.booking_date && contact.booking_time
      ? `${formatDayMonthYearShort(isoToYmd(contact.booking_date))} at ${contact.booking_time}`
      : 'your requested slot';
  const name = contact.name || 'parent';
  const signOff = `Call Us: ${CLINIC_PHONE}\nEmail: ${CLINIC_EMAIL}\nVisit Us: ${CLINIC_ADDRESS}\n\nWarm regards,\n${CLINIC_NAME}`;

  return {
    to: contact.email ?? '',
    kind: approved ? 'approval' : 'rejection',
    subject: approved
      ? `Your free consultation with ${CLINIC_NAME} is confirmed`
      : `About your consultation request with ${CLINIC_NAME}`,
    message: approved
      ? `Dear ${name},\n\nThank you for choosing ${CLINIC_NAME}. Your free 30-minute consultation has been confirmed for ${slot}.\n\nOur team looks forward to meeting with you and learning more about your child's needs, so we can better understand how ${CLINIC_NAME} may support your family.\n\nIf you need to make any changes to your consultation, simply reply to this email and our team will be happy to assist.\n\n${signOff}`
      : `Dear ${name},\n\nThank you for your interest in ${CLINIC_NAME} and for taking the time to request a free consultation.\n\nUnfortunately, we're unable to confirm the consultation slot you requested (${slot}). This may simply be due to limited availability at that time, but we'd still love the opportunity to support your family.\n\nPlease reply to this email or reach out to us directly, and our team will be happy to help you find another time that works.\n\n${signOff}`,
  };
}
