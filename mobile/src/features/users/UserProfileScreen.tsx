import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { UserProfile } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { useCurrentUser } from '@/auth/session';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { leaveFlash, useReturnFlash } from '@/hooks/useReturnFlash';
import { colors, spacing, userStatusColors } from '@/theme';
import { formatDayMonthYear, isoToYmd, timeAgo } from '@/utils/dates';

import { enumLabel, fullName } from './userFormat';

/** user/[id].blade.php: contact details, employment, direct reports and the record dates. */
export function UserProfileScreen() {
  const { id, flash: carried } = useLocalSearchParams<{ id: string; flash?: string }>();
  const query = useApiQuery(`users.show:${id}`, () => api.users.show(id));
  useRefetchOnFocus(query.reload);
  const [flash, setFlash] = useState<string | undefined>(carried);
  const showReturned = useCallback((text: string) => setFlash(text), []);
  useReturnFlash(`users.show:${id}`, showReturned);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  return <Profile user={query.data} flash={flash} refreshing={query.refreshing} onRefresh={query.refresh} />;
}

function Profile({ user: u, flash, refreshing, onRefresh }: { user: UserProfile; flash?: string; refreshing: boolean; onRefresh: () => void }) {
  const [confirming, setConfirming] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const name = fullName(u);
  // A Full Admin or Clinical Supervisor account is edited by a Full Admin only.
  const canEdit = useCurrentUser().role === 'FULL_ADMIN' || !['FULL_ADMIN', 'CLINICAL_SUPERVISOR'].includes(u.role);
  const open = (publicId: string) => router.push({ pathname: '/users/[id]', params: { id: publicId } });

  async function remove() {
    setBusy(true);
    setError(null);
    try {
      const res = await api.users.destroy(u.public_id);
      leaveFlash('users', res.message);
      router.back();
    } catch (e) {
      setError(errorMessage(e));
      setBusy(false);
    }
  }

  return (
    <Screen edges={[]} refreshing={refreshing} onRefresh={onRefresh}>
      <Stack.Screen options={{ title: name }} />

      <Card style={styles.gap}>
        <View style={styles.head}>
          <Avatar id={u.email} name={name} size={52} />
          <View style={styles.flex}>
            <AppText variant="heading">{name}</AppText>
            <AppText variant="caption">
              {enumLabel(u.role)} · {enumLabel(u.department)}
            </AppText>
            {u.job_title ? <AppText variant="caption">{u.job_title}</AppText> : null}
          </View>
          <Chip label={u.is_active ? 'Active' : 'Inactive'} colors={userStatusColors[u.is_active ? 'active' : 'inactive']} />
        </View>
        {canEdit ? (
          <Button title="Edit" variant="secondary" onPress={() => router.push({ pathname: '/users/form', params: { id: u.public_id } })} />
        ) : (
          <AppText variant="caption">Only a Full Admin can edit this account.</AppText>
        )}
        {u.can_delete ? <Button title="Delete" variant="secondary" onPress={() => setConfirming(!confirming)} /> : null}
        {confirming ? (
          <View style={styles.confirm}>
            <AppText variant="bodyStrong">Delete {name}?</AppText>
            <AppText variant="caption">This removes the account permanently. To stop someone signing in, deactivate them instead.</AppText>
            <Button title="Delete user" loading={busy} onPress={remove} />
          </View>
        ) : null}
      </Card>

      {flash ? <Banner text={flash} tone="success" /> : null}
      {error ? <Banner text={error} /> : null}

      <Card>
        <CardHeader title="Contact information" />
        <Field label="Email" value={u.email} />
        <Field label="Phone" value={u.phone_number} empty="Not provided" />
        <Field label="Full legal name" value={fullName(u, true)} />
      </Card>

      <Card>
        <CardHeader title="Employment" />
        <Field label="Department" value={enumLabel(u.department)} />
        <Field label="Role" value={enumLabel(u.role)} />
        {u.manager ? (
          <Pressable onPress={() => open(u.manager!.public_id)} accessibilityRole="button" accessibilityLabel={`Open ${fullName(u.manager)}`}>
            <Field label="Manager" value={fullName(u.manager)} link />
          </Pressable>
        ) : (
          <Field label="Manager" value={null} empty="No manager assigned" />
        )}
        <Field label="Start date" value={u.start_date ? formatDayMonthYear(isoToYmd(u.start_date)) : null} empty="Not set" />
        {u.notes ? <Field label="Notes" value={u.notes} /> : null}
      </Card>

      <Card padded={false}>
        <View style={styles.header}>
          <CardHeader title={`Direct reports${u.subordinates.length ? ` · ${u.subordinates.length}` : ''}`} />
        </View>
        {u.subordinates.length === 0 ? (
          <AppText variant="caption" style={styles.empty}>
            No direct reports.
          </AppText>
        ) : (
          u.subordinates.map((s) => (
            <View key={s.id}>
              <Divider />
              <Pressable
                onPress={() => open(s.public_id)}
                accessibilityRole="button"
                accessibilityLabel={`Open ${fullName(s)}`}
                style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
                <Avatar id={s.email} name={fullName(s)} size={34} />
                <View style={styles.flex}>
                  <AppText variant="bodyStrong">{fullName(s)}</AppText>
                  <AppText variant="caption">{enumLabel(s.role)}</AppText>
                </View>
              </Pressable>
            </View>
          ))
        )}
      </Card>

      <Card>
        <CardHeader title="Record" />
        <Field label="Created" value={formatDayMonthYear(isoToYmd(u.created_at))} />
        <Field label="Updated" value={timeAgo(u.updated_at)} />
        <Field label="Last login" value={u.last_login_at ? timeAgo(u.last_login_at) : 'Never'} />
      </Card>
    </Screen>
  );
}

function Field({ label, value, empty = '—', link }: { label: string; value: string | null | undefined; empty?: string; link?: boolean }) {
  return (
    <View style={styles.field}>
      <AppText variant="caption">{label}</AppText>
      <AppText variant="bodyStrong" color={!value ? colors.textMuted : link ? colors.navy : undefined}>
        {value || empty}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  confirm: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  header: { paddingHorizontal: spacing.lg, paddingTop: spacing.lg, paddingBottom: spacing.sm },
  empty: { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  field: { paddingVertical: 6, gap: 2 },
});
