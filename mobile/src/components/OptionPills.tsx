import { Pressable, StyleSheet, View } from 'react-native';

import { colors, fonts, radius, spacing } from '@/theme';

import { AppText } from './AppText';

type Option<T> = { value: T; label: string };

type Props<T> = {
  label?: string;
  options: Option<T>[];
  value: T;
  onChange: (value: T) => void;
  disabled?: boolean;
};

/** Single-choice picker as wrapping pills — the phone-friendly stand-in for a <select>. */
export function OptionPills<T extends string | number | null>({ label, options, value, onChange, disabled }: Props<T>) {
  return (
    <View style={styles.wrap}>
      {label ? (
        <AppText variant="bodyStrong" style={styles.label}>
          {label}
        </AppText>
      ) : null}
      <View style={styles.row} accessibilityRole="radiogroup">
        {options.map((o) => {
          const active = o.value === value;
          return (
            <Pressable
              key={String(o.value)}
              onPress={() => onChange(o.value)}
              disabled={disabled}
              accessibilityRole="radio"
              accessibilityState={{ checked: active, disabled: !!disabled }}
              style={[styles.pill, active && styles.pillActive, disabled && !active && styles.disabled]}>
              <AppText style={[styles.text, active && styles.textActive]}>{o.label}</AppText>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: 6 },
  label: { fontSize: 13 },
  row: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  pill: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: spacing.md,
    paddingVertical: 8,
  },
  pillActive: { backgroundColor: colors.navy, borderColor: colors.navy },
  disabled: { opacity: 0.5 },
  text: { fontFamily: fonts.bodyBold, fontSize: 12.5, color: colors.textSecondary },
  textActive: { color: colors.white },
});
