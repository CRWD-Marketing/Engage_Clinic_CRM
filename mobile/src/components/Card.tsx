import type { PropsWithChildren } from 'react';
import { Pressable, StyleSheet, View, type StyleProp, type ViewStyle } from 'react-native';

import { colors, radius, spacing } from '@/theme';

import { AppText } from './AppText';

type CardProps = PropsWithChildren<{ style?: StyleProp<ViewStyle>; padded?: boolean }>;

/** White card with the web app's 1px #EBE4DA border and 14px radius. */
export function Card({ children, style, padded = true }: CardProps) {
  return <View style={[styles.card, padded && styles.padded, style]}>{children}</View>;
}

type HeaderProps = {
  title: string;
  actionLabel?: string;
  onAction?: () => void;
};

export function CardHeader({ title, actionLabel, onAction }: HeaderProps) {
  return (
    <View style={styles.header}>
      <AppText variant="heading" style={styles.title}>
        {title}
      </AppText>
      {actionLabel && onAction ? (
        <Pressable onPress={onAction} hitSlop={8} accessibilityRole="link">
          <AppText variant="link">{actionLabel}</AppText>
        </Pressable>
      ) : null}
    </View>
  );
}

/** Hairline between rows inside a card. */
export function Divider() {
  return <View style={styles.divider} />;
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: radius.card,
    overflow: 'hidden',
  },
  padded: { padding: spacing.lg },
  header: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.sm, marginBottom: spacing.sm },
  title: { flex: 1 },
  divider: { height: 1, backgroundColor: colors.divider },
});
