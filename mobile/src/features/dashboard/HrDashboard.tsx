import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, spacing } from '@/theme';
import { formatDayMonthYear, isoToYmd } from '@/utils/dates';
import { plural, roleLabel } from '@/utils/format';

import { AttendanceTile, BarListCard, DashboardHeader, panelStyles, ScheduleTodayCard, StatsGrid } from './panels';

/** Mirrors resources/views/dashboard/hr_staff.blade.php. Adding and opening staff stays on the web (User Management). */
export function HrDashboard() {
  const query = useApiQuery('dashboard.hr', () => api.dashboard.hr());
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <DashboardHeader name={d.user_full_name} location={d.location_label} />

      <StatsGrid>
        <StatTile label="Active staff" value={String(d.active_staff_count)} caption={`${d.new_hires_this_month} started this month`} />
        <StatTile
          label="Sessions today"
          value={String(d.sessions_today_count)}
          caption={`${d.rooms_in_use_count} ${plural(d.rooms_in_use_count, 'room')} · ${d.active_therapists_count} ${plural(d.active_therapists_count, 'therapist')}`}
        />
        <AttendanceTile label="Attendance · 30d" rate={d.attendance_rate} delta={d.attendance_delta} />
        <StatTile label="Roles on team" value={String(d.staff_by_role.length)} caption="across the clinic" />
      </StatsGrid>

      <ScheduleTodayCard title="Today's schedule" sessions={d.today_sessions} showTherapist />

      <BarListCard
        title="Staff by role"
        empty="No staff records yet."
        rows={d.staff_by_role.map((r) => ({ label: roleLabel(r.role), value: r.count, text: String(r.count), color: colors.navy }))}
      />

      <Card>
        <CardHeader title="Recently added staff" />
        {d.recent_staff.length === 0 ? (
          <AppText variant="caption">No staff records yet.</AppText>
        ) : (
          d.recent_staff.map((s) => (
            <View key={s.id}>
              <Divider />
              <View style={styles.row}>
                <Avatar id={s.id} name={s.full_name} size={36} />
                <View style={panelStyles.rowMain}>
                  <AppText variant="bodyStrong">{s.full_name}</AppText>
                  <AppText variant="caption">
                    {s.job_title ?? roleLabel(s.role)}
                    {s.start_date ? ` · started ${formatDayMonthYear(isoToYmd(s.start_date))}` : ''}
                  </AppText>
                </View>
              </View>
            </View>
          ))
        )}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingVertical: spacing.sm },
});
