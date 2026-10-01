import Ionicons from '@expo/vector-icons/Ionicons';
import { Pressable, StyleSheet, View } from 'react-native';

import type { MySessionPayload } from '@/api/types';
import { AppText } from '@/components/AppText';
import { colors, colorsForActivity, fonts, radius, sessionCategoryColors, spacing } from '@/theme';
import { formatTime } from '@/utils/dates';

import { canAddTherapistNote, sessionTag } from './sessionStatus';

type Props = { session: MySessionPayload; viewerId: number; onPress: () => void };

/** A session card from calendar/my.blade.php, tinted by activity type. */
export function SessionCard({ session: s, viewerId, onPress }: Props) {
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
      {s.room ? (
        <AppText variant="caption" color={colors.textSecondary}>
          {s.room}
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

      <View style={styles.footer}>
        <AppText style={[styles.tag, { color: tag.color }, tag.cancelled && styles.strike]}>{tag.label}</AppText>
        {showNote ? (
          <View style={styles.noteHint}>
            <Ionicons name={s.therapist_note ? 'document-text-outline' : 'add-circle-outline'} size={14} color={colors.pink} />
            <AppText variant="link" style={styles.noteHintText}>
              {s.therapist_note ? 'Note added' : 'Add session note'}
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
  noteHintText: { fontSize: 12 },
});
