import { StyleSheet } from 'react-native';

import { colors, fonts, spacing } from '@/theme';

import { AppText } from './AppText';
import { Card } from './Card';

type Props = {
  label: string;
  value: string;
  caption: string;
  captionColor?: string;
};

/** Dashboard stat card: uppercase label, big number, coloured caption. */
export function StatTile({ label, value, caption, captionColor = colors.textWarm }: Props) {
  return (
    <Card style={styles.tile}>
      <AppText variant="label" numberOfLines={1}>
        {label}
      </AppText>
      <AppText variant="stat">{value}</AppText>
      <AppText variant="caption" color={captionColor} numberOfLines={1} style={styles.caption}>
        {caption}
      </AppText>
    </Card>
  );
}

const styles = StyleSheet.create({
  tile: { flexBasis: '47%', flexGrow: 1, paddingVertical: spacing.md, paddingHorizontal: spacing.md },
  caption: { fontFamily: fonts.bodyBold },
});
