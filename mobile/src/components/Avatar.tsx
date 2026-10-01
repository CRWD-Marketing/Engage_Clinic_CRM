import { StyleSheet, View } from 'react-native';

import { colors, fonts } from '@/theme';
import { avatarColor, initials } from '@/utils/format';

import { AppText } from './AppText';

type Props = { id: number | string; name: string; size?: number };

/** Initials avatar coloured like the web app (crc32(id) % 7). */
export function Avatar({ id, name, size = 40 }: Props) {
  return (
    <View
      style={[styles.circle, { width: size, height: size, borderRadius: size / 2, backgroundColor: avatarColor(id) }]}
      accessibilityElementsHidden
      importantForAccessibility="no">
      <AppText style={[styles.text, { fontSize: size * 0.38, lineHeight: size * 0.5 }]}>{initials(name)}</AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  circle: { alignItems: 'center', justifyContent: 'center' },
  text: { fontFamily: fonts.bodyExtraBold, color: colors.white },
});
