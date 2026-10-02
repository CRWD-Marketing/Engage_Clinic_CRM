import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { TherapistRosterRow } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, neutralChip, spacing } from '@/theme';
import type { Ymd } from '@/utils/dates';
import { plural } from '@/utils/format';

import { WeekNav } from './WeekNav';

/** The therapist list of resources/views/therapist/index.blade.php, with each one's week at a glance. */
export function TherapistsScreen() {
  const [week, setWeek] = useState<Ymd | undefined>(undefined);
  const query = useApiQuery(`therapists.index:${week ?? 'now'}`, () => api.therapists.index({ week }));
  useRefetchOnFocus(query.reload);

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
      <WeekNav weekStart={d.week_start} isCurrentWeek={d.is_current_week} onWeek={setWeek} />
      <AppText variant="caption">Tap a therapist to see their week and close or reopen sessions.</AppText>

      <Card padded={false}>
        <View style={styles.head}>
          <AppText variant="heading">Therapists</AppText>
          <AppText variant="caption">{d.therapists.length} active</AppText>
        </View>
        {d.therapists.length === 0 ? (
          <EmptyRow text="No therapists yet." />
        ) : (
          d.therapists.map((t) => <Row key={t.id} therapist={t} week={d.is_current_week ? undefined : d.week_start} />)
        )}
      </Card>
    </Screen>
  );
}

function Row({ therapist: t, week }: { therapist: TherapistRosterRow; week: Ymd | undefined }) {
  const name = t.name || 'Unnamed';
  return (
    <>
      <Divider />
      <Pressable
        onPress={() => router.push({ pathname: '/therapists/[id]', params: { id: String(t.id), ...(week ? { week } : {}) } })}
        accessibilityRole="button"
        accessibilityLabel={`${name}, ${t.session_count} ${plural(t.session_count, 'session')}`}
        style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
        <Avatar id={t.id} name={name} size={38} />
        <View style={styles.flex}>
          <AppText variant="bodyStrong">{name}</AppText>
          <AppText variant="caption">
            {t.department_label} · {t.weekly_hours}h/wk
          </AppText>
        </View>
        <Chip label={`${t.session_count} ${plural(t.session_count, 'session')}`} colors={neutralChip} />
      </Pressable>
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  head: {
    flexDirection: 'row',
    alignItems: 'baseline',
    justifyContent: 'space-between',
    padding: spacing.lg,
    paddingBottom: spacing.md,
  },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
});
