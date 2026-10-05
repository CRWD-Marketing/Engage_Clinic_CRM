import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { VendorRow, VendorStatus } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { StatsGrid } from '@/features/dashboard/panels';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useDebouncedValue } from '@/hooks/useDebouncedValue';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { useReturnFlash } from '@/hooks/useReturnFlash';
import { colors, spacing } from '@/theme';
import { timeAgo } from '@/utils/dates';

import { COMPLIANCE_LABELS, vendorBadge } from './vendorBadge';

type Status = VendorStatus | 'all';

/** vendor/index.blade.php: tiles, status tabs, search and category filter, and the vendor list. */
export function VendorsScreen() {
  const [status, setStatus] = useState<Status>('all');
  const [search, setSearch] = useState('');
  const term = useDebouncedValue(search.trim());
  const [category, setCategory] = useState('');
  const [showCategories, setShowCategories] = useState(false);
  const [page, setPage] = useState(1);
  const query = useApiQuery(`vendors.list:${status}:${term}:${category}:${page}`, () =>
    api.vendors.list({ status, search: term || undefined, category: category || undefined, page }),
  );
  useRefetchOnFocus(query.reload);
  const [flash, setFlash] = useState<string | null>(null);
  const showReturned = useCallback((text: string) => setFlash(text), []);
  useReturnFlash('vendors', showReturned);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const statuses = Object.keys(d.statuses) as VendorStatus[];
  const filtered = Boolean(term || category);
  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <StatsGrid>
        <StatTile label="New registrations" value={String(d.stats.new)} caption="not opened yet" captionColor={d.stats.new ? colors.navy : undefined} />
        <StatTile label="Critical vendors" value={String(d.stats.critical)} caption="essential services" />
        <StatTile label="Documents expiring" value={String(d.stats.documents_expiring)} caption="or expired, within 30 days" captionColor={d.stats.documents_expiring ? colors.danger : undefined} />
        <StatTile label="Contracts ending" value={String(d.stats.contracts_expiring)} caption="within 30 days" />
      </StatsGrid>
      <AppText variant="caption" selectable>
        Vendors register through the website form: {d.registration_url}
      </AppText>
      {flash ? <Banner text={flash} tone="success" /> : null}

      <OptionPills<Status>
        options={[{ value: 'all', label: `All · ${d.total_count}` }, ...statuses.map((s) => ({ value: s, label: `${d.statuses[s]} · ${d.status_counts[s]}` }))]}
        value={status}
        onChange={(s) => {
          setStatus(s);
          setPage(1);
        }}
      />
      <TextField
        label="Search vendors"
        value={search}
        onChangeText={(text) => {
          setSearch(text);
          setPage(1);
        }}
        placeholder="Company, contact, email or vendor ID"
        autoCapitalize="none"
        autoCorrect={false}
        clearButtonMode="while-editing"
      />
      <Button title={category ? `Category: ${category}` : 'All categories'} variant="secondary" onPress={() => setShowCategories(!showCategories)} />
      {showCategories ? (
        <Card>
          <OptionPills
            options={[{ value: '', label: 'All categories' }, ...d.categories.map((c) => ({ value: c, label: c }))]}
            value={category}
            onChange={(c) => {
              setCategory(c);
              setShowCategories(false);
              setPage(1);
            }}
          />
        </Card>
      ) : null}
      {filtered ? (
        <AppText variant="caption">
          {d.meta.total} vendor{d.meta.total === 1 ? '' : 's'} match
        </AppText>
      ) : null}

      <Card padded={false}>
        {d.vendors.length === 0 ? (
          <EmptyRow text={d.total_count === 0 ? 'No vendor registrations yet. Share the form link to get started.' : 'No vendors match these filters.'} />
        ) : (
          d.vendors.map((v, i) => (
            <View key={v.id}>
              {i > 0 ? <Divider /> : null}
              <VendorItem vendor={v} />
            </View>
          ))
        )}
      </Card>

      {d.meta.last_page > 1 ? (
        <View style={styles.pager}>
          <Button title="Previous" variant="secondary" disabled={page <= 1} onPress={() => setPage(page - 1)} />
          <Button title="Next" variant="secondary" disabled={page >= d.meta.last_page} onPress={() => setPage(page + 1)} />
        </View>
      ) : null}
    </Screen>
  );
}

function VendorItem({ vendor: v }: { vendor: VendorRow }) {
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/vendors/[id]', params: { id: String(v.id) } })}
      accessibilityRole="button"
      accessibilityLabel={`${v.legal_name}, ${v.status_label}${v.is_new ? ', new' : ''}`}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
      <Avatar id={v.legal_name} name={v.legal_name} />
      <View style={styles.flex}>
        <AppText variant="bodyStrong">
          {v.is_new ? '● ' : ''}
          {v.legal_name}
        </AppText>
        <AppText variant="caption">
          {v.reference} · {v.category_label}
        </AppText>
        <AppText variant="caption" numberOfLines={1}>
          {v.contact_name} · {v.email}
        </AppText>
        <AppText variant="caption">Submitted {timeAgo(v.created_at)}</AppText>
      </View>
      <View style={styles.right}>
        <Chip label={v.status_label} colors={vendorBadge(v.status)} />
        {v.is_critical ? <Chip label="Critical" colors={vendorBadge('critical')} /> : null}
        <Chip label={COMPLIANCE_LABELS[v.compliance_status]} colors={vendorBadge(v.compliance_status)} />
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  right: { alignItems: 'flex-end', gap: 4 },
  pager: { flexDirection: 'row', gap: spacing.sm },
});
