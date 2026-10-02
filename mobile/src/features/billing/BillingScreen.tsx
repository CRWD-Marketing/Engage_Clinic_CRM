import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { BillingInvoice, BillingOverview } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { BarListCard, StatsGrid } from '@/features/dashboard/panels';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, reportColors, spacing } from '@/theme';

import { AgingTab } from './AgingTab';
import { aed, STATUS_FILTERS, statusColors } from './billingFormat';

type Filter = (typeof STATUS_FILTERS)[number]['value'];
type Tab = 'invoices' | 'aging';

/** billing/index.blade.php: the invoices tab (tiles, payer mix, invoice list) and the aging & statements tab. */
export function BillingScreen() {
  const query = useApiQuery('billing.overview', () => api.billing.overview());
  useRefetchOnFocus(query.reload);
  const [tab, setTab] = useState<Tab>('invoices');

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <AppText variant="caption">
        {d.month_label} · {d.payer_summary}
        {d.can_invoice ? '' : ' · view only: invoices are raised by Finance'}
      </AppText>
      {d.can_invoice ? <Button title="New invoice" onPress={() => router.push('/billing/new')} /> : null}
      <OptionPills<Tab>
        options={[
          { value: 'invoices', label: 'Invoices' },
          { value: 'aging', label: 'Aging & statements' },
        ]}
        value={tab}
        onChange={setTab}
      />

      {tab === 'aging' ? <AgingTab aging={d.aging} /> : <InvoicesTab billing={d} />}
    </Screen>
  );
}

function InvoicesTab({ billing: d }: { billing: BillingOverview }) {
  const [filter, setFilter] = useState<Filter>('all');
  const [search, setSearch] = useState('');
  const term = search.trim().toLowerCase();
  const shown = d.invoices.filter(
    (i) =>
      (filter === 'all' || i.status === filter) &&
      (!term || `${i.number} ${i.patient} ${i.parent} ${i.payer}`.toLowerCase().includes(term)),
  );
  const count = (f: Filter) => (f === 'all' ? d.invoices.length : d.invoices.filter((i) => i.status === f).length);

  return (
    <>
      <StatsGrid>
        <StatTile label="Invoiced · MTD" value={aed(d.tiles.invoiced_mtd, 0)} caption="VAT incl., not voided" />
        <StatTile label="Collected" value={aed(d.tiles.collected_mtd, 0)} caption="receipts this month" captionColor={colors.success} />
        <StatTile label="Outstanding claims" value={aed(d.tiles.outstanding_claims, 0)} caption="with insurers" captionColor={colors.warning} />
        <StatTile label="Avg. claim cycle" value={`${d.tiles.avg_claim_cycle} d`} caption="submitted to settled" />
      </StatsGrid>

      <BarListCard
        title="Revenue by payer · this month"
        empty="No invoices issued this month yet."
        rows={d.revenue_by_payer.map((r, i) => ({
          label: r.payer,
          value: r.amount,
          text: `${r.pct}%`,
          color: reportColors.funnel[i % reportColors.funnel.length],
        }))}
      />

      <TextField
        label="Search invoices"
        value={search}
        onChangeText={setSearch}
        placeholder="Invoice number, client, parent or payer"
        autoCapitalize="none"
        autoCorrect={false}
        clearButtonMode="while-editing"
      />
      <OptionPills<Filter>
        options={STATUS_FILTERS.map((f) => ({ value: f.value, label: `${f.label} · ${count(f.value)}` }))}
        value={filter}
        onChange={setFilter}
      />

      <Card padded={false}>
        {shown.length === 0 ? (
          <EmptyRow text={d.invoices.length === 0 ? 'No invoices raised yet.' : 'No invoices match.'} />
        ) : (
          shown.map((invoice, i) => (
            <View key={invoice.id}>
              {i > 0 ? <Divider /> : null}
              <InvoiceRowItem invoice={invoice} />
            </View>
          ))
        )}
      </Card>
    </>
  );
}

function InvoiceRowItem({ invoice: i }: { invoice: BillingInvoice }) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/billing/[id]', params: { id: String(i.id) } })}
      accessibilityRole="button"
      accessibilityLabel={`${i.number}, ${i.patient}, ${i.status_label}`}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
      <View style={styles.flex}>
        <AppText variant="bodyStrong">
          {i.number} · {i.patient}
        </AppText>
        <AppText variant="caption">
          {i.payer} · {i.period} · issued {i.issued_label}
        </AppText>
        {i.status === 'outstanding' || i.status === 'partly_paid' ? (
          <AppText variant="caption" color={i.days_past_due > 0 ? colors.danger : colors.textMuted}>
            {i.age_label} · balance {aed(i.balance)}
          </AppText>
        ) : null}
      </View>
      <View style={styles.right}>
        <AppText variant="bodyStrong">{aed(i.total, 0)}</AppText>
        <Chip label={i.status_label} colors={statusColors(i)} />
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  right: { alignItems: 'flex-end', gap: 4 },
});
