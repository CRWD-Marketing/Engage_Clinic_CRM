import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import type { ComponentProps } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { NotificationItem } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, spacing } from '@/theme';

import { notificationTarget } from './notificationTarget';

type IconName = ComponentProps<typeof Ionicons>['name'];

/** The web bell uses Font Awesome names; these are the Ionicons equivalents. */
const ICONS: Record<string, IconName> = {
  'fa-filter': 'funnel-outline',
  'fa-envelope': 'mail-outline',
  'fa-whatsapp': 'logo-whatsapp',
  'fa-calendar-check': 'calendar-outline',
  'fa-user-plus': 'person-add-outline',
};

/** The topbar bell dropdown of the web app, as a screen. */
export function NotificationsScreen() {
  const query = useApiQuery('notifications.list', () => api.notifications.list());
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const { items, count } = query.data;

  async function open(item: NotificationItem) {
    // Mark it read first so the badge is right when the user comes back.
    if (!item.read) await api.notifications.read(item.id).catch(() => undefined);
    const target = notificationTarget(item);
    if (target) router.push(target, { withAnchor: true });
    else query.reload();
  }

  async function readAll() {
    await api.notifications.readAll().catch(() => undefined);
    query.reload();
  }

  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <AppText variant="caption">{count > 0 ? `${count} unread` : 'You are all caught up.'}</AppText>
      {count > 0 ? <Button title="Mark all as read" variant="secondary" onPress={readAll} /> : null}

      <Card padded={false}>
        {items.length === 0 ? (
          <EmptyRow text="No notifications." />
        ) : (
          items.map((item, i) => (
            <View key={item.id}>
              {i > 0 ? <Divider /> : null}
              <Pressable
                onPress={() => open(item)}
                accessibilityRole="button"
                accessibilityLabel={`${item.title}${item.read ? '' : ', unread'}`}
                style={({ pressed }) => [styles.row, pressed && styles.pressed]}>
                <Ionicons name={ICONS[item.icon] ?? 'notifications-outline'} size={20} color={colors.navy} />
                <View style={styles.flex}>
                  <AppText variant={item.read ? 'body' : 'bodyStrong'}>{item.title}</AppText>
                  {item.subtitle ? (
                    <AppText variant="caption" numberOfLines={1}>
                      {item.subtitle}
                    </AppText>
                  ) : null}
                  <AppText variant="caption">{item.created_at}</AppText>
                </View>
                {item.read ? null : <View style={styles.dot} />}
              </Pressable>
            </View>
          ))
        )}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  dot: { width: 9, height: 9, borderRadius: 5, backgroundColor: colors.pink },
});
