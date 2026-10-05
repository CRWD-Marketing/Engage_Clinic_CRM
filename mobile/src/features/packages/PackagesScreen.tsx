import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { PackageRow } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { useReturnFlash } from '@/hooks/useReturnFlash';
import { colors, fundingBadgeColors, spacing } from '@/theme';
import { formatMoney, trimNumber } from '@/utils/format';

/** package/index.blade.php: custom client packages; a row opens the edit form. */
export function PackagesScreen() {
  const query = useApiQuery('packages.list', () => api.packages.list());
  useRefetchOnFocus(query.reload);
  const [flash, setFlash] = useState<string | null>(null);
  const showReturned = useCallback((text: string) => setFlash(text), []);
  useReturnFlash('packages', showReturned);

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
      <AppText variant="caption">Service, location and funding come from Settings; a package bundles them with hours and a rate.</AppText>
      <Button title="New package" onPress={() => router.push('/packages/form')} />
      {flash ? <Banner text={flash} tone="success" /> : null}
      <Card padded={false}>
        {d.packages.length === 0 ? (
          <EmptyRow text="No packages yet." />
        ) : (
          d.packages.map((p, i) => (
            <View key={p.id}>
              {i > 0 ? <Divider /> : null}
              <PackageItem pkg={p} />
            </View>
          ))
        )}
      </Card>
    </Screen>
  );
}

function PackageItem({ pkg: p }: { pkg: PackageRow }) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/packages/form', params: { id: String(p.id) } })}
      accessibilityRole="button"
      accessibilityLabel={`Edit ${p.name}`}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
      <View style={styles.flex}>
        <AppText variant="bodyStrong">{p.name}</AppText>
        <AppText variant="caption">
          {p.service ?? '—'} · {p.location ?? '—'}
        </AppText>
        <AppText variant="caption">
          {p.delivery_mode ?? '—'} · {trimNumber(p.hours_per_week)} h · AED {formatMoney(p.rate, 0)}/hr
        </AppText>
      </View>
      <View style={styles.right}>
        <AppText variant="bodyStrong">AED {formatMoney(p.total_excl_vat, 0)}</AppText>
        <AppText variant="caption">excl. VAT</AppText>
        {p.funding_type ? (
          <Chip label={p.funding_type} colors={p.funding_type === 'Insurance' ? fundingBadgeColors.insurance : fundingBadgeColors.self_pay} />
        ) : null}
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
