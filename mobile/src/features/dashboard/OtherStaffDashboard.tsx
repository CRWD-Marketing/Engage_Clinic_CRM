import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, invoiceStatusColors, neutralChip, spacing } from '@/theme';
import { formatDayMonthYear, isoToYmd } from '@/utils/dates';
import { formatMoney, roleLabel } from '@/utils/format';

import { DashboardHeader, panelStyles, StatsGrid } from './panels';

/** Mirrors resources/views/dashboard/other_staff.blade.php. Opening an invoice stays on the web (Billing). */
export function OtherStaffDashboard() {
  const query = useApiQuery('dashboard.otherStaff', () => api.dashboard.otherStaff());
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
      <Button title="Open billing" onPress={() => router.push('/billing')} />

      <StatsGrid>
        <StatTile label="Draft quotations" value={String(d.draft_quotations_count)} caption="not yet submitted" />
        <StatTile
          label="Awaiting payment"
          value={String(d.awaiting_payment_count)}
          caption="submitted or pending info"
          captionColor={colors.warning}
        />
        <StatTile label="Paid · this month" value={String(d.paid_this_month_count)} caption="invoices settled" captionColor={colors.success} />
      </StatsGrid>

      <Card>
        <CardHeader title="Recent invoices & quotations" />
        {d.recent_invoices.length === 0 ? (
          <AppText variant="caption">No invoices or quotations yet.</AppText>
        ) : (
          d.recent_invoices.map((i) => {
            const status = invoiceStatusColors[i.status];
            return (
              <View key={i.id}>
                <Divider />
                <Pressable
                  style={styles.row}
                  accessibilityRole="button"
                  accessibilityLabel={`Open ${i.invoice_number}`}
                  onPress={() => router.push({ pathname: '/billing/[id]', params: { id: String(i.id) } })}>
                  <View style={panelStyles.rowMain}>
                    <AppText variant="bodyStrong">
                      {i.invoice_number} · {i.name}
                    </AppText>
                    <AppText variant="caption">
                      {i.payer} · issued {formatDayMonthYear(isoToYmd(i.issue_date))}
                    </AppText>
                  </View>
                  <View style={styles.right}>
                    <AppText variant="bodyStrong">AED {formatMoney(Number(i.subtotal), 0)}</AppText>
                    <Chip label={status?.label ?? roleLabel(i.status)} colors={status ?? neutralChip} />
                  </View>
                </Pressable>
              </View>
            );
          })
        )}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingVertical: spacing.sm },
  right: { alignItems: 'flex-end', gap: 4 },
});
