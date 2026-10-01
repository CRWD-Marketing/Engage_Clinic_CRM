import Ionicons from '@expo/vector-icons/Ionicons';
import { useLocalSearchParams } from 'expo-router';
import { useState, type ComponentProps } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { MySessionPayload } from '@/api/types';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, colorsForActivity, fonts, sessionCategoryColors, spacing } from '@/theme';
import { formatDayShort, formatTime } from '@/utils/dates';

import { canAddTherapistNote, sessionTag } from './sessionStatus';

const NOTE_MAX = 2000;

export function SessionDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const user = useCurrentUser();
  const query = useApiQuery(`calendar.show:${id}`, () => api.calendar.show(Number(id)));

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const s = query.data;
  return (
    <Screen edges={['bottom']} refreshing={query.refreshing} onRefresh={query.refresh}>
      <SummaryCard session={s} />
      {canAddTherapistNote(s, user.id) ? <TherapistNoteCard session={s} /> : null}
      {s.notes ? (
        <Card style={styles.section}>
          <AppText variant="label">Scheduling notes</AppText>
          <AppText variant="body">{s.notes}</AppText>
        </Card>
      ) : null}
      {s.supervised ? <SupervisionCard session={s} /> : null}
    </Screen>
  );
}

function SummaryCard({ session: s }: { session: MySessionPayload }) {
  const tag = sessionTag(s);
  const strike = tag.cancelled ? styles.strike : null;
  return (
    <Card style={styles.section}>
      <View style={styles.chips}>
        <Chip label={s.activity_type} colors={colorsForActivity(s.activity_type, s.category)} />
        <AppText style={[styles.tag, { color: tag.color }]}>{s.status_label}</AppText>
      </View>
      <AppText variant="display" style={strike}>
        {s.patient_name}
      </AppText>
      <Divider />
      <DetailRow icon="calendar-outline" text={formatDayShort(s.session_date)} />
      <DetailRow
        icon="time-outline"
        text={`${formatTime(s.start_time)}–${formatTime(s.end_time)} · ${s.duration_minutes} min`}
        strike={tag.cancelled}
      />
      <DetailRow icon="location-outline" text={s.room ?? 'Room TBD'} />
      {s.therapist_name ? <DetailRow icon="person-outline" text={s.therapist_name} /> : null}
      {s.cover_for_name ? <DetailRow icon="swap-horizontal-outline" text={`Covering for ${s.cover_for_name}`} /> : null}
    </Card>
  );
}

function DetailRow({ icon, text, strike }: { icon: ComponentProps<typeof Ionicons>['name']; text: string; strike?: boolean }) {
  return (
    <View style={styles.detailRow}>
      <Ionicons name={icon} size={18} color={colors.textMuted} />
      <AppText variant="body" style={strike ? styles.strike : undefined}>
        {text}
      </AppText>
    </View>
  );
}

function SupervisionCard({ session: s }: { session: MySessionPayload }) {
  const sup = sessionCategoryColors.supervision;
  return (
    <Card style={[styles.section, { backgroundColor: sup.bg, borderColor: sup.bg }]}>
      <AppText style={[styles.supTitle, { color: sup.fg }]}>★ Supervised</AppText>
      {s.supervised_by_name ? (
        <AppText variant="caption" color={sup.fg}>
          {s.supervised_by_name}
          {s.supervised_at ? ` · ${s.supervised_at}` : ''}
        </AppText>
      ) : null}
      {s.supervision_notes ? <AppText variant="body">{s.supervision_notes}</AppText> : null}
    </Card>
  );
}

/**
 * The therapist's private note on a completed session
 * (PATCH /calendar/{id}/therapist-note; own sessions only).
 */
function TherapistNoteCard({ session }: { session: MySessionPayload }) {
  const [saved, setSaved] = useState<string | null>(session.therapist_note);
  const [editing, setEditing] = useState(false);
  const [draft, setDraft] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [flash, setFlash] = useState<string | null>(null);

  const startEditing = () => {
    setDraft(saved ?? '');
    setError(null);
    setFlash(null);
    setEditing(true);
  };

  async function save() {
    setSaving(true);
    setError(null);
    try {
      const trimmed = draft.trim();
      const res = await api.calendar.saveTherapistNote(session.id, trimmed || null);
      setSaved(res.therapist_note);
      setFlash(res.message);
      setEditing(false);
    } catch (e) {
      setError(isApiError(e) ? (e.fieldError('note') ?? e.message) : errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Card style={styles.section}>
      <AppText variant="heading">Session note</AppText>
      <AppText variant="caption" color={colors.success} style={styles.statusLine}>
        Completed — {saved ? 'note recorded by you' : 'add a note about how it went'}
      </AppText>
      {flash && !editing ? <Banner text={flash} tone="success" /> : null}

      {editing ? (
        <>
          <TextField
            label="Your note"
            value={draft}
            onChangeText={setDraft}
            placeholder="How did the session go?"
            multiline
            maxLength={NOTE_MAX}
            autoFocus
            error={error ?? undefined}
          />
          <AppText variant="caption" style={styles.counter}>
            {draft.length}/{NOTE_MAX}
          </AppText>
          <View style={styles.actions}>
            <View style={styles.flex}>
              <Button title="Cancel" variant="secondary" onPress={() => setEditing(false)} disabled={saving} />
            </View>
            <View style={styles.flex}>
              <Button title="Save note" onPress={save} loading={saving} />
            </View>
          </View>
        </>
      ) : saved ? (
        <>
          <AppText variant="body">{saved}</AppText>
          <Button title="Edit note" variant="secondary" onPress={startEditing} />
        </>
      ) : (
        <Button title="+ Add session note" variant="secondary" onPress={startEditing} />
      )}
      <AppText variant="caption">Private to you — separate from scheduling and supervision notes.</AppText>
    </Card>
  );
}

const styles = StyleSheet.create({
  section: { gap: spacing.sm },
  chips: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  tag: { fontFamily: fonts.bodyExtraBold, fontSize: 11, textTransform: 'uppercase', letterSpacing: 0.5 },
  strike: { textDecorationLine: 'line-through' },
  detailRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  supTitle: { fontFamily: fonts.bodyExtraBold, fontSize: 14 },
  statusLine: { fontFamily: fonts.bodyExtraBold },
  counter: { textAlign: 'right', marginTop: -4 },
  actions: { flexDirection: 'row', gap: spacing.sm },
  flex: { flex: 1 },
});
