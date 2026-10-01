import { router } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { LeadStoreRequest } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { spacing } from '@/theme';

import { LEAD_SOURCES } from './leadFormat';

type Errors = Partial<Record<keyof LeadStoreRequest, string>>;

/** The "+ New Lead" modal (lead/index.blade.php) as a screen. POST /admin/leads. */
export function NewLeadScreen() {
  const board = useApiQuery('leads.board', () => api.leads.board());

  const [childName, setChildName] = useState('');
  const [childAge, setChildAge] = useState('');
  const [parent, setParent] = useState('');
  const [phone, setPhone] = useState('');
  const [source, setSource] = useState<string | null>(null);
  const [interestedIn, setInterestedIn] = useState('');
  const [insurance, setInsurance] = useState<string | null>(null);
  const [value, setValue] = useState('');
  const [owner, setOwner] = useState<number | null>(null);
  const [followUp, setFollowUp] = useState('');
  const [notes, setNotes] = useState('');

  const [errors, setErrors] = useState<Errors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  if (!board.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {board.error ? <ErrorState error={board.error} onRetry={board.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  async function save() {
    // The web marks child and parent/guardian as required in the form (the server allows blanks).
    const required: Errors = {};
    if (!childName.trim()) required.child_name = "Enter the child's name.";
    if (!parent.trim()) required.parent_guardian_name = 'Enter the parent or guardian.';
    if (followUp && !/^\d{4}-\d{2}-\d{2}$/.test(followUp)) required.follow_up_due_at = 'Use the format YYYY-MM-DD.';
    setErrors(required);
    setFormError(null);
    if (Object.keys(required).length > 0) return;

    setSaving(true);
    try {
      const res = await api.leads.store({
        child_name: childName,
        child_age: childAge,
        parent_guardian_name: parent,
        phone,
        source,
        interested_in: interestedIn,
        insurance,
        estimated_value: value,
        assigned_to: owner,
        follow_up_due_at: followUp,
        notes,
      });
      router.replace({ pathname: '/leads/[id]', params: { id: String(res.lead.id) } });
    } catch (e) {
      if (isApiError(e) && e.status === 422) {
        const next: Errors = {};
        for (const key of Object.keys(e.errors) as (keyof LeadStoreRequest)[]) next[key] = e.fieldError(key);
        setErrors(next);
      }
      setFormError(errorMessage(e));
      setSaving(false);
    }
  }

  return (
    <Screen edges={['bottom']}>
      <Card style={styles.card}>
        <View>
          <AppText variant="heading">New lead</AppText>
          <AppText variant="caption">Add an enquiry to the pipeline. It starts in the New stage.</AppText>
        </View>
        {formError ? <Banner text={formError} /> : null}

        <TextField label="Child's name" required value={childName} onChangeText={setChildName} error={errors.child_name} />
        <TextField label="Age" value={childAge} onChangeText={setChildAge} keyboardType="number-pad" maxLength={10} error={errors.child_age} />
        <TextField label="Parent / guardian" required value={parent} onChangeText={setParent} error={errors.parent_guardian_name} />
        <TextField label="Phone" icon="call-outline" value={phone} onChangeText={setPhone} keyboardType="phone-pad" placeholder="+971 5x xxx xxxx" error={errors.phone} />

        <OptionPills label="Source" options={LEAD_SOURCES.map((s) => ({ value: s, label: s }))} value={source} onChange={setSource} />
        <TextField label="Interested in" value={interestedIn} onChangeText={setInterestedIn} placeholder="e.g. ABA therapy" error={errors.interested_in} />
        <OptionPills
          label="Insurance"
          options={board.data.insurance_options.map((s) => ({ value: s, label: s }))}
          value={insurance}
          onChange={setInsurance}
        />
        <TextField label="Est. monthly value (AED)" value={value} onChangeText={setValue} keyboardType="number-pad" error={errors.estimated_value} />

        <OptionPills
          label="Assigned to"
          options={[{ value: null, label: 'Unassigned' }, ...board.data.assignable_users.map((u) => ({ value: u.id, label: u.name }))]}
          value={owner}
          onChange={setOwner}
        />
        <TextField
          label="Follow-up date (YYYY-MM-DD)"
          value={followUp}
          onChangeText={setFollowUp}
          keyboardType="numbers-and-punctuation"
          error={errors.follow_up_due_at}
        />
        <TextField label="Notes" value={notes} onChangeText={setNotes} multiline error={errors.notes} />

        <Button title="Create lead" onPress={save} loading={saving} />
        <Button title="Cancel" variant="secondary" onPress={() => router.back()} disabled={saving} />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { gap: spacing.md },
});
