/**
 * Mock of WhatsappController (index/poll/send/updateAiState/convertToLead)
 * and the WhatsappMessage helpers its Blade partials use. The web answers
 * with HTML fragments and redirects; these return the same data as JSON.
 *
 * MOCK: there is no Meta API, so a sent message moves pending → sent →
 * delivered on timers instead of via webhook status events.
 */

import type { ApiClient } from '../client';
import { ApiError } from '../errors';
import type {
  AiState,
  FamilyDetails,
  InboxContact,
  InboxMessage,
  InboxPoll,
  InboxThread,
  Lead,
  WhatsappContact,
  WhatsappMessage,
} from '../types';
import { blankLead } from './leadDefaults';
import type { MockDb } from './rows';
import { laravelIso } from './seed';
import { delay, getDb, requireFeature, requireUser, staffDisplayName } from './server';

const MESSAGE_MAX = 4096;
const SEND_LIMIT_PER_MINUTE = 20;
const AI_STATES: AiState[] = ['ai_active', 'human_assigned', 'human_takeover', 'closed'];

// ---------------------------------------------------------------------------
// WhatsappMessage helpers
// ---------------------------------------------------------------------------

/** WhatsappMessage::fallbackLabel() */
export function fallbackLabel(type: string): string {
  switch (type) {
    case 'story_mention':
      return '📸 Mentioned you in their story';
    case 'story_reply':
      return '↩️ Replied to your story';
    case 'image':
      return '📷 Photo';
    case 'video':
      return '🎥 Video';
    case 'audio':
      return '🎵 Voice message';
    case 'document':
    case 'file':
      return '📎 File';
    case 'sticker':
      return 'Sticker';
    case 'like':
      return '👍';
    case 'location':
      return '📍 Location shared';
    case 'contacts':
      return '👤 Contact shared';
    case 'shared_post':
      return '🔗 Shared a post';
    case 'unsupported_type':
      return '⚠️ Unsupported message — check Instagram/WhatsApp directly';
    default:
      return 'Message unavailable';
  }
}

/** WhatsappMessage::statusLabel() */
function statusLabel(status: string | null): string {
  switch (status) {
    case 'pending':
      return 'Sending';
    case 'sent':
      return 'Sent';
    case 'delivered':
      return 'Delivered';
    case 'read':
      return 'Read';
    case 'failed':
      return 'Not sent';
    default:
      return '';
  }
}

/** WhatsappMessage::responderLabel(): "AI", the sender's name, or null (outbound only). */
function responderLabel(db: MockDb, m: WhatsappMessage): string | null {
  if (m.direction !== 'outbound') return null;
  if (m.is_ai_generated) return 'AI';
  const sender = db.users.find((u) => u.id === m.sent_by_user_id);
  return sender ? staffDisplayName(sender) : null;
}

function presentMessage(db: MockDb, m: WhatsappMessage): InboxMessage {
  return {
    ...m,
    display_body: m.body ?? fallbackLabel(m.type),
    responder_label: responderLabel(db, m),
    status_label: statusLabel(m.status),
  };
}

function messagesOf(db: MockDb, contactId: number): WhatsappMessage[] {
  return db.whatsappMessages
    .filter((m) => m.whatsapp_contact_id === contactId)
    .sort((a, b) => a.sent_at.localeCompare(b.sent_at) || a.id - b.id);
}

function presentContact(db: MockDb, c: WhatsappContact): InboxContact {
  const latest = messagesOf(db, c.id).at(-1);
  return { ...c, last_responder: latest ? responderLabel(db, latest) : null };
}

function allContacts(db: MockDb): InboxContact[] {
  return [...db.whatsappContacts]
    .sort((a, b) => (b.last_message_at ?? '').localeCompare(a.last_message_at ?? ''))
    .map((c) => presentContact(db, c));
}

function findContactOr404(db: MockDb, id: number): WhatsappContact {
  const contact = db.whatsappContacts.find((c) => c.id === id);
  if (!contact) throw new ApiError(404, { message: 'Not found.' });
  return contact;
}

// ---------------------------------------------------------------------------
// WhatsappController::extractLeadHints()
// ---------------------------------------------------------------------------

const NAME_PATTERNS = [
  /\bmy\s+(?:son|daughter|child|kid)\s+([A-Z][A-Za-z'-]+)\s+is\s+(\d{1,2})\b/i,
  /\bmy\s+(?:son|daughter|child|kid)(?:'s)?\s+name\s+is\s+([A-Z][A-Za-z'-]+)\b/i,
  /\b(?:his|her)\s+name\s+is\s+([A-Z][A-Za-z'-]+)\b/i,
  /\bmy\s+(?:son|daughter|child|kid),?\s+([A-Z][A-Za-z'-]+),/i,
];

const INSURERS = [
  'Daman Enhanced', 'Daman', 'Thiqa', 'ADNIC', 'AXA', 'Bupa', 'Cigna', 'MetLife', 'NextCare',
  'Oman Insurance', 'Al Madallah', 'Almadallah', 'Saico', 'Orient Insurance',
  'Union Insurance', 'National Health Insurance', 'Neuron',
];

/** Most specific phrase first, as in Laravel. */
const SERVICES: [string, string][] = [
  ['early intervention', 'Early intervention'],
  ['diagnostic assessment', 'Diagnostic assessment'],
  ['combined program', 'Combined program'],
  ['occupational therapy', 'Occupational therapy'],
  ['speech therapy', 'Speech therapy'],
  ['speech', 'Speech therapy'],
  ['aba therapy', 'ABA therapy'],
  ['aba', 'ABA therapy'],
  ['parent training', 'Parent training'],
  ['assessment', 'Diagnostic assessment'],
  [' ot ', 'Occupational therapy'],
];

function extractLeadHints(messages: WhatsappMessage[]) {
  const text = messages
    .filter((m) => m.direction === 'inbound' && m.body)
    .map((m) => m.body)
    .join(' . ');
  const lower = text.toLowerCase();

  let childName: string | null = null;
  let childAge: number | null = null;
  for (const pattern of NAME_PATTERNS) {
    const m = text.match(pattern);
    if (m) {
      childName = m[1];
      childAge = m[2] ? Number(m[2]) : null;
      break;
    }
  }
  if (childAge === null) {
    const m = text.match(/\b(?:he|she)\s+is\s+(\d{1,2})\b/i);
    if (m) childAge = Number(m[1]);
  }

  const insurance = INSURERS.find((i) => lower.includes(i.toLowerCase())) ?? null;
  const service = SERVICES.find(([needle]) => lower.includes(needle))?.[1] ?? null;
  const hoursMatch = text.match(/(\d{1,3})\s*(?:hours?|hrs?|h)\b(?:\s*(?:\/|per)\s*week)?/i);
  const hours = hoursMatch ? `${hoursMatch[1]}h/week` : null;
  const interestedIn = [service, hours].filter(Boolean).join(' ').trim() || null;

  return { child_name: childName, child_age: childAge, interested_in: interestedIn, insurance };
}

function ucfirst(v: string): string {
  return v.charAt(0).toUpperCase() + v.slice(1);
}

/** The "Family details" panel: the linked lead's data, else contact overrides / hints. */
function familyDetails(db: MockDb, contact: WhatsappContact): FamilyDetails {
  const lead = db.leads.find((l) => l.id === contact.lead_id);
  if (lead) {
    return {
      child_name: lead.child_name,
      child_age: lead.child_age,
      interested_in: lead.interested_in,
      source: lead.source,
      first_contact_at: lead.created_at,
      insurance: lead.insurance,
      in_pipeline: true,
    };
  }
  const hints = extractLeadHints(messagesOf(db, contact.id));
  return {
    child_name: contact.child_name ?? hints.child_name,
    child_age: hints.child_age !== null ? String(hints.child_age) : null,
    interested_in: contact.interested_in ?? hints.interested_in,
    source: ucfirst(contact.channel),
    first_contact_at: contact.created_at,
    insurance: contact.insurance ?? hints.insurance,
    in_pipeline: false,
  };
}

// ---------------------------------------------------------------------------
// Handlers
// ---------------------------------------------------------------------------

function list(): InboxContact[] {
  const user = requireUser();
  requireFeature(user, 'whatsapp');
  return allContacts(getDb());
}

function thread(contactId: number): InboxThread {
  const user = requireUser();
  requireFeature(user, 'whatsapp');
  const db = getDb();
  const contact = findContactOr404(db, contactId);
  // Opening a conversation marks it read.
  contact.unread_count = 0;
  return {
    contact: presentContact(db, contact),
    messages: messagesOf(db, contact.id).map((m) => presentMessage(db, m)),
    family: familyDetails(db, contact),
  };
}

function poll(contactId: number | null, after: number): InboxPoll {
  const user = requireUser();
  requireFeature(user, 'whatsapp');
  const db = getDb();

  let fresh: WhatsappMessage[] = [];
  let statuses: InboxPoll['statuses'] = [];
  if (contactId !== null) {
    const contact = db.whatsappContacts.find((c) => c.id === contactId);
    if (contact) {
      const all = messagesOf(db, contact.id);
      fresh = all.filter((m) => m.id > after);
      if (fresh.length > 0 && contact.unread_count > 0) contact.unread_count = 0;
      statuses = all
        .filter((m) => m.direction === 'outbound')
        .sort((a, b) => b.id - a.id)
        .slice(0, 30)
        .map((m) => ({ id: m.id, status: m.status, label: statusLabel(m.status), error: m.send_error }));
    }
  }

  return {
    contacts: allContacts(db),
    messages: fresh.map((m) => presentMessage(db, m)),
    latest_message_id: fresh.length > 0 ? Math.max(...fresh.map((m) => m.id)) : after,
    statuses,
  };
}

/** throttle:20,1 on POST /whatsapp/send (per user). */
const sendTimes = new Map<number, number[]>();

function send(contactId: number, message: string): { message: InboxMessage; latest_message_id: number } {
  const user = requireUser();
  requireFeature(user, 'whatsapp', true);
  const db = getDb();

  const now = Date.now();
  const recent = (sendTimes.get(user.id) ?? []).filter((t) => now - t < 60_000);
  if (recent.length >= SEND_LIMIT_PER_MINUTE) {
    throw new ApiError(429, { message: 'Too Many Attempts.' });
  }

  const contact = findContactOr404(db, contactId);
  const body = message.trim();
  if (!body) {
    throw new ApiError(422, { message: 'The message field is required.', errors: { message: ['The message field is required.'] } });
  }
  if (body.length > MESSAGE_MAX) {
    const msg = `The message field must not be greater than ${MESSAGE_MAX} characters.`;
    throw new ApiError(422, { message: msg, errors: { message: [msg] } });
  }
  sendTimes.set(user.id, [...recent, now]);

  const stamp = laravelIso(new Date(now).toISOString());
  const row: WhatsappMessage = {
    id: Math.max(0, ...db.whatsappMessages.map((m) => m.id)) + 1,
    whatsapp_contact_id: contact.id,
    wa_message_id: null,
    direction: 'outbound',
    type: 'text',
    sticker_id: null,
    media_url: null,
    body,
    status: 'pending',
    send_error: null,
    is_ai_generated: false,
    sent_by_user_id: user.id,
    ai_processing_status: null,
    triggered_by_message_id: null,
    ai_error: null,
    voice_call_session_id: null,
    sent_at: stamp,
    created_at: stamp,
    updated_at: stamp,
  };
  db.whatsappMessages.push(row);
  // The AI state is NOT changed by a manual reply, and the attention flag stays (as on the web).
  contact.last_message_preview = body.slice(0, 255);
  contact.last_message_at = stamp;
  contact.updated_at = stamp;

  // MOCK: stand-in for the provider call + webhook status events.
  setTimeout(() => {
    row.status = 'sent';
    row.wa_message_id = `mock_${row.id}`;
  }, 1500);
  setTimeout(() => {
    if (row.status === 'sent') row.status = 'delivered';
  }, 4000);

  return { message: presentMessage(db, row), latest_message_id: row.id };
}

function setAiState(contactId: number, state: AiState): { contact: InboxContact } {
  const user = requireUser();
  requireFeature(user, 'whatsapp', true);
  const db = getDb();
  const contact = findContactOr404(db, contactId);
  if (!AI_STATES.includes(state)) {
    const msg = 'The selected ai state is invalid.';
    throw new ApiError(422, { message: msg, errors: { ai_state: [msg] } });
  }
  const stamp = laravelIso(new Date().toISOString());
  contact.ai_state = state;
  contact.ai_state_changed_by = user.id;
  contact.ai_state_changed_at = stamp;
  // Any state change clears the attention flag (the reason text is kept, as on the web).
  contact.needs_human_attention = false;
  contact.updated_at = stamp;
  return { contact: presentContact(db, contact) };
}

function convertToLead(contactId: number): { contact: InboxContact; lead: Lead } {
  const user = requireUser();
  requireFeature(user, 'whatsapp', true);
  const db = getDb();
  const contact = findContactOr404(db, contactId);

  const existing = db.leads.find((l) => l.id === contact.lead_id);
  if (existing) return { contact: presentContact(db, contact), lead: existing };

  const hints = extractLeadHints(messagesOf(db, contact.id));
  const stamp = laravelIso(new Date().toISOString());
  const lead: Lead = blankLead({
    id: Math.max(0, ...db.leads.map((l) => l.id)) + 1,
    child_name: contact.child_name || hints.child_name,
    child_age: hints.child_age !== null ? String(hints.child_age) : null,
    parent_guardian_name: contact.name,
    phone: contact.channel === 'whatsapp' ? contact.wa_id : null,
    // Laravel writes ucfirst(channel): "Whatsapp", not the board's "WhatsApp" (known issue).
    source: ucfirst(contact.channel),
    interested_in: contact.interested_in || hints.interested_in,
    insurance: contact.insurance || hints.insurance,
    status: 'new',
    created_at: stamp,
  });
  db.leads.push(lead);
  contact.lead_id = lead.id;
  contact.updated_at = stamp;
  return { contact: presentContact(db, contact), lead };
}

export function createInboxApi(): ApiClient['inbox'] {
  return {
    list: () => delay(list),
    thread: (id) => delay(() => thread(id)),
    poll: (id, after) => delay(() => poll(id, after)),
    send: (id, message) => delay(() => send(id, message)),
    setAiState: (id, state) => delay(() => setAiState(id, state)),
    convertToLead: (id) => delay(() => convertToLead(id)),
  };
}
