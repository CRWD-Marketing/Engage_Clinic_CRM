import type { PropsWithChildren, ReactNode } from 'react';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView, type Edge } from 'react-native-safe-area-context';

import { colors, spacing } from '@/theme';

type Props = PropsWithChildren<{
  /** Safe-area edges to pad. Screens under a native header use `[]`. */
  edges?: Edge[];
  scroll?: boolean;
  refreshing?: boolean;
  onRefresh?: () => void;
  /** Rendered above the scroll area (e.g. a sticky week strip). */
  header?: ReactNode;
}>;

/** Page shell: cream background, safe area, optional pull-to-refresh. */
export function Screen({ children, edges = ['top'], scroll = true, refreshing, onRefresh, header }: Props) {
  return (
    <SafeAreaView style={styles.safe} edges={edges}>
      {header}
      {scroll ? (
        <ScrollView
          contentContainerStyle={styles.content}
          keyboardShouldPersistTaps="handled"
          automaticallyAdjustKeyboardInsets
          refreshControl={
            onRefresh ? (
              <RefreshControl refreshing={!!refreshing} onRefresh={onRefresh} tintColor={colors.pink} colors={[colors.pink]} />
            ) : undefined
          }>
          {children}
        </ScrollView>
      ) : (
        <View style={[styles.content, styles.fill]}>{children}</View>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.page },
  content: { padding: spacing.lg, gap: spacing.md, paddingBottom: spacing.xxl },
  fill: { flex: 1 },
});
