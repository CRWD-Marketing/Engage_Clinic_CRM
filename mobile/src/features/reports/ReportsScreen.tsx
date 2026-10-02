import type { ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { ReportsData } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Card, Divider } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, fonts, reportColors, spacing } from '@/theme';
import { formatDateTimeShort } from '@/utils/dates';
import { formatMoney, plural, trimNumber } from '@/utils/format';

const aed = (amount: number, decimals = 2) => `AED ${formatMoney(amount, decimals)}`;
/** Bar width as a share of the largest value; a non-zero bar never drops below 2%. */
const share = (value: number, max: number) => (value > 0 && max > 0 ? Math.max(2, Math.round((value / max) * 100)) : 0);

/** Mirrors resources/views/report/index.blade.php (read-only; PDF export stays on the web). */
export function ReportsScreen() {
  const query = useApiQuery('reports.index', () => api.reports.index());

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
        {d.period_start_label} – {d.period_end_label} · updated {formatDateTimeShort(d.updated_at)}
      </AppText>

      <VatCard d={d} />
      <CollectionCard d={d} />
      <RevenueSummaryCard d={d} />
      <RevenueByMonthCard d={d} />
      <BarsCard
        title="Revenue by service"
        subtitle="Billable session value from invoiced line items"
        empty="No invoiced sessions in this period yet."
        rows={d.revenue_by_service.map((r) => ({ label: r.label, value: r.amount, text: aed(r.amount), color: r.color }))}
      />
      <BarsCard
        title="Revenue by setting & therapist"
        subtitle="Where the work happens and who delivered it"
        empty="No invoiced sessions in this period yet."
        rows={[
          ...d.revenue_by_setting.map((r) => ({ label: r.label, value: r.amount, text: aed(r.amount), color: colors.navy })),
          ...d.revenue_by_therapist.map((r) => ({ label: r.label, value: r.amount, text: aed(r.amount), color: colors.pink })),
        ]}
      />
      <FunnelCard d={d} />
      <LeadSourcesCard d={d} />
      <BarsCard
        title="Why leads are lost"
        subtitle={`${d.lost_leads_count} terminated ${plural(d.lost_leads_count, 'lead')} · reasons captured at termination`}
        empty="No terminated leads in this period."
        rows={d.lost_reasons.map((r) => ({
          label: r.reason,
          value: r.count,
          text: `${r.count} ${plural(r.count, 'lead')}`,
          color: colors.pink,
        }))}
        footer={
          d.lost_reasons.length > 0
            ? `${aed(d.lost_leads_value)} of estimated monthly value lost · biggest driver: ${d.lost_reasons[0].reason}`
            : undefined
        }
      />
      <BarsCard
        title={`Therapy hours delivered · ${d.therapy_month_label}`}
        subtitle={`${formatMoney(d.therapy_total_hours, 0)} clinical hours`}
        empty="No completed sessions yet this month."
        rows={d.therapy_hours_by_type.map((r) => ({ label: r.type, value: r.hours, text: `${trimNumber(r.hours)}h`, color: r.color }))}
      />
    </Screen>
  );
}

function Section({ title, subtitle, children }: { title: string; subtitle?: string; children: ReactNode }) {
  return (
    <Card style={styles.card}>
      <View>
        <AppText variant="heading">{title}</AppText>
        {subtitle ? <AppText variant="caption">{subtitle}</AppText> : null}
      </View>
      {children}
    </Card>
  );
}

function Line({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <View style={styles.line}>
      <AppText variant={strong ? 'bodyStrong' : 'body'} style={styles.flex}>
        {label}
      </AppText>
      <AppText variant="bodyStrong" color={strong ? colors.navy : undefined}>
        {value}
      </AppText>
    </View>
  );
}

function Bar({ pct, color }: { pct: number; color: string }) {
  return (
    <View style={styles.track}>
      <View style={[styles.bar, { width: `${pct}%`, backgroundColor: color }]} />
    </View>
  );
}

type BarRow = { label: string; value: number; text: string; color: string };

/** A titled list of labelled horizontal bars on one shared scale. */
function BarsCard({ title, subtitle, rows, empty, footer }: { title: string; subtitle: string; rows: BarRow[]; empty: string; footer?: string }) {
  const max = Math.max(1, ...rows.map((r) => r.value));
  return (
    <Section title={title} subtitle={subtitle}>
      {rows.length === 0 ? (
        <AppText variant="caption">{empty}</AppText>
      ) : (
        rows.map((r) => (
          <View key={r.label} style={styles.barRow} accessible accessibilityLabel={`${r.label}: ${r.text}`}>
            <View style={styles.line}>
              <AppText variant="body" style={styles.flex} numberOfLines={1}>
                {r.label}
              </AppText>
              <AppText variant="bodyStrong">{r.text}</AppText>
            </View>
            <Bar pct={share(r.value, max)} color={r.color} />
          </View>
        ))
      )}
      {footer ? <AppText variant="caption">{footer}</AppText> : null}
    </Section>
  );
}

function VatCard({ d }: { d: ReportsData }) {
  return (
    <Section title="VAT return summary" subtitle={`Current filing period · ${d.vat_filing_label} · TRN ${d.vat_trn}`}>
      <Line label="Standard-rated supplies (net)" value={aed(d.vat_standard_rated_supplies)} />
      <Line label={`Output tax at ${trimNumber(d.vat_rate)}%`} value={aed(d.vat_output_tax)} />
      <Line label="Credit notes issued" value={`– ${aed(d.vat_credit_notes_issued)}`} />
      <Divider />
      <Line label="Net VAT payable" value={aed(d.vat_net_payable)} strong />
    </Section>
  );
}

function CollectionCard({ d }: { d: ReportsData }) {
  return (
    <Section
      title="Collection rate"
      subtitle={`${aed(d.collection_collected_total)} collected of ${aed(d.collection_invoiced_total)} invoiced`}>
      <AppText style={styles.big}>{d.collection_rate_pct}%</AppText>
      <Bar pct={Math.min(100, d.collection_rate_pct)} color={colors.success} />
      <Line label="Billable session hours" value={`${trimNumber(d.collection_billable_hours)} h`} />
      <Line
        label="Effective revenue per billable hour"
        value={d.collection_revenue_per_hour !== null ? aed(d.collection_revenue_per_hour) : '—'}
      />
    </Section>
  );
}

function RevenueSummaryCard({ d }: { d: ReportsData }) {
  const delta = d.revenue_mom_delta;
  return (
    <Section title="Revenue summary" subtitle={`${d.period_start_label} – ${d.period_end_label}`}>
      {d.revenue_total_6mo === 0 ? (
        <AppText variant="caption">No invoices raised in this period yet.</AppText>
      ) : (
        <>
          <View style={styles.totalRow}>
            <AppText style={styles.big}>{aed(d.revenue_total_6mo, 0)}</AppText>
            {delta !== null ? (
              <AppText variant="bodyStrong" color={delta >= 0 ? colors.success : colors.danger}>
                {delta >= 0 ? '↑' : '↓'} {Math.abs(delta)}%
              </AppText>
            ) : null}
          </View>
          <AppText variant="caption">total invoiced, VAT excl.{delta !== null ? ' · vs last month' : ''}</AppText>
          <Line label="Monthly average" value={aed(d.revenue_average_6mo, 0)} />
          <Line label="Best month" value={`${d.revenue_best_month.label} · ${aed(d.revenue_best_month.amount, 0)}`} />
        </>
      )}
    </Section>
  );
}

function RevenueByMonthCard({ d }: { d: ReportsData }) {
  const max = Math.max(1, ...d.revenue_by_month.map((m) => m.thousands));
  return (
    <Section title="Revenue by month" subtitle="AED thousands (invoiced, VAT excl.) · * month in progress">
      <View style={styles.columns}>
        {d.revenue_by_month.map((m) => (
          <View key={m.label} style={styles.column} accessible accessibilityLabel={`${m.label}: AED ${m.thousands} thousand`}>
            <AppText variant="caption">{m.thousands < 10 ? m.thousands.toFixed(1) : Math.round(m.thousands)}</AppText>
            <View style={styles.columnTrack}>
              <View
                style={[
                  styles.columnBar,
                  { height: `${share(m.thousands, max)}%`, backgroundColor: m.is_current ? colors.pink : colors.navy },
                ]}
              />
            </View>
            <AppText variant="caption">
              {m.label}
              {m.is_current ? '*' : ''}
            </AppText>
          </View>
        ))}
      </View>
    </Section>
  );
}

function FunnelCard({ d }: { d: ReportsData }) {
  const best = d.best_source;
  const worst = d.worst_source;
  return (
    <Section
      title="Lead conversion funnel"
      subtitle={`Last 90 days${d.conversion_rate !== null ? ` · ${d.conversion_rate}% lead → enrolled` : ''}`}>
      {d.captured_count === 0 ? (
        <AppText variant="caption">No leads captured in the last 90 days.</AppText>
      ) : (
        <>
          {d.funnel_stages.map((stage, i) => (
            <View key={stage.label} style={styles.barRow} accessible accessibilityLabel={`${stage.label}: ${stage.count}`}>
              <View style={styles.line}>
                <AppText variant="body" style={styles.flex}>
                  {stage.label}
                </AppText>
                <AppText variant="bodyStrong">{stage.count}</AppText>
              </View>
              <Bar pct={share(stage.pct, 100)} color={reportColors.funnel[i % reportColors.funnel.length]} />
            </View>
          ))}
          {best || d.median_enroll_days !== null ? (
            <AppText variant="caption">
              {best && worst && best.source !== worst.source
                ? `${best.source} leads convert best (${best.conversion_rate}%) — ${worst.source} lowest (${worst.conversion_rate}%). `
                : best
                  ? `${best.source} converts at ${best.conversion_rate}%. `
                  : ''}
              {d.median_enroll_days !== null
                ? `Median time from first contact to enrollment: ${trimNumber(d.median_enroll_days)} days.`
                : ''}
            </AppText>
          ) : null}
        </>
      )}
    </Section>
  );
}

/** The web's pie chart, drawn as one stacked bar with a legend. */
function LeadSourcesCard({ d }: { d: ReportsData }) {
  return (
    <Section title="Lead sources" subtitle={`${d.captured_count} ${plural(d.captured_count, 'lead')} · last 90 days`}>
      {d.lead_sources.length === 0 ? (
        <AppText variant="caption">No leads captured in the last 90 days.</AppText>
      ) : (
        <>
          <View style={styles.stack}>
            {d.lead_sources.map((s) => (
              <View key={s.source} style={{ flex: Math.max(s.count, 0.0001), backgroundColor: s.color }} />
            ))}
          </View>
          {d.lead_sources.map((s) => (
            <View key={s.source} style={styles.legendRow} accessible accessibilityLabel={`${s.source}: ${s.pct}%`}>
              <View style={[styles.dot, { backgroundColor: s.color }]} />
              <AppText variant="body" style={styles.flex}>
                {s.source}
              </AppText>
              <AppText variant="bodyStrong">{s.pct}%</AppText>
            </View>
          ))}
        </>
      )}
    </Section>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  card: { gap: spacing.sm },
  line: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.md },
  barRow: { gap: 4 },
  track: { height: 10, borderRadius: 5, backgroundColor: colors.divider, overflow: 'hidden' },
  bar: { height: '100%', borderRadius: 5 },
  big: { fontFamily: fonts.heading, fontSize: 26, color: colors.navy },
  totalRow: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.md },
  columns: { flexDirection: 'row', gap: spacing.sm, alignItems: 'flex-end' },
  column: { flex: 1, alignItems: 'center', gap: 4 },
  columnTrack: { height: 120, width: '100%', justifyContent: 'flex-end' },
  columnBar: { width: '100%', borderTopLeftRadius: 6, borderTopRightRadius: 6 },
  stack: { flexDirection: 'row', height: 16, borderRadius: 8, overflow: 'hidden', backgroundColor: colors.divider },
  legendRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  dot: { width: 10, height: 10, borderRadius: 5 },
});
