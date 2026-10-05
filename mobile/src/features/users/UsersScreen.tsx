import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { StaffUser, UserListQuery } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { StatsGrid } from '@/features/dashboard/panels';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useDebouncedValue } from '@/hooks/useDebouncedValue';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, spacing, userStatusColors } from '@/theme';

import { enumLabel, fullName } from './userFormat';

type Filters = Required<Pick<UserListQuery, 'department' | 'role' | 'status'>>;

/** user/index.blade.php: stat tiles, search and filters, and the paged user list. */
export function UsersScreen() {
  const [search, setSearch] = useState('');
  const term = useDebouncedValue(search.trim());
  const [filters, setFilters] = useState<Filters>({ department: '', role: '', status: '' });
  const [showFilters, setShowFilters] = useState(false);
  const [page, setPage] = useState(1);
  const query = useApiQuery(`users.list:${term}:${filters.department}:${filters.role}:${filters.status}:${page}`, () =>
    api.users.list({ search: term, ...filters, page }),
  );
  useRefetchOnFocus(query.reload);

  // A new search or filter starts again from page 1.
  const setFilter = <K extends keyof Filters>(key: K, value: Filters[K]) => {
    setFilters((f) => ({ ...f, [key]: value }));
    setPage(1);
  };
  const filtered = Boolean(term || filters.department || filters.role || filters.status);
  const activeFilters = [filters.department, filters.role, filters.status].filter(Boolean).length;

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const first = (d.meta.current_page - 1) * d.meta.per_page + 1;
  const last = first + d.users.length - 1;
  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <StatsGrid>
        <StatTile label="Total users" value={String(d.stats.total)} caption="all accounts" />
        <StatTile label="Active" value={String(d.stats.active)} caption="can sign in" captionColor={colors.success} />
        <StatTile label="Inactive" value={String(d.stats.inactive)} caption="deactivated" captionColor={d.stats.inactive ? colors.danger : undefined} />
        <StatTile label="Departments" value={String(d.stats.departments)} caption="in use" />
      </StatsGrid>

      <Button title="Add user" onPress={() => router.push('/users/form')} />

      <TextField
        label="Search users"
        value={search}
        onChangeText={(text) => {
          setSearch(text);
          setPage(1);
        }}
        placeholder="Name or email"
        autoCapitalize="none"
        autoCorrect={false}
        clearButtonMode="while-editing"
      />
      <View style={styles.filterBar}>
        <Button
          title={showFilters ? 'Hide filters' : activeFilters ? `Filters · ${activeFilters}` : 'Filters'}
          variant="secondary"
          onPress={() => setShowFilters(!showFilters)}
        />
        {filtered ? (
          <Button
            title="Clear"
            variant="secondary"
            onPress={() => {
              setSearch('');
              setFilters({ department: '', role: '', status: '' });
              setPage(1);
            }}
          />
        ) : null}
      </View>
      {showFilters ? (
        <Card style={styles.filters}>
          <OptionPills
            label="Department"
            options={[{ value: '', label: 'All departments' }, ...d.departments.map((x) => ({ value: x, label: enumLabel(x) }))]}
            value={filters.department}
            onChange={(v) => setFilter('department', v)}
          />
          <OptionPills
            label="Role"
            options={[{ value: '', label: 'All roles' }, ...d.roles.map((x) => ({ value: x, label: enumLabel(x) }))]}
            value={filters.role}
            onChange={(v) => setFilter('role', v)}
          />
          <OptionPills<Filters['status']>
            label="Status"
            options={[
              { value: '', label: 'All statuses' },
              { value: 'active', label: 'Active' },
              { value: 'inactive', label: 'Inactive' },
            ]}
            value={filters.status}
            onChange={(v) => setFilter('status', v)}
          />
        </Card>
      ) : null}

      <Card padded={false}>
        {d.users.length === 0 ? (
          <EmptyRow text={filtered ? 'No users match your filters.' : 'No users found.'} />
        ) : (
          d.users.map((u, i) => (
            <View key={u.id}>
              {i > 0 ? <Divider /> : null}
              <UserRowItem user={u} />
            </View>
          ))
        )}
      </Card>

      {d.meta.last_page > 1 ? (
        <View style={styles.pager}>
          <AppText variant="caption" style={styles.flex}>
            Showing {first}–{last} of {d.meta.total}
          </AppText>
          <View style={styles.pagerButtons}>
            <Button title="Previous" variant="secondary" disabled={page <= 1} onPress={() => setPage(page - 1)} />
            <Button title="Next" variant="secondary" disabled={page >= d.meta.last_page} onPress={() => setPage(page + 1)} />
          </View>
        </View>
      ) : null}
    </Screen>
  );
}

function UserRowItem({ user: u }: { user: StaffUser }) {
  const name = fullName(u);
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/users/[id]', params: { id: u.public_id } })}
      accessibilityRole="button"
      accessibilityLabel={`${name}, ${enumLabel(u.role)}, ${u.is_active ? 'Active' : 'Inactive'}`}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
      <Avatar id={u.email} name={name} />
      <View style={styles.flex}>
        <AppText variant="bodyStrong">{name}</AppText>
        <AppText variant="caption" numberOfLines={1}>
          {u.email}
        </AppText>
        <AppText variant="caption">
          {enumLabel(u.department)} · {enumLabel(u.role)}
        </AppText>
      </View>
      <Chip label={u.is_active ? 'Active' : 'Inactive'} colors={userStatusColors[u.is_active ? 'active' : 'inactive']} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  filterBar: { flexDirection: 'row', gap: spacing.sm },
  filters: { gap: spacing.md },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  pager: { gap: spacing.sm },
  pagerButtons: { flexDirection: 'row', gap: spacing.sm },
});
