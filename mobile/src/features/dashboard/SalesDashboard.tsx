import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { Lead } from '@/api/types';
import { canAccessFeature, canWriteIn } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { STATUS_LABEL } from '@/features/leads/leadFormat';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, leadSourceBarColors, leadSourceBarDefault, neutralChip, spacing } from '@/theme';

import { BarListCard, DashboardHeader, InboxPreviewCard, panelStyles, StatsGrid } from './panels';

/** Mirrors resources/views/dashboard/sales_staff.blade.php. */
export function SalesDashboard() {
  const user = useCurrentUser();
  const query = useApiQuery('dashboard.sales', () => api.dashboard.sales());
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
          caption={`${delta > 0 ? '▲' : delta < 0 ? '▼' : '–'} ${Math.abs(delta)}% vs prev. 7 days`}
          captionColor={delta > 0 ? colors.success : delta < 0 ? colors.danger : colors.textWarm}
        />
        <StatTile label="My active leads" value={String(d.my_active_leads_count)} caption="assigned to you" />
        <StatTile label="Awaiting first contact" value={String(d.awaiting_contact_count)} caption="clinic-wide" captionColor={colors.warning} />
      </StatsGrid>

      <Card>
        <CardHeader title="My leads pipeline" actionLabel="View all →" onAction={() => router.navigate('/leads')} />
        {d.my_leads_pipeline.length === 0 ? (
          <AppText variant="caption">No leads assigned to you yet.</AppText>
        ) : (
          d.my_leads_pipeline.map((lead) => <PipelineRow key={lead.id} lead={lead} />)
        )}
      </Card>

      <BarListCard
        title="Lead sources · last 7 days"
        empty="No leads captured in the last 7 days."
        scale={d.lead_sources_scale}
        rows={d.lead_sources.map((r) => ({
          label: r.source || 'Other',
          value: r.count,
          text: String(r.count),
          color: leadSourceBarColors[r.source] ?? leadSourceBarDefault,
        }))}
      />
      {canAccessFeature(user, 'whatsapp') ? <InboxPreviewCard contacts={d.whatsapp_inbox} /> : null}
    </Screen>
  );
}

function PipelineRow({ lead }: { lead: Lead }) {
  return (
    <View>
      <Divider />
      <Pressable
        onPress={() => router.push({ pathname: '/leads/[id]', params: { id: String(lead.id) } }, { withAnchor: true })}
        style={({ pressed }) => [styles.row, pressed && panelStyles.pressed]}
        accessibilityRole="button">
        <View style={panelStyles.rowMain}>
          <AppText variant="bodyStrong">{lead.parent_guardian_name ?? lead.child_name}</AppText>
          <AppText variant="caption">
            {lead.interested_in ?? 'Interest not captured'} · {lead.source}
          </AppText>
        </View>
        <Chip label={STATUS_LABEL[lead.status]} colors={neutralChip} />
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.sm },
});
