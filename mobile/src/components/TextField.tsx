import Ionicons from '@expo/vector-icons/Ionicons';
import type { ComponentProps, ReactNode, Ref } from 'react';
import { StyleSheet, TextInput, View, type TextInputProps } from 'react-native';

import { colors, fonts, radius, spacing } from '@/theme';

import { AppText } from './AppText';

type Props = TextInputProps & {
  ref?: Ref<TextInput>;
  label: string;
  error?: string;
  /** Muted helper line under the input. */
  hint?: string;
  /** Pink asterisk after the label, like the web forms. */
  required?: boolean;
  /** Leading icon inside the input. */
  icon?: ComponentProps<typeof Ionicons>['name'];
  /** Rendered inside the input on the right (e.g. show/hide toggle). */
  accessory?: ReactNode;
};

export function TextField({ ref, label, error, hint, required, icon, accessory, style, multiline, ...rest }: Props) {
  return (
    <View style={styles.wrap}>
      <AppText variant="bodyStrong" style={styles.label}>
        {label}
        {required ? <AppText style={styles.required}>*</AppText> : null}
      </AppText>
      <View style={[styles.inputRow, !!error && styles.inputError, multiline && styles.multilineRow]}>
        {icon ? <Ionicons name={icon} size={16} color={colors.textFaint} style={styles.icon} /> : null}
        <TextInput
          ref={ref}
          placeholderTextColor={colors.textFaint}
          accessibilityLabel={label}
          multiline={multiline}
          style={[styles.input, multiline && styles.multiline, style]}
          {...rest}
        />
        {accessory}
      </View>
      {error ? (
        <AppText variant="caption" color={colors.danger} accessibilityLiveRegion="polite">
          {error}
        </AppText>
      ) : hint ? (
        <AppText variant="caption">{hint}</AppText>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: 6 },
  label: { fontSize: 13 },
  required: { color: colors.pink, fontFamily: fonts.bodyExtraBold },
  inputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.input,
    paddingHorizontal: spacing.md,
  },
  multilineRow: { alignItems: 'flex-start' },
  inputError: { borderColor: colors.danger },
  icon: { marginRight: spacing.sm },
  input: {
    flex: 1,
    minHeight: 48,
    fontFamily: fonts.bodySemiBold,
    fontSize: 15,
    color: colors.text,
  },
  multiline: { minHeight: 120, paddingTop: spacing.md, paddingBottom: spacing.md, textAlignVertical: 'top' },
});
