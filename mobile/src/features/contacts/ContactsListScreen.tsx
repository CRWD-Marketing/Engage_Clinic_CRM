import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { ContactItem, ContactStatus } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, contactStatusColors, spacing } from '@/theme';
import { timeAgo } from '@/utils/dates';
import { plural } from '@/utils/format';

import { enquiryLine, slotLabel, STATUS_ORDER } from './contactFormat';

type Filter = ContactStatus | 'all';

/** Mirrors resources/views/contact/index.blade.php (the list and its status filter). */
export function ContactsListScreen() {
  const query = useApiQuery('contacts.list', () => api.contacts.list());
  useRefetchOnFocus(query.reload);
  const [filter, setFilter] = useState<Filter>('all');

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const { contacts, statuses, new_count } = query.data;
  const shown = filter === 'all' ? contacts : contacts.filter((c) => c.status === filter);
  const countOf = (status: ContactStatus) => contacts.filter((c) => c.status === status).length;

  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <AppText variant="caption">
        {shown.length} {plural(shown.length, 'submission')} shown · {new_count} new
      </AppText>

      <OptionPills<Filter>
        label="Filter by status"
        options={[
          { value: 'all', label: `All · ${contacts.length}` },
          ...STATUS_ORDER.map((s) => ({ value: s, label: `${statuses[s]} · ${countOf(s)}` })),
        ]}
        value={filter}
        onChange={setFilter}
      />

      {shown.length === 0 ? (
        <Card style={styles.empty}>
          {filter === 'all' ? (
            <>
              <AppText variant="heading">No submissions yet</AppText>
              <AppText variant="caption" style={styles.center}>
                Enquiries from the website&apos;s Contact form will show up here as soon as families get in touch.
              </AppText>
            </>
          ) : (
            <>
              <AppText variant="heading">No {statuses[filter].toLowerCase()} submissions</AppText>
              <AppText variant="caption" style={styles.center}>
                Try a different filter, or check back once families move into this stage.
              </AppText>
              <Button title="Clear filter" variant="secondary" onPress={() => setFilter('all')} />
            </>
          )}
        </Card>
      ) : (
        shown.map((c) => <ContactCard key={c.id} contact={c} statusLabel={statuses[c.status]} />)
      )}
    </Screen>
  );
}

function ContactCard({ contact, statusLabel }: { contact: ContactItem; statusLabel: string }) {
  const slot = slotLabel(contact);
  const name = contact.name || 'Unknown contact';
  return (
    <Pressable
      onPress={() => router.push({ pathname: '/contacts/[id]', params: { id: String(contact.id) } })}
      accessibilityRole="button"
      accessibilityLabel={`${name}, ${statusLabel}`}
      style={({ pressed }) => pressed && styles.pressed}>
      <Card style={styles.card}>
        <View style={styles.cardHead}>
          <Avatar id={contact.id} name={name} size={36} />
          <View style={styles.flex}>
            <AppText variant="bodyStrong">{name}</AppText>
            <AppText variant="caption">Website enquiry · {timeAgo(contact.created_at)}</AppText>
          </View>
          <Chip label={statusLabel} colors={contactStatusColors[contact.status]} />
        </View>
        <AppText variant="body">{enquiryLine(contact)}</AppText>
        <AppText variant="caption">
          {contact.phone || 'No phone'} · {contact.email || 'No email'}
        </AppText>
        {slot ? (
          <AppText variant="caption" color={colors.navy} style={styles.slot}>
            {slot} · free 30-min consultation
          </AppText>
        ) : null}
      </Card>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  pressed: { opacity: 0.7 },
  card: { gap: spacing.xs },
  cardHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, marginBottom: spacing.xs },
  slot: { marginTop: 2 },
  empty: { alignItems: 'center', gap: spacing.sm, paddingVertical: spacing.xxl },
  center: { textAlign: 'center' },
});
