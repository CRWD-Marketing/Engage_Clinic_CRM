import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { CalendarSessionPayload } from '@/api/types';
import { canDo, canManageCalendar } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { Button } from '@/components/Button';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, colorsForActivity, fonts, neutralChip, radius, spacing } from '@/theme';
import { addDays, formatDayShort, todayYmd, weekdayIndex, type Ymd } from '@/utils/dates';

import { WeekNav } from './WeekNav';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** One therapist's week from resources/views/therapist/index.blade.php, with Close / Reopen. */
export function TherapistScheduleScreen() {
  const params = useLocalSearchParams<{ id: string; week?: string }>();
  const therapistId = Number(params.id);
  const user = useCurrentUser();
  const [week, setWeek] = useState<Ymd | undefined>(params.week);
  const query = useApiQuery(`therapists.index:${therapistId}:${week ?? 'now'}`, () =>
    api.therapists.index({ therapist_id: therapistId, week }),
  );
  // Coming back from the booking form: show the new or changed session.
  useRefetchOnFocus(query.reload);
  const [busyId, setBusyId] = useState<number | null>(null);
  const [flash, setFlash] = useState<Flash>(null);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const therapist = d.therapists.find((t) => t.id === therapistId);
  const canManage = d.can_view_all && canManageCalendar(user);
  const days = Array.from({ length: 7 }, (_, i) => addDays(d.week_start, i));

  // Close = discontinue the slot (it leaves the Calendar and frees the hours); Reopen puts it back as scheduled.
  async function toggle(session: CalendarSessionPayload) {
    const closing = session.status !== 'closed';
    setBusyId(session.id);
    setFlash(null);
    try {
      await api.calendar.setStatus(session.id, closing ? 'closed' : 'scheduled');
      setFlash({
        text: closing ? 'Session closed.' : 'Session reopened.',
        tone: 'success',
      });
      query.reload();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusyId(null);
    }
  }

  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <Stack.Screen options={{ title: therapist?.name || 'Schedule' }} />
      <WeekNav weekStart={d.week_start} isCurrentWeek={d.is_current_week} onWeek={setWeek} />
      {therapist ? (
        <AppText variant="caption">
          {therapist.department_label} · {therapist.weekly_hours}h/wk · {therapist.session_count} sessions
        </AppText>
      ) : null}
      {d.can_view_all && canDo(user, 'book_modify_session') ? (
        <Button
          title="+ Add session"
          onPress={() =>
            router.push({
              pathname: '/session-form',
              params: { therapist_id: String(therapistId), date: d.is_current_week ? todayYmd() : d.week_start },
            })
          }
        />
      ) : null}
      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      {days.map((day) => {
        const sessions = d.sessions.filter((s) => s.session_date === day);
        return (
          <View key={day} style={[styles.day, weekdayIndex(day) >= 5 && styles.weekend]}>
            <View style={styles.dayHead}>
              <AppText variant="bodyStrong" style={styles.flex}>
                {formatDayShort(day)}
              </AppText>
              <AppText variant="caption">{sessions.length}</AppText>
            </View>
            {sessions.length === 0 ? (
              <AppText variant="caption">No sessions</AppText>
            ) : (
              sessions.map((s) => (
                <SessionRow
                  key={s.id}
                  session={s}
                  busy={busyId === s.id}
                  disabled={busyId !== null}
                  onToggle={canManage ? () => toggle(s) : undefined}
                />
              ))
            )}
          </View>
        );
      })}
    </Screen>
  );
}

function SessionRow({
  session: s,
  busy,
  disabled,
  onToggle,
}: {
  session: CalendarSessionPayload;
  busy: boolean;
  disabled: boolean;
  onToggle?: () => void;
}) {
  const tint = colorsForActivity(s.activity_type, s.category);
  const closed = s.status === 'closed';
  const cancelled = s.status === 'cancelled';

  return (
    <View style={[styles.card, { backgroundColor: tint.bg }, closed && styles.closed]}>
      {/* The details and the Close button are siblings: a button can't sit inside another button on web. */}
      <Pressable
        onPress={() =>
          router.push(
            {
              pathname: '/calendar/[id]',
              params: { id: String(s.id) },
            },
            { withAnchor: true },
          )
        }
        accessibilityRole="button"
        accessibilityLabel={`${s.start_time} to ${s.end_time}, ${s.patient_name}, ${s.activity_type}, ${s.status_label}`}
        style={({ pressed }) => [styles.cardBody, pressed && styles.pressed]}>
        <View style={styles.cardTop}>
          <AppText style={[styles.time, { color: tint.fg }, cancelled && styles.strike]}>
            {s.start_time}–{s.end_time}
          </AppText>
          <AppText variant="caption">{s.duration_minutes} min</AppText>
        </View>
        <AppText variant="bodyStrong" style={cancelled && styles.strike}>
          {s.patient_name}
        </AppText>
        <View style={styles.tags}>
          <Chip label={s.activity_type} colors={{ bg: colors.page, fg: tint.fg }} />
          {s.status === 'scheduled' ? null : <Chip label={s.status_label} colors={neutralChip} />}
        </View>
        {s.room ? <AppText variant="caption">{s.room}</AppText> : null}
      </Pressable>
      {onToggle ? (
        <View style={styles.actions}>
          <Pressable
            onPress={() => router.push({ pathname: '/session-form', params: { id: String(s.id) } })}
            disabled={disabled}
            accessibilityRole="button"
            accessibilityLabel={`Edit ${s.start_time} ${s.patient_name}`}
            style={({ pressed }) => [styles.action, pressed && styles.pressed, disabled && styles.dim]}>
            <AppText style={[styles.actionText, styles.editText]}>Edit</AppText>
          </Pressable>
          <Pressable
            onPress={onToggle}
            disabled={disabled}
            accessibilityRole="button"
            accessibilityLabel={`${closed ? 'Reopen' : 'Close'} ${s.start_time} ${s.patient_name}`}
            style={({ pressed }) => [styles.action, pressed && styles.pressed, disabled && styles.dim]}>
            <AppText style={[styles.actionText, closed && styles.reopenText]}>
              {busy ? '…' : closed ? 'Reopen' : 'Close'}
            </AppText>
          </Pressable>
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  day: {
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radius.card,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
  },
  weekend: { backgroundColor: colors.pageAlt },
  dayHead: { flexDirection: 'row', alignItems: 'center' },
  card: { borderRadius: radius.input, padding: spacing.md, gap: 4 },
  cardBody: { gap: 4 },
  closed: { opacity: 0.6 },
  pressed: { opacity: 0.75 },
  dim: { opacity: 0.5 },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'baseline',
  },
  time: { fontFamily: fonts.heading, fontSize: 15 },
  strike: { textDecorationLine: 'line-through' },
  tags: { flexDirection: 'row', gap: spacing.xs, flexWrap: 'wrap' },
  action: {
    alignSelf: 'flex-start',
    marginTop: spacing.xs,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.chip,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  actionText: {
    fontFamily: fonts.bodyExtraBold,
    fontSize: 12.5,
    color: colors.danger,
  },
  reopenText: { color: colors.success },
  editText: { color: colors.navy },
  actions: { flexDirection: 'row', gap: spacing.sm },
});
