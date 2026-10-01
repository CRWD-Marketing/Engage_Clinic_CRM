import { StyleSheet } from 'react-native';

import { AppText } from '@/components/AppText';
import { Card } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { colors, spacing } from '@/theme';

/** Full-access roles get the clinic-wide calendar (day/week/month grid) on the web. */
export function CalendarPlaceholder() {
  return (
    <Screen>
      <AppText variant="title">Calendar</AppText>
      <Card style={styles.card}>
        <AppText variant="heading">The clinic calendar is coming to mobile</AppText>
        <AppText variant="body" color={colors.textSecondary}>
          Booking, rescheduling, staff leave and supervision are still on the Engage Clinic web app. Mobile
          currently covers the therapist&apos;s own schedule.
        </AppText>
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { gap: spacing.sm },
});
