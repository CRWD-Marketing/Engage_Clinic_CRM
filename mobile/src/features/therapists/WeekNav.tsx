import Ionicons from '@expo/vector-icons/Ionicons';
import { Pressable, StyleSheet, View } from 'react-native';

import { AppText } from '@/components/AppText';
import { colors, radius, spacing } from '@/theme';
import { addDays, formatDayMonthShort, formatDayMonthYearShort, type Ymd } from '@/utils/dates';

type Props = {
  weekStart: Ymd;
  isCurrentWeek: boolean;
  /** Called with any date in the week to show; undefined means the current week. */
  onWeek: (week: Ymd | undefined) => void;
};

/** "Week of 28 Sep–4 Oct 2026" with previous / next / Today, like the web's week navigator. */
export function WeekNav({ weekStart, isCurrentWeek, onWeek }: Props) {
  return (
    <View style={styles.row}>
      <AppText variant="bodyStrong" style={styles.label}>
        Week of {formatDayMonthShort(weekStart)}–{formatDayMonthYearShort(addDays(weekStart, 6))}
      </AppText>
      {isCurrentWeek ? null : (
        <Pressable onPress={() => onWeek(undefined)} accessibilityRole="button" hitSlop={6}>
          <AppText variant="link">Today</AppText>
        </Pressable>
      )}
      <Arrow icon="chevron-back" label="Previous week" onPress={() => onWeek(addDays(weekStart, -7))} />
      <Arrow icon="chevron-forward" label="Next week" onPress={() => onWeek(addDays(weekStart, 7))} />
    </View>
  );
}

function Arrow({ icon, label, onPress }: { icon: 'chevron-back' | 'chevron-forward'; label: string; onPress: () => void }) {
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel={label}
      hitSlop={6}
      style={({ pressed }) => [styles.arrow, pressed && styles.pressed]}>
      <Ionicons name={icon} size={18} color={colors.navy} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  label: { flex: 1 },
  arrow: {
    width: 36,
    height: 36,
    borderRadius: radius.input,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pressed: { backgroundColor: colors.pageAlt },
});
