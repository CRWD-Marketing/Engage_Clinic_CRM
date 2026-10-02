import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import type { PatientNote } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { TextField } from '@/components/TextField';
import { colors, fonts, noteAgeColors, noteStatusColors, spacing } from '@/theme';
import { formatDateTimeShort, hoursSince } from '@/utils/dates';

type Props = {
  note: PatientNote;
  selectable: boolean;
  selected: boolean;
  busy: boolean;
  onToggleSelect: () => void;
  onSignOff: () => void;
  onFlag: (reason: string) => Promise<boolean>;
  onUnflag: () => void;
};

/** One note in the review queue: who/when/age, the note, and sign-off / flag actions. */
export function NoteReviewCard({ note, selectable, selected, busy, onToggleSelect, onSignOff, onFlag, onUnflag }: Props) {
  const [flagging, setFlagging] = useState(false);
  const [reason, setReason] = useState('');
  const [reasonError, setReasonError] = useState<string | undefined>();

  const hoursOld = hoursSince(note.created_at);
  const overdue = hoursOld >= 48;
  const child = note.patient?.lead?.child_name ?? 'Unknown patient';
  const signed = note.signed_off_at !== null;

  async function saveFlag() {
    if (!reason.trim()) {
      setReasonError('Add a reason for flagging this note.');
      return;
    }
    setReasonError(undefined);
    const ok = await onFlag(reason);
    if (ok) {
      setFlagging(false);
      setReason('');
    }
  }

  return (
    <Card style={[styles.card, selected && styles.cardSelected]}>
      <View style={styles.head}>
        {selectable ? (
          <Pressable
            onPress={onToggleSelect}
            accessibilityRole="checkbox"
            accessibilityState={{ checked: selected }}
            accessibilityLabel={`Select note on ${child}`}
            hitSlop={8}>
            <Ionicons name={selected ? 'checkbox' : 'square-outline'} size={22} color={selected ? colors.pink : colors.textFaint} />
          </Pressable>
        ) : null}
        <Pressable
          style={styles.flex}
          onPress={() => router.push({ pathname: '/patients/[id]', params: { id: String(note.patient_id) } }, { withAnchor: true })}
          accessibilityRole="link">
          <AppText variant="bodyStrong">{child}</AppText>
          <AppText variant="caption">
            {note.author_name} · {formatDateTimeShort(note.created_at)}
          </AppText>
        </Pressable>
        {signed ? (
          <Chip label="Signed off" colors={noteStatusColors.signed} />
        ) : (
          <Chip
            label={overdue ? `Overdue ${Math.floor(hoursOld / 24)}d` : `Pending ${hoursOld}h`}
            colors={overdue ? noteAgeColors.overdue : noteAgeColors.pending}
          />
        )}
      </View>

      <AppText variant="body">{note.body}</AppText>

      {note.flagged ? (
        <View style={styles.flagBox}>
          <Ionicons name="flag" size={14} color={noteStatusColors.flagged.fg} />
          <AppText variant="caption" color={noteStatusColors.flagged.fg} style={styles.flex}>
            {note.flag_reason ?? 'Flagged for supervisor review'}
          </AppText>
        </View>
      ) : null}

      {flagging ? (
        <View style={styles.flagForm}>
          <TextField
            label="Reason for flagging"
            value={reason}
            onChangeText={setReason}
            placeholder="e.g. Needs BCBA review before sign-off"
            maxLength={255}
            autoFocus
            error={reasonError}
          />
          <View style={styles.actions}>
            <View style={styles.flex}>
              <Button title="Cancel" variant="secondary" onPress={() => setFlagging(false)} disabled={busy} />
            </View>
            <View style={styles.flex}>
              <Button title="Save flag" onPress={saveFlag} loading={busy} />
            </View>
          </View>
        </View>
      ) : (
        <View style={styles.actions}>
          <View style={styles.flex}>
            {note.flagged ? (
              <Button title="Remove flag" variant="secondary" onPress={onUnflag} disabled={busy} />
            ) : (
              <Button title="Flag" variant="secondary" onPress={() => setFlagging(true)} disabled={busy} />
            )}
          </View>
          {!signed ? (
            <View style={styles.flex}>
              <Button title="Sign off" onPress={onSignOff} loading={busy} />
            </View>
          ) : null}
        </View>
      )}

      {signed && note.signed_off_by_name ? (
        <AppText variant="caption" color={noteStatusColors.signed.fg} style={styles.signedLine}>
          Signed off by {note.signed_off_by_name}
        </AppText>
      ) : null}
    </Card>
  );
}

const styles = StyleSheet.create({
  card: { gap: spacing.sm },
  cardSelected: { borderColor: colors.pink },
  head: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  flex: { flex: 1 },
  flagBox: {
    flexDirection: 'row',
    gap: 6,
    alignItems: 'flex-start',
    padding: spacing.sm,
    borderRadius: 8,
    backgroundColor: noteStatusColors.flagged.bg,
  },
  flagForm: { gap: spacing.sm },
  actions: { flexDirection: 'row', gap: spacing.sm },
  signedLine: { fontFamily: fonts.bodyBold },
});
