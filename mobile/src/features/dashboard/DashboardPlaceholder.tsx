import { StyleSheet, View } from 'react-native';

import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Card } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { colors, spacing } from '@/theme';
import { formatLongDate, greeting, todayYmd } from '@/utils/dates';
import { fullName, roleLabel } from '@/utils/format';

/** Other roles' dashboards are not on mobile yet (milestone 1 targets therapists). */
export function DashboardPlaceholder() {
  const user = useCurrentUser();
  return (
    <Screen>
      <View style={styles.header}>
        <AppText variant="title">
          {greeting()}, {fullName(user)}
        </AppText>
        <AppText variant="caption">{formatLongDate(todayYmd())}</AppText>
      </View>
      <Card style={styles.card}>
        <AppText variant="heading">The {roleLabel(user.role)} dashboard is coming to mobile</AppText>
        <AppText variant="body" color={colors.textSecondary}>
          For now, use the Engage Clinic web app for your dashboard. The tabs below show the modules already
          available on mobile, and your full access is listed under My profile.
        </AppText>
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  header: { gap: 2, marginBottom: spacing.xs },
  card: { gap: spacing.sm },
});
