import Ionicons from '@expo/vector-icons/Ionicons';
import { Stack, useLocalSearchParams } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { AiState, FamilyDetails, InboxContact, InboxMessage, InboxThread } from '@/api/types';
import { canWriteIn } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { usePolling } from '@/hooks/usePolling';
import { colors, fonts, inboxColors, radius, spacing } from '@/theme';
import { formatClockTime, formatDateTimeShort } from '@/utils/dates';

import { MiniChip } from './InboxListScreen';
import { AI_STATE_LABELS, AI_STATES, channelSubtitle, contactName, dividerLabel, needsDivider } from './inboxFormat';

const MESSAGE_MAX = 4096;

/**
 * One conversation (whatsapp/index.blade.php with ?contact=): header with
 * channel, attention flag and AI state; read-only family details with
 * "Convert to Lead"; messages with date dividers; reply composer. Polls
 * every 4 seconds for new messages and delivery status, like the web.
 */
export function ConversationScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const contactId = Number(id);
  const user = useCurrentUser();
  const canWrite = canWriteIn(user, 'whatsapp');

  const query = useApiQuery(`inbox.thread:${id}`, () => api.inbox.thread(contactId));
  // Live copy of the thread, advanced by polls, sends and state changes.
  const [live, setLive] = useState<InboxThread | null>(null);
  const thread = live ?? query.data ?? null;

  const [draft, setDraft] = useState('');
  const [sendError, setSendError] = useState<string | null>(null);
  const [showFamily, setShowFamily] = useState(false);
  const scrollRef = useRef<ScrollView>(null);

  const update = (fn: (t: InboxThread) => InboxThread) => setLive((cur) => (cur ?? query.data ? fn((cur ?? query.data)!) : cur));

  const lastId = thread?.messages.reduce((max, m) => Math.max(max, m.id), 0) ?? 0;

  usePolling(async () => {
    if (!thread) return;
    const res = await api.inbox.poll(contactId, lastId);
    update((t) => {
      const known = new Set(t.messages.map((m) => m.id));
      const statuses = new Map(res.statuses.map((s) => [s.id, s]));
      const messages = [...t.messages, ...res.messages.filter((m) => !known.has(m.id))].map((m) => {
        const s = statuses.get(m.id);
        return s ? { ...m, status: s.status, status_label: s.label, send_error: s.error } : m;
      });
      const contact = res.contacts.find((c) => c.id === contactId) ?? t.contact;
      return { ...t, messages, contact };
    });
  }, 4000);

  // Keep the newest message in view when the thread loads or grows.
  const count = thread?.messages.length ?? 0;
  useEffect(() => {
    const t = setTimeout(() => scrollRef.current?.scrollToEnd({ animated: count > 0 }), 50);
    return () => clearTimeout(t);
  }, [count]);

  async function send() {
    const body = draft.trim();
    if (!body) return;
    setDraft(''); // the web clears the composer straight away
    setSendError(null);
    try {
      const res = await api.inbox.send(contactId, body);
      update((t) => ({
        ...t,
        messages: t.messages.some((m) => m.id === res.message.id) ? t.messages : [...t.messages, res.message],
        contact: { ...t.contact, last_message_preview: body, last_message_at: res.message.sent_at, last_responder: res.message.responder_label },
      }));
    } catch (e) {
      setSendError(errorMessage(e) || 'Failed to send message.');
    }
  }

  if (!thread) {
    return (
      <View style={styles.page}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </View>
    );
  }

  const c = thread.contact;
  return (
    <KeyboardAvoidingView
      style={styles.page}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 90 : 0}>
      <Stack.Screen options={{ title: contactName(c) }} />

      <ScrollView ref={scrollRef} contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <ThreadHeader
          contact={c}
          canWrite={canWrite}
          showFamily={showFamily}
          onToggleFamily={() => setShowFamily((v) => !v)}
          onContact={(next) => update((t) => ({ ...t, contact: next }))}
        />
        {showFamily ? (
          <FamilyPanel
            contactId={c.id}
            family={thread.family}
            canWrite={canWrite}
            onConverted={(next) =>
              update((t) => ({
                ...t,
                contact: next.contact,
                family: next.family,
              }))
            }
          />
        ) : null}

        {thread.messages.length === 0 ? (
          <AppText variant="caption" style={styles.center}>
            No messages in this conversation yet.
          </AppText>
        ) : (
          thread.messages.map((m, i) => (
            <View key={m.id} style={styles.msgBlock}>
              {needsDivider(thread.messages[i - 1], m) ? (
                <View style={styles.divider}>
                  <AppText style={styles.dividerText}>{dividerLabel(m.sent_at)}</AppText>
                </View>
              ) : null}
              <Bubble message={m} />
            </View>
          ))
        )}
      </ScrollView>

      {canWrite ? (
        <SafeAreaView edges={['bottom']} style={styles.composerWrap}>
          {sendError ? <Banner text={sendError} /> : null}
          <View style={styles.composer}>
            <TextInput
              value={draft}
              onChangeText={setDraft}
              placeholder="Type a reply…"
              placeholderTextColor={colors.textFaint}
              multiline
              maxLength={MESSAGE_MAX}
              style={styles.input}
              accessibilityLabel="Reply"
            />
            <Pressable
              onPress={send}
              disabled={!draft.trim()}
              accessibilityRole="button"
              accessibilityLabel="Send"
              style={({ pressed }) => [styles.sendBtn, !draft.trim() && styles.sendDisabled, pressed && styles.sendPressed]}>
              <AppText style={styles.sendText}>Send</AppText>
            </Pressable>
          </View>
        </SafeAreaView>
      ) : null}
    </KeyboardAvoidingView>
  );
}

function ThreadHeader({
  contact: c,
  canWrite,
  showFamily,
  onToggleFamily,
  onContact,
}: {
  contact: InboxContact;
  canWrite: boolean;
  showFamily: boolean;
  onToggleFamily: () => void;
  onContact: (c: InboxContact) => void;
}) {
  const [saving, setSaving] = useState<AiState | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function change(state: AiState) {
    if (state === c.ai_state || saving) return;
    setSaving(state);
    setError(null);
    try {
      onContact((await api.inbox.setAiState(c.id, state)).contact);
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setSaving(null);
    }
  }

  return (
    <Card style={styles.headerCard}>
      <View style={styles.headerTop}>
        <AppText variant="caption" style={styles.flex}>
          {channelSubtitle(c)}
        </AppText>
        <Pressable onPress={onToggleFamily} accessibilityRole="button" hitSlop={6} style={styles.familyToggle}>
          <Ionicons name={showFamily ? 'chevron-up' : 'people-outline'} size={14} color={colors.pink} />
          <AppText variant="link" style={styles.small}>
            Family details
          </AppText>
        </Pressable>
      </View>

      {c.needs_human_attention ? (
        <View style={styles.attention}>
          <AppText style={styles.attentionText}>● Needs attention</AppText>
          {c.needs_human_reason ? (
            <AppText variant="caption" color={inboxColors.attention}>
              {c.needs_human_reason}
            </AppText>
          ) : null}
        </View>
      ) : null}

      <AppText variant="label">AI Employee</AppText>
      <View style={styles.states}>
        {AI_STATES.map((s) => {
          const active = c.ai_state === s;
          return (
            <Pressable
              key={s}
              onPress={() => change(s)}
              disabled={!canWrite || !!saving}
              accessibilityRole="radio"
              accessibilityState={{ checked: active, disabled: !canWrite }}
              style={[styles.state, active && styles.stateActive]}>
              <AppText style={[styles.stateText, active && styles.stateTextActive]}>
                {saving === s ? 'Saving…' : AI_STATE_LABELS[s]}
              </AppText>
            </Pressable>
          );
        })}
      </View>
      {error ? <Banner text={error} /> : null}
    </Card>
  );
}

function FamilyPanel({
  contactId,
  family: f,
  canWrite,
  onConverted,
}: {
  contactId: number;
  family: FamilyDetails;
  canWrite: boolean;
  onConverted: (next: { contact: InboxContact; family: FamilyDetails }) => void;
}) {
  const [converting, setConverting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function convert() {
    setConverting(true);
    setError(null);
    try {
      const res = await api.inbox.convertToLead(contactId);
      const lead = res.lead;
      onConverted({
        contact: res.contact,
        family: {
          child_name: lead.child_name,
          child_age: lead.child_age,
          interested_in: lead.interested_in,
          source: lead.source,
          first_contact_at: lead.created_at,
          insurance: lead.insurance,
          in_pipeline: true,
        },
      });
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setConverting(false);
    }
  }

  const rows: [string, string][] = [
    ['Child', f.child_name ? `${f.child_name}${f.child_age ? ` · ${f.child_age}` : ''}` : '—'],
    ['Interested in', f.interested_in ?? '—'],
    ['Source', f.source ?? '—'],
    ['First contact', f.first_contact_at ? formatDateTimeShort(f.first_contact_at) : '—'],
    ['Insurance mentioned', f.insurance ?? '—'],
  ];

  return (
    <Card style={styles.headerCard}>
      <AppText variant="heading">Family details</AppText>
      {rows.map(([label, value]) => (
        <View key={label} style={styles.familyRow}>
          <AppText variant="caption" style={styles.familyLabel}>
            {label}
          </AppText>
          <AppText variant="body" style={styles.flex}>
            {value}
          </AppText>
        </View>
      ))}
      {f.in_pipeline ? (
        <View style={styles.pipeline}>
          <AppText style={styles.pipelineText}>In leads pipeline ✓</AppText>
        </View>
      ) : (
        <>
          <AppText variant="caption">
            Auto-detected from the conversation. No lead created yet — review and convert when ready.
          </AppText>
          {error ? <Banner text={error} /> : null}
          {canWrite ? <Button title="Convert to Lead" onPress={convert} loading={converting} /> : null}
        </>
      )}
    </Card>
  );
}

function Bubble({ message: m }: { message: InboxMessage }) {
  const outbound = m.direction === 'outbound';
  const failed = m.status === 'failed';
  return (
    <View style={[styles.bubbleRow, outbound ? styles.right : styles.left]}>
      <View style={[styles.bubble, outbound ? styles.bubbleOut : styles.bubbleIn]}>
        {outbound && m.responder_label ? (
          <View style={styles.responder}>
            <MiniChip
              label={m.responder_label === 'AI' ? '🤖 AI' : m.responder_label}
              colors={m.responder_label === 'AI' ? inboxColors.aiChip : inboxColors.staffChip}
            />
          </View>
        ) : null}
        <AppText variant="body" style={!m.body ? styles.fallback : undefined}>
          {m.display_body}
        </AppText>
        <AppText style={styles.bubbleTime}>
          {formatClockTime(m.sent_at)}
          {outbound && failed ? '  ⚠ failed' : ''}
        </AppText>
      </View>
      {outbound && m.status_label ? (
        <AppText style={[styles.statusLine, failed && styles.statusFailed]}>
          {m.status_label}
          {failed && m.send_error ? ` · ${m.send_error}` : ''}
        </AppText>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: colors.page },
  flex: { flex: 1 },
  small: { fontSize: 12 },
  center: { textAlign: 'center', paddingVertical: spacing.xl },
  scroll: { padding: spacing.lg, gap: spacing.sm, paddingBottom: spacing.xl },

  headerCard: { gap: spacing.sm },
  headerTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  familyToggle: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  attention: { gap: 2 },
  attentionText: { fontFamily: fonts.bodyExtraBold, fontSize: 12, color: inboxColors.attention },
  states: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  state: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    paddingHorizontal: 12,
    paddingVertical: 7,
    backgroundColor: colors.card,
  },
  stateActive: { backgroundColor: colors.navy, borderColor: colors.navy },
  stateText: { fontFamily: fonts.bodyBold, fontSize: 12, color: colors.textSecondary },
  stateTextActive: { color: colors.white },

  familyRow: { flexDirection: 'row', gap: spacing.sm },
  familyLabel: { width: 120 },
  pipeline: { backgroundColor: colors.successBg, borderRadius: radius.input, padding: spacing.sm },
  pipelineText: { fontFamily: fonts.bodyExtraBold, fontSize: 13, color: colors.success },

  msgBlock: { gap: spacing.sm },
  divider: { alignItems: 'center', marginTop: spacing.sm },
  dividerText: {
    fontFamily: fonts.bodyBold,
    fontSize: 11,
    color: colors.textMuted,
    backgroundColor: colors.pageAlt,
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 3,
    overflow: 'hidden',
  },
  bubbleRow: { gap: 2 },
  left: { alignItems: 'flex-start' },
  right: { alignItems: 'flex-end' },
  bubble: { maxWidth: '82%', borderRadius: 14, paddingHorizontal: 12, paddingVertical: 8, gap: 2 },
  bubbleIn: { backgroundColor: colors.card, borderWidth: 1, borderColor: colors.border, borderBottomLeftRadius: 4 },
  bubbleOut: { backgroundColor: inboxColors.outboundBubble, borderBottomRightRadius: 4 },
  responder: { flexDirection: 'row', marginBottom: 2 },
  fallback: { fontStyle: 'italic', color: colors.textSecondary },
  bubbleTime: { fontFamily: fonts.bodySemiBold, fontSize: 10, color: inboxColors.bubbleTime, alignSelf: 'flex-end' },
  statusLine: { fontFamily: fonts.bodySemiBold, fontSize: 10.5, color: colors.textMuted, marginRight: 4 },
  statusFailed: { color: inboxColors.failed },

  composerWrap: {
    backgroundColor: colors.page,
    borderTopWidth: 1,
    borderTopColor: colors.border,
    paddingHorizontal: spacing.md,
    paddingTop: spacing.sm,
    paddingBottom: spacing.sm,
    gap: spacing.sm,
  },
  composer: { flexDirection: 'row', alignItems: 'flex-end', gap: spacing.sm },
  input: {
    flex: 1,
    minHeight: 44,
    maxHeight: 120,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: 22,
    paddingHorizontal: 16,
    paddingTop: 11,
    paddingBottom: 11,
    backgroundColor: colors.card,
    fontFamily: fonts.bodySemiBold,
    fontSize: 15,
    color: colors.text,
  },
  sendBtn: {
    height: 44,
    paddingHorizontal: 18,
    borderRadius: 22,
    backgroundColor: inboxColors.whatsappGreen,
    alignItems: 'center',
    justifyContent: 'center',
  },
  sendDisabled: { opacity: 0.5 },
  sendPressed: { opacity: 0.8 },
  sendText: { fontFamily: fonts.bodyExtraBold, fontSize: 14, color: colors.white },
});
