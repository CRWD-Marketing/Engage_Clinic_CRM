import Ionicons from '@expo/vector-icons/Ionicons';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Linking, Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { CalendarSessionPayload, PatientAuthorizationSummary, PatientDetail } from '@/api/types';
import { canWriteIn } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, colorsForActivity, fonts, neutralChip, patientColors, radius, spacing } from '@/theme';
import {
  addDays,
  formatDayMonthYear,
  formatDayMonthYearShort,
  formatMonthYear,
  formatTime,
  formatWeekdayShort,
  isoToYmd,
  todayYmd,
} from '@/utils/dates';

import { GoalsCard } from './GoalsCard';
import { NotesCard } from './NotesCard';
import { DocumentsTab, PaymentsTab, ProfileTab } from './PatientExtraTabs';
import { ageAndDiagnosis, hoursLeft } from './patientFormat';

const TABS = [
  { key: 'overview', label: 'Overview' },
  { key: 'history', label: 'Session history' },
  { key: 'payments', label: 'Payments' },
  { key: 'documents', label: 'Documents' },
  { key: 'profile', label: 'Profile & intake' },
] as const;
type Tab = (typeof TABS)[number]['key'];

/**
 * Mirrors patient/show.blade.php: header, banners, chips, then the
 * Overview, Session history, Payments, Documents and Profile & intake tabs.
 */
export function PatientDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const query = useApiQuery(`patients.show:${id}`, () => api.patients.show(Number(id)));
  useRefetchOnFocus(query.reload);
  const [tab, setTab] = useState<Tab>('overview');
  const user = useCurrentUser();
  const canWrite = canWriteIn(user, 'patients');

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const name = d.patient.lead.child_name ?? 'Unnamed';

  return (
    <Screen edges={['bottom']} refreshing={query.refreshing} onRefresh={query.refresh}>
      <Stack.Screen options={{ title: name }} />
      <Header detail={d} />
      <Banners detail={d} />
      <Chips detail={d} />

      {canWrite ? (
        <Button
          title="Edit details"
          variant="secondary"
          onPress={() => router.push({ pathname: '/patients/edit', params: { id: String(d.patient.id) } })}
        />
      ) : null}

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.tabs} accessibilityRole="tablist">
        {TABS.map((t) => (
          <Pressable
            key={t.key}
            onPress={() => setTab(t.key)}
            accessibilityRole="tab"
            accessibilityState={{ selected: tab === t.key }}
            style={[styles.tab, tab === t.key && styles.tabActive]}>
            <AppText style={[styles.tabText, tab === t.key && styles.tabTextActive]}>{t.label}</AppText>
          </Pressable>
        ))}
      </ScrollView>

      {tab === 'overview' ? <Overview detail={d} /> : null}
      {tab === 'history' ? <History sessions={d.past_sessions} /> : null}
      {tab === 'payments' ? <PaymentsTab detail={d} /> : null}
      {tab === 'documents' ? <DocumentsTab detail={d} canWrite={canWrite} onChanged={query.reload} /> : null}
      {tab === 'profile' ? <ProfileTab detail={d} /> : null}
    </Screen>
  );
}

// ---------------------------------------------------------------------------
// Header, banners, chips
// ---------------------------------------------------------------------------

function Header({ detail: d }: { detail: PatientDetail }) {
  const lead = d.patient.lead;
  const name = lead.child_name ?? 'Unnamed';
  return (
    <Card style={styles.header}>
      <View style={styles.headerTop}>
        <Avatar id={d.patient.lead_id} name={name} size={52} />
        <View style={styles.flex}>
          <AppText variant="title">{name}</AppText>
          <AppText variant="caption">
            {ageAndDiagnosis(lead.child_age, d.patient.diagnosis)} · enrolled{' '}
            {formatMonthYear(isoToYmd(d.patient.enrolled_at))}
          </AppText>
        </View>
      </View>
      <Divider />
      <View style={styles.contactRow}>
        <View style={styles.flex}>
          <AppText variant="bodyStrong">{lead.parent_guardian_name ?? '—'}</AppText>
          <AppText variant="caption">{lead.phone ?? 'No phone on file'}</AppText>
        </View>
        {lead.phone ? (
          <Pressable
            onPress={() => Linking.openURL(`tel:${lead.phone!.replace(/\s+/g, '')}`)}
            style={({ pressed }) => [styles.callBtn, pressed && styles.pressed]}
            accessibilityRole="button"
            accessibilityLabel={`Call ${lead.parent_guardian_name ?? 'parent'}`}>
            <Ionicons name="call-outline" size={18} color={colors.pink} />
          </Pressable>
        ) : null}
      </View>
    </Card>
  );
}

function Banners({ detail: d }: { detail: PatientDetail }) {
  const today = todayYmd();
  const soonest = d.authorizations
    .filter((a) => a.renews_at)
    .map((a) => ({ auth: a, renews: isoToYmd(a.renews_at!) }))
    .sort((a, b) => a.renews.localeCompare(b.renews))[0];
  const renewsSoon = soonest && soonest.renews > today && soonest.renews <= addDays(today, 45);

  return (
    <>
      {renewsSoon ? (
        <View style={[styles.banner, styles.attention]}>
          <AppText style={styles.attentionText}>
            Attention — {soonest.auth.payer_name} authorization renews {formatDayMonthYear(soonest.renews)}
          </AppText>
        </View>
      ) : null}
      {d.is_profile_incomplete ? (
        <View style={[styles.banner, styles.incomplete]}>
          <AppText style={styles.incompleteTitle}>New client — profile incomplete</AppText>
          <AppText variant="caption" color={patientColors.incompleteBanner.sub}>
            Missing: {d.missing_fields_label}
          </AppText>
        </View>
      ) : null}
    </>
  );
}

function Chips({ detail: d }: { detail: PatientDetail }) {
  const primary = d.authorizations[0];
  return (
    <View style={styles.chips}>
      {d.patient.programme ? <Chip label={d.patient.programme} colors={patientColors.programmeChip} /> : null}
      {primary ? <Chip label={primary.payer_name} colors={patientColors.payerChip} /> : null}
      {d.attendance_rate !== null ? <Chip label={`Attendance ${d.attendance_rate}%`} colors={neutralChip} /> : null}
    </View>
  );
}

// ---------------------------------------------------------------------------
// Overview tab
// ---------------------------------------------------------------------------

function Overview({ detail: d }: { detail: PatientDetail }) {
  const user = useCurrentUser();
  const canWrite = canWriteIn(user, 'patients');

  return (
    <View style={styles.stack}>
      <GoalsCard
        // Remount with fresh server state after a refresh changes it.
        key={`${d.todays_session?.id ?? 'none'}:${d.todays_goal_ids.join(',')}:${d.recommended_goals.length}`}
        patientId={d.patient.id}
        hasSessionToday={!!d.todays_session}
        recommended={d.recommended_goals}
        todaysGoalIds={d.todays_goal_ids}
        canEdit={canWrite}
      />
      <NotesCard
        patientId={d.patient.id}
        notes={d.notes}
        conversionSummary={d.patient.lead.assessment_report_summary}
        canAdd={canWrite && user.role !== 'COORDINATOR'}
      />
      <AuthorizationsCard authorizations={d.authorizations} />

      <Card style={styles.section}>
        <AppText variant="heading">Care team</AppText>
        {d.care_team.length === 0 ? (
          <AppText variant="caption">No therapists assigned yet.</AppText>
        ) : (
          d.care_team.map((m) => (
            <View key={m.id} style={styles.teamRow}>
              <Avatar id={m.id} name={`${m.first_name} ${m.last_name}`} size={30} />
              <AppText variant="body" style={styles.flex}>
                {`${m.first_name} ${m.last_name}`.trim()}
                {m.job_title ? ` — ${m.job_title}` : ''}
              </AppText>
            </View>
          ))
        )}
      </Card>

      <Card style={styles.section}>
        <AppText variant="heading">Upcoming sessions</AppText>
        {d.upcoming_sessions.length === 0 ? (
          <AppText variant="caption">No sessions booked yet.</AppText>
        ) : (
          d.upcoming_sessions.map((s) => <UpcomingRow key={s.id} session={s} />)
        )}
      </Card>
    </View>
  );
}

function AuthorizationsCard({ authorizations }: { authorizations: PatientAuthorizationSummary[] }) {
  return (
    <Card style={styles.section}>
      <AppText variant="heading">Insurance & authorization</AppText>
      {authorizations.length > 1 ? (
        <AppText variant="caption">More than one payer on file — each covers different services.</AppText>
      ) : null}
      {authorizations.length === 0 ? (
        <AppText variant="caption">No authorization on file — add one to track hours.</AppText>
      ) : (
        authorizations.map((a) => {
          const total = a.authorized_hours_total ?? 0;
          const left = hoursLeft(a);
          const pct = total ? Math.min(100, Math.round((a.hours_used / total) * 100)) : 0;
          return (
            <View key={a.id} style={styles.auth}>
              <View style={styles.authTop}>
                <AppText variant="bodyStrong" style={styles.flex}>
                  {a.payer_name}
                </AppText>
                <View style={styles.coverage}>
                  <AppText variant="chip" color={patientColors.coverageBadge.fg}>
                    {a.coverage_percent}% covered
                  </AppText>
                </View>
              </View>
              <AppText variant="caption">Covers {a.covers_label}</AppText>
              <AppText style={styles.authValue}>
                {left} of {total} hours left
              </AppText>
              <View style={styles.track} accessibilityLabel={`${left} of ${total} hours left`}>
                <View
                  style={[
                    styles.fill,
                    { width: `${pct}%`, backgroundColor: left <= 5 ? patientColors.progressLow : patientColors.progressOk },
                  ]}
                />
              </View>
              <View style={styles.authMeta}>
                <AppText variant="caption">Policy {a.policy_number || '—'}</AppText>
                <AppText variant="caption">
                  {a.renews_at ? `renews ${formatDayMonthYearShort(isoToYmd(a.renews_at))}` : ''}
                </AppText>
              </View>
            </View>
          );
        })
      )}
    </Card>
  );
}

function UpcomingRow({ session: s }: { session: CalendarSessionPayload }) {
  const day = s.session_date === todayYmd() ? 'Today' : formatWeekdayShort(s.session_date);
  return (
    <View style={styles.upcoming}>
      <AppText variant="body" style={styles.flex}>
        {day} {formatTime(s.start_time)}
        {s.status === 'cancelled' ? ` · ${s.status_label}` : ''}
      </AppText>
      <Chip label={s.activity_type} colors={colorsForActivity(s.activity_type, s.category)} />
    </View>
  );
}

// ---------------------------------------------------------------------------
// Session history tab
// ---------------------------------------------------------------------------

function History({ sessions }: { sessions: CalendarSessionPayload[] }) {
  const attended = sessions.filter((s) => s.status === 'completed').length;
  return (
    <Card style={styles.section}>
      <View style={styles.historyHead}>
        <AppText variant="heading" style={styles.flex}>
          Session history
        </AppText>
        <AppText variant="caption">
          {attended} of {sessions.length} attended
        </AppText>
      </View>
      {sessions.length === 0 ? (
        <AppText variant="caption">No sessions on record yet.</AppText>
      ) : (
        sessions.map((s) => (
          <View key={s.id} style={styles.historyRow}>
            <Divider />
            <View style={styles.historyTop}>
              <AppText variant="bodyStrong" style={styles.flex}>
                {formatDayMonthYear(s.session_date)} · {formatTime(s.start_time)}
              </AppText>
              <AppText style={[styles.historyStatus, { color: historyStatusColor(s) }]}>{s.status_label}</AppText>
            </View>
            <AppText variant="caption">
              {s.activity_type} · {s.duration_minutes} min · {s.therapist_name ?? '—'}
            </AppText>
            {s.notes ? <AppText variant="caption">{s.notes}</AppText> : null}
          </View>
        ))
      )}
    </Card>
  );
}

function historyStatusColor(s: CalendarSessionPayload): string {
  if (s.status === 'completed') return colors.success;
  if (s.status === 'no_show') return colors.pink;
  if (s.status === 'cancelled') return colors.textMuted;
  return colors.warning;
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  stack: { gap: spacing.md },
  section: { gap: spacing.sm },
  pressed: { opacity: 0.7 },

  header: { gap: spacing.md },
  headerTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  contactRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  callBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: colors.border,
    alignItems: 'center',
    justifyContent: 'center',
  },

  banner: { borderRadius: 12, borderWidth: 1, padding: spacing.md, gap: 2 },
  attention: { backgroundColor: patientColors.attentionBanner.bg, borderColor: patientColors.attentionBanner.border },
  attentionText: { fontFamily: fonts.bodyBold, fontSize: 13, color: patientColors.attentionBanner.fg },
  incomplete: { backgroundColor: patientColors.incompleteBanner.bg, borderColor: patientColors.incompleteBanner.border },
  incompleteTitle: { fontFamily: fonts.heading, fontSize: 15, color: patientColors.incompleteBanner.title },

  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },

  tabs: {
    flexDirection: 'row',
    backgroundColor: colors.pageAlt,
    borderRadius: radius.input,
    padding: 4,
    gap: 4,
  },
  tab: { minHeight: 40, paddingHorizontal: spacing.md, alignItems: 'center', justifyContent: 'center', borderRadius: 8 },
  tabActive: { backgroundColor: colors.card },
  tabText: { fontFamily: fonts.bodyBold, fontSize: 13, color: colors.textMuted },
  tabTextActive: { color: colors.navy },

  auth: {
    gap: 4,
    padding: spacing.md,
    borderRadius: radius.input,
    borderWidth: 1,
    borderColor: colors.border,
  },
  authTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  coverage: {
    backgroundColor: patientColors.coverageBadge.bg,
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 3,
  },
  authValue: { fontFamily: fonts.heading, fontSize: 16, color: colors.navy, marginTop: 2 },
  track: { height: 9, backgroundColor: patientColors.progressTrack, borderRadius: 5, overflow: 'hidden' },
  fill: { height: '100%', borderRadius: 5 },
  authMeta: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm },

  teamRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  upcoming: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, minHeight: 32 },

  historyHead: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.sm },
  historyRow: { gap: 3 },
  historyTop: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.sm, paddingTop: spacing.sm },
  historyStatus: { fontFamily: fonts.bodyExtraBold, fontSize: 11, textTransform: 'uppercase', letterSpacing: 0.4 },
});
