import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { useReturnFlash } from '@/hooks/useReturnFlash';
import { colors, postingStatusColors, spacing } from '@/theme';

/** career/postings/index.blade.php: the careers page postings, newest first. */
export function PostingsScreen() {
  const query = useApiQuery('careers.postings', () => api.careers.postings());
  useRefetchOnFocus(query.reload);
  const [flash, setFlash] = useState<string | null>(null);
  const showReturned = useCallback((text: string) => setFlash(text), []);
  useReturnFlash('careers.postings', showReturned);

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
      <AppText variant="caption">Active postings show on the public careers page.</AppText>
      <Button title="New posting" onPress={() => router.push('/careers/posting-form')} />
      {flash ? <Banner text={flash} tone="success" /> : null}
      <Card padded={false}>
        {d.postings.length === 0 ? (
          <EmptyRow text="No job postings yet." />
        ) : (
          d.postings.map((p, i) => (
            <View key={p.id}>
              {i > 0 ? <Divider /> : null}
              <Pressable
                onPress={() => router.push({ pathname: '/careers/posting-form', params: { id: String(p.id) } })}
                accessibilityRole="button"
                accessibilityLabel={`Edit ${p.title}`}
                style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
                <View style={styles.flex}>
                  <AppText variant="bodyStrong">{p.title}</AppText>
                  {p.employment_type || p.location ? (
                    <AppText variant="caption">{[p.employment_type, p.location].filter(Boolean).join(' · ')}</AppText>
                  ) : null}
                  <AppText variant="caption">
                    {p.applications_count} applicant{p.applications_count === 1 ? '' : 's'}
                  </AppText>
                </View>
                <Chip label={d.statuses[p.status]} colors={postingStatusColors[p.status]} />
              </Pressable>
            </View>
          ))
        )}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
});
