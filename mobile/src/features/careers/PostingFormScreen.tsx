import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { JobPosting } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { leaveFlash } from '@/hooks/useReturnFlash';
import { colors, spacing } from '@/theme';

/** The New posting modal and edit page (career/postings/_fields.blade.php). `id` means edit. */
export function PostingFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const query = useApiQuery('careers.postings', () => api.careers.postings());

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  const posting = id ? (query.data.postings.find((p) => p.id === Number(id)) ?? null) : null;
  return <Form posting={posting} />;
}

function Form({ posting }: { posting: JobPosting | null }) {
  const [title, setTitle] = useState(posting?.title ?? '');
  const [type, setType] = useState(posting?.employment_type ?? '');
  const [location, setLocation] = useState(posting?.location ?? '');
  const [status, setStatus] = useState<JobPosting['status']>(posting?.status ?? 'active');
  const [description, setDescription] = useState(posting?.description ?? '');
  // One input per requirement, like the web's "+ Add requirement" repeater.
  const [requirements, setRequirements] = useState<string[]>(posting?.requirements.length ? posting.requirements : ['']);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [titleError, setTitleError] = useState<string | undefined>();
  const [confirming, setConfirming] = useState(false);

  async function save() {
    setBusy(true);
    setError(null);
    setTitleError(undefined);
    const input = { title, employment_type: type || null, location: location || null, description: description || null, requirements, status };
    try {
      const res = posting ? await api.careers.updatePosting(posting.id, input) : await api.careers.storePosting(input);
      leaveFlash('careers.postings', res.message);
      router.back();
    } catch (e) {
      setError(errorMessage(e));
      if (isApiError(e)) setTitleError(e.fieldError('title'));
      setBusy(false);
    }
  }

  async function remove() {
    if (!posting) return;
    setBusy(true);
    setError(null);
    try {
      const res = await api.careers.destroyPosting(posting.id);
      leaveFlash('careers.postings', res.message);
      router.back();
    } catch (e) {
      setError(errorMessage(e));
      setBusy(false);
    }
  }

  return (
    <Screen edges={[]}>
      <Stack.Screen options={{ title: posting ? 'Edit posting' : 'New posting' }} />
      {error ? <Banner text={error} /> : null}

      <Card style={styles.section}>
        <TextField label="Job title" required value={title} onChangeText={setTitle} placeholder="e.g. Registered Behavior Technician" error={titleError} />
        <TextField label="Employment type" value={type} onChangeText={setType} placeholder="e.g. Full-time" />
        <TextField label="Location" value={location} onChangeText={setLocation} placeholder="e.g. Abu Dhabi" />
        <OptionPills
          label="Status *"
          options={[
            { value: 'active', label: 'Active' },
            { value: 'inactive', label: 'Inactive' },
          ]}
          value={status}
          onChange={setStatus}
        />
        <TextField label="Description" value={description} onChangeText={setDescription} multiline placeholder="What the role involves" />
      </Card>

      <Card style={styles.section}>
        <AppText variant="heading">Requirements</AppText>
        {requirements.map((r, i) => (
          <View key={i} style={styles.requirement}>
            <View style={styles.flex}>
              <TextField
                label={`Requirement ${i + 1}`}
                value={r}
                onChangeText={(text) => setRequirements(requirements.map((x, j) => (j === i ? text : x)))}
                placeholder="e.g. Active BCBA certification"
              />
            </View>
            {requirements.length > 1 ? (
              <Button title="Remove" variant="secondary" onPress={() => setRequirements(requirements.filter((_, j) => j !== i))} />
            ) : null}
          </View>
        ))}
        <Button title="Add requirement" variant="secondary" onPress={() => setRequirements([...requirements, ''])} />
      </Card>

      <Button title={posting ? 'Save posting' : 'Create posting'} loading={busy} onPress={save} />
      {posting ? (
        <>
          <Button title="Delete posting" variant="secondary" onPress={() => setConfirming(!confirming)} />
          {confirming ? (
            <Card style={styles.section}>
              <AppText variant="bodyStrong">Delete this posting?</AppText>
              <AppText variant="caption" color={colors.textMuted}>
                It disappears from the public careers page immediately. Applications keep the job title.
              </AppText>
              <Button title="Delete" loading={busy} onPress={remove} />
            </Card>
          ) : null}
        </>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  section: { gap: spacing.md },
  requirement: { flexDirection: 'row', alignItems: 'flex-end', gap: spacing.sm },
});
