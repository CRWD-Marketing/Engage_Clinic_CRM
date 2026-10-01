import { StyleSheet, View } from 'react-native';

import { radius, type ChipColors } from '@/theme';

import { AppText } from './AppText';

type Props = {
  label: string;
  colors: ChipColors;
  strikethrough?: boolean;
};

/** Small coloured tag (activity type, status, badges). */
export function Chip({ label, colors, strikethrough }: Props) {
  return (
    <View style={[styles.chip, { backgroundColor: colors.bg }]}>
      <AppText
        variant="chip"
        color={colors.fg}
        numberOfLines={1}
        style={strikethrough ? styles.strike : undefined}>
        {label}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  chip: { borderRadius: radius.chip, paddingHorizontal: 9, paddingVertical: 3, alignSelf: 'flex-start' },
  strike: { textDecorationLine: 'line-through' },
});
