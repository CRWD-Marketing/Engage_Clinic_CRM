import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { PatientDetail } from '@/api/types';
import { useCurrentUser } from '@/auth/session';
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
import { isoToYmd } from '@/utils/dates';

/** The "Client details" form behind Edit details on patient/show.blade.php. */
export function PatientEditScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const query = useApiQuery(`patients.show:${id}`, () => api.patients.show(Number(id)));

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  return <Form detail={query.data} />;
}

function Form({ detail: d }: { detail: PatientDetail }) {
  const user = useCurrentUser();
  const lead = d.patient.lead;
  const primary = d.authorizations[0] ?? null;
  // Coordinators handle intake and scheduling; the server ignores clinical fields from them.
  const clinical = user.role !== 'COORDINATOR';

  const [childName, setChildName] = useState(lead.child_name ?? '');
  const [age, setAge] = useState(lead.child_age ?? '');
  const [diagnosis, setDiagnosis] = useState(d.patient.diagnosis ?? '');
  const [programme, setProgramme] = useState(d.patient.programme ?? '');
  const [parent, setParent] = useState(lead.parent_guardian_name ?? '');
  const [phone, setPhone] = useState(lead.phone ?? '');
  const [insurance, setInsurance] = useState(primary?.payer_name ?? '');
  const [hours, setHours] = useState(primary?.authorized_hours_total != null ? String(primary.authorized_hours_total) : '');
  const [renews, setRenews] = useState(primary?.renews_at ? isoToYmd(primary.renews_at) : '');
  const [start, setStart] = useState(isoToYmd(d.patient.enrolled_at));
  const [note, setNote] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Keep the current programme / payer pickable even when it is no longer in the active lists.
  const packages = d.edit_options.packages.some((p) => p.name === programme) || !programme
    ? d.edit_options.packages
    : [{ name: programme, label: `${programme} (inactive)` }, ...d.edit_options.packages];
  const insurers = [...d.edit_options.insurances, 'Self-pay'];
  const insurerOptions = insurance && !insurers.includes(insurance) ? [insurance, ...insurers] : insurers;

  async function save() {
    if (!childName.trim()) return setError('Enter the child’s name.');
    if (!phone.trim()) return setError('Enter a phone number.');
    if (age && !/^\d+$/.test(age)) return setError('Age must be a whole number.');
    if (hours && !/^\d+$/.test(hours)) return setError('Authorized hours must be a whole number.');
    setError(null);
    setSaving(true);
    try {
      await api.patients.update(d.patient.id, {
        child_name: childName,
        child_age: age ? Number(age) : null,
        diagnosis,
        programme,
        parent_guardian_name: parent,
        phone,
        insurance,
        authorized_hours_total: hours ? Number(hours) : null,
        renews_at: renews || null,
        enrolled_at: start || null,
        clinical_note: note,
      });
      router.back();
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Screen edges={[]}>
      <AppText variant="caption">Clinical and enrollment record</AppText>
      {error ? <Banner text={error} /> : null}

      <Card style={styles.card}>
        <TextField label="Child's name" required value={childName} onChangeText={setChildName} />
        <TextField label="Age" value={age} onChangeText={setAge} keyboardType="number-pad" />
        <TextField label="Parent / guardian" value={parent} onChangeText={setParent} />
        <TextField label="Phone" required value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
      </Card>

      {clinical ? (
        <Card style={styles.card}>
          <TextField label="Diagnosis" required value={diagnosis} onChangeText={setDiagnosis} />
          <OptionPills
            label="Programme *"
            options={[{ value: '', label: 'None' }, ...packages.map((p) => ({ value: p.name, label: p.label }))]}
            value={programme}
            onChange={setProgramme}
          />
          <DateField label="Start" value={start} onChange={setStart} />
        </Card>
      ) : null}

      <Card style={styles.card}>
        <OptionPills
          label="Insurance"
          options={[{ value: '', label: 'No insurance' }, ...insurerOptions.map((n) => ({ value: n, label: n }))]}
          value={insurance}
          onChange={setInsurance}
        />
        <TextField label="Auth. hrs" value={hours} onChangeText={setHours} keyboardType="number-pad" />
        <DateField label="Renewal" value={renews} onChange={setRenews} />
      </Card>

      {clinical ? (
        <Card style={styles.card}>
          <TextField
            label="Clinical note"
            value={note}
            onChangeText={setNote}
            multiline
            placeholder="Optional — adds a timestamped session note"
          />
        </Card>
      ) : null}

      <Button title="Save client" onPress={save} loading={saving} />
      <Button title="Cancel" variant="secondary" onPress={() => router.back()} disabled={saving} />
    </Screen>
  );
}

const styles = { card: { gap: spacing.md } } as const;
