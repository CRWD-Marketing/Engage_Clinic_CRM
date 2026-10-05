import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { AccessGrantInput, AccessTemplate, RolesAccessPage } from '@/api/types';
import type { Role } from '@/auth/roles';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useCurrentUser } from '@/auth/session';
import { useApiQuery } from '@/hooks/useApiQuery';
import { leaveFlash } from '@/hooks/useReturnFlash';
import { spacing } from '@/theme';
import { roleLabel } from '@/utils/format';

import { AccessEditor, levelsOf, PROTECTED_GRANTS } from './AccessEditor';

/**
 * New role template, or edit one (`id`). The web edits a card's chips in place, one
 * save per tap; on a phone the same PUT is sent once, with every change together.
 */
export function TemplateFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const query = useApiQuery('roles.page', () => api.roles.page());

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  const template = id ? (query.data.templates.find((t) => t.id === Number(id)) ?? null) : null;
  return <Form page={query.data} template={template} />;
}

function Form({ page: d, template }: { page: RolesAccessPage; template: AccessTemplate | null }) {
  const editing = template !== null;
  const [name, setName] = useState(template?.name ?? '');
  const [description, setDescription] = useState(template?.description ?? '');
  const [baseRole, setBaseRole] = useState<Role>(template?.base_role ?? 'OTHER_STAFF');
  const [grants, setGrants] = useState<AccessGrantInput>({
    modules: template?.modules ?? ['dashboard'],
    module_levels: levelsOf(template?.module_levels),
    actions: template?.actions ?? [],
  });
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [nameError, setNameError] = useState<string | undefined>();
  // A system template keeps its name and base role; only the grants move.
  const fixed = template?.is_system ?? false;
  const fullAdmin = useCurrentUser().role === 'FULL_ADMIN';

  async function save() {
    setError(null);
    setNameError(undefined);
    if (!grants.modules.includes('dashboard')) {
      setError('Dashboard stays on for every template.');
      return;
    }
    setBusy(true);
    try {
      const res = template
        ? await api.roles.updateTemplate(template.id, { ...(fixed ? {} : { name, base_role: baseRole }), description: description || null, ...grants })
        : await api.roles.storeTemplate({ name, description: description || null, base_role: baseRole, ...grants });
      leaveFlash('roles', res.message);
      router.back();
    } catch (e) {
      setError(errorMessage(e));
      if (isApiError(e)) setNameError(e.fieldError('name'));
      setBusy(false);
    }
  }

  return (
    <Screen edges={[]}>
      <Stack.Screen options={{ title: editing ? template.name : 'New role template' }} />
      <AppText variant="caption">
        {editing ? 'Template only — people already assigned keep the access they hold.' : 'A starting set of modules — per-user access can go beyond it.'}
      </AppText>
      {error ? <Banner text={error} /> : null}

      <Card style={styles.section}>
        {fixed ? (
          <AppText variant="caption">System template: the name and base role stay as they are.</AppText>
        ) : (
          <TextField
            label="Role name"
            required
            value={name}
            onChangeText={setName}
            placeholder="e.g. Sales, Insurance Officer, BCBA Supervisor"
            maxLength={100}
            error={nameError}
          />
        )}
        <TextField label="Description" value={description} onChangeText={setDescription} placeholder="e.g. Owns enquiries and follow-ups" maxLength={255} />
        {fixed ? null : (
          <OptionPills
            label="Based on (dashboard & data scope)"
            options={d.base_roles.map((r) => ({ value: r, label: roleLabel(r) }))}
            value={baseRole}
            onChange={setBaseRole}
          />
        )}
      </Card>

      <Card>
        <AccessEditor page={d} value={grants} onChange={setGrants} locked={fullAdmin ? [] : PROTECTED_GRANTS} />
        {fullAdmin ? null : <AppText variant="caption">Billing, Settings and Manage users &amp; roles are granted by a Full Admin.</AppText>}
      </Card>

      <Button title={editing ? 'Save template' : 'Create role'} loading={busy} onPress={save} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  section: { gap: spacing.md },
});
