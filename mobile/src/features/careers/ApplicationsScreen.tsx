import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { ApplicationStatus, JobApplication } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { useReturnFlash } from '@/hooks/useReturnFlash';
import { applicationStatusColors, colors, spacing } from '@/theme';
import { formatDayMonthYear, isoToYmd } from '@/utils/dates';

type Filter = ApplicationStatus | 'all';

/** career/applications/index.blade.php: applications with a status filter; a row opens the detail. */
export function ApplicationsScreen() {
  const [filter, setFilter] = useState<Filter>('all');
  const query = useApiQuery(`careers.applications:${filter}`, () => api.careers.applications(filter));
  useRefetchOnFocus(query.reload);
  const [flash, setFlash] = useState<string | null>(null);
  const showReturned = useCallback((text: string) => setFlash(text), []);
  useReturnFlash('careers', showReturned);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const statuses = Object.keys(d.statuses) as ApplicationStatus[];
  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <AppText variant="caption">
        {d.total_count} application{d.total_count === 1 ? '' : 's'} · {d.new_count} new
      </AppText>
      <Button title="Job postings" variant="secondary" onPress={() => router.push('/careers/postings')} />
      {flash ? <Banner text={flash} tone="success" /> : null}
      <OptionPills<Filter>
        options={[
          { value: 'all', label: `All applications · ${d.total_count}` },
          ...statuses.map((s) => ({ value: s, label: `${d.statuses[s]} · ${d.status_counts[s] ?? 0}` })),
        ]}
        value={filter}
        onChange={setFilter}
      />

      <Card padded={false}>
        {d.applications.length === 0 ? (
          <EmptyRow text={filter === 'all' ? 'No applications yet. They arrive from the careers page.' : 'No applications with this status.'} />
        ) : (
          d.applications.map((a, i) => (
            <View key={a.id}>
              {i > 0 ? <Divider /> : null}
              <ApplicationRow application={a} />
            </View>
          ))
        )}
      </Card>
    </Screen>
  );
}

function ApplicationRow({ application: a }: { application: JobApplication }) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/careers/[id]', params: { id: String(a.id) } })}
      accessibilityRole="button"
      accessibilityLabel={`${a.full_name}, ${a.job_title}, ${a.status_label}`}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
      <Avatar id={a.id} name={a.full_name} />
      <View style={styles.flex}>
        <AppText variant="bodyStrong">{a.full_name}</AppText>
        <AppText variant="caption">{a.job_title}</AppText>
        <AppText variant="caption" numberOfLines={1}>
          {a.email}
          {a.years_experience ? ` · ${a.years_experience} yrs` : ''} · applied {formatDayMonthYear(isoToYmd(a.created_at))}
        </AppText>
      </View>
      <Chip label={a.status_label} colors={applicationStatusColors[a.status]} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
});
