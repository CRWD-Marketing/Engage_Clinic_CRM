import type { AiState, InboxContact, InboxMessage } from '@/api/types';
import { inboxColors } from '@/theme';
import { addDays, formatClock12, isoToYmd, timeAgo, todayYmd, formatDayMonthYearShort } from '@/utils/dates';
import { crc32 } from '@/utils/format';

/** WhatsappContact::aiStateLabels() */
export const AI_STATE_LABELS: Record<AiState, string> = {
  ai_active: 'AI Active',
  human_assigned: 'Human Assigned',
  human_takeover: 'Human Takeover',
  closed: 'Closed',
};
export const AI_STATES: AiState[] = ['ai_active', 'human_assigned', 'human_takeover', 'closed'];

/** WhatsappMessage::DATE_DIVIDER_GAP_MINUTES */
const DIVIDER_GAP_MINUTES = 180;

export function contactName(c: Pick<InboxContact, 'name' | 'wa_id'>): string {
  return c.name ?? c.wa_id;
}

/** WhatsappContact::avatarColor(wa_id) */
export function inboxAvatarColor(waId: string): string {
  return inboxColors.avatarPalette[crc32(waId) % inboxColors.avatarPalette.length];
}

/** First two characters of the name (or wa_id), upper-cased. */
export function inboxInitials(c: Pick<InboxContact, 'name' | 'wa_id'>): string {
  return contactName(c).slice(0, 2).toUpperCase();
}

/** Thread header subtitle by channel. */
export function channelSubtitle(c: InboxContact): string {
  if (c.channel === 'instagram') return `@${c.name ?? 'Instagram DM'} · Instagram`;
  if (c.channel === 'facebook') return `${c.name ?? 'Facebook Messenger'} · Facebook`;
  return `+${c.wa_id} · WhatsApp`;
}

/** diffForHumans(null, true): "5 minutes", without "ago". */
export function shortSince(iso: string | null): string {
  if (!iso) return '';
  const label = timeAgo(iso);
  return label === 'just now' ? 'now' : label.replace(/ ago$/, '');
}

/** WhatsappMessage::dateDividerLabel(): "Today, 5:57 PM" / "Yesterday, …" / "Sep 28, 2026, …". */
export function dividerLabel(iso: string): string {
  const day = isoToYmd(iso);
  const time = formatClock12(iso);
  const today = todayYmd();
  if (day === today) return `Today, ${time}`;
  if (day === addDays(today, -1)) return `Yesterday, ${time}`;
  const [d, mon, y] = formatDayMonthYearShort(day).split(' ');
  return `${mon} ${d}, ${y}, ${time}`;
}

/** A divider goes before the first message, on a new day, or after a 3-hour gap. */
export function needsDivider(prev: InboxMessage | undefined, cur: InboxMessage): boolean {
  if (!prev) return true;
  if (isoToYmd(prev.sent_at) !== isoToYmd(cur.sent_at)) return true;
  return new Date(cur.sent_at).getTime() - new Date(prev.sent_at).getTime() >= DIVIDER_GAP_MINUTES * 60_000;
}
