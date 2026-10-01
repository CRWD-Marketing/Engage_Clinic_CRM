import { Stack } from 'expo-router';

import { colors, fonts } from '@/theme';

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
