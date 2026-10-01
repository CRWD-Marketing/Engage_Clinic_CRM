import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { CalendarSessionPayload, Patient, PatientNote, TherapistDashboard as Dashboard } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, colorsForActivity, fonts, noteAgeColors, planCardColors, spacing } from '@/theme';
import {
  diffDays,
  formatDateTimeShort,
  formatDayMonth,
  formatLongDate,
  formatTimeShort,
  greeting,
  hoursSince,
  isoToYmd,
  todayYmd,
} from '@/utils/dates';
import { plural } from '@/utils/format';

import { todayStatus } from './todayStatus';

/** Mirrors resources/views/dashboard/therapist.blade.php. */
export function TherapistDashboard() {
  const query = useApiQuery('dashboard.therapist', () => api.dashboard.therapist());

  // Refresh quietly when returning to the tab (e.g. after adding a session note).
  useRefetchOnFocus(query.reload);

  if (query.loading && !query.data) return <Screen scroll={false}><LoadingState /></Screen>;
  if (query.error && !query.data) {
    return (
      <Screen scroll={false}>
        <ErrorState error={query.error} onRetry={query.refresh} />
      </Screen>
    );
  }

  const d = query.data!;
  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <View style={styles.header}>
        <AppText variant="title">
          {greeting()}, {d.user_full_name}
        </AppText>
        <AppText variant="caption">
          {formatLongDate(todayYmd())} · {d.location_label}
        </AppText>
      </View>

      <Stats d={d} />
      <ScheduleToday sessions={d.today_sessions} />
      <NotesAwaitingSignoff notes={d.my_notes_awaiting_signoff_list} />
      <TreatmentPlansDue patients={d.my_treatment_plans_due_list} />
    </Screen>
  );
}

function openPatient(patientId: number) {
  router.push({ pathname: '/patients/[id]', params: { id: String(patientId) } });
}

function Stats({ d }: { d: Dashboard }) {
  const delta = d.attendance_delta;
  const deltaText =
    delta === null ? 'Not enough data yet' : `${delta > 0 ? '▲' : delta < 0 ? '▼' : '–'} ${Math.abs(delta)} pts`;
  const deltaColor = delta === null || delta === 0 ? colors.textWarm : delta > 0 ? colors.success : colors.danger;

  return (
    <View style={styles.statsGrid}>
      <StatTile
        label="My sessions today"
        value={String(d.sessions_today_count)}
        caption={`${d.rooms_in_use_count} ${plural(d.rooms_in_use_count, 'room')}`}
      />
      <StatTile
        label="My attendance · 30d"
        value={d.attendance_rate !== null ? `${d.attendance_rate}%` : '—'}
        caption={deltaText}
        captionColor={deltaColor}
      />
      <StatTile label="My active patients" value={String(d.my_active_patients_count)} caption="assigned to you" />
      <StatTile
        label="My notes pending"
        value={String(d.my_pending_notes_count)}
        caption={d.my_pending_notes_count > 0 ? 'awaiting sign-off' : 'All caught up'}
        captionColor={colors.warning}
      />
    </View>
  );
}

function ScheduleToday({ sessions }: { sessions: CalendarSessionPayload[] }) {
  return (
    <Card padded={false}>
      <View style={styles.cardHead}>
        <CardHeader title="My schedule today" actionLabel="Open calendar →" onAction={() => router.navigate('/calendar')} />
      </View>
      {sessions.length === 0 ? (
        <EmptyRow text="No sessions scheduled today." />
      ) : (
        sessions.map((s) => {
          const status = todayStatus(s);
          return (
            <View key={s.id}>
              <Divider />
              <Pressable
                onPress={() => router.push({ pathname: '/calendar/[id]', params: { id: String(s.id) } })}
                style={({ pressed }) => [styles.scheduleRow, pressed && styles.pressed]}
                accessibilityRole="button"
                accessibilityLabel={`${formatTimeShort(s.start_time)}, ${s.patient_name}, ${s.activity_type}, ${status.label}`}>
                <AppText style={styles.time}>{formatTimeShort(s.start_time)}</AppText>
                <View style={styles.rowMain}>
                  <AppText variant="bodyStrong" numberOfLines={1}>
                    {s.patient_name}
                  </AppText>
                  <AppText variant="caption">{s.room ?? 'Room TBD'}</AppText>
                </View>
                <View style={styles.chips}>
                  <Chip label={s.activity_type} colors={colorsForActivity(s.activity_type, s.category)} />
                  <Chip label={status.label} colors={status.colors} />
                </View>
              </Pressable>
            </View>
          );
        })
      )}
    </Card>
  );
}

function NotesAwaitingSignoff({ notes }: { notes: PatientNote[] }) {
  return (
    <Card>
      <CardHeader title="My notes awaiting sign-off" actionLabel="Review →" onAction={() => router.navigate('/patients')} />
      {notes.length === 0 ? (
        <>
          <Divider />
          <AppText variant="caption" style={styles.emptyInline}>
            Nothing awaiting sign-off.
          </AppText>
        </>
      ) : (
        notes.map((note) => {
          const hoursOld = hoursSince(note.created_at);
          const overdue = hoursOld >= 48;
          return (
            <View key={note.id}>
              <Divider />
              <Pressable
                onPress={() => openPatient(note.patient_id)}
                style={({ pressed }) => [styles.noteRow, pressed && styles.pressed]}
                accessibilityRole="button">
                <View style={styles.rowMain}>
                  <AppText variant="bodyStrong">{note.patient?.lead?.child_name ?? 'Unknown patient'}</AppText>
                  <AppText variant="caption">{formatDateTimeShort(note.created_at)}</AppText>
                </View>
                <Chip
                  label={overdue ? `Overdue ${Math.floor(hoursOld / 24)}d` : `Pending ${hoursOld}h`}
                  colors={overdue ? noteAgeColors.overdue : noteAgeColors.pending}
                />
              </Pressable>
            </View>
          );
        })
      )}
    </Card>
  );
}

function TreatmentPlansDue({ patients }: { patients: Patient[] }) {
  const today = todayYmd();
  return (
    <Card style={styles.planCard}>
      <AppText variant="heading" color={planCardColors.fg} style={styles.planTitle}>
        My treatment plans due for review
      </AppText>
      {patients.length === 0 ? (
        <AppText variant="caption" color={planCardColors.fg}>
          Nothing due in the next 7 days.
        </AppText>
      ) : (
        patients.map((p) => {
          const due = isoToYmd(p.treatment_plan_review_due_at!);
          const daysDue = diffDays(today, due);
          return (
            <Pressable
              key={p.id}
              onPress={() => openPatient(p.id)}
              style={({ pressed }) => [styles.planRow, pressed && styles.planPressed]}
              accessibilityRole="button">
              <AppText variant="bodyStrong" style={styles.planName}>
                {p.lead?.child_name ?? 'Unknown'}
              </AppText>
              <AppText variant="caption" color={planCardColors.fg}>
                {daysDue >= 0 ? `Review due ${formatDayMonth(due)}` : `Review overdue ${Math.abs(daysDue)}d`}
              </AppText>
            </Pressable>
          );
        })
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  header: { gap: 2, marginBottom: spacing.xs },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  cardHead: { paddingHorizontal: spacing.lg, paddingTop: spacing.lg },
  scheduleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    minHeight: 56,
  },
  pressed: { backgroundColor: colors.pageAlt },
  time: { width: 44, fontFamily: fonts.heading, fontSize: 15, color: colors.navy },
  rowMain: { flex: 1, minWidth: 0 },
  chips: { alignItems: 'flex-end', gap: 4 },
  noteRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
  emptyInline: { paddingTop: spacing.sm, fontFamily: fonts.bodyBold },
  planCard: { backgroundColor: planCardColors.bg, borderColor: planCardColors.border, gap: spacing.sm },
  planTitle: { fontSize: 15 },
  planRow: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm, paddingVertical: 4 },
  planPressed: { opacity: 0.6 },
  planName: { flex: 1, fontSize: 13 },
});
