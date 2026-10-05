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
      <Stack.Screen name="billing/new" options={{ title: 'New invoice' }} />
      <Stack.Screen name="billing/statement/[id]" options={{ title: 'Statement' }} />
      <Stack.Screen name="users/index" options={{ title: 'User Management' }} />
      <Stack.Screen name="users/[id]" options={{ title: 'User' }} />
      <Stack.Screen name="users/form" options={{ title: 'Add user' }} />
      <Stack.Screen name="roles/index" options={{ title: 'Roles & access' }} />
      <Stack.Screen name="roles/user/[id]" options={{ title: 'Access' }} />
      <Stack.Screen name="roles/user-form" options={{ title: 'Add user' }} />
      <Stack.Screen name="roles/template" options={{ title: 'Role template' }} />
      <Stack.Screen name="careers/index" options={{ title: 'Job applications' }} />
      <Stack.Screen name="careers/[id]" options={{ title: 'Application' }} />
      <Stack.Screen name="careers/postings" options={{ title: 'Job postings' }} />
      <Stack.Screen name="careers/posting-form" options={{ title: 'Job posting' }} />
      <Stack.Screen name="packages/index" options={{ title: 'Packages' }} />
      <Stack.Screen name="packages/form" options={{ title: 'Package' }} />
      <Stack.Screen name="contacts/[id]" options={{ title: 'Submission' }} />
    </Stack>
  );
}
