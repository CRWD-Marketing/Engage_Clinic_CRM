import Ionicons from '@expo/vector-icons/Ionicons';
import { Pressable, StyleSheet, View } from 'react-native';

import type { MySessionPayload } from '@/api/types';
import { AppText } from '@/components/AppText';
import { colors, colorsForActivity, fonts, monthColors, radius, sessionCategoryColors, spacing } from '@/theme';
import { formatTime } from '@/utils/dates';

import { canAddTherapistNote, sessionTag } from './sessionStatus';

type Props = { session: MySessionPayload; viewerId: number; onPress: () => void; showTherapist?: boolean };

/** A session card from calendar/my.blade.php, tinted by activity type. */
export function SessionCard({ session: s, viewerId, onPress, showTherapist = false }: Props) {
  const tint = colorsForActivity(s.activity_type, s.category);
  const tag = sessionTag(s);
  const meta = [s.activity_type, s.notes].filter(Boolean).join(' · ');
  const strike = tag.cancelled ? styles.strike : null;
  const showNote = canAddTherapistNote(s, viewerId);

  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel={`${formatTime(s.start_time)} to ${formatTime(s.end_time)}, ${s.patient_name}, ${s.activity_type}, ${tag.label}`}
      style={({ pressed }) => [styles.card, { backgroundColor: tint.bg }, pressed && styles.pressed]}>
      <View style={styles.top}>
        <AppText style={[styles.time, { color: tint.fg }, strike]}>
          {formatTime(s.start_time)}–{formatTime(s.end_time)}
        </AppText>
        <AppText style={[styles.duration, { color: tint.fg }]}>{s.duration_minutes} min</AppText>
      </View>

      <AppText style={[styles.name, strike]} numberOfLines={1}>
        {s.patient_name}
      </AppText>
      {meta ? (
        <AppText variant="caption" color={colors.textSecondary} numberOfLines={2}>
          {meta}
        </AppText>
      ) : null}
      {s.room || showTherapist ? (
        <AppText variant="caption" color={colors.textSecondary}>
          {[showTherapist ? s.therapist_name : null, s.room].filter(Boolean).join(' · ')}
        </AppText>
      ) : null}

      {s.supervised ? (
        <View style={styles.supervised}>
          <AppText style={styles.supBadge}>★ Supervised</AppText>
          {s.supervision_notes ? (
            <AppText variant="caption" color={colors.textSecondary} numberOfLines={2}>
              {s.supervision_notes}
            </AppText>
          ) : null}
        </View>
      ) : null}

      {s.therapist_note ? (
        <View style={styles.myNote}>
          <Ionicons name="document-text-outline" size={14} color={colors.textSecondary} />
          <AppText variant="caption" color={colors.text} numberOfLines={3} style={styles.flex}>
            {s.therapist_note}
          </AppText>
        </View>
      ) : null}

      <View style={styles.footer}>
        <AppText style={[styles.tag, { color: tag.color }, tag.cancelled && styles.strike]}>{tag.label}</AppText>
        {showNote ? (
          <View style={styles.noteHint}>
            <Ionicons name={s.therapist_note ? 'create-outline' : 'add-circle-outline'} size={14} color={colors.pink} />
            <AppText variant="link" style={styles.noteHintText}>
              {s.therapist_note ? 'Edit note' : 'Add session note'}
            </AppText>
          </View>
        ) : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: { borderRadius: radius.input, padding: spacing.md, gap: 3 },
  pressed: { opacity: 0.85 },
  top: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline' },
  time: { fontFamily: fonts.heading, fontSize: 15 },
  duration: { fontFamily: fonts.bodyBold, fontSize: 11.5 },
  name: { fontFamily: fonts.bodyExtraBold, fontSize: 15, color: colors.text },
  strike: { textDecorationLine: 'line-through', opacity: 0.7 },
  supervised: { gap: 2, marginTop: 4 },
  supBadge: { fontFamily: fonts.bodyExtraBold, fontSize: 11, color: sessionCategoryColors.supervision.fg },
  footer: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 6 },
  tag: { fontFamily: fonts.bodyExtraBold, fontSize: 10.5, textTransform: 'uppercase', letterSpacing: 0.5 },
  noteHint: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  myNote: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 6,
    marginTop: 6,
    padding: spacing.sm,
    borderRadius: radius.chip,
    backgroundColor: monthColors.cardOverlay,
  },
  flex: { flex: 1 },
  noteHintText: { fontSize: 12 },
});
