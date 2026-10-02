import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import type { ComponentProps } from 'react';
import { StyleSheet, View } from 'react-native';

import type { InboxContact } from '@/api/types';
import { AppText } from '@/components/AppText';
import { colors, fonts, inboxColors } from '@/theme';

import { inboxAvatarColor, inboxInitials } from './inboxFormat';

const CHANNEL_ICON: Record<InboxContact['channel'], ComponentProps<typeof Ionicons>['name']> = {
  whatsapp: 'logo-whatsapp',
  instagram: 'logo-instagram',
  facebook: 'logo-facebook',
  voice: 'call',
};

/** Contact avatar (photo or initials) with the channel badge in the corner. */
export function InboxAvatar({ contact, size = 42 }: { contact: InboxContact; size?: number }) {
  return (
    <View style={{ width: size, height: size }}>
      {contact.avatar_url ? (
        <Image source={{ uri: contact.avatar_url }} style={{ width: size, height: size, borderRadius: size / 2 }} />
      ) : (
        <View
          style={[
            styles.circle,
            { width: size, height: size, borderRadius: size / 2, backgroundColor: inboxAvatarColor(contact.wa_id) },
          ]}>
          <AppText style={[styles.initials, { fontSize: size * 0.34, lineHeight: size * 0.45 }]}>
            {inboxInitials(contact)}
          </AppText>
        </View>
      )}
      <View style={[styles.badge, { backgroundColor: inboxColors.channel[contact.channel] }]}>
        <Ionicons name={CHANNEL_ICON[contact.channel]} size={9} color={colors.white} />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  circle: { alignItems: 'center', justifyContent: 'center' },
  initials: { fontFamily: fonts.heading, color: colors.white },
  badge: {
    position: 'absolute',
    right: -2,
    bottom: -2,
    width: 16,
    height: 16,
    borderRadius: 8,
    borderWidth: 2,
    borderColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
