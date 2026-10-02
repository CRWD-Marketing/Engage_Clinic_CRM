import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { ContactItem, ContactStatus } from '@/api/types';
import { canAccessFeature, canWriteIn } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, contactStatusColors, neutralChip, spacing } from '@/theme';
import { formatDateTimeShort, isoToYmd, timeAgo, todayYmd, weekdayIndex } from '@/utils/dates';

import { decisionEmail, hasSlot, slotLabel } from './contactFormat';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** The clinic takes no consultations on Fridays and Saturdays, or in the past. */
const slotDateDisabled = (date: string) => date < todayYmd() || [4, 5].includes(weekdayIndex(date));

/** Mirrors the contact detail dialog in resources/views/contact/index.blade.php. */
export function ContactDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const query = useApiQuery('contacts.list', () => api.contacts.list());
  // Kept here (not in Detail) so it survives Detail resetting after a save.
  const [flash, setFlash] = useState<Flash>(null);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const contact = query.data.contacts.find((c) => c.id === Number(id));
  if (!contact) {
    return (
      <Screen scroll={false} edges={[]}>
        <ErrorState error={new Error('missing')} onRetry={() => router.back()} />
      </Screen>
    );
  }

  return (
    <Detail
      // Reset the editors whenever the submission changes on the server.
      key={`${contact.id}:${contact.updated_at}`}
      contact={contact}
      statusLabel={query.data.statuses[contact.status]}
      times={query.data.consultation_times}
      refreshing={query.refreshing}
      onRefresh={query.refresh}
      onChanged={query.reload}
      flash={flash}
      setFlash={setFlash}
    />
  );
}

function Detail({
  contact,
  statusLabel,
  times,
  refreshing,
  onRefresh,
  onChanged,
  flash,
  setFlash,
}: {
  contact: ContactItem;
  statusLabel: string;
  times: string[];
  refreshing: boolean;
  onRefresh: () => void;
  onChanged: () => void;
  flash: Flash;
  setFlash: (flash: Flash) => void;
}) {
  const user = useCurrentUser();
  const canWrite = canWriteIn(user, 'contacts');
  // Coordinators support intake but can't convert or delete (ContactController).
  const isCoordinator = user.role === 'COORDINATOR';
  const [busy, setBusy] = useState<string | null>(null);
  const [panel, setPanel] = useState<'email' | 'slot' | 'close' | 'delete' | null>(null);

  const name = contact.name || 'Unknown contact';
  const slot = slotLabel(contact);
  const decidable = contact.status === 'new' || contact.status === 'approved' || contact.status === 'rejected';

  async function run(key: string, action: () => Promise<string | void>) {
    setBusy(key);
    setFlash(null);
    try {
      const text = await action();
      setPanel(null);
      if (text) setFlash({ text, tone: 'success' });
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(null);
    }
  }

  const setStatus = (status: ContactStatus) =>
    run(status, async () => {
      await api.contacts.updateStatus(contact.id, status);
    });

  return (
    <Screen edges={[]} refreshing={refreshing} onRefresh={onRefresh}>
      <Card style={styles.gap}>
        <View style={styles.head}>
          <Avatar id={contact.id} name={name} size={44} />
          <View style={styles.flex}>
            <AppText variant="heading">{name}</AppText>
            <AppText variant="caption">Website enquiry · {timeAgo(contact.created_at)}</AppText>
          </View>
        </View>
        <View style={styles.chips}>
          <Chip label="Website" colors={neutralChip} />
          <Chip label={statusLabel} colors={contactStatusColors[contact.status]} />
        </View>
        {canWrite && contact.can_send_email ? (
          <Button title="Send email" variant="secondary" onPress={() => setPanel(panel === 'email' ? null : 'email')} />
        ) : null}
        {canWrite && contact.can_convert && !isCoordinator ? (
          <Button
            title="Convert to lead"
            loading={busy === 'convert'}
            disabled={busy !== null}
            onPress={() =>
              run('convert', async () => {
                await api.contacts.convertToLead(contact.id);
                return 'Converted to a lead.';
              })
            }
          />
        ) : null}
        {contact.status === 'converted' && contact.converted_lead_id && canAccessFeature(user, 'leads') ? (
          <Button
            title="View lead"
            variant="secondary"
            onPress={() =>
              router.push({ pathname: '/leads/[id]', params: { id: String(contact.converted_lead_id) } }, { withAnchor: true })
            }
          />
        ) : null}
      </Card>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      {panel === 'email' ? (
        <EmailCard
          contact={contact}
          sending={busy === 'email'}
          onCancel={() => setPanel(null)}
          onSend={(input) => run('email', async () => (await api.contacts.sendEmail(contact.id, input)).message)}
        />
      ) : null}

      <Card style={styles.gap}>
        <AppText variant="heading">Message</AppText>
        <AppText variant={contact.message ? 'body' : 'caption'}>{contact.message || 'No message provided.'}</AppText>
      </Card>

      <Card style={styles.gap}>
        <AppText variant="heading">Requested consultation slot</AppText>
        {slot ? (
          <View>
            <AppText variant="bodyStrong">{slot}</AppText>
            <AppText variant="caption">Free 30-min consultation</AppText>
          </View>
        ) : (
          <AppText variant="caption">No consultation slot was requested.</AppText>
        )}
        {!contact.can_edit_slot && hasSlot(contact) && contact.status_email_sent_at ? (
          <AppText variant="caption">The decision has been emailed, so this slot is final.</AppText>
        ) : null}
        {canWrite && contact.can_edit_slot && panel !== 'slot' ? (
          <Button title={slot ? 'Change' : 'Set a slot'} variant="secondary" onPress={() => setPanel('slot')} disabled={busy !== null} />
        ) : null}
        {panel === 'slot' ? (
          <SlotEditor
            contact={contact}
            times={times}
            saving={busy === 'slot'}
            removing={busy === 'slot-remove'}
            onCancel={() => setPanel(null)}
            onSave={(date, time) =>
              run('slot', async () => (await api.contacts.updateSlot(contact.id, { booking_date: date, booking_time: time })).message)
            }
            onRemove={() =>
              run('slot-remove', async () => {
                await api.contacts.updateSlot(contact.id, { booking_date: null, booking_time: null });
                return 'Consultation slot removed.';
              })
            }
          />
        ) : null}
      </Card>

      <Card>
        <AppText variant="heading" style={styles.cardTitle}>
          Contact details
        </AppText>
        <Field label="Phone" value={contact.phone} />
        <Field label="Email" value={contact.email} />
        <Field label="Child's name" value={contact.child_name} />
        <Field label="Child's age" value={contact.child_age ? `Age ${contact.child_age}` : null} />
        <Field label="Service enquiry" value={contact.interested_in} />
        <Field label="Insurance" value={contact.insurance} />
        <Field label="Channel" value="Website" />
        <Field label="Received" value={formatDateTimeShort(contact.created_at)} />
      </Card>

      <Card style={styles.gap}>
        <AppText variant="heading">Decision</AppText>
        {decidable && canWrite ? (
          <>
            <View style={styles.row}>
              <View style={styles.flex}>
                <Button
                  title={contact.status === 'approved' ? '✓ Approved' : 'Approve'}
                  variant={contact.status === 'approved' ? 'primary' : 'secondary'}
                  loading={busy === 'approved'}
                  disabled={busy !== null || contact.status === 'approved'}
                  onPress={() => setStatus('approved')}
                />
              </View>
              <View style={styles.flex}>
                <Button
                  title={contact.status === 'rejected' ? '✕ Rejected' : 'Reject'}
                  variant={contact.status === 'rejected' ? 'primary' : 'secondary'}
                  loading={busy === 'rejected'}
                  disabled={busy !== null || contact.status === 'rejected'}
                  onPress={() => setStatus('rejected')}
                />
              </View>
            </View>
            <AppText variant="caption">Approve or reject the requested slot, then email the family.</AppText>
          </>
        ) : null}
        {contact.status_email_sent_at ? (
          <AppText variant="bodyStrong" color={colors.success}>
            {contact.booking_decision === 'approved' ? 'Approved' : contact.booking_decision === 'rejected' ? 'Rejected' : 'Decision'} ·
            emailed {formatDateTimeShort(contact.status_email_sent_at)}
          </AppText>
        ) : null}
        {!decidable && !contact.status_email_sent_at ? (
          <AppText variant="caption">This submission is {statusLabel.toLowerCase()}.</AppText>
        ) : null}

        {canWrite && contact.status === 'new' ? (
          <Confirmable
            open={panel === 'close'}
            link="Close without action"
            question="Close this submission without approving or rejecting?"
            confirm="Close submission"
            loading={busy === 'closed'}
            onOpen={() => setPanel('close')}
            onCancel={() => setPanel(null)}
            onConfirm={() => setStatus('closed')}
          />
        ) : null}
        {canWrite && !isCoordinator ? (
          <Confirmable
            danger
            open={panel === 'delete'}
            link="Delete submission"
            question="Delete this submission? This cannot be undone."
            confirm="Delete"
            loading={busy === 'delete'}
            onOpen={() => setPanel('delete')}
            onCancel={() => setPanel(null)}
            onConfirm={async () => {
              setBusy('delete');
              try {
                await api.contacts.destroy(contact.id);
                router.back();
              } catch (e) {
                setFlash({ text: errorMessage(e), tone: 'danger' });
                setBusy(null);
              }
            }}
          />
        ) : null}
      </Card>
    </Screen>
  );
}

function Field({ label, value }: { label: string; value: string | null }) {
  return (
    <>
      <Divider />
      <View style={styles.field}>
        <AppText variant="caption">{label}</AppText>
        <AppText variant="bodyStrong" style={styles.fieldValue} selectable>
          {value || 'Not provided'}
        </AppText>
      </View>
    </>
  );
}

/** A text link that asks for confirmation in place before running a destructive action. */
function Confirmable({
  open,
  link,
  question,
  confirm,
  danger,
  loading,
  onOpen,
  onCancel,
  onConfirm,
}: {
  open: boolean;
  link: string;
  question: string;
  confirm: string;
  danger?: boolean;
  loading: boolean;
  onOpen: () => void;
  onCancel: () => void;
  onConfirm: () => void;
}) {
  if (!open) {
    return (
      <Pressable onPress={onOpen} accessibilityRole="button" hitSlop={6}>
        <AppText variant="bodyStrong" color={danger ? colors.danger : colors.textSecondary}>
          {link}
        </AppText>
      </Pressable>
    );
  }
  return (
    <View style={styles.confirm}>
      <AppText variant="bodyStrong">{question}</AppText>
      <View style={styles.row}>
        <View style={styles.flex}>
          <Button title="Cancel" variant="secondary" onPress={onCancel} disabled={loading} />
        </View>
        <View style={styles.flex}>
          <Button title={confirm} onPress={onConfirm} loading={loading} />
        </View>
      </View>
    </View>
  );
}

function SlotEditor({
  contact,
  times,
  saving,
  removing,
  onSave,
  onRemove,
  onCancel,
}: {
  contact: ContactItem;
  times: string[];
  saving: boolean;
  removing: boolean;
  onSave: (date: string, time: string) => void;
  onRemove: () => void;
  onCancel: () => void;
}) {
  const [date, setDate] = useState(contact.booking_date ? isoToYmd(contact.booking_date) : '');
  const [time, setTime] = useState<string | null>(contact.booking_time);
  const [error, setError] = useState<string | null>(null);
  const working = saving || removing;

  function save() {
    if (!date) return setError('Pick a date for the consultation.');
    if (!time) return setError('Choose a time for the consultation.');
    setError(null);
    onSave(date, time);
  }

  return (
    <View style={styles.gap}>
      <Divider />
      {error ? <Banner text={error} /> : null}
      <DateField label="Date" value={date} onChange={setDate} isDateDisabled={slotDateDisabled} hint="Closed Fridays and Saturdays" />
      {date ? (
        <OptionPills label="Time" options={times.map((t) => ({ value: t, label: t }))} value={time} onChange={setTime} />
      ) : (
        <AppText variant="caption">Pick a date first to choose a time.</AppText>
      )}
      {contact.status === 'approved' ? (
        <AppText variant="caption">This slot is approved - after moving it, email the family the new time.</AppText>
      ) : null}
      <Button title="Save slot" onPress={save} loading={saving} disabled={working} />
      <Button title="Cancel" variant="secondary" onPress={onCancel} disabled={working} />
      {contact.booking_date ? (
        <Pressable onPress={onRemove} disabled={working} accessibilityRole="button" hitSlop={6}>
          <AppText variant="bodyStrong" color={colors.danger}>
            {removing ? 'Removing…' : 'Remove slot'}
          </AppText>
        </Pressable>
      ) : null}
    </View>
  );
}

function EmailCard({
  contact,
  sending,
  onSend,
  onCancel,
}: {
  contact: ContactItem;
  sending: boolean;
  onSend: (input: { to: string; subject: string; message: string }) => void;
  onCancel: () => void;
}) {
  const [draft] = useState(() => decisionEmail(contact));
  const [to, setTo] = useState(draft.to);
  const [subject, setSubject] = useState(draft.subject);
  const [message, setMessage] = useState(draft.message);

  return (
    <Card style={styles.gap}>
      <View>
        <AppText variant="heading">Email the family</AppText>
        <AppText variant="caption">
          {contact.name || 'This family'} · {draft.kind} notice
        </AppText>
      </View>
      <TextField label="To" value={to} onChangeText={setTo} keyboardType="email-address" autoCapitalize="none" />
      <TextField label="Subject" value={subject} onChangeText={setSubject} />
      <TextField label="Message" value={message} onChangeText={setMessage} multiline />
      <Button title="Send email" onPress={() => onSend({ to, subject, message })} loading={sending} />
      <Button title="Cancel" variant="secondary" onPress={onCancel} disabled={sending} />
    </Card>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  chips: { flexDirection: 'row', gap: spacing.sm },
  row: { flexDirection: 'row', gap: spacing.sm },
  cardTitle: { marginBottom: spacing.sm },
  field: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.md, paddingVertical: spacing.sm },
  fieldValue: { flex: 1, textAlign: 'right' },
  confirm: { gap: spacing.sm, paddingTop: spacing.xs },
});
