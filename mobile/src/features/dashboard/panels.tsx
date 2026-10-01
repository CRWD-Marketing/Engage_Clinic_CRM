/**
 * Dashboard panels shared by the per-role dashboards. Each mirrors the
 * matching block in resources/views/dashboard/*.blade.php.
 */

import { router } from 'expo-router';
import type { ReactNode } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import type { CalendarSessionPayload, Patient, PatientNote } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { StatTile } from '@/components/StatTile';
import { EmptyRow } from '@/components/StateViews';
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

import { todayStatus } from './todayStatus';

export function openPatient(patientId: number) {
  router.push({ pathname: '/patients/[id]', params: { id: String(patientId) } });
}

export function DashboardHeader({ name, location }: { name: string; location: string }) {
  return (
    <View style={styles.header}>
      <AppText variant="title">
        {greeting()}, {name}
      </AppText>
      <AppText variant="caption">
        {formatLongDate(todayYmd())} · {location}
      </AppText>
    </View>
  );
}

export function StatsGrid({ children }: { children: ReactNode }) {
  return <View style={styles.statsGrid}>{children}</View>;
}

/** "Attendance · 30d" tile with the ▲/▼ pts delta. */
export function AttendanceTile({ label, rate, delta }: { label: string; rate: number | null; delta: number | null }) {
  const text = delta === null ? 'Not enough data yet' : `${delta > 0 ? '▲' : delta < 0 ? '▼' : '–'} ${Math.abs(delta)} pts`;
  const color = delta === null || delta === 0 ? colors.textWarm : delta > 0 ? colors.success : colors.danger;
  return <StatTile label={label} value={rate !== null ? `${rate}%` : '—'} caption={text} captionColor={color} />;
}

/** "My schedule today" / "Today's schedule". Clinic-wide lists show the therapist. */
export function ScheduleTodayCard({
  title,
  sessions,
  showTherapist,
}: {
  title: string;
  sessions: CalendarSessionPayload[];
  showTherapist: boolean;
}) {
  return (
    <Card padded={false}>
      <View style={styles.cardHead}>
        <CardHeader title={title} actionLabel="Open calendar →" onAction={() => router.navigate('/calendar')} />
      </View>
      {sessions.length === 0 ? (
        <EmptyRow text="No sessions scheduled today." />
      ) : (
        sessions.map((s) => {
          const status = todayStatus(s);
          const sub = showTherapist
            ? `${s.therapist_name ?? 'Unassigned'}${s.room ? ` · ${s.room}` : ''}`
            : (s.room ?? 'Room TBD');
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
                  <AppText variant="caption" numberOfLines={1}>
                    {sub}
                  </AppText>
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

/** Notes awaiting sign-off, with the Pending Nh / Overdue Nd age chip. */
export function NotesAwaitingCard({
  title,
  notes,
  showAuthor,
  actionLabel,
  onAction,
}: {
  title: string;
  notes: PatientNote[];
  showAuthor: boolean;
  actionLabel: string;
  onAction: () => void;
}) {
  return (
    <Card>
      <CardHeader title={title} actionLabel={actionLabel} onAction={onAction} />
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
                  <AppText variant="caption">
                    {showAuthor ? `${note.author_name} · ` : ''}
                    {formatDateTimeShort(note.created_at)}
                  </AppText>
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

/** Amber "treatment plans due for review" card. */
export function TreatmentPlansCard({ title, patients }: { title: string; patients: Patient[] }) {
  const today = todayYmd();
  return (
    <Card style={styles.planCard}>
      <AppText variant="heading" color={planCardColors.fg} style={styles.planTitle}>
        {title}
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

export const panelStyles = StyleSheet.create({
  pressed: { backgroundColor: colors.pageAlt },
  rowMain: { flex: 1, minWidth: 0 },
  cardHead: { paddingHorizontal: spacing.lg, paddingTop: spacing.lg },
});

const styles = StyleSheet.create({
  header: { gap: 2, marginBottom: spacing.xs },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  cardHead: panelStyles.cardHead,
  scheduleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    minHeight: 56,
  },
  pressed: panelStyles.pressed,
  time: { width: 44, fontFamily: fonts.heading, fontSize: 15, color: colors.navy },
  rowMain: panelStyles.rowMain,
  chips: { alignItems: 'flex-end', gap: 4 },
  noteRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
  emptyInline: { paddingTop: spacing.sm, fontFamily: fonts.bodyBold },
  planCard: { backgroundColor: planCardColors.bg, borderColor: planCardColors.border, gap: spacing.sm },
  planTitle: { fontSize: 15 },
  planRow: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm, paddingVertical: 4 },
  planPressed: { opacity: 0.6 },
  planName: { flex: 1, fontSize: 13 },
});
