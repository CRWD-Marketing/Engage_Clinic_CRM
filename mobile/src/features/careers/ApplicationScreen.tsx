import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { ApplicationStatus, JobApplication } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, CardHeader } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { leaveFlash } from '@/hooks/useReturnFlash';
import { applicationStatusColors, colors, spacing } from '@/theme';
import { formatClockTime, formatDayMonthYear, isoToYmd, timeAgo } from '@/utils/dates';
import { openPdf } from '@/utils/openPdf';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** The application detail panel of career/applications/index.blade.php. */
export function ApplicationScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  // The web page reads the whole list too; the detail is one row of it.
  const query = useApiQuery('careers.applications:all', () => api.careers.applications('all'));
  const [flash, setFlash] = useState<Flash>(null);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  const application = query.data.applications.find((a) => a.id === Number(id));
  if (!application) {
    return (
      <Screen edges={[]}>
        <Banner text="This application is no longer on the list." tone="info" />
      </Screen>
    );
  }
  return (
    <Detail
      application={application}
      statuses={query.data.statuses}
      flash={flash}
      setFlash={setFlash}
      refreshing={query.refreshing}
      onRefresh={query.refresh}
      onChanged={query.reload}
    />
  );
}

function Detail({
  application: a,
  statuses,
  flash,
  setFlash,
  refreshing,
  onRefresh,
  onChanged,
}: {
  application: JobApplication;
  statuses: Record<ApplicationStatus, string>;
  flash: Flash;
  setFlash: (f: Flash) => void;
  refreshing: boolean;
  onRefresh: () => void;
  onChanged: () => void;
}) {
  const [note, setNote] = useState('');
  const [busy, setBusy] = useState(false);
  const [confirming, setConfirming] = useState(false);

  async function run(action: () => Promise<{ message: string }>, after?: () => void) {
    setBusy(true);
    setFlash(null);
    try {
      const res = await action();
      setFlash({ text: res.message, tone: 'success' });
      after?.();
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  async function remove() {
    setBusy(true);
    setFlash(null);
    try {
      const res = await api.careers.destroy(a.id);
      leaveFlash('careers', res.message);
      router.back();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
      setBusy(false);
    }
  }

  async function openResume() {
    setFlash(null);
    try {
      await openPdf(await api.careers.resume(a.id, a.resume_name ?? 'resume.pdf'));
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    }
  }

  const applied = isoToYmd(a.created_at);
  return (
    <Screen edges={[]} refreshing={refreshing} onRefresh={onRefresh}>
      <Stack.Screen options={{ title: a.full_name }} />

      <Card style={styles.gap}>
        <View style={styles.head}>
          <Avatar id={a.id} name={a.full_name} size={48} />
          <View style={styles.flex}>
            <AppText variant="heading">{a.full_name}</AppText>
            <AppText variant="caption">
              Applied for {a.job_title} · {timeAgo(a.created_at)}
            </AppText>
            <AppText variant="caption">{a.email}</AppText>
            <AppText variant="caption">{a.years_experience ? `${a.years_experience} yrs experience` : 'Experience not stated'}</AppText>
          </View>
          <Chip label={a.status_label} colors={applicationStatusColors[a.status]} />
        </View>
        {a.has_resume ? (
          <Button title={`Open resume · ${a.resume_name}`} variant="secondary" onPress={openResume} />
        ) : (
          <AppText variant="caption">No resume</AppText>
        )}
      </Card>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <Card style={styles.gap}>
        <CardHeader title="Status" />
        <OptionPills<ApplicationStatus>
          options={(Object.keys(statuses) as ApplicationStatus[]).map((s) => ({ value: s, label: statuses[s] }))}
          value={a.status}
          onChange={(s) => s !== a.status && run(() => api.careers.setStatus(a.id, s))}
          disabled={busy}
        />
      </Card>

      <Card style={styles.gap}>
        <CardHeader title="Cover letter" />
        <AppText variant="body" color={a.cover_letter ? undefined : colors.textMuted}>
          {a.cover_letter || 'No cover letter provided.'}
        </AppText>
      </Card>

      <Card style={styles.gap}>
        <CardHeader title="Notes" />
        {a.notes.length === 0 ? <AppText variant="caption">No notes yet.</AppText> : null}
        {a.notes.map((n) => (
          <View key={n.id} style={styles.note}>
            <AppText variant="caption">
              {n.author_name} · {timeAgo(n.created_at)}
            </AppText>
            <AppText variant="body">{n.body}</AppText>
          </View>
        ))}
        <TextField label="Add a note" value={note} onChangeText={setNote} multiline placeholder="Interview feedback, next steps, etc." />
        <Button title="Add note" variant="secondary" loading={busy} disabled={!note.trim()} onPress={() => run(() => api.careers.addNote(a.id, note), () => setNote(''))} />
      </Card>

      <Card style={styles.gap}>
        <CardHeader title="Details" />
        <Field label="Email" value={a.email} />
        <Field label="Experience" value={a.years_experience || '—'} />
        <Field label="Applied for" value={a.job_title} />
        <Field label="Applied" value={`${formatDayMonthYear(applied)} · ${formatClockTime(a.created_at)}`} />
      </Card>

      <Button title="Delete application" variant="secondary" onPress={() => setConfirming(!confirming)} />
      {confirming ? (
        <Card style={styles.gap}>
          <AppText variant="bodyStrong">Delete this application?</AppText>
          <AppText variant="caption">This also removes the stored resume and can&apos;t be undone.</AppText>
          <Button title="Delete" loading={busy} onPress={remove} />
        </Card>
      ) : null}
    </Screen>
  );
}

function Field({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.field}>
      <AppText variant="caption">{label}</AppText>
      <AppText variant="bodyStrong">{value}</AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  note: { gap: 2, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  field: { paddingVertical: 4, gap: 2 },
});
