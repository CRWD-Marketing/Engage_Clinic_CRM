import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { PatientNote } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { TextField } from '@/components/TextField';
import { patientColors, radius, spacing } from '@/theme';
import { timeAgo } from '@/utils/dates';

const NOTE_MAX = 2000;

type Props = {
  patientId: number;
  notes: PatientNote[];
  /** lead.assessment_report_summary — the "Converted from lead" banner. */
  conversionSummary: string | null;
  /** False for coordinators (PatientController::addNote 403s) and view-only users. */
  canAdd: boolean;
};

/** "Session notes" card (patient/show.blade.php). */
export function NotesCard({ patientId, notes, conversionSummary, canAdd }: Props) {
  const [draft, setDraft] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | undefined>();
  // Notes added here, shown on top until the next refresh brings them back from the server.
  const [added, setAdded] = useState<PatientNote[]>([]);

  const list = [...added, ...notes.filter((n) => !added.some((a) => a.id === n.id))];

  async function addNote() {
    setSaving(true);
    setError(undefined);
    try {
      const res = await api.patients.addNote(patientId, draft);
      setAdded((current) => [res.note, ...current]);
      setDraft('');
    } catch (e) {
      setError(isApiError(e) ? (e.fieldError('body') ?? e.message) : errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Card style={styles.card}>
      <AppText variant="heading">Session notes</AppText>

      {conversionSummary ? (
        <View style={styles.conversion}>
          <AppText variant="caption" color={patientColors.conversionBanner.fg}>
            Converted from lead — {conversionSummary}
          </AppText>
        </View>
      ) : null}

      {canAdd ? (
        <>
          <TextField
            label="New note"
            value={draft}
            onChangeText={setDraft}
            placeholder="Write a session note — what was worked on, response, next step…"
            multiline
            maxLength={NOTE_MAX}
            hint="Saved to the clinical record with your name and time."
            error={error}
          />
          <Button title="Add note" onPress={addNote} loading={saving} disabled={!draft.trim()} />
        </>
      ) : null}

      {list.length === 0 ? (
        <AppText variant="caption">No notes on record yet.</AppText>
      ) : (
        list.map((note) => (
          <View key={note.id} style={styles.note}>
            <Divider />
            <View style={styles.noteHead}>
              <AppText variant="bodyStrong" style={styles.flex}>
                {note.author_name}
              </AppText>
              <AppText variant="caption">{timeAgo(note.created_at)}</AppText>
            </View>
            <AppText variant="body">{note.body}</AppText>
          </View>
        ))
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  card: { gap: spacing.md },
  flex: { flex: 1 },
  conversion: {
    backgroundColor: patientColors.conversionBanner.bg,
    borderColor: patientColors.conversionBanner.border,
    borderWidth: 1,
    borderRadius: radius.input,
    padding: spacing.md,
  },
  note: { gap: spacing.xs },
  noteHead: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.sm, paddingTop: spacing.sm },
});
