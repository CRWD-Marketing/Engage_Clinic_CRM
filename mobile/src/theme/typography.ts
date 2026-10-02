import {
  Baloo2_500Medium,
  Baloo2_600SemiBold,
  Baloo2_700Bold,
} from '@expo-google-fonts/baloo-2';
import {
  NunitoSans_400Regular,
  NunitoSans_600SemiBold,
  NunitoSans_700Bold,
  NunitoSans_800ExtraBold,
} from '@expo-google-fonts/nunito-sans';
import type { TextStyle } from 'react-native';

import { colors } from './colors';

/** Font files loaded once in the root layout via useFonts(). */
export const fontAssets = {
  Baloo2_500Medium,
  Baloo2_600SemiBold,
  Baloo2_700Bold,
  NunitoSans_400Regular,
  NunitoSans_600SemiBold,
  NunitoSans_700Bold,
  NunitoSans_800ExtraBold,
};

/**
 * Custom fonts carry their weight in the family name; never combine these
 * with `fontWeight` (Android would fall back to the system font).
 */
export const fonts = {
  heading: 'Baloo2_600SemiBold',
  headingMedium: 'Baloo2_500Medium',
  headingBold: 'Baloo2_700Bold',
  body: 'NunitoSans_400Regular',
  bodySemiBold: 'NunitoSans_600SemiBold',
  bodyBold: 'NunitoSans_700Bold',
  bodyExtraBold: 'NunitoSans_800ExtraBold',
} as const;

/** Text presets sized from the web app's mobile breakpoints. */
export const textStyles = {
  display: { fontFamily: fonts.heading, fontSize: 24, lineHeight: 30, color: colors.navy },
  title: { fontFamily: fonts.heading, fontSize: 20, lineHeight: 26, color: colors.navy },
  heading: { fontFamily: fonts.heading, fontSize: 16, lineHeight: 22, color: colors.navy },
  stat: { fontFamily: fonts.heading, fontSize: 24, lineHeight: 30, color: colors.navy },
  body: { fontFamily: fonts.bodySemiBold, fontSize: 14, lineHeight: 20, color: colors.text },
  bodyStrong: { fontFamily: fonts.bodyExtraBold, fontSize: 14, lineHeight: 20, color: colors.text },
  caption: { fontFamily: fonts.bodySemiBold, fontSize: 12, lineHeight: 16, color: colors.textMuted },
  label: {
    fontFamily: fonts.bodyBold,
    fontSize: 10.5,
    lineHeight: 14,
    color: colors.textMuted,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  link: { fontFamily: fonts.bodyExtraBold, fontSize: 13, lineHeight: 18, color: colors.pink },
  chip: { fontFamily: fonts.bodyExtraBold, fontSize: 11, lineHeight: 14 },
} satisfies Record<string, TextStyle>;

export type TextVariant = keyof typeof textStyles;
