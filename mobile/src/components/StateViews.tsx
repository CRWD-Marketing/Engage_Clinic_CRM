import { ActivityIndicator, StyleSheet, View } from 'react-native';

import { errorMessage } from '@/api/errors';
import { colors, fonts, spacing } from '@/theme';

import { AppText } from './AppText';
import { Button } from './Button';

export function LoadingState() {
  return (
    <View style={styles.center} accessibilityLabel="Loading">
      <ActivityIndicator color={colors.pink} size="large" />
    </View>
  );
}

export function ErrorState({ error, onRetry }: { error: unknown; onRetry?: () => void }) {
  return (
    <View style={styles.center}>
      <AppText variant="heading" style={styles.text}>
        Couldn&apos;t load this
      </AppText>
      <AppText variant="caption" style={styles.text}>
        {errorMessage(error)}
      </AppText>
      {onRetry ? <Button title="Try again" variant="secondary" onPress={onRetry} /> : null}
    </View>
  );
}

/** Muted centred line used inside cards ("No sessions scheduled today."). */
export function EmptyRow({ text }: { text: string }) {
  return (
    <AppText variant="caption" style={styles.empty}>
      {text}
    </AppText>
  );
}

/** Inline alert box (errors on forms, info notices). */
export function Banner({ text, tone = 'danger' }: { text: string; tone?: 'danger' | 'success' | 'info' }) {
  const palette = {
    danger: { bg: colors.dangerBg, fg: colors.danger },
    success: { bg: colors.successBg, fg: colors.success },
    info: { bg: colors.infoBg, fg: colors.info },
  }[tone];
  return (
    <View style={[styles.banner, { backgroundColor: palette.bg }]} accessibilityLiveRegion="polite">
      <AppText variant="body" color={palette.fg}>
        {text}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.md, padding: spacing.xxl, minHeight: 240 },
  text: { textAlign: 'center' },
  empty: { textAlign: 'center', paddingVertical: spacing.xl, fontFamily: fonts.bodyBold },
  banner: { borderRadius: 10, padding: spacing.md },
});
