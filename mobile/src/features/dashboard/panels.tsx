/**
 * Dashboard panels shared by the per-role dashboards. Each mirrors the
 * matching block in resources/views/dashboard/*.blade.php.
 */

import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import type { ReactNode } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { CalendarSessionPayload, Patient, PatientNote, WaitlistEntry, WhatsappContact } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { StatTile } from '@/components/StatTile';
import { EmptyRow } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, colorsForActivity, fonts, inboxColors, noteAgeColors, planCardColors, spacing } from '@/theme';
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

export function openPatient(patientId: number) {
  router.push({ pathname: '/patients/[id]', params: { id: String(patientId) } }, { withAnchor: true });
}

export function DashboardHeader({ name, location }: { name: string; location: string }) {
  return (
    <View style={styles.headerRow}>
      <View style={styles.header}>
        <AppText variant="title">
          {greeting()}, {name}
        </AppText>
        <AppText variant="caption">
          {formatLongDate(todayYmd())} · {location}
        </AppText>
      </View>
      <Bell />
    </View>
  );
}

/** The topbar bell: unread count, opening the notifications screen. */
function Bell() {
  const query = useApiQuery('notifications.list', () => api.notifications.list());
  useRefetchOnFocus(query.reload);
  const count = query.data?.count ?? 0;
  return (
    <Pressable
      onPress={() => router.push('/notifications')}
      accessibilityRole="button"
      accessibilityLabel={count > 0 ? `Notifications, ${count} unread` : 'Notifications'}
      hitSlop={8}
      style={({ pressed }) => [styles.bell, pressed && panelStyles.pressed]}>
      <Ionicons name="notifications-outline" size={20} color={colors.navy} />
      {count > 0 ? (
        <View style={styles.bellBadge}>
          <AppText style={styles.bellBadgeText}>{count > 99 ? '99+' : count}</AppText>
        </View>
      ) : null}
    </Pressable>
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
                onPress={() => router.push({ pathname: '/calendar/[id]', params: { id: String(s.id) } }, { withAnchor: true })}
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

export function InboxPreviewCard({ contacts }: { contacts: WhatsappContact[] }) {
  return (
    <Card padded={false}>
      <View style={[panelStyles.cardHead, styles.inboxHead]}>
        <View style={styles.greenDot} />
        <View style={styles.rowMain}>
          <CardHeader title="WhatsApp inbox" actionLabel="Open →" onAction={() => router.navigate('/inbox')} />
        </View>
      </View>
      {contacts.length === 0 ? (
        <EmptyRow text="No conversations yet." />
      ) : (
        contacts.map((c) => (
          <View key={c.id}>
            <Divider />
            <Pressable
              onPress={() => router.push({ pathname: '/inbox/[id]', params: { id: String(c.id) } }, { withAnchor: true })}
              style={({ pressed }) => [styles.inboxRow, pressed && panelStyles.pressed]}
              accessibilityRole="button">
              <Avatar id={c.id} name={c.name ?? '?'} size={36} />
              <View style={panelStyles.rowMain}>
                <AppText variant="bodyStrong">{c.name ?? 'Unknown contact'}</AppText>
                <AppText variant="caption" numberOfLines={1}>
                  {c.last_message_preview ?? 'No messages yet'}
                </AppText>
              </View>
              {c.unread_count > 0 ? (
                <View style={styles.unread}>
                  <AppText style={styles.unreadText}>{c.unread_count}</AppText>
                </View>
              ) : null}
            </Pressable>
          </View>
        ))
      )}
    </Card>
  );
}

export function WaitlistCard({ entries }: { entries: WaitlistEntry[] }) {
  return (
    <Card style={styles.waitlist}>
      <AppText variant="heading">Waitlist — next up</AppText>
      {entries.length === 0 ? (
        <AppText variant="caption">No one on the waitlist.</AppText>
      ) : (
        entries.map((e, i) => (
          <View key={e.id} style={styles.waitRow}>
            <AppText style={styles.waitIndex}>{i + 1}</AppText>
            <View style={styles.rowMain}>
              <AppText variant="bodyStrong">
                {e.child_name || e.parent_guardian_name || 'Unnamed enquiry'}
                {e.child_age ? ` · ${e.child_age}` : ''}
              </AppText>
              <AppText variant="caption">
                {e.interested_in || 'Interest not captured'}
                {e.source ? ` · ${e.source}` : ''} · waiting {e.waiting_weeks} {plural(e.waiting_weeks, 'wk', 'wks')}
              </AppText>
            </View>
          </View>
        ))
      )}
    </Card>
  );
}

export type BarListRow = { label: string; value: number; text: string; color: string };

/** A card of labelled horizontal bars on one fixed scale (lead sources, staff by role, payer mix, claims aging). */
export function BarListCard({
  title,
  rows,
  scale,
  empty,
  actionLabel,
  onAction,
}: {
  title: string;
  rows: BarListRow[];
  /** Axis maximum; defaults to the largest value. */
  scale?: number;
  empty: string;
  actionLabel?: string;
  onAction?: () => void;
}) {
  const max = scale ?? Math.max(1, ...rows.map((r) => r.value));
  // A non-empty bar never drops below 4% so it stays visible.
  const width = (value: number) => (value > 0 ? Math.max(4, Math.min(100, Math.round((value / max) * 100))) : 0);
  return (
    <Card style={styles.barCard}>
      <CardHeader title={title} actionLabel={actionLabel} onAction={onAction} />
      {rows.length === 0 ? (
        <AppText variant="caption">{empty}</AppText>
      ) : (
        rows.map((row) => (
          <View key={row.label} style={styles.barRow} accessible accessibilityLabel={`${row.label}: ${row.text}`}>
            <AppText style={styles.barLabel} numberOfLines={1}>
              {row.label}
            </AppText>
            <View style={styles.barTrack}>
              <View style={[styles.barFill, { width: `${width(row.value)}%`, backgroundColor: row.color }]} />
            </View>
            <AppText style={styles.barValue}>{row.text}</AppText>
          </View>
        ))
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
  headerRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, marginBottom: spacing.xs },
  header: { flex: 1, gap: 2 },
  bell: {
    width: 42,
    height: 42,
    borderRadius: 21,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bellBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 18,
    height: 18,
    borderRadius: 9,
    paddingHorizontal: 4,
    backgroundColor: colors.pink,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bellBadgeText: { fontFamily: fonts.bodyExtraBold, fontSize: 10, color: colors.white, lineHeight: 13 },
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
  inboxHead: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.sm },
  greenDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: inboxColors.whatsappGreen },
  inboxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  unread: { backgroundColor: inboxColors.whatsappGreen, borderRadius: 9, paddingHorizontal: 7, paddingVertical: 1 },
  unreadText: { fontFamily: fonts.bodyExtraBold, fontSize: 11, color: colors.white },
  waitlist: { gap: spacing.sm },
  waitRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  waitIndex: { width: 20, fontFamily: fonts.heading, fontSize: 14, color: colors.pink },
  barCard: { gap: spacing.sm },
  barRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  barLabel: { width: 96, fontFamily: fonts.bodyBold, fontSize: 13, color: colors.text },
  barTrack: { flex: 1, height: 10, borderRadius: 5, backgroundColor: colors.divider, overflow: 'hidden' },
  barFill: { height: '100%', borderRadius: 5 },
  barValue: { minWidth: 24, textAlign: 'right', fontFamily: fonts.bodyExtraBold, fontSize: 13, color: colors.navy },
});
