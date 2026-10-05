import { router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { AccessTemplate, AccessUser, RolesAccessPage } from '@/api/types';
import type { ActionKey, ModuleKey } from '@/auth/roles';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useCurrentUser } from '@/auth/session';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { useReturnFlash } from '@/hooks/useReturnFlash';
import { accessChipColors, accessStatusColors, colors, spacing } from '@/theme';
import { roleLabel } from '@/utils/format';

import { levelsOf, PROTECTED_ROLES, Toggle } from './AccessEditor';

type Tab = 'users' | 'templates';
type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** user/roles.blade.php: users with their role template and access, and the template cards. */
export function RolesScreen() {
  const query = useApiQuery('roles.page', () => api.roles.page());
  useRefetchOnFocus(query.reload);
  const [tab, setTab] = useState<Tab>('users');
  const [flash, setFlash] = useState<Flash>(null);
  const showReturned = useCallback((text: string) => setFlash({ text, tone: 'success' }), []);
  useReturnFlash('roles', showReturned);

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
      <AppText variant="caption">
        Users hold the permissions; role templates are where you start from.
        {d.can_manage ? '' : ' View only: changes need “Manage users & roles”.'}
      </AppText>
      <OptionPills<Tab>
        options={[
          { value: 'users', label: `Users · ${d.stats.total}` },
          { value: 'templates', label: `Role templates · ${d.templates.length}` },
        ]}
        value={tab}
        onChange={setTab}
      />
      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}
      {tab === 'users' ? <UsersTab page={d} /> : <TemplatesTab page={d} setFlash={setFlash} onChanged={query.reload} />}
    </Screen>
  );
}

function UsersTab({ page: d }: { page: RolesAccessPage }) {
  const templateName = (id: number | null) => d.templates.find((t) => t.id === id)?.name ?? 'No template';
  const moduleCount = Object.keys(d.modules).length;
  return (
    <>
      <AppText variant="caption">
        {d.stats.total} user{d.stats.total === 1 ? '' : 's'} · {d.stats.active} active · {d.stats.invited} invited · {d.stats.suspended} suspended · open a
        user to change their template or grant access beyond it.
      </AppText>
      {d.can_manage ? <Button title="Add user" onPress={() => router.push('/roles/user-form')} /> : null}
      <Card padded={false}>
        {d.users.length === 0 ? (
          <EmptyRow text="No users yet." />
        ) : (
          d.users.map((u, i) => (
            <View key={u.id}>
              {i > 0 ? <Divider /> : null}
              <UserItem user={u} template={templateName(u.template_id)} moduleCount={moduleCount} />
            </View>
          ))
        )}
      </Card>
    </>
  );
}

function UserItem({ user: u, template, moduleCount }: { user: AccessUser; template: string; moduleCount: number }) {
  const status = u.status.charAt(0).toUpperCase() + u.status.slice(1);
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/roles/user/[id]', params: { id: u.id } })}
      accessibilityRole="button"
      accessibilityLabel={`${u.name}, ${template}, ${status}`}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
      <Avatar id={u.id} name={u.name} />
      <View style={styles.flex}>
        <AppText variant="bodyStrong">
          {u.name}
          {u.is_me ? ' (you)' : ''}
        </AppText>
        <AppText variant="caption" numberOfLines={1}>
          {u.job_title ? `${u.job_title} · ` : ''}
          {u.email}
        </AppText>
        <AppText variant="caption">
          {template} · {u.access}/{moduleCount} modules
        </AppText>
      </View>
      <Chip label={status} colors={accessStatusColors[u.status]} />
    </Pressable>
  );
}

function TemplatesTab({ page: d, setFlash, onChanged }: { page: RolesAccessPage; setFlash: (f: Flash) => void; onChanged: () => void }) {
  const fullAdmin = useCurrentUser().role === 'FULL_ADMIN';
  const [deleting, setDeleting] = useState<number | null>(null);
  const [busy, setBusy] = useState(false);

  async function remove(t: AccessTemplate) {
    setBusy(true);
    setFlash(null);
    try {
      const res = await api.roles.destroyTemplate(t.id);
      setFlash({ text: res.message, tone: 'success' });
      setDeleting(null);
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  return (
    <>
      {d.can_manage ? <Button title="New role template" onPress={() => router.push('/roles/template')} /> : null}
      {d.templates.map((t) => (
        <Card key={t.id} style={styles.template}>
          <View style={styles.head}>
            <View style={styles.flex}>
              <AppText variant="heading">{t.name}</AppText>
              <AppText variant="caption">
                Based on {roleLabel(t.base_role)} · {t.users_count} user{t.users_count === 1 ? '' : 's'}
              </AppText>
            </View>
            <Chip label={t.is_system ? 'System template' : 'Custom template'} colors={t.is_system ? accessChipColors.systemTag : accessChipColors.customTag} />
          </View>
          {t.description ? <AppText variant="caption">{t.description}</AppText> : null}

          <AppText variant="bodyStrong">
            Modules · {t.modules.length} of {Object.keys(d.modules).length}
          </AppText>
          <View style={styles.chips}>
            {(Object.keys(d.modules) as ModuleKey[])
              .filter((m) => t.modules.includes(m))
              .map((m) => {
                const level = levelsOf(t.module_levels)[m];
                return <Toggle key={m} label={`${d.modules[m]}${level && level !== 'full' ? ` · ${d.levels[level]}` : ''}`} on kind="module" />;
              })}
          </View>
          <AppText variant="bodyStrong">
            Actions · {t.actions.length} of {Object.keys(d.actions).length}
          </AppText>
          <View style={styles.chips}>
            {t.actions.length === 0 ? (
              <AppText variant="caption">None</AppText>
            ) : (
              (Object.keys(d.actions) as ActionKey[]).filter((a) => t.actions.includes(a)).map((a) => <Toggle key={a} label={d.actions[a]} on kind="action" />)
            )}
          </View>

          {t.locked ? <AppText variant="caption">🔒 Locked — Full Admin always has every module and action.</AppText> : null}
          <AppText variant="caption">Template only — editing it does not change people already assigned.</AppText>
          {d.can_manage && !t.locked && (fullAdmin || !PROTECTED_ROLES.includes(t.base_role)) ? (
            <Button title="Edit template" variant="secondary" onPress={() => router.push({ pathname: '/roles/template', params: { id: String(t.id) } })} />
          ) : null}
          {d.can_manage && !t.is_system ? <Button title="Delete role" variant="secondary" onPress={() => setDeleting(deleting === t.id ? null : t.id)} /> : null}
          {deleting === t.id ? (
            <View style={styles.confirm}>
              <AppText variant="bodyStrong">Delete “{t.name}”?</AppText>
              <AppText variant="caption">Anyone on it moves back to the system template for their base role, keeping the access they already hold.</AppText>
              <Button title="Delete template" loading={busy} onPress={() => remove(t)} />
            </View>
          ) : null}
        </Card>
      ))}
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  template: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  confirm: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
});
