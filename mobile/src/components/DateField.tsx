import Ionicons from '@expo/vector-icons/Ionicons';
import { useState, type ComponentProps } from 'react';
import { Modal, Pressable, StyleSheet, View } from 'react-native';

import { colors, fonts, radius, spacing } from '@/theme';
import {
  addDays,
  addMonths,
  dayOfMonth,
  daysInMonth,
  formatDayMonthYear,
  formatMonthLong,
  monthStartOf,
  todayYmd,
  weekdayIndex,
  type Ymd,
} from '@/utils/dates';

import { AppText } from './AppText';

const WEEKDAYS = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
const YMD = /^\d{4}-\d{2}-\d{2}$/;

type Props = {
  label: string;
  /** "YYYY-MM-DD", or '' when no date is set. */
  value: string;
  onChange: (value: string) => void;
  error?: string;
  hint?: string;
  required?: boolean;
  disabled?: boolean;
  placeholder?: string;
};

/** A date input: tap to open a month calendar (same on iOS, Android and web). */
export function DateField({ label, value, onChange, error, hint, required, disabled, placeholder = 'Select a date' }: Props) {
  const [open, setOpen] = useState(false);
  const selected = YMD.test(value) ? value : null;

  return (
    <View style={styles.wrap}>
      <AppText variant="bodyStrong" style={styles.label}>
        {label}
        {required ? <AppText style={styles.required}>*</AppText> : null}
      </AppText>
      <View style={[styles.inputRow, !!error && styles.inputError, disabled && styles.disabled]}>
        <Pressable
          accessibilityRole="button"
          accessibilityLabel={`${label}: ${selected ? formatDayMonthYear(selected) : 'not set'}`}
          accessibilityState={{ disabled }}
          disabled={disabled}
          onPress={() => setOpen(true)}
          style={styles.trigger}
        >
          <Ionicons name="calendar-outline" size={16} color={colors.textFaint} />
          <AppText style={[styles.value, !selected && styles.placeholder]}>
            {selected ? formatDayMonthYear(selected) : placeholder}
          </AppText>
        </Pressable>
        {selected && !disabled ? (
          <Pressable accessibilityRole="button" accessibilityLabel={`Clear ${label}`} hitSlop={10} onPress={() => onChange('')}>
            <Ionicons name="close-circle" size={18} color={colors.textFaint} />
          </Pressable>
        ) : null}
      </View>
      {error ? (
        <AppText variant="caption" color={colors.danger} accessibilityLiveRegion="polite">
          {error}
        </AppText>
      ) : hint ? (
        <AppText variant="caption">{hint}</AppText>
      ) : null}

      {open ? (
        <CalendarSheet
          title={label}
          selected={selected}
          onClose={() => setOpen(false)}
          onPick={(date) => {
            onChange(date);
            setOpen(false);
          }}
        />
      ) : null}
    </View>
  );
}

function CalendarSheet({
  title,
  selected,
  onPick,
  onClose,
}: {
  title: string;
  selected: Ymd | null;
  onPick: (date: Ymd) => void;
  onClose: () => void;
}) {
  const today = todayYmd();
  const [month, setMonth] = useState(() => monthStartOf(selected ?? today));

  const cells: (Ymd | null)[] = [
    ...Array.from({ length: weekdayIndex(month) }, () => null),
    ...Array.from({ length: daysInMonth(month) }, (_, i) => addDays(month, i)),
  ];
  while (cells.length % 7 !== 0) cells.push(null);

  return (
    <Modal transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.scrim}>
        <Pressable style={StyleSheet.absoluteFill} onPress={onClose} accessibilityLabel="Close calendar" />
        <View style={styles.sheet}>
          <AppText variant="caption">{title}</AppText>
          <View style={styles.header}>
            <NavButton icon="play-back" label="Previous year" onPress={() => setMonth(addMonths(month, -12))} />
            <NavButton icon="chevron-back" label="Previous month" onPress={() => setMonth(addMonths(month, -1))} />
            <AppText variant="heading" style={styles.month}>
              {formatMonthLong(month)}
            </AppText>
            <NavButton icon="chevron-forward" label="Next month" onPress={() => setMonth(addMonths(month, 1))} />
            <NavButton icon="play-forward" label="Next year" onPress={() => setMonth(addMonths(month, 12))} />
          </View>

          <View style={styles.row}>
            {WEEKDAYS.map((d, i) => (
              <AppText key={i} variant="caption" style={styles.weekday}>
                {d}
              </AppText>
            ))}
          </View>
          {Array.from({ length: cells.length / 7 }, (_, r) => (
            <View key={r} style={styles.row}>
              {cells.slice(r * 7, r * 7 + 7).map((date, i) =>
                date ? (
                  <Pressable
                    key={date}
                    accessibilityRole="button"
                    accessibilityLabel={formatDayMonthYear(date)}
                    accessibilityState={{ selected: date === selected }}
                    onPress={() => onPick(date)}
                    style={styles.cell}
                  >
                    <View style={[styles.day, date === today && styles.dayToday, date === selected && styles.daySelected]}>
                      <AppText style={[styles.dayText, date === selected && styles.dayTextSelected]}>{dayOfMonth(date)}</AppText>
                    </View>
                  </Pressable>
                ) : (
                  <View key={`blank-${i}`} style={styles.cell} />
                ),
              )}
            </View>
          ))}

          <View style={styles.footer}>
            <Pressable accessibilityRole="button" onPress={() => onPick(today)} hitSlop={8}>
              <AppText variant="bodyStrong" color={colors.pink}>
                Today
              </AppText>
            </Pressable>
            <Pressable accessibilityRole="button" onPress={onClose} hitSlop={8}>
              <AppText variant="bodyStrong" color={colors.textSecondary}>
                Cancel
              </AppText>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

function NavButton({ icon, label, onPress }: { icon: ComponentProps<typeof Ionicons>['name']; label: string; onPress: () => void }) {
  return (
    <Pressable accessibilityRole="button" accessibilityLabel={label} onPress={onPress} hitSlop={6} style={styles.nav}>
      <Ionicons name={icon} size={16} color={colors.navy} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: 6 },
  label: { fontSize: 13 },
  required: { color: colors.pink, fontFamily: fonts.bodyExtraBold },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.input,
    paddingHorizontal: spacing.md,
  },
  trigger: { flex: 1, flexDirection: 'row', alignItems: 'center', gap: spacing.sm, minHeight: 48 },
  inputError: { borderColor: colors.danger },
  disabled: { opacity: 0.6 },
  value: { flex: 1, fontFamily: fonts.bodySemiBold, fontSize: 15, color: colors.text },
  placeholder: { color: colors.textFaint },

  scrim: { flex: 1, backgroundColor: colors.overlay, alignItems: 'center', justifyContent: 'center', padding: spacing.xl },
  sheet: {
    width: '100%',
    maxWidth: 360,
    backgroundColor: colors.card,
    borderRadius: radius.card,
    padding: spacing.lg,
    gap: spacing.xs,
  },
  header: { flexDirection: 'row', alignItems: 'center', marginBottom: spacing.sm },
  month: { flex: 1, textAlign: 'center' },
  nav: { width: 36, height: 36, alignItems: 'center', justifyContent: 'center', borderRadius: radius.pill },
  row: { flexDirection: 'row' },
  weekday: { flex: 1, textAlign: 'center', paddingBottom: spacing.xs },
  cell: { flex: 1, alignItems: 'center', paddingVertical: 2 },
  day: { width: 38, height: 38, borderRadius: radius.pill, alignItems: 'center', justifyContent: 'center' },
  dayToday: { borderWidth: 1, borderColor: colors.pink },
  daySelected: { backgroundColor: colors.navy, borderColor: colors.navy },
  dayText: { fontFamily: fonts.bodySemiBold, fontSize: 14, color: colors.text },
  dayTextSelected: { color: colors.white },
  footer: { flexDirection: 'row', justifyContent: 'space-between', marginTop: spacing.md, paddingHorizontal: spacing.xs },
});
