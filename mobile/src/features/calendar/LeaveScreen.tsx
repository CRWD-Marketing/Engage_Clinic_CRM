import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BookingOptions, CalendarFeed } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, spacing } from '@/theme';
import { addDays, formatDayShort, todayYmd } from '@/utils/dates';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** "Mark leave" from the web calendar, plus the leave already booked for the next two months. */
export function LeaveScreen() {
  const today = todayYmd();
  const options = useApiQuery('calendar.bookingOptions', () => api.calendar.bookingOptions());
  const feed = useApiQuery('calendar.feed:leave', () => api.calendar.feed(today, addDays(today, 60)));

  if (!options.data || !feed.data) {
    const error = options.error ?? feed.error;
    return (
      <Screen scroll={false} edges={[]}>
        {error ? <ErrorState error={error} onRetry={() => { options.refresh(); feed.refresh(); }} /> : <LoadingState />}
      </Screen>
    );
  }
  return <Form options={options.data} feed={feed.data} onChanged={feed.reload} />;
}

function Form({ options, feed, onChanged }: { options: BookingOptions; feed: CalendarFeed; onChanged: () => void }) {
  const [staff, setStaff] = useState<number | null>(null);
  const [date, setDate] = useState('');
  const [type, setType] = useState(options.leave_types[0]);
  const [reason, setReason] = useState('');
  const [busy, setBusy] = useState<string | null>(null);
  const [flash, setFlash] = useState<Flash>(null);
  const [confirming, setConfirming] = useState<number | null>(null);

  // How many booked sessions the leave would cancel, shown before committing.
  const impact = useApiQuery(`calendar.leaveImpact:${staff}:${date}`, () =>
    staff && date ? api.calendar.leaveImpact(staff, date) : Promise.resolve(null),
  );
  const toCancel = impact.data?.count ?? 0;

  async function run(key: string, action: () => Promise<string>) {
    setBusy(key);
    setFlash(null);
    try {
      setFlash({ text: await action(), tone: 'success' });
      setConfirming(null);
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(null);
    }
  }

  function save() {
    if (!staff) return setFlash({ text: 'Pick a staff member.', tone: 'danger' });
    if (!date) return setFlash({ text: 'Pick the leave date.', tone: 'danger' });
    if (!reason.trim()) return setFlash({ text: 'Add a reason.', tone: 'danger' });
    run('save', async () => {
      const res = await api.calendar.markLeave({ user_id: staff, leave_date: date, leave_type: type, reason });
      setReason('');
      return res.message;
    });
  }

  return (
    <Screen edges={[]}>
      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <Card style={styles.card}>
        <OptionPills<number | null>
          label="Staff member"
          options={options.therapists.map((t) => ({ value: t.id, label: t.name }))}
          value={staff}
          onChange={setStaff}
        />
        <DateField label="Date" value={date} onChange={setDate} required />
        <OptionPills label="Leave type" options={options.leave_types.map((t) => ({ value: t, label: t }))} value={type} onChange={setType} />
        <TextField label="Reason" required value={reason} onChangeText={setReason} multiline />
        {staff && date && toCancel > 0 ? (
          <Banner text={`${toCancel} scheduled session${toCancel === 1 ? '' : 's'} that day will be cancelled. Arrange cover afterwards.`} tone="info" />
        ) : null}
        <Button title="Mark leave" onPress={save} loading={busy === 'save'} disabled={busy !== null} />
      </Card>

      <Card>
        <AppText variant="heading">Upcoming leave</AppText>
        <AppText variant="caption">Next 60 days</AppText>
        {feed.leaves.length === 0 ? (
          <AppText variant="caption" style={styles.empty}>
            No leave booked.
          </AppText>
        ) : (
          [...feed.leaves]
            .sort((a, b) => a.leave_date.localeCompare(b.leave_date))
            .map((l) => (
              <View key={l.id}>
                <Divider />
                <View style={styles.leave}>
                  <View style={styles.flex}>
                    <AppText variant="bodyStrong">
                      {l.user_name ?? 'Staff'} · {formatDayShort(l.leave_date)}
                    </AppText>
                    <AppText variant="caption">
                      {l.leave_type} · {l.reason}
                    </AppText>
                  </View>
                  {confirming === l.id ? (
                    <View style={styles.confirm}>
                      <Button
                        title="Remove"
                        loading={busy === `remove-${l.id}`}
                        onPress={() => run(`remove-${l.id}`, async () => (await api.calendar.removeLeave(l.id)).message)}
                      />
                      <Button title="Keep" variant="secondary" onPress={() => setConfirming(null)} disabled={busy !== null} />
                    </View>
                  ) : (
                    <Pressable
                      onPress={() => setConfirming(l.id)}
                      accessibilityRole="button"
                      accessibilityLabel={`Remove leave for ${l.user_name ?? 'staff'} on ${l.leave_date}`}
                      hitSlop={8}>
                      <AppText variant="bodyStrong" color={colors.danger}>
                        Remove
                      </AppText>
                    </Pressable>
                  )}
                </View>
              </View>
            ))
        )}
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  card: { gap: spacing.md },
  empty: { marginTop: spacing.sm },
  leave: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingVertical: spacing.md },
  confirm: { gap: spacing.xs, minWidth: 110 },
});
