import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { CalendarSessionPayload, IntakeLead } from '@/api/types';
import { canAccessFeature } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, noteAgeColors, planCardColors, spacing } from '@/theme';
import { formatTimeShort, formatWeekdayShort } from '@/utils/dates';
import { plural } from '@/utils/format';

import { AttendanceTile, DashboardHeader, InboxPreviewCard, panelStyles, ScheduleTodayCard, StatsGrid } from './panels';

/** Mirrors resources/views/dashboard/coordinator.blade.php. */
export function CoordinatorDashboard() {
  const user = useCurrentUser();
  const query = useApiQuery('dashboard.coordinator', () => api.dashboard.coordinator());
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const delta = d.new_leads_delta;
  const hasInbox = canAccessFeature(user, 'whatsapp');

  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <DashboardHeader name={d.user_full_name} location={d.location_label} />

      <StatsGrid>
        <StatTile
          label="New leads · week"
          value={String(d.new_leads_count)}
          caption={`${delta > 0 ? '▲' : delta < 0 ? '▼' : '–'} ${Math.abs(delta)}% vs last week`}
          captionColor={delta > 0 ? colors.success : delta < 0 ? colors.danger : colors.textWarm}
        />
        <StatTile
          label="Intake calls pending"
          value={String(d.pending_intake_calls_count)}
          caption={d.overdue_intake_calls_count > 0 ? `${d.overdue_intake_calls_count} overdue` : 'None overdue'}
          captionColor={colors.warning}
        />
        <StatTile
          label="No-shows · week"
          value={String(d.no_shows_count)}
          caption={d.no_show_follow_ups_needed > 0 ? `${d.no_show_follow_ups_needed} need follow-up` : 'All followed up'}
          captionColor={colors.warning}
        />
        <StatTile
          label="Waitlist"
          value={String(d.waitlist_count)}
          caption={`${d.openings_this_week} openings this week`}
          captionColor={colors.warning}
        />
        <StatTile
          label="Sessions today"
          value={String(d.sessions_today_count)}
          caption={`${d.rooms_in_use_count} ${plural(d.rooms_in_use_count, 'room')} · ${d.active_therapists_count} ${plural(d.active_therapists_count, 'therapist')}`}
        />
        <AttendanceTile label="Attendance · 30d" rate={d.attendance_rate} delta={d.attendance_delta} />
      </StatsGrid>

      <ScheduleTodayCard title="Today's schedule" sessions={d.today_sessions} showTherapist />
      <IntakePipelineCard
        leads={d.intake_pipeline}
        onViewLeads={canAccessFeature(user, 'leads') ? () => router.navigate('/leads') : undefined}
      />
      {hasInbox ? <InboxPreviewCard contacts={d.whatsapp_inbox} /> : null}
      <NoShowsCard sessions={d.no_show_follow_up_list} />
    </Screen>
  );
}

function IntakePipelineCard({ leads, onViewLeads }: { leads: IntakeLead[]; onViewLeads?: () => void }) {
  return (
    <Card>
      <CardHeader
        title="Intake pipeline — awaiting scheduling"
        actionLabel={onViewLeads ? 'View leads →' : undefined}
        onAction={onViewLeads}
      />
      {leads.length === 0 ? (
        <>
          <Divider />
          <AppText variant="caption" style={styles.emptyInline}>
            No leads waiting on an intake call.
          </AppText>
        </>
      ) : (
        leads.map((lead) => (
          <View key={lead.id}>
            <Divider />
            <Pressable
              onPress={() => router.push({ pathname: '/leads/[id]', params: { id: String(lead.id) } }, { withAnchor: true })}
              disabled={!onViewLeads}
              style={({ pressed }) => [styles.intakeRow, pressed && panelStyles.pressed]}
              accessibilityRole="button">
              <View style={panelStyles.rowMain}>
                <AppText variant="bodyStrong">{lead.parent_guardian_name ?? lead.child_name}</AppText>
                <AppText variant="caption">
                  {lead.interested_in ?? 'Interest not captured'} · {lead.source}
                </AppText>
              </View>
              <Chip
                label={lead.is_overdue ? 'Overdue' : 'Awaiting call'}
                colors={lead.is_overdue ? noteAgeColors.overdue : noteAgeColors.pending}
              />
            </Pressable>
          </View>
        ))
      )}
    </Card>
  );
}

function NoShowsCard({ sessions }: { sessions: CalendarSessionPayload[] }) {
  return (
    <Card style={styles.noShows}>
      <AppText variant="heading" color={planCardColors.fg} style={styles.noShowTitle}>
        No-shows needing follow-up
      </AppText>
      {sessions.length === 0 ? (
        <AppText variant="caption" color={planCardColors.fg}>
          No outstanding no-show follow-ups.
        </AppText>
      ) : (
        sessions.map((s) => (
          <Pressable
            key={s.id}
            onPress={() => router.push({ pathname: '/calendar/[id]', params: { id: String(s.id) } }, { withAnchor: true })}
            style={({ pressed }) => [styles.noShowRow, pressed && styles.dim]}
            accessibilityRole="button">
            <AppText variant="bodyStrong" style={styles.flex}>
              {s.patient_name}
            </AppText>
            <AppText variant="caption" color={planCardColors.fg}>
              Missed {s.activity_type} · {formatWeekdayShort(s.session_date)} {formatTimeShort(s.start_time)}
            </AppText>
          </Pressable>
        ))
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  dim: { opacity: 0.6 },
  emptyInline: { paddingTop: spacing.sm },
  intakeRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
  noShows: { backgroundColor: planCardColors.bg, borderColor: planCardColors.border, gap: spacing.sm },
  noShowTitle: { fontSize: 15 },
  noShowRow: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm, paddingVertical: 4 },
});
