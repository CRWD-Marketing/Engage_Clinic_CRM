import { Stack } from 'expo-router';

import { colors, fonts } from '@/theme';

/**
 * Signed-in stack: the tab bar lives in (tabs); screens opened from
 * anywhere (e.g. the notes review queue) are pushed on top with a back button.
 */
export default function AppStackLayout() {
  return (
    <Stack
      screenOptions={{
        contentStyle: { backgroundColor: colors.page },
        headerStyle: { backgroundColor: colors.page },
        headerTintColor: colors.navy,
        headerTitleStyle: { fontFamily: fonts.heading },
        headerShadowVisible: false,
        headerBackButtonDisplayMode: 'minimal',
      }}>
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      <Stack.Screen name="notes-review" options={{ title: 'Session notes review' }} />
      <Stack.Screen name="therapists/index" options={{ title: 'Therapists & schedules' }} />
      <Stack.Screen name="therapists/[id]" options={{ title: 'Schedule' }} />
      <Stack.Screen name="notifications" options={{ title: 'Notifications' }} />
      <Stack.Screen name="leave" options={{ title: 'Staff leave' }} />
      <Stack.Screen name="session-form" options={{ title: 'Book session' }} />
      <Stack.Screen name="contacts/index" options={{ title: 'Contacts' }} />
      <Stack.Screen name="reports" options={{ title: 'Reports & analytics' }} />
      <Stack.Screen name="billing/index" options={{ title: 'Billing & insurance' }} />
      <Stack.Screen name="billing/[id]" options={{ title: 'Invoice' }} />
      <Stack.Screen name="contacts/[id]" options={{ title: 'Submission' }} />
    </Stack>
  );
}
