import { Text, type TextProps } from 'react-native';

import { textStyles, type TextVariant } from '@/theme';

type Props = TextProps & {
  variant?: TextVariant;
  color?: string;
};

/** Text with the clinic's type presets. Always use this instead of raw <Text>. */
export function AppText({ variant = 'body', color, style, ...rest }: Props) {
  return <Text {...rest} style={[textStyles[variant], color ? { color } : null, style]} />;
}
