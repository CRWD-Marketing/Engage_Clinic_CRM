import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { UserInput, UserListResponse, UserProfile } from '@/api/types';
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
import { isoToYmd } from '@/utils/dates';

import { enumLabel, generatePassword } from './userFormat';

/** user/create.blade.php and user/edit.blade.php: one form; `id` (a public_id) means edit. */
export function UserFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  // The list call carries the department, role and manager options.
  const options = useApiQuery('users.options', () => api.users.list({ page: 1 }));
  const existing = useApiQuery(`users.show:${id ?? 'new'}`, () => (id ? api.users.show(id) : Promise.resolve(null)));

  if (!options.data || existing.data === undefined) {
    const error = options.error ?? existing.error;
    return (
      <Screen scroll={false} edges={[]}>
        {error ? <ErrorState error={error} onRetry={() => { options.refresh(); existing.refresh(); }} /> : <LoadingState />}
      </Screen>
    );
  }
  return <Form key={existing.data?.updated_at ?? 'new'} options={options.data} user={existing.data} />;
}

function Form({ options, user }: { options: UserListResponse; user: UserProfile | null }) {
  const editing = user !== null;
  const [firstName, setFirstName] = useState(user?.first_name ?? '');
  const [middleName, setMiddleName] = useState(user?.middle_name ?? '');
  const [lastName, setLastName] = useState(user?.last_name ?? '');
  const [email, setEmail] = useState(user?.email ?? '');
  const [phone, setPhone] = useState(user?.phone_number ?? '');
  // Create: a generated password to hand over. Edit: optional new password, typed twice.
  const [generated, setGenerated] = useState(() => generatePassword());
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [department, setDepartment] = useState<UserInput['department'] | ''>(user?.department ?? '');
  const [role, setRole] = useState<UserInput['role'] | ''>(user?.role ?? '');
  const [managerId, setManagerId] = useState<number | null>(user?.manager_id ?? null);
  const [startDate, setStartDate] = useState(user?.start_date ? isoToYmd(user.start_date) : '');
  const [notes, setNotes] = useState(user?.notes ?? '');
  const [active, setActive] = useState(user?.is_active ?? true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  async function save() {
    setBusy(true);
    setError(null);
    setFieldErrors({});
    const input = {
      first_name: firstName,
      middle_name: middleName || null,
      last_name: lastName,
      email,
      phone_number: phone || null,
      department: department as UserInput['department'],
      role: role as UserInput['role'],
      manager_id: managerId,
      start_date: startDate || null,
      notes: notes || null,
      is_active: active,
      ...(editing ? (password ? { password, password_confirmation: confirm } : {}) : { password: generated, password_confirmation: generated }),
    };
    try {
      if (user) {
        const res = await api.users.update(user.public_id, input);
        leaveFlash(`users.show:${user.public_id}`, res.message);
        router.back();
      } else {
        const res = await api.users.store(input);
        router.replace({ pathname: '/users/[id]', params: { id: res.data.public_id, flash: res.message } });
      }
    } catch (e) {
      setError(errorMessage(e));
      if (isApiError(e)) setFieldErrors(Object.fromEntries(Object.keys(e.errors).map((k) => [k, e.fieldError(k)!])));
      setBusy(false);
    }
  }

  return (
    <Screen edges={[]}>
      <Stack.Screen options={{ title: editing ? 'Edit user' : 'Add user' }} />
      {error ? <Banner text={error} /> : null}

      <Card style={styles.section}>
        <AppText variant="heading">Personal information</AppText>
        <TextField label="First name" required value={firstName} onChangeText={setFirstName} error={fieldErrors.first_name} />
        <TextField label="Middle name" value={middleName} onChangeText={setMiddleName} placeholder="Optional" error={fieldErrors.middle_name} />
        <TextField label="Last name" required value={lastName} onChangeText={setLastName} error={fieldErrors.last_name} />
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
        <TextField label="Phone number" value={phone} onChangeText={setPhone} keyboardType="phone-pad" error={fieldErrors.phone_number} />
      </Card>

      <Card style={styles.section}>
        <AppText variant="heading">Security</AppText>
        {editing ? (
          <>
            <TextField
              label="New password"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              autoCapitalize="none"
              hint="Leave blank to keep the current password."
              error={fieldErrors.password}
            />
            <TextField label="Confirm new password" value={confirm} onChangeText={setConfirm} secureTextEntry autoCapitalize="none" />
          </>
        ) : (
          <>
            <View style={styles.password}>
              <AppText variant="caption">Generated password</AppText>
              <AppText variant="heading" selectable style={styles.mono}>
                {generated}
              </AppText>
            </View>
            <AppText variant="caption" color={fieldErrors.password ? colors.danger : undefined}>
              {fieldErrors.password ?? 'Share this with the user securely — it won’t be shown again after the account is created.'}
            </AppText>
            <Button title="Generate another" variant="secondary" onPress={() => setGenerated(generatePassword())} />
          </>
        )}
      </Card>

      <Card style={styles.section}>
        <AppText variant="heading">Role & assignment</AppText>
        <OptionPills
          label="Department *"
          options={options.departments.map((x) => ({ value: x, label: enumLabel(x) }))}
          value={department}
          onChange={setDepartment}
        />
        {fieldErrors.department ? <AppText variant="caption" color={colors.danger}>{fieldErrors.department}</AppText> : null}
        <OptionPills label="Role *" options={options.roles.map((x) => ({ value: x, label: enumLabel(x) }))} value={role} onChange={setRole} />
        {fieldErrors.role ? <AppText variant="caption" color={colors.danger}>{fieldErrors.role}</AppText> : null}
        <OptionPills<number | null>
          label="Manager"
          options={[
            { value: null, label: 'No manager' },
            ...options.managers.filter((m) => m.id !== user?.id).map((m) => ({ value: m.id, label: m.name })),
          ]}
          value={managerId}
          onChange={setManagerId}
        />
        <DateField label="Start date" value={startDate} onChange={setStartDate} placeholder="Not set (defaults to today)" error={fieldErrors.start_date} />
        <TextField label="Notes" value={notes} onChangeText={setNotes} multiline placeholder="Optional notes about this user" />
        <OptionPills
          label="Account"
          options={[
            { value: 'active', label: 'Active account' },
            { value: 'inactive', label: 'Inactive' },
          ]}
          value={active ? 'active' : 'inactive'}
          onChange={(v) => setActive(v === 'active')}
        />
      </Card>

      <Button title={editing ? 'Save changes' : 'Create user'} loading={busy} onPress={save} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  section: { gap: spacing.md },
  password: { gap: 4, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  mono: { letterSpacing: 1 },
});
