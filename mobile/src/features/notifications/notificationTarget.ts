import type { Href } from 'expo-router';

import type { NotificationItem } from '@/api/types';

/**
 * Where a bell item opens in the app. The server sends a web URL; the item's
 * id (lead:12, contact:7, whatsapp:3:…) or that URL's query (?session=,
 * ?lead=) says which record it is about.
 */
export function notificationTarget(item: Pick<NotificationItem, 'id' | 'url'>): Href | null {
  const [kind, id] = item.id.split(':');
  if (kind === 'lead' && id) return { pathname: '/leads/[id]', params: { id } };
  if (kind === 'contact' && id) return { pathname: '/contacts/[id]', params: { id } };
  if (kind === 'whatsapp' && id) return { pathname: '/inbox/[id]', params: { id } };

  const query = (name: string) => new RegExp(`[?&]${name}=(\\d+)`).exec(item.url ?? '')?.[1];
  const session = query('session');
  if (session) return { pathname: '/calendar/[id]', params: { id: session } };
  const lead = query('lead');
  if (lead) return { pathname: '/leads/[id]', params: { id: lead } };
  return null;
}
