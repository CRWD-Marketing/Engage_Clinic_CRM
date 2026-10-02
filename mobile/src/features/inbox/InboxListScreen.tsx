import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { InboxContact } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Card, Divider } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { usePolling } from '@/hooks/usePolling';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, fonts, inboxColors, radius, spacing } from '@/theme';

import { InboxAvatar } from './InboxAvatar';
import { AI_STATE_LABELS, contactName, shortSince } from './inboxFormat';

type ChannelFilter = 'all' | 'whatsapp' | 'instagram' | 'facebook';
const FILTERS: { key: ChannelFilter; label: string }[] = [
  { key: 'all', label: 'All' },
  { key: 'whatsapp', label: 'WhatsApp' },
  { key: 'instagram', label: 'Instagram' },
  { key: 'facebook', label: 'Facebook' },
];

/**
 * Mirrors whatsapp/index.blade.php's conversation list: newest activity
 * first, client-side search and channel pills, refreshed every 4 seconds
 * like the web's poll.
 */
export function InboxListScreen() {
  const query = useApiQuery('inbox.list', () => api.inbox.list());
  useRefetchOnFocus(query.reload);
  const [polled, setPolled] = useState<InboxContact[] | null>(null);
  usePolling(async () => setPolled((await api.inbox.poll(null, 0)).contacts), 4000);

  const [search, setSearch] = useState('');
  const [channel, setChannel] = useState<ChannelFilter>('all');

  const contacts = polled ?? query.data;
  const term = search.trim().toLowerCase();
  const visible = (contacts ?? []).filter(
    (c) =>
      (channel === 'all' || c.channel === channel) &&
      (!term || `${c.name ?? ''} ${c.wa_id} ${c.last_message_preview ?? ''}`.toLowerCase().includes(term)),
  );

  return (
    <Screen refreshing={query.refreshing} onRefresh={() => { setPolled(null); query.refresh(); }}>
      <View style={styles.header}>
        <AppText variant="title">Messages</AppText>
        <AppText variant="caption">WhatsApp + Instagram · leads auto-captured</AppText>
      </View>

      {!contacts ? (
        query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />
      ) : contacts.length === 0 ? (
        <Card style={styles.empty}>
          <AppText variant="heading">No conversations yet</AppText>
          <AppText variant="caption">
            Messages sent to your WhatsApp Business number will show up here automatically.
          </AppText>
        </Card>
      ) : (
        <>
          <TextField
            label="Search"
            icon="search-outline"
            value={search}
            onChangeText={setSearch}
            placeholder="Search conversations…"
            autoCapitalize="none"
            autoCorrect={false}
          />
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.pills}>
            {FILTERS.map((f) => (
              <Pressable
                key={f.key}
                onPress={() => setChannel(f.key)}
                accessibilityRole="button"
                accessibilityState={{ selected: channel === f.key }}
                style={[styles.pill, channel === f.key && styles.pillActive]}>
                <AppText style={[styles.pillText, channel === f.key && styles.pillTextActive]}>{f.label}</AppText>
              </Pressable>
            ))}
          </ScrollView>

          <Card padded={false}>
            {visible.length === 0 ? (
              <EmptyRow text="No conversations match." />
            ) : (
              visible.map((c, i) => (
                <View key={c.id}>
                  {i > 0 ? <Divider /> : null}
                  <ContactRow contact={c} />
                </View>
              ))
            )}
          </Card>
        </>
      )}
    </Screen>
  );
}

function ContactRow({ contact: c }: { contact: InboxContact }) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/inbox/[id]', params: { id: String(c.id) } })}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}
      accessibilityRole="button"
      accessibilityLabel={`${contactName(c)}${c.unread_count ? `, ${c.unread_count} unread` : ''}${
        c.needs_human_attention ? ', needs attention' : ''
      }`}>
      <InboxAvatar contact={c} />
      <View style={styles.rowMain}>
        <View style={styles.rowTop}>
          <AppText variant="bodyStrong" numberOfLines={1} style={styles.flex}>
            {contactName(c)}
          </AppText>
          {c.needs_human_attention ? <View style={styles.attentionDot} /> : null}
          <AppText style={styles.time}>{shortSince(c.last_message_at)}</AppText>
        </View>
        <View style={styles.rowBottom}>
          {c.ai_state !== 'ai_active' ? <MiniChip label={AI_STATE_LABELS[c.ai_state]} colors={inboxColors.aiChip} /> : null}
          {c.last_responder === 'AI' ? (
            <MiniChip label="🤖 AI" colors={inboxColors.aiChip} />
          ) : c.last_responder ? (
            <MiniChip label={c.last_responder} colors={inboxColors.staffChip} />
          ) : null}
          <AppText variant="caption" numberOfLines={1} style={styles.flex}>
            {c.last_message_preview}
          </AppText>
        </View>
      </View>
      {c.unread_count > 0 ? (
        <View style={styles.unread}>
          <AppText style={styles.unreadText}>{c.unread_count}</AppText>
        </View>
      ) : null}
    </Pressable>
  );
}

export function MiniChip({ label, colors: c }: { label: string; colors: { bg: string; fg: string } }) {
  return (
    <View style={[styles.miniChip, { backgroundColor: c.bg }]}>
      <AppText style={[styles.miniChipText, { color: c.fg }]} numberOfLines={1}>
        {label}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  header: { gap: 2, marginBottom: spacing.xs },
  empty: { gap: spacing.xs },
  pills: { gap: 6 },
  pill: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: 14,
    paddingVertical: 7,
  },
  pillActive: { backgroundColor: colors.navy, borderColor: colors.navy },
  pillText: { fontFamily: fonts.bodyBold, fontSize: 12.5, color: colors.textSecondary },
  pillTextActive: { color: colors.white },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    minHeight: 68,
  },
  pressed: { backgroundColor: colors.pageAlt },
  rowMain: { flex: 1, minWidth: 0, gap: 3 },
  rowTop: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  rowBottom: { flexDirection: 'row', alignItems: 'center', gap: 5 },
  attentionDot: { width: 7, height: 7, borderRadius: 4, backgroundColor: inboxColors.attention },
  time: { fontFamily: fonts.bodySemiBold, fontSize: 10.5, color: colors.textFaint },
  miniChip: { borderRadius: 5, paddingHorizontal: 5, paddingVertical: 1, maxWidth: 120 },
  miniChipText: { fontFamily: fonts.bodyExtraBold, fontSize: 9.5 },
  unread: { backgroundColor: inboxColors.whatsappGreen, borderRadius: 9, paddingHorizontal: 7, paddingVertical: 1 },
  unreadText: { fontFamily: fonts.bodyExtraBold, fontSize: 11, color: colors.white },
});
