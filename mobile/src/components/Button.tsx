import { ActivityIndicator, Pressable, StyleSheet } from 'react-native';

import { colors, fonts, radius, spacing } from '@/theme';

import { AppText } from './AppText';

type Props = {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'secondary';
  loading?: boolean;
  disabled?: boolean;
};

export function Button({ title, onPress, variant = 'primary', loading, disabled }: Props) {
  const primary = variant === 'primary';
  const inactive = disabled || loading;
  return (
    <Pressable
      onPress={onPress}
      disabled={inactive}
      accessibilityRole="button"
      accessibilityState={{ disabled: !!inactive, busy: !!loading }}
      style={({ pressed }) => [
        styles.base,
        primary ? styles.primary : styles.secondary,
        pressed && (primary ? styles.primaryPressed : styles.secondaryPressed),
        inactive && styles.inactive,
      ]}>
      {loading ? (
        <ActivityIndicator color={primary ? colors.white : colors.pink} />
      ) : (
        <AppText style={[styles.label, { color: primary ? colors.white : colors.textSecondary }]}>{title}</AppText>
      )}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: 48,
    borderRadius: radius.input,
    paddingHorizontal: spacing.lg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  primary: { backgroundColor: colors.pink },
  primaryPressed: { backgroundColor: colors.pinkPressed },
  secondary: { backgroundColor: colors.card, borderWidth: 1, borderColor: colors.border },
  secondaryPressed: { borderColor: colors.pink },
  inactive: { opacity: 0.6 },
  label: { fontFamily: fonts.bodyExtraBold, fontSize: 15 },
});
