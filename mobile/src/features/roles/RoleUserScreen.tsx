import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useCallback, useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { AccessGrantInput, AccessUser, RolesAccessPage } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { leaveFlash, useReturnFlash } from '@/hooks/useReturnFlash';
import { accessStatusColors, colors, spacing } from '@/theme';

import { AccessEditor, levelsOf } from './AccessEditor';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** One user on Roles & access: their template, their own module / action grants, suspend and remove. */
export function RoleUserScreen() {
  const { id, flash: carried } = useLocalSearchParams<{ id: string; flash?: string }>();
  const query = useApiQuery('roles.page', () => api.roles.page());
  useRefetchOnFocus(query.reload);
  // Kept here so a message survives the access editor resetting after a save.
  const [flash, setFlash] = useState<Flash>(carried ? { text: carried, tone: 'success' } : null);
  const showReturned = useCallback((text: string) => setFlash({ text, tone: 'success' }), []);
  useReturnFlash(`roles.user:${id}`, showReturned);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  const user = query.data.users.find((u) => u.id === id);
  if (!user) {
    return (
      <Screen edges={[]}>
        {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}
        <Banner text="This user is no longer on the list." tone="info" />
      </Screen>
    );
  }
  return (
    <Detail
      key={`${user.id}:${user.template_id}:${user.modules.join()}:${user.actions.join()}:${user.is_active}`}
      page={query.data}
      user={user}
      flash={flash}
      setFlash={setFlash}
      refreshing={query.refreshing}
      onRefresh={query.refresh}
      onChanged={query.reload}
    />
  );
}

function Detail({
  page: d,
  user: u,
  flash,
  setFlash,
  refreshing,
  onRefresh,
  onChanged,
}: {
  page: RolesAccessPage;
  user: AccessUser;
  flash: Flash;
  setFlash: (f: Flash) => void;
  refreshing: boolean;
  onRefresh: () => void;
  onChanged: () => void;
}) {
  const [grants, setGrants] = useState<AccessGrantInput>({ modules: u.modules, module_levels: levelsOf(u.module_levels), actions: u.actions });
  const [removing, setRemoving] = useState(false);
  const [busy, setBusy] = useState(false);
  const template = d.templates.find((t) => t.id === u.template_id);
  const status = u.status.charAt(0).toUpperCase() + u.status.slice(1);
  const canChange = d.can_manage;

  async function run(action: () => Promise<{ message: string }>, after?: () => void) {
    setBusy(true);
    setFlash(null);
    try {
      const res = await action();
      if (after) {
        // Leaving this screen: the message goes with the user to the list.
        leaveFlash('roles', res.message);
        after();
        return;
      }
      setFlash({ text: res.message, tone: 'success' });
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  return (
    <Screen edges={[]} refreshing={refreshing} onRefresh={onRefresh}>
      <Stack.Screen options={{ title: u.name }} />

      <Card style={styles.gap}>
        <View style={styles.head}>
          <Avatar id={u.id} name={u.name} size={52} />
          <View style={styles.flex}>
            <AppText variant="heading">
              {u.name}
              {u.is_me ? ' (you)' : ''}
            </AppText>
            <AppText variant="caption">{u.job_title ? `${u.job_title} · ${u.email}` : u.email}</AppText>
            <AppText variant="caption">
              {template?.name ?? 'No template'} · {u.access}/{Object.keys(d.modules).length} modules
            </AppText>
          </View>
          <Chip label={status} colors={accessStatusColors[u.status]} />
        </View>
        {canChange ? (
          <Button title="Edit details" variant="secondary" onPress={() => router.push({ pathname: '/roles/user-form', params: { id: u.id } })} />
        ) : null}
      </Card>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <Card style={styles.gap}>
        <AppText variant="heading">Role template</AppText>
        <AppText variant="caption">Moving to another template replaces their access with that template&apos;s.</AppText>
        <OptionPills<number | null>
          options={d.templates.map((t) => ({ value: t.id, label: t.name }))}
          value={u.template_id}
          onChange={(templateId) => templateId !== null && templateId !== u.template_id && run(() => api.roles.setTemplate(u.id, templateId))}
          disabled={!canChange || busy}
        />
      </Card>

      <Card style={styles.gap}>
        <AppText variant="heading">Access</AppText>
        <AppText variant="caption">
          {canChange
            ? `Starts from ${template?.name ?? 'their role'} · turn anything on or off to grant beyond (or trim below) the template.`
            : `On ${template?.name ?? 'their role'} · view only.`}
        </AppText>
        <AccessEditor page={d} value={grants} onChange={setGrants} disabled={!canChange} />
        {canChange ? <Button title="Save access" loading={busy} onPress={() => run(() => api.roles.setAccess(u.id, grants))} /> : null}
      </Card>

      {canChange && !u.is_me ? (
        <Card style={styles.gap}>
          <Button
            title={u.status === 'suspended' ? 'Reactivate' : 'Suspend'}
            variant="secondary"
            loading={busy}
            onPress={() => run(() => api.roles.toggleSuspend(u.id))}
          />
          <Button title="Remove" variant="secondary" onPress={() => setRemoving(!removing)} />
          {removing ? (
            <View style={styles.confirm}>
              <AppText variant="bodyStrong">Remove {u.name}?</AppText>
              <AppText variant="caption">They will no longer be able to sign in.</AppText>
              <Button
                title="Remove user"
                loading={busy}
                onPress={() =>
                  run(
                    () => api.roles.destroyUser(u.id),
                    () => router.back(),
                  )
                }
              />
            </View>
          ) : null}
        </Card>
      ) : null}

      <AppText variant="caption" color={colors.textMuted}>
        Base role {u.base_role.replace(/_/g, ' ').toLowerCase()} decides their dashboard and data scope.
      </AppText>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  confirm: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
});
