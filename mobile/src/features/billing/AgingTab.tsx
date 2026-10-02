import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import type { BillingAging } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { StatTile } from '@/components/StatTile';
import { EmptyRow } from '@/components/StateViews';
import { BarListCard, StatsGrid } from '@/features/dashboard/panels';
import { agingBucketColors, billingStatusColors, colors, spacing } from '@/theme';

import { aed } from './billingFormat';

const SETTLED = 'Nothing outstanding — every issued invoice is settled.';

/** The "Aging & statements" tab of billing/index.blade.php. */
export function AgingTab({ aging }: { aging: BillingAging }) {
  return (
    <>
      <StatsGrid>
        <StatTile label="Total outstanding" value={aed(aging.total_outstanding, 0)} caption={`${aging.open_count} open invoices`} />
        <StatTile
          label="Past due"
          value={aed(aging.past_due, 0)}
          caption={`${aging.past_due_count} past the due date`}
          captionColor={aging.past_due_count > 0 ? colors.danger : undefined}
        />
        <StatTile label="Oldest item" value={`${aging.oldest_days} d`} caption={aging.oldest_ref || '—'} />
        <StatTile label="Reminders sent" value={String(aging.reminders_month)} caption="this month" />
      </StatsGrid>

      <Card padded={false}>
        <View style={styles.header}>
          <CardHeader title="Open invoices" />
          <AppText variant="caption">Most overdue first. Open one to send a reminder or record a payment.</AppText>
        </View>
        {aging.rows.length === 0 ? (
          <EmptyRow text={SETTLED} />
        ) : (
          aging.rows.map((i) => {
            const overdue = i.days_past_due > 0;
            return (
              <View key={i.id}>
                <Divider />
                <Pressable
                  onPress={() => router.push({ pathname: '/billing/[id]', params: { id: String(i.id) } })}
                  accessibilityRole="button"
                  accessibilityLabel={`Open invoice ${i.number}, ${i.patient}, ${i.age_label}`}
                  style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
                  <View style={styles.flex}>
                    <AppText variant="bodyStrong">{i.patient}</AppText>
                    <AppText variant="caption">
                      {i.number} · {i.payer} · due {i.due_label}
                    </AppText>
                    {i.reminder_sent_at ? (
                      <AppText variant="caption" color={colors.navy}>
                        Reminder sent {i.reminder_sent_at}
                      </AppText>
                    ) : null}
                  </View>
                  <View style={styles.right}>
                    <AppText variant="bodyStrong" color={colors.danger}>
                      {aed(i.balance)}
                    </AppText>
                    <Chip label={i.age_label} colors={overdue ? billingStatusColors.outstanding : billingStatusColors.paid} />
                  </View>
                </Pressable>
              </View>
            );
          })
        )}
      </Card>

      <BarListCard
        title="Aging buckets"
        empty={SETTLED}
        rows={aging.buckets.map((b) => ({
          label: b.label,
          value: b.amount,
          text: `${aed(b.amount, 0)} · ${b.count}`,
          color: agingBucketColors[b.key] ?? colors.navy,
        }))}
      />
      <BarListCard
        title="Outstanding by payer"
        empty={SETTLED}
        rows={aging.by_payer.map((p) => ({ label: p.payer, value: p.amount, text: aed(p.amount, 0), color: colors.navy }))}
      />

      <Card padded={false}>
        <View style={styles.header}>
          <CardHeader title="Family statements" />
          <AppText variant="caption">Running balance across every invoice, credit note and payment.</AppText>
        </View>
        {aging.families.length === 0 ? (
          <EmptyRow text="No family has been invoiced yet." />
        ) : (
          aging.families.map((f) => (
            <View key={f.patient_id}>
              <Divider />
              <Pressable
                onPress={() => router.push({ pathname: '/billing/statement/[id]', params: { id: String(f.patient_id) } })}
                accessibilityRole="button"
                accessibilityLabel={`Open statement for ${f.patient}`}
                style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
                <View style={styles.flex}>
                  <AppText variant="bodyStrong">{f.patient}</AppText>
                  <AppText variant="caption">
                    {f.parent || 'No parent on file'} · {f.payer}
                  </AppText>
                  <AppText variant="caption">
                    {f.invoices} invoice{f.invoices === 1 ? '' : 's'} · billed {aed(f.billed, 0)}
                  </AppText>
                </View>
                <View style={styles.right}>
                  <AppText variant="bodyStrong" color={f.balance > 0.01 ? colors.danger : colors.success}>
                    {aed(Math.max(0, f.balance))}
                  </AppText>
                  <AppText variant="caption">balance</AppText>
                </View>
              </Pressable>
            </View>
          ))
        )}
      </Card>
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  header: { paddingHorizontal: spacing.lg, paddingTop: spacing.lg, paddingBottom: spacing.md },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  right: { alignItems: 'flex-end', gap: 4 },
});
