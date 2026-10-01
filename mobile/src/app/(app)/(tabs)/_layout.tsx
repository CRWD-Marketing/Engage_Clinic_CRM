import Ionicons from '@expo/vector-icons/Ionicons';
import { Tabs } from 'expo-router';
import type { ComponentProps } from 'react';
import type { ColorValue } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { canAccessFeature, levelFor } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { colors, fonts } from '@/theme';

type IconName = ComponentProps<typeof Ionicons>['name'];

function icon(name: IconName) {
  return function TabIcon({ color, size }: { color: ColorValue; size: number }) {
    return <Ionicons name={name} color={color} size={size} />;
  };
}

/**
 * Tabs mirror the web sidebar: a module's tab exists only when
 * canAccessFeature() allows it (same check as the `feature:` middleware).
 * Modules not yet built on mobile are listed on the My profile tab instead.
 */
export default function TabsLayout() {
  const user = useCurrentUser();
  const insets = useSafeAreaInsets();
  const hasCalendar = canAccessFeature(user, 'calendar');
  const hasPatients = canAccessFeature(user, 'patients');

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: colors.pink,
        tabBarInactiveTintColor: colors.textMuted,
        // The tab item is a fixed 28px icon + 5px padding each side, so the
        // default bar height leaves Nunito Sans labels clipped. Give the tab
        // ~58px of content height, plus the home-indicator inset.
        tabBarStyle: {
          backgroundColor: colors.card,
          borderTopColor: colors.border,
          height: 62 + insets.bottom,
          paddingTop: 2,
          paddingBottom: insets.bottom + 2,
        },
        tabBarLabelStyle: { fontFamily: fonts.bodyBold, fontSize: 11, lineHeight: 14 },
        sceneStyle: { backgroundColor: colors.page },
      }}>
      <Tabs.Screen name="index" options={{ title: 'Dashboard', tabBarIcon: icon('grid-outline') }} />
      <Tabs.Screen
        name="calendar"
        options={{
          // Sidebar label is "My calendar" when the calendar level is "own".
          title: levelFor(user, 'calendar') === 'own' ? 'My calendar' : 'Calendar',
          tabBarIcon: icon('calendar-outline'),
          href: hasCalendar ? undefined : null,
        }}
      />
      <Tabs.Screen
        name="patients"
        options={{ title: 'Patients', tabBarIcon: icon('people-outline'), href: hasPatients ? undefined : null }}
      />
      <Tabs.Screen name="profile" options={{ title: 'My profile', tabBarIcon: icon('person-circle-outline') }} />
    </Tabs>
  );
}
