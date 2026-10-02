import { Stack, useLocalSearchParams } from 'expo-router';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { AppText } from '@/components/AppText';
import { Card, Divider } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { StatsGrid } from '@/features/dashboard/panels';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, spacing } from '@/theme';

import { aed } from './billingFormat';

/** A family's account statement (billing/statement.blade.php): the running balance, then the sessions behind it. */
export function StatementScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const query = useApiQuery(`billing.statement:${id}`, () => api.billing.statement(Number(id)));
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const s = query.data;
  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <Stack.Screen options={{ title: `Statement · ${s.child ?? ''}` }} />

      <Card style={styles.gap}>
        <AppText variant="caption">Account statement · not a tax invoice</AppText>
        <AppText variant="heading">{s.bill_to || 'Parent / guardian'}</AppText>
        <AppText variant="caption">
          Patient: {s.child} · Payer: {s.payer} · as of {s.as_of}
        </AppText>
      </Card>

      <StatsGrid>
        <StatTile label="Charged" value={aed(s.charged, 0)} caption="invoices" />
        <StatTile label="Credited" value={aed(s.credited, 0)} caption="payments and credit notes" captionColor={colors.success} />
        <StatTile
          label="Balance owed"
          value={aed(Math.max(0, s.balance), 0)}
          caption={s.oldest_open ? `oldest ${s.oldest_open.number}` : 'nothing open'}
          captionColor={s.balance > 0.01 ? colors.danger : colors.success}
        />
        <StatTile label="Entries" value={String(s.rows.length)} caption="on this statement" />
      </StatsGrid>

      {s.oldest_open ? (
        <AppText variant="caption">
          Oldest open item: {s.oldest_open.number}, due {s.oldest_open.due_label} — {Math.max(0, s.oldest_open.days_past_due)} days past due.
        </AppText>
      ) : null}

      <Card padded={false}>
        {s.rows.length === 0 ? (
          <EmptyRow text="Nothing has been invoiced to this family yet." />
        ) : (
          s.rows.map((r, i) => (
            <View key={`${r.ref}:${i}`}>
              {i > 0 ? <Divider /> : null}
              <View style={styles.row} accessible accessibilityLabel={`${r.ref}, ${r.desc}, balance ${aed(r.balance)}`}>
                <View style={styles.flex}>
                  <AppText variant="bodyStrong">{r.ref}</AppText>
                  <AppText variant="caption">{r.desc}</AppText>
                  <AppText variant="caption">{r.date_label}</AppText>
                </View>
                <View style={styles.right}>
                  {r.charge > 0 ? <AppText variant="bodyStrong">{aed(r.charge)}</AppText> : null}
                  {r.credit > 0 ? (
                    <AppText variant="bodyStrong" color={colors.success}>
                      – {aed(r.credit)}
                    </AppText>
                  ) : null}
                  <AppText variant="caption">balance {aed(r.balance)}</AppText>
                </View>
              </View>
            </View>
          ))
        )}
      </Card>

      <View>
        <AppText variant="heading">Sessions delivered</AppText>
        <AppText variant="caption">
          {s.sessions.length} session{s.sessions.length === 1 ? '' : 's'} · {s.session_hours} billable h · {aed(s.session_charged)} charged
        </AppText>
      </View>
      <Card padded={false}>
        {s.sessions.length === 0 ? (
          <EmptyRow text="No delivered sessions on file for this client yet." />
        ) : (
          s.sessions.map((r, i) => (
            <View key={r.id}>
              {i > 0 ? <Divider /> : null}
              <View style={styles.row}>
                <View style={styles.flex}>
                  <AppText variant="bodyStrong">{r.service_label}</AppText>
                  <AppText variant="caption">
                    {r.date_label} · {r.start_time}–{r.end_time} · {r.therapist_name}
                  </AppText>
                  <AppText variant="caption" color={r.bill_hours > 0 ? colors.textSecondary : colors.textMuted}>
                    {r.charge_rule}
                  </AppText>
                </View>
                <View style={styles.right}>
                  <AppText variant="bodyStrong">{aed(r.gross)}</AppText>
                  <AppText variant="caption">{r.invoice_number ?? r.billing_status}</AppText>
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
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.xs },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  right: { alignItems: 'flex-end', gap: 2 },
});
