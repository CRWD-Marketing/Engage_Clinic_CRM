import { router } from 'expo-router';

import { api } from '@/api/client';
import { canAccessFeature } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { Button } from '@/components/Button';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors } from '@/theme';
import { formatMoney, plural } from '@/utils/format';

import { BarListCard, DashboardHeader, StatsGrid } from './panels';

const aed = (amount: number) => `AED ${formatMoney(amount, 0)}`;

/** Mirrors resources/views/dashboard/finance_staff.blade.php. Billing itself stays on the web. */
export function FinanceDashboard() {
  const user = useCurrentUser();
  const query = useApiQuery('dashboard.finance', () => api.dashboard.finance());
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const delta = d.revenue_delta;
  const old = d.aging_buckets[d.aging_buckets.length - 1];
  const openReports = canAccessFeature(user, 'reports') ? () => router.push('/reports') : undefined;

  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <DashboardHeader name={d.user_full_name} location={d.location_label} />
      {canAccessFeature(user, 'billing') ? <Button title="Open billing" onPress={() => router.push('/billing')} /> : null}

      <StatsGrid>
        <StatTile
          label="Revenue · MTD"
          value={aed(d.revenue_mtd)}
          caption={
            delta === null
              ? `No ${d.last_month_name} data to compare`
              : `${delta > 0 ? '▲' : delta < 0 ? '▼' : '–'} ${Math.abs(delta)}% vs ${d.last_month_name}`
          }
          captionColor={delta === null || delta === 0 ? colors.textWarm : delta > 0 ? colors.success : colors.danger}
        />
        <StatTile
          label="Collected · MTD"
          value={aed(d.collected_mtd)}
          caption={d.revenue_mtd > 0 ? `${Math.round((d.collected_mtd / d.revenue_mtd) * 100)}% of invoiced` : '—'}
        />
        <StatTile
          label="Claims pending"
          value={aed(d.claims_pending_amount)}
          caption={
            d.claims_pending_count > 0
              ? `${d.claims_pending_count} ${plural(d.claims_pending_count, 'claim')} · oldest ${d.oldest_claim_days}d`
              : 'No pending claims'
          }
          captionColor={colors.warning}
        />
        <StatTile
          label="60+ days aging"
          value={aed(old.amount)}
          caption={`${old.count} ${plural(old.count, 'claim')}`}
          captionColor={colors.danger}
        />
      </StatsGrid>

      <BarListCard
        title="Revenue by payer · this month"
        empty="No invoices issued this month yet."
        actionLabel={openReports ? 'Reports →' : undefined}
        onAction={openReports}
        rows={d.revenue_by_payer.map((r) => ({ label: r.payer || 'Other', value: r.total, text: aed(r.total), color: r.color }))}
      />
      <BarListCard
        title="Claims aging"
        empty="No pending claims."
        rows={d.aging_buckets.map((b) => ({ label: b.label, value: b.amount, text: aed(b.amount), color: colors.warning }))}
      />
    </Screen>
  );
}
