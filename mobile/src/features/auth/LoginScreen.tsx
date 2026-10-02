import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import { Link } from 'expo-router';
import { useRef, useState } from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  View,
  type TextInput,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { errorMessage, isApiError } from '@/api/errors';
import { useSession } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Banner } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { colors, fonts, spacing } from '@/theme';

type FieldErrors = { email?: string; password?: string };

/** Mirrors resources/views/auth/login.blade.php. */
export function LoginScreen() {
  const { signIn } = useSession();
  const passwordRef = useRef<TextInput>(null);

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [remember, setRemember] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
  const [formError, setFormError] = useState<string | null>(null);

  async function submit() {
    if (submitting) return;
    setSubmitting(true);
    setFieldErrors({});
    setFormError(null);
    try {
      await signIn({ email, password, remember });
      // The protected-route guard switches to the signed-in stack.
    } catch (error) {
      if (isApiError(error) && error.status === 422) {
        setFieldErrors({ email: error.fieldError('email'), password: error.fieldError('password') });
      } else {
        // 429 lockout message, or a network failure.
        setFormError(errorMessage(error));
      }
      setSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={styles.safe}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <View style={styles.brand}>
            <Image
              source={require('@/assets/images/engage-icon.png')}
              style={styles.logo}
              contentFit="contain"
              accessibilityLabel="Engage Clinic"
            />
            <AppText variant="title">Engage Clinic</AppText>
          </View>

          <Card style={styles.card}>
            <View style={styles.heading}>
              <AppText variant="display">Sign in</AppText>
              <AppText variant="body" color={colors.textSecondary}>
                Enter your credentials to reach the dashboard.
              </AppText>
            </View>

            {formError ? <Banner text={formError} /> : null}

            <TextField
              label="Work email"
              value={email}
              onChangeText={setEmail}
              placeholder="you@engageclinic.ae"
              autoCapitalize="none"
              autoCorrect={false}
              autoComplete="email"
              keyboardType="email-address"
              textContentType="username"
              returnKeyType="next"
              onSubmitEditing={() => passwordRef.current?.focus()}
              error={fieldErrors.email}
            />

            <TextField
              ref={passwordRef}
              label="Password"
              value={password}
              onChangeText={setPassword}
              placeholder="Enter your password"
              secureTextEntry={!showPassword}
              autoCapitalize="none"
              autoComplete="current-password"
              textContentType="password"
              returnKeyType="go"
              onSubmitEditing={submit}
              error={fieldErrors.password}
              accessory={
                <Pressable
                  onPress={() => setShowPassword((v) => !v)}
                  hitSlop={10}
                  accessibilityRole="button"
                  accessibilityLabel={showPassword ? 'Hide password' : 'Show password'}>
                  <Ionicons name={showPassword ? 'eye-off-outline' : 'eye-outline'} size={20} color={colors.textMuted} />
                </Pressable>
              }
            />

            <View style={styles.row}>
              <Pressable
                style={styles.remember}
                onPress={() => setRemember((v) => !v)}
                accessibilityRole="checkbox"
                accessibilityState={{ checked: remember }}
                hitSlop={6}>
                <Ionicons
                  name={remember ? 'checkbox' : 'square-outline'}
                  size={22}
                  color={remember ? colors.pink : colors.textMuted}
                />
                <AppText variant="body">Remember me</AppText>
              </Pressable>
              <Link href="/forgot-password" asChild>
                <Pressable hitSlop={8} accessibilityRole="link">
                  <AppText variant="link">Forgot password?</AppText>
                </Pressable>
              </Link>
            </View>

            <Button title="Sign in" onPress={submit} loading={submitting} />
          </Card>

          <View style={styles.notice}>
            <Ionicons name="alert-circle-outline" size={18} color={colors.textMuted} />
            <AppText variant="caption" style={styles.flex}>
              <AppText variant="caption" style={styles.bold}>
                Authorized access only.
              </AppText>{' '}
              This portal is restricted to Engage Clinic staff. All activity is logged.
            </AppText>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.page },
  flex: { flex: 1 },
  scroll: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg, gap: spacing.lg },
  brand: { alignItems: 'center', gap: spacing.sm },
  logo: { width: 72, height: 72, borderRadius: 16 },
  card: { gap: spacing.lg, padding: spacing.xl },
  heading: { gap: 2 },
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  remember: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  notice: { flexDirection: 'row', gap: spacing.sm, paddingHorizontal: spacing.xs },
  bold: { fontFamily: fonts.bodyExtraBold },
});
