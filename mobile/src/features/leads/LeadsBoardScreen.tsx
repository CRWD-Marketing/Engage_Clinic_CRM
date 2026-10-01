import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BoardLead } from '@/api/types';
import { canDo, canWriteIn } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, fonts, leadColors, radius, spacing } from '@/theme';
import { formatDayMonthYear, isoToYmd, timeAgo } from '@/utils/dates';

import {
  activeLeads,
  COLUMNS,
  dueTag,
  formatAed,
  sourceBadge,
  STATUS_TAG_LABEL,
  valueNumber,
  type ColumnKey,
} from './leadFormat';

type BoardView = ColumnKey | 'terminated';

/**
 * Mirrors lead/index.blade.php: the pipeline board. On a phone the four
 * columns become a stage switcher (one column at a time), plus the
 * "Terminated" history the web shows in a modal.
 */
export function LeadsBoardScreen() {
  const user = useCurrentUser();
  const query = useApiQuery('leads.board', () => api.leads.board());
  useRefetchOnFocus(query.reload);
  const [view, setView] = useState<BoardView>('new');
  const [flash, setFlash] = useState<{ text: string; tone: 'success' | 'danger' } | null>(null);
  const [restoring, setRestoring] = useState<number | null>(null);

  if (!query.data) {
    return (
      <Screen scroll={false}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const all = query.data.leads;
  const active = activeLeads(all);
  const terminated = all.filter((l) => l.status === 'terminated');
  const unassigned = active.filter((l) => l.assigned_to === null).length;
  const onFollowUp = active.filter((l) => l.follow_up_due_at !== null).length;
  const totalValue = active.reduce((sum, l) => sum + valueNumber(l), 0);

  const column = COLUMNS.find((c) => c.key === view);
  const cards = column ? active.filter((l) => (column.statuses as readonly string[]).includes(l.status)) : [];

  async function restore(lead: BoardLead) {
    setRestoring(lead.id);
    setFlash(null);
    try {
      const res = await api.leads.restore(lead.id);
      setFlash({ text: res.message, tone: 'success' });
      query.reload();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setRestoring(null);
    }
  }

  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <View style={styles.header}>
        <AppText variant="title">Leads pipeline</AppText>
        <AppText variant="caption">
          {active.length} active leads · {unassigned} unassigned · {onFollowUp} on follow-up · {formatAed(totalValue)} est.
          monthly value
        </AppText>
      </View>

      {canWriteIn(user, 'leads') ? <Button title="+ New Lead" onPress={() => router.push('/leads/new')} /> : null}

      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.stages}>
        {COLUMNS.map((c) => {
          const count = active.filter((l) => (c.statuses as readonly string[]).includes(l.status)).length;
          return <StagePill key={c.key} label={`${c.short} · ${count}`} active={view === c.key} onPress={() => setView(c.key)} />;
        })}
        <StagePill
          label={`Terminated · ${terminated.length}`}
          active={view === 'terminated'}
          onPress={() => setView('terminated')}
        />
      </ScrollView>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      {column ? (
        <>
          <AppText variant="heading">{column.label}</AppText>
          {cards.length === 0 ? (
            <Card>
              <EmptyRow text="No leads in this stage" />
            </Card>
          ) : (
            cards.map((lead) => <LeadCard key={lead.id} lead={lead} />)
          )}
        </>
      ) : (
        <>
          <AppText variant="heading">Terminated leads — history</AppText>
          {terminated.length === 0 ? (
            <Card>
              <EmptyRow text="No terminated leads" />
            </Card>
          ) : (
            terminated.map((lead) => (
              <Card key={lead.id} style={styles.card}>
                <AppText variant="bodyStrong">
                  {lead.child_name ?? 'N/A'} · {lead.parent_guardian_name ?? 'N/A'}
                </AppText>
                <AppText variant="caption">
                  Terminated {lead.terminated_at ? formatDayMonthYear(isoToYmd(lead.terminated_at)) : '—'}
                  {lead.status_before_termination ? ` · lost at ${STATUS_TAG_LABEL[lead.status_before_termination]}` : ''}
                </AppText>
                <AppText variant="body">{lead.termination_reason ?? 'No reason given'}</AppText>
                {lead.termination_note ? <AppText variant="caption">{lead.termination_note}</AppText> : null}
                {canDo(user, 'terminate_lead') ? (
                  <Button title="Restore" variant="secondary" onPress={() => restore(lead)} loading={restoring === lead.id} />
                ) : null}
              </Card>
            ))
          )}
        </>
      )}
    </Screen>
  );
}

function StagePill({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      style={[styles.stage, active && styles.stageActive]}>
      <AppText style={[styles.stageText, active && styles.stageTextActive]}>{label}</AppText>
    </Pressable>
  );
}

/** lead/_card.blade.php */
function LeadCard({ lead }: { lead: BoardLead }) {
  const due = dueTag(lead);
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/leads/[id]', params: { id: String(lead.id) } })}
      accessibilityRole="button"
      accessibilityLabel={`${lead.child_name ?? 'N/A'}, ${STATUS_TAG_LABEL[lead.status]}`}
      style={({ pressed }) => pressed && styles.pressed}>
      <Card style={styles.card}>
        <View style={styles.cardTop}>
          <AppText variant="bodyStrong" style={styles.flex}>
            {lead.child_name ?? 'N/A'} · {lead.child_age ?? 'N/A'}
          </AppText>
          <Chip label={lead.source ?? 'N/A'} colors={sourceBadge(lead.source)} />
        </View>
        <View style={styles.tags}>
          <Chip label={STATUS_TAG_LABEL[lead.status]} colors={leadColors.statusTag[lead.status]} />
          {due ? <Chip label={due.label} colors={due.colors} /> : null}
          <AppText variant="caption" numberOfLines={1} style={styles.flex}>
            {lead.parent_guardian_name ?? 'N/A'}
          </AppText>
        </View>
        <AppText variant="caption" numberOfLines={2}>
          {lead.notes ?? 'No notes'}
        </AppText>
        <View style={styles.cardBottom}>
          <AppText style={styles.value}>{formatAed(valueNumber(lead))}/mo</AppText>
          <AppText variant="caption">{timeAgo(lead.created_at)}</AppText>
        </View>
        <AppText variant="caption" color={lead.assigned_to_name ? colors.textSecondary : colors.pink} style={styles.owner}>
          {lead.assigned_to_name ? `Assigned to ${lead.assigned_to_name}` : 'Unassigned — open to assign'}
        </AppText>
      </Card>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  header: { gap: 2, marginBottom: spacing.xs },
  stages: { gap: 6 },
  stage: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: 14,
    paddingVertical: 8,
  },
  stageActive: { backgroundColor: colors.navy, borderColor: colors.navy },
  stageText: { fontFamily: fonts.bodyBold, fontSize: 12.5, color: colors.textSecondary },
  stageTextActive: { color: colors.white },
  pressed: { opacity: 0.8 },
  card: { gap: spacing.sm },
  cardTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  tags: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  cardBottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline' },
  value: { fontFamily: fonts.heading, fontSize: 14, color: colors.navy },
  owner: { fontFamily: fonts.bodyBold },
});
