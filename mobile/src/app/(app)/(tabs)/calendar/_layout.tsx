import { Stack } from 'expo-router';

import { colors, fonts } from '@/theme';

/** Opening a detail screen directly (e.g. from the dashboard) still gets the list underneath, so Back works. */
export const unstable_settings = { anchor: 'index' };

export default function CalendarLayout() {
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
      <Stack.Screen name="index" options={{ headerShown: false }} />
      <Stack.Screen name="[id]" options={{ title: 'Session' }} />
    </Stack>
  );
}
