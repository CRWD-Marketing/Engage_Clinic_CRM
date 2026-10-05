import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { ExpiringAuthorization, LeadSourceRow } from '@/api/types';
import { canAccessFeature, canWriteIn } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, CardHeader } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { formatAed } from '@/features/leads/leadFormat';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, fonts, leadSourceBarColors, leadSourceBarDefault, planCardColors, spacing } from '@/theme';
import { formatDayMonth, isoToYmd } from '@/utils/dates';
import { plural } from '@/utils/format';

import {
  AttendanceTile,
  DashboardHeader,
  InboxPreviewCard,
  openPatient,
  ScheduleTodayCard,
  StatsGrid,
  WaitlistCard,
} from './panels';

const arrow = (delta: number) => (delta > 0 ? '▲' : delta < 0 ? '▼' : '–');
const deltaColor = (delta: number | null) =>
  delta === null || delta === 0 ? colors.textWarm : delta > 0 ? colors.success : colors.danger;

/** Mirrors resources/views/dashboard/admin.blade.php. */
export function AdminDashboard() {
  const user = useCurrentUser();
  const query = useApiQuery('dashboard.admin', () => api.dashboard.admin());
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const canOpenPatients = canAccessFeature(user, 'patients');

  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <DashboardHeader name={d.user_full_name} location={d.location_label} />
      {canWriteIn(user, 'leads') ? (
        <Button title="+ New Lead" onPress={() => router.push('/leads/new', { withAnchor: true })} />
      ) : null}

      <StatsGrid>
        <StatTile
          label="New leads · 7 days"
          value={String(d.new_leads_count)}
          caption={`${arrow(d.new_leads_delta)} ${Math.abs(d.new_leads_delta)}% vs prev. 7 days`}
          captionColor={deltaColor(d.new_leads_delta)}
        />
        <StatTile
          label="Sessions today"
          value={String(d.sessions_today_count)}
          caption={`${d.rooms_in_use_count} ${plural(d.rooms_in_use_count, 'room')} · ${d.active_therapists_count} ${plural(d.active_therapists_count, 'therapist')}`}
        />
        <AttendanceTile label="Attendance · 30d" rate={d.attendance_rate} delta={d.attendance_delta} />
        <StatTile
          label="Revenue · MTD"
          value={formatAed(d.revenue_mtd)}
          caption={
            d.revenue_delta === null
              ? `No ${d.last_month_name} data to compare`
              : `${arrow(d.revenue_delta)} ${Math.abs(d.revenue_delta)}% vs ${d.last_month_name}`
          }
          captionColor={deltaColor(d.revenue_delta)}
        />
        <StatTile
          label="Waitlist"
          value={String(d.waitlist_count)}
          caption={d.waitlist_count > 0 ? `avg. wait ${d.avg_wait_weeks} wks` : 'No one waiting'}
          captionColor={colors.warning}
        />
        <StatTile
          label="Claims pending"
          value={formatAed(d.claims_pending_amount)}
          caption={
            d.claims_pending_count > 0
              ? `${d.claims_pending_count} ${plural(d.claims_pending_count, 'claim')} · oldest ${d.oldest_claim_days}d`
              : 'No pending claims'
          }
          captionColor={colors.warning}
        />
      </StatsGrid>

      {canAccessFeature(user, 'billing') ? (
        <Button title="Open billing" variant="secondary" onPress={() => router.push('/billing')} />
      ) : null}
      {canAccessFeature(user, 'users') ? (
        <Button title="Open user management" variant="secondary" onPress={() => router.push('/users')} />
      ) : null}
      <ScheduleTodayCard title="Today's schedule" sessions={d.today_sessions} showTherapist />
      <LeadSourcesCard
        rows={d.lead_sources}
        scale={d.lead_sources_scale}
        onReports={canAccessFeature(user, 'reports') ? () => router.push('/reports') : undefined}
      />
      {canAccessFeature(user, 'whatsapp') ? <InboxPreviewCard contacts={d.whatsapp_inbox} /> : null}
      <AuthorizationsCard rows={d.authorizations_expiring} onOpen={canOpenPatients ? openPatient : undefined} />
      <WaitlistCard entries={d.waitlist_next_up} />
    </Screen>
  );
}

function LeadSourcesCard({ rows, scale, onReports }: { rows: LeadSourceRow[]; scale: number; onReports?: () => void }) {
  return (
    <Card style={styles.gap}>
      <CardHeader title="Lead sources · last 7 days" actionLabel={onReports ? 'Reports →' : undefined} onAction={onReports} />
      {rows.length === 0 ? (
        <AppText variant="caption">No leads captured in the last 7 days.</AppText>
      ) : (
        rows.map((row) => {
          // Fixed 0–scale axis; a non-empty bar never drops below 4% so it stays visible.
          const width = row.count > 0 ? Math.max(4, Math.min(100, Math.round((row.count / scale) * 100))) : 0;
          return (
            <View key={row.source} style={styles.sourceRow} accessible accessibilityLabel={`${row.source}: ${row.count}`}>
              <AppText style={styles.sourceName} numberOfLines={1}>
                {row.source || 'Other'}
              </AppText>
              <View style={styles.track}>
                <View
                  style={[
                    styles.bar,
                    { width: `${width}%`, backgroundColor: leadSourceBarColors[row.source] ?? leadSourceBarDefault },
                  ]}
                />
              </View>
              <AppText style={styles.sourceCount}>{row.count}</AppText>
            </View>
          );
        })
      )}
    </Card>
  );
}

function AuthorizationsCard({ rows, onOpen }: { rows: ExpiringAuthorization[]; onOpen?: (patientId: number) => void }) {
  return (
    <Card style={styles.authCard}>
      <AppText variant="heading" color={planCardColors.fg} style={styles.authTitle}>
        Authorizations expiring
      </AppText>
      {rows.length === 0 ? (
        <AppText variant="caption" color={planCardColors.fg}>
          No authorizations expiring in the next 45 days.
        </AppText>
      ) : (
        rows.map((a) => (
          <Pressable
            key={a.id}
            onPress={() => onOpen?.(a.patient_id)}
            disabled={!onOpen}
            style={({ pressed }) => [styles.authRow, pressed && styles.dim]}
            accessibilityRole="button">
            <AppText variant="bodyStrong">{a.child_name}</AppText>
            <AppText variant="caption" color={planCardColors.fg}>
              {a.payer_name} · {a.hours_left} hours left ·{' '}
              {a.days_to_renew >= 0
                ? `renews ${formatDayMonth(isoToYmd(a.renews_at))}`
                : `renewal overdue ${Math.abs(a.days_to_renew)}d`}
            </AppText>
          </Pressable>
        ))
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  gap: { gap: spacing.sm },
  dim: { opacity: 0.6 },
  sourceRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  sourceName: { width: 86, fontFamily: fonts.bodyBold, fontSize: 13, color: colors.text },
  track: { flex: 1, height: 10, borderRadius: 5, backgroundColor: colors.divider, overflow: 'hidden' },
  bar: { height: '100%', borderRadius: 5 },
  sourceCount: { width: 24, textAlign: 'right', fontFamily: fonts.bodyExtraBold, fontSize: 13, color: colors.navy },
  authCard: { backgroundColor: planCardColors.bg, borderColor: planCardColors.border, gap: spacing.sm },
  authTitle: { fontSize: 15 },
  authRow: { gap: 2, paddingVertical: 2 },
});
