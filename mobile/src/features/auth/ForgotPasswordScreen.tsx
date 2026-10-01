import { router } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Banner } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { colors, spacing } from '@/theme';

/** Mirrors resources/views/auth/forgot_password.blade.php. */
export function ForgotPasswordScreen() {
  const [email, setEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [fieldError, setFieldError] = useState<string | undefined>();
  const [formError, setFormError] = useState<string | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  async function submit() {
    if (submitting) return;
    setSubmitting(true);
    setFieldError(undefined);
    setFormError(null);
    try {
      const { message } = await api.auth.forgotPassword(email);
      setStatus(message);
    } catch (error) {
      if (isApiError(error) && error.status === 422) setFieldError(error.fieldError('email'));
      else setFormError(errorMessage(error));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={styles.safe} edges={['bottom']}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
          <Card style={styles.card}>
            <View style={styles.heading}>
              <AppText variant="display">Reset your password</AppText>
              <AppText variant="body" color={colors.textSecondary}>
                We&apos;ll email you a link to set a new one.
              </AppText>
            </View>

            {status ? <Banner text={status} tone="success" /> : null}
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
              returnKeyType="send"
              onSubmitEditing={submit}
              error={fieldError}
            />

            <Button title="Send reset link" onPress={submit} loading={submitting} />
            <Button title="Back to sign in" variant="secondary" onPress={() => router.back()} />
          </Card>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.page },
  flex: { flex: 1 },
  scroll: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg },
  card: { gap: spacing.lg, padding: spacing.xl },
  heading: { gap: 2 },
});
