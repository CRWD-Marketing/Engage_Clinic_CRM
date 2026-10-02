import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BillingOverview, BulkRunGroup } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, spacing } from '@/theme';

import { aed } from './billingFormat';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** The "Bulk run" tab of billing/index.blade.php: one invoice per family for every unbilled session in a period. */
export function BulkRunTab({ billing: d, onChanged }: { billing: BillingOverview; onChanged: () => void }) {
  const [from, setFrom] = useState(d.bulk_defaults.from);
  const [to, setTo] = useState(d.bulk_defaults.to);
  const [payer, setPayer] = useState('all');
  const [selected, setSelected] = useState<number[]>([]);
  const [busy, setBusy] = useState(false);
  const [flash, setFlash] = useState<Flash>(null);
  const query = useApiQuery(`billing.bulk:${from}:${to}:${payer}`, () => api.billing.bulkPreview({ from, to, payer }));

  const groups = query.data?.groups ?? [];
  // Families that dropped out of the list (a new period or payer) are no longer selected.
  const chosen = groups.filter((g) => selected.includes(g.patient_id));
  const sum = (pick: (g: BulkRunGroup) => number) => chosen.reduce((total, g) => total + pick(g), 0);
  const allSelected = groups.length > 0 && chosen.length === groups.length;
  const toggle = (id: number) => setSelected((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));

  async function issue() {
    setBusy(true);
    setFlash(null);
    try {
      const res = await api.billing.bulkIssue({ from, to, payer, patient_ids: chosen.map((g) => g.patient_id) });
      setFlash({ text: res.message, tone: 'success' });
      setSelected([]);
      query.reload();
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  return (
    <>
      <Card style={styles.gap}>
        <AppText variant="heading">Bulk invoice run</AppText>
        <AppText variant="caption">
          Every delivered session in the period that has not been invoiced yet, grouped by family. One invoice is raised per family.
        </AppText>
        <DateField label="Period from" value={from} onChange={setFrom} required />
        <DateField label="Period to" value={to} onChange={setTo} required />
        <OptionPills
          label="Payer"
          options={[{ value: 'all', label: 'All payers' }, ...d.payers.map((p) => ({ value: p, label: p }))]}
          value={payer}
          onChange={setPayer}
        />
      </Card>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      {!query.data ? (
        query.error ? (
          <ErrorState error={query.error} onRetry={query.refresh} />
        ) : (
          <LoadingState />
        )
      ) : (
        <>
          {groups.length > 0 ? (
            <Button title={allSelected ? 'Clear selection' : 'Select all'} variant="secondary" onPress={() => setSelected(allSelected ? [] : groups.map((g) => g.patient_id))} />
          ) : null}
          <Card padded={false}>
            {groups.length === 0 ? (
              <EmptyRow text="Nothing to invoice — every session in this period has already been billed." />
            ) : (
              groups.map((g, i) => {
                const checked = selected.includes(g.patient_id);
                return (
                  <View key={g.patient_id}>
                    {i > 0 ? <Divider /> : null}
                    <Pressable
                      onPress={() => toggle(g.patient_id)}
                      accessibilityRole="checkbox"
                      accessibilityState={{ checked }}
                      accessibilityLabel={`Invoice ${g.patient}`}
                      style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
                      <View style={[styles.box, checked && styles.boxOn]}>{checked ? <AppText style={styles.tick}>✓</AppText> : null}</View>
                      <View style={styles.flex}>
                        <AppText variant="bodyStrong">{g.patient}</AppText>
                        <AppText variant="caption">
                          {g.parent || 'No parent on file'} · {g.payer}
                        </AppText>
                        <AppText variant="caption">
                          {g.sessions} session{g.sessions === 1 ? '' : 's'} · {g.hours} h · net {aed(g.net)} · VAT {aed(g.vat)}
                        </AppText>
                        <AppText variant="caption">
                          Insurer share {aed(g.insurer_share)} · family {aed(g.family_share)}
                        </AppText>
                        {g.adjusted ? (
                          <AppText variant="caption" color={colors.warning}>
                            {g.adjusted} session(s) adjusted by cancellation policy
                          </AppText>
                        ) : null}
                      </View>
                      <AppText variant="bodyStrong" color={colors.pink}>
                        {aed(g.total)}
                      </AppText>
                    </Pressable>
                  </View>
                );
              })
            )}
          </Card>

          {chosen.length > 0 ? (
            <AppText variant="bodyStrong">
              {chosen.length} famil{chosen.length === 1 ? 'y' : 'ies'} selected — {sum((g) => g.sessions)} sessions, {sum((g) => g.hours)} billable hours,{' '}
              {aed(sum((g) => g.total))} total.
            </AppText>
          ) : null}
          {d.can_invoice ? <Button title="Issue invoices" disabled={chosen.length === 0} loading={busy} onPress={issue} /> : null}
        </>
      )}
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  box: {
    width: 22,
    height: 22,
    marginTop: 2,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.border,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
  boxOn: { backgroundColor: colors.navy, borderColor: colors.navy },
  tick: { color: colors.white, fontSize: 13, lineHeight: 16 },
});
