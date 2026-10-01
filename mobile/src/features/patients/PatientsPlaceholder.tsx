import { StyleSheet } from 'react-native';

import { levelFor } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Card } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { colors, spacing } from '@/theme';

/** Milestone 2 replaces this with the patient list and detail. */
export function PatientsPlaceholder() {
  const user = useCurrentUser();
  const own = levelFor(user, 'patients') === 'own';
  return (
    <Screen>
      <AppText variant="title">{own ? 'My patients' : 'Patients'}</AppText>
      <Card style={styles.card}>
        <AppText variant="heading">Coming next</AppText>
        <AppText variant="body" color={colors.textSecondary}>
          Patient profiles, goals, session notes and authorizations arrive in the next update. Until then, use the
          Engage Clinic web app.
        </AppText>
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { gap: spacing.sm },
});
