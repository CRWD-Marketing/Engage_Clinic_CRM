import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { AccessUser, AccessUserInput, RolesAccessPage } from '@/api/types';
import type { Department } from '@/auth/roles';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { leaveFlash } from '@/hooks/useReturnFlash';
import { colors, spacing } from '@/theme';
import { todayYmd } from '@/utils/dates';
import { roleLabel } from '@/utils/format';

/** Letters and numbers only (at least one upper, lower and digit) — easy to read out (roles.blade.php generatePassword()). */
function generatePassword(): string {
  const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789'];
  const all = sets.join('');
  const pick = (s: string) => s[Math.floor(Math.random() * s.length)];
  const chars = sets.map(pick);
  while (chars.length < 12) chars.push(pick(all));
  for (let i = chars.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [chars[i], chars[j]] = [chars[j], chars[i]];
  }
  return chars.join('');
}

/** The Add user / Edit dialog of Roles & access: the role template decides the access. `id` (a public_id) means edit. */
export function RoleUserFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const query = useApiQuery('roles.page', () => api.roles.page());

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  const user = id ? (query.data.users.find((u) => u.id === id) ?? null) : null;
  return <Form page={query.data} user={user} />;
}

function Form({ page: d, user }: { page: RolesAccessPage; user: AccessUser | null }) {
  const editing = user !== null;
  const basic = d.templates.find((t) => t.key === 'basic') ?? d.templates[0];
  const [first, setFirst] = useState(user?.first_name ?? '');
  const [middle, setMiddle] = useState(user?.middle_name ?? '');
  const [last, setLast] = useState(user?.last_name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [phone, setPhone] = useState(user?.phone_number ?? '');
  const [jobTitle, setJobTitle] = useState(user?.job_title ?? '');
  const [templateId, setTemplateId] = useState<number>(user?.template_id ?? basic?.id ?? 0);
  const [department, setDepartment] = useState<Department>(
    user?.department ?? d.department_for_role[d.templates.find((t) => t.id === templateId)?.base_role ?? 'OTHER_STAFF'],
  );
  // The department follows the template until it is picked by hand.
  const [departmentTouched, setDepartmentTouched] = useState(false);
  const [managerId, setManagerId] = useState<number | null>(user?.manager_id ?? null);
  const [startDate, setStartDate] = useState(user?.start_date ?? todayYmd());
  const [notes, setNotes] = useState(user?.notes ?? '');
  const [active, setActive] = useState(user?.is_active ?? true);
  const [invite, setInvite] = useState(!editing);
  // Password: always set on add (generated or typed); on edit only when asked for.
  const [resetPassword, setResetPassword] = useState(false);
  const [mode, setMode] = useState<'auto' | 'custom'>('auto');
  const [password, setPassword] = useState(() => generatePassword());
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const wantsPassword = !editing || resetPassword;

  const chooseTemplate = (id: number) => {
    setTemplateId(id);
    const role = d.templates.find((t) => t.id === id)?.base_role;
    if (!departmentTouched && role) setDepartment(d.department_for_role[role]);
  };
  const chooseMode = (next: 'auto' | 'custom') => {
    setMode(next);
    setPassword(next === 'auto' ? generatePassword() : '');
  };

  async function save() {
    setError(null);
    setFieldErrors({});
    if (wantsPassword && password.length < 8) {
      setError('Password must be at least 8 characters.');
      return;
    }
    const input: AccessUserInput = {
      first_name: first.trim(),
      middle_name: middle.trim() || null,
      last_name: last.trim(),
      email: email.trim(),
      phone_number: phone.trim() || null,
      job_title: jobTitle.trim() || null,
      role_template_id: templateId,
      department,
      manager_id: managerId,
      start_date: startDate || null,
      notes: notes.trim() || null,
      is_active: active,
      ...(editing ? { password: wantsPassword ? password : null } : { password, send_invite: invite }),
    };
    setBusy(true);
    try {
      if (user) {
        const res = await api.roles.updateUser(user.id, input);
        leaveFlash(`roles.user:${user.id}`, res.message);
        router.back();
      } else {
        const res = await api.roles.storeUser(input);
        router.replace({ pathname: '/roles/user/[id]', params: { id: res.user.id, flash: res.message } });
      }
    } catch (e) {
      setError(errorMessage(e));
      if (isApiError(e)) setFieldErrors(Object.fromEntries(Object.keys(e.errors).map((k) => [k, e.fieldError(k)!])));
      setBusy(false);
    }
  }

  return (
    <Screen edges={[]}>
      <Stack.Screen options={{ title: editing ? `Edit ${user.name}` : 'Add user' }} />
      <AppText variant="caption">
        {editing ? 'Update their details — the role template sets their access.' : 'Same details as User Management — the role template sets their access.'}
      </AppText>
      {error ? <Banner text={error} /> : null}

      <Card style={styles.section}>
        <TextField label="First name" required value={first} onChangeText={setFirst} placeholder="Juan" error={fieldErrors.first_name} />
        <TextField label="Middle name" value={middle} onChangeText={setMiddle} placeholder="Optional" />
        <TextField label="Last name" required value={last} onChangeText={setLast} placeholder="Dela Cruz" error={fieldErrors.last_name} />
        <TextField
          label="Email"
          required
          value={email}
          onChangeText={setEmail}
          keyboardType="email-address"
          autoCapitalize="none"
          autoCorrect={false}
          error={fieldErrors.email}
        />
        <TextField label="Phone number" value={phone} onChangeText={setPhone} keyboardType="phone-pad" placeholder="+971 50 000 0000" />
        <TextField label="Job title" value={jobTitle} onChangeText={setJobTitle} placeholder="e.g. RBT, Speech-Language Pathologist" />
      </Card>

      <Card style={styles.section}>
        <AppText variant="heading">{editing ? 'Password' : 'Password *'}</AppText>
        {editing ? (
          <OptionPills
            options={[
              { value: 'keep', label: 'Keep current password' },
              { value: 'reset', label: 'Set a new password' },
            ]}
            value={resetPassword ? 'reset' : 'keep'}
            onChange={(v) => {
              setResetPassword(v === 'reset');
              chooseMode('auto');
            }}
          />
        ) : null}
        {wantsPassword ? (
          <>
            <OptionPills
              options={[
                { value: 'auto', label: 'Auto-generate' },
                { value: 'custom', label: 'Custom' },
              ]}
              value={mode}
              onChange={chooseMode}
            />
            {mode === 'auto' ? (
              <View style={styles.password}>
                <AppText variant="heading" selectable style={styles.mono}>
                  {password}
                </AppText>
                <Button title="Generate another" variant="secondary" onPress={() => setPassword(generatePassword())} />
              </View>
            ) : (
              <TextField label="Password" value={password} onChangeText={setPassword} autoCapitalize="none" placeholder="At least 8 characters" />
            )}
            <AppText variant="caption" color={fieldErrors.password ? colors.danger : undefined}>
              {fieldErrors.password ?? 'Share it with the user — it isn’t shown again after saving.'}
            </AppText>
          </>
        ) : null}
      </Card>

      <Card style={styles.section}>
        <OptionPills label="Role template *" options={d.templates.map((t) => ({ value: t.id, label: t.name }))} value={templateId} onChange={chooseTemplate} />
        {fieldErrors.role_template_id ? (
          <AppText variant="caption" color={colors.danger}>
            {fieldErrors.role_template_id}
          </AppText>
        ) : null}
        <OptionPills
          label="Department *"
          options={d.departments.map((x) => ({ value: x, label: roleLabel(x) }))}
          value={department}
          onChange={(v) => {
            setDepartment(v);
            setDepartmentTouched(true);
          }}
        />
        <OptionPills<number | null>
          label="Manager"
          options={[{ value: null, label: 'No manager' }, ...d.managers.filter((m) => m.id !== user?.uid).map((m) => ({ value: m.id, label: m.name }))]}
          value={managerId}
          onChange={setManagerId}
        />
        <DateField label="Start date" value={startDate} onChange={setStartDate} />
        <OptionPills
          label="Account"
          options={[
            { value: 'active', label: 'Active account' },
            { value: 'inactive', label: 'Inactive' },
          ]}
          value={active ? 'active' : 'inactive'}
          onChange={(v) => setActive(v === 'active')}
        />
        {!editing ? (
          <OptionPills
            label="Invite"
            options={[
              { value: 'yes', label: 'Email a set-your-password link' },
              { value: 'no', label: "Don't email" },
            ]}
            value={invite ? 'yes' : 'no'}
            onChange={(v) => setInvite(v === 'yes')}
          />
        ) : null}
        <TextField label="Notes" value={notes} onChangeText={setNotes} multiline placeholder="Optional notes about this user" />
      </Card>

      <Button title={editing ? 'Save changes' : 'Create user'} loading={busy} onPress={save} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  section: { gap: spacing.md },
  password: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  mono: { letterSpacing: 1 },
});
