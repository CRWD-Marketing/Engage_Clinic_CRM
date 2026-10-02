import { router } from 'expo-router';
import { useState } from 'react';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { PatientCreateOptions, PatientStoreRequest } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { spacing } from '@/theme';
import { todayYmd } from '@/utils/dates';

type Errors = Partial<Record<keyof PatientStoreRequest, string>>;

/** "Add patient" on the web's Patients page: a client entered directly, without going through the leads pipeline. */
export function PatientNewScreen() {
  const options = useApiQuery('patients.createOptions', () => api.patients.createOptions());

  if (!options.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {options.error ? <ErrorState error={options.error} onRetry={options.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  return <Form options={options.data} />;
}

function Form({ options }: { options: PatientCreateOptions }) {
  const [childName, setChildName] = useState('');
  const [age, setAge] = useState('');
  const [diagnosis, setDiagnosis] = useState('');
  const [programme, setProgramme] = useState('');
  const [parent, setParent] = useState('');
  const [phone, setPhone] = useState('');
  const [payer, setPayer] = useState('');
  const [hours, setHours] = useState('');
  const [renews, setRenews] = useState('');
  const [start, setStart] = useState(todayYmd());
  const [note, setNote] = useState('');
  const [errors, setErrors] = useState<Errors>({});
  const [banner, setBanner] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  async function save() {
    const next: Errors = {};
    if (!childName.trim()) next.child_name = "Enter the child's name.";
    if (age && !/^\d+$/.test(age)) next.child_age = 'Age must be a whole number.';
    if (!diagnosis.trim()) next.diagnosis = 'Enter the diagnosis.';
    if (!programme) next.programme = 'Pick a programme.';
    if (!phone.trim()) next.phone = 'Enter a phone number.';
    if (hours && !/^\d+$/.test(hours)) next.authorized_hours_total = 'Authorized hours must be a whole number.';
    setErrors(next);
    setBanner(Object.keys(next).length > 0 ? 'Fill every field marked * to add the patient.' : null);
    if (Object.keys(next).length > 0) return;

    setSaving(true);
    try {
      const res = await api.patients.store({
        child_name: childName,
        child_age: age ? Number(age) : null,
        diagnosis,
        programme,
        parent_guardian_name: parent || null,
        phone,
        payer_name: payer || null,
        authorized_hours_total: payer && hours ? Number(hours) : null,
        authorization_renews_at: payer && renews ? renews : null,
        enrolled_at: start || null,
        clinical_note: note || null,
      });
      router.replace({ pathname: '/patients/[id]', params: { id: String(res.patient_id) } });
    } catch (e) {
      if (isApiError(e) && e.status === 422) {
        const fromServer: Errors = {};
        for (const key of Object.keys(e.errors) as (keyof PatientStoreRequest)[]) fromServer[key] = e.fieldError(key);
        setErrors(fromServer);
      }
      setBanner(errorMessage(e));
      setSaving(false);
    }
  }

  return (
    <Screen edges={[]}>
      <AppText variant="caption">For a client who didn&apos;t come through the leads pipeline.</AppText>
      {banner ? <Banner text={banner} /> : null}

      <Card style={styles.card}>
        <TextField label="Child's name" required value={childName} onChangeText={setChildName} error={errors.child_name} />
        <TextField label="Age" value={age} onChangeText={setAge} keyboardType="number-pad" error={errors.child_age} />
        <TextField label="Parent / guardian" value={parent} onChangeText={setParent} error={errors.parent_guardian_name} />
        <TextField label="Phone" required value={phone} onChangeText={setPhone} keyboardType="phone-pad" error={errors.phone} />
      </Card>

      <Card style={styles.card}>
        <TextField label="Diagnosis" required value={diagnosis} onChangeText={setDiagnosis} error={errors.diagnosis} />
        <OptionPills
          label="Programme *"
          options={options.packages.map((p) => ({ value: p.name, label: p.label }))}
          value={programme}
          onChange={setProgramme}
        />
        {errors.programme ? <Banner text={errors.programme} /> : null}
        <DateField label="Start" value={start} onChange={setStart} error={errors.enrolled_at} />
      </Card>

      <Card style={styles.card}>
        <OptionPills
          label="Insurance (optional)"
          options={[{ value: '', label: 'Add later' }, ...[...options.insurances, 'Self-pay'].map((n) => ({ value: n, label: n }))]}
          value={payer}
          onChange={setPayer}
        />
        {payer ? (
          <>
            <TextField label="Auth. hrs" value={hours} onChangeText={setHours} keyboardType="number-pad" error={errors.authorized_hours_total} />
            <DateField label="Renewal" value={renews} onChange={setRenews} error={errors.authorization_renews_at} />
          </>
        ) : null}
      </Card>

      <Card style={styles.card}>
        <TextField label="Clinical note" value={note} onChangeText={setNote} multiline placeholder="Optional — adds a first session note" />
      </Card>

      <Button title="Add patient" onPress={save} loading={saving} />
      <Button title="Cancel" variant="secondary" onPress={() => router.back()} disabled={saving} />
    </Screen>
  );
}

const styles = { card: { gap: spacing.md } } as const;
