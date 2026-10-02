import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BookingLead, BookingOptions, MySessionPayload, SessionUpdateRequest } from '@/api/types';
import { canDo } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, fonts, radius, spacing } from '@/theme';
import { formatDayMonthShort, todayYmd, weekdayIndex } from '@/utils/dates';
import { plural, trimNumber } from '@/utils/format';

const HOURS = Array.from({ length: 13 }, (_, i) => i + 7); // 07–19
const MINUTES = ['00', '15', '30', '45'];
/** Carbon dayOfWeek order: 0 = Sunday. */
const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const carbonDow = (date: string) => (weekdayIndex(date) + 1) % 7;

type Repeats = 'none' | 'weekly' | 'biweekly';
/** "keep" leaves a completed session's status alone. */
type StatusChoice = 'keep' | 'scheduled' | 'cancelled:family' | 'cancelled:clinic' | 'no_show' | 'closed';

const STATUS_OPTIONS: { value: StatusChoice; label: string }[] = [
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'cancelled:family', label: 'Cancelled by family' },
  { value: 'cancelled:clinic', label: 'Cancelled by clinic' },
  { value: 'no_show', label: 'No-show' },
  { value: 'closed', label: 'Closed' },
];

function initialStatus(s: MySessionPayload): StatusChoice {
  if (s.status === 'completed') return 'keep';
  if (s.status === 'cancelled') return s.cancel_reason === 'family' ? 'cancelled:family' : 'cancelled:clinic';
  return s.status;
}

/**
 * Book a session, or edit one (`id` param) — the calendar booking panel and
 * the Therapists page's Add / Edit session form on the web.
 */
export function SessionFormScreen() {
  const params = useLocalSearchParams<{ id?: string; therapist_id?: string; date?: string }>();
  const editId = params.id ? Number(params.id) : null;
  const options = useApiQuery('calendar.bookingOptions', () => api.calendar.bookingOptions());
  const session = useApiQuery(`calendar.show:${editId ?? 'new'}`, () => (editId ? api.calendar.show(editId) : Promise.resolve(null)));

  if (!options.data || session.data === undefined) {
    const error = options.error ?? session.error;
    return (
      <Screen scroll={false} edges={[]}>
        <Stack.Screen options={{ title: editId ? 'Edit session' : 'Book session' }} />
        {error ? <ErrorState error={error} onRetry={() => { options.refresh(); session.refresh(); }} /> : <LoadingState />}
      </Screen>
    );
  }

  return (
    <Form
      options={options.data}
      session={session.data}
      presetTherapist={params.therapist_id ? Number(params.therapist_id) : null}
      presetDate={params.date}
      onBooked={options.reload}
    />
  );
}

function Form({
  options,
  session,
  presetTherapist,
  presetDate,
  onBooked,
}: {
  options: BookingOptions;
  session: MySessionPayload | null;
  presetTherapist: number | null;
  presetDate?: string;
  onBooked: () => void;
}) {
  const user = useCurrentUser();
  const editing = session !== null;
  const canMulti = canDo(user, 'assign_multiple_therapists');

  const [therapists, setTherapists] = useState<number[]>(
    session ? [session.therapist_id] : presetTherapist ? [presetTherapist] : [],
  );
  const [patientId, setPatientId] = useState<number | null>(session && !session.is_custom_patient ? session.patient_id : null);
  const [custom, setCustom] = useState(session && (session.is_custom_patient || session.patient_id === null) ? session.patient_name : '');
  const [type, setType] = useState(session?.activity_type ?? 'ABA');
  const [date, setDate] = useState(session?.session_date ?? presetDate ?? todayYmd());
  const [hour, setHour] = useState(session ? Number(session.start_time.slice(0, 2)) : 9);
  const [minute, setMinute] = useState(session ? session.start_time.slice(3, 5) : '00');
  const [duration, setDuration] = useState(session?.duration_minutes ?? 60);
  const [room, setRoom] = useState(session?.room ?? '');
  const [notes, setNotes] = useState(session?.notes ?? '');
  const [repeats, setRepeats] = useState<Repeats>('none');
  const [weekdays, setWeekdays] = useState<number[]>([]);
  const [occurrences, setOccurrences] = useState('');
  const [status, setStatus] = useState<StatusChoice>(session ? initialStatus(session) : 'scheduled');
  const [noticeHours, setNoticeHours] = useState(session?.cancel_notice_hours != null ? String(session.cancel_notice_hours) : '');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [booked, setBooked] = useState<string | null>(null);

  const lead = options.leads.find((l) => l.id === patientId) ?? null;
  const startTime = `${String(hour).padStart(2, '0')}:${minute}`;
  // A group booking made on the web keeps its children; the app edits everything else about it.
  const isGroup = !!session?.patient_ids && session.patient_ids.length > 0;
  const toggle = (list: number[], value: number) => (list.includes(value) ? list.filter((v) => v !== value) : [...list, value]);

  async function submit() {
    if (therapists.length === 0) return setError('Pick a therapist.');
    if (patientId === null && !custom.trim()) return setError('Pick a patient, or type a custom patient / activity.');
    if (!date) return setError('Pick a date.');
    if (occurrences && !/^\d+$/.test(occurrences)) return setError('Number of sessions must be a whole number.');
    setError(null);
    setSaving(true);
    try {
      if (session) {
        const body: SessionUpdateRequest = {
          therapist_id: therapists[0],
          activity_types: [type],
          session_date: date,
          start_time: startTime,
          duration_minutes: duration,
          room,
          notes,
        };
        if (!isGroup) {
          body.patient_ids = patientId === null ? [] : [patientId];
          body.custom_patient = patientId === null ? custom : null;
        }
        if (status !== 'keep') {
          const [value, reason] = status.split(':') as [SessionUpdateRequest['status'], 'family' | 'clinic' | undefined];
          body.status = value;
          body.cancel_reason = reason ?? null;
          body.cancel_notice_hours = status === 'cancelled:family' && noticeHours ? Number(noticeHours) : null;
        }
        await api.calendar.update(session.id, body);
        router.back();
      } else {
        const result = await api.calendar.store({
          therapist_ids: therapists,
          patient_ids: patientId === null ? [] : [patientId],
          custom_patient: patientId === null ? custom : null,
          activity_types: [type],
          session_date: date,
          start_time: startTime,
          duration_minutes: duration,
          room,
          notes,
          repeats,
          weekdays: repeats === 'weekly' && weekdays.length > 0 ? weekdays : undefined,
          occurrences: repeats !== 'none' && occurrences ? Number(occurrences) : null,
        });
        setBooked(result.message);
        onBooked();
      }
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  if (booked) {
    return (
      <Screen edges={[]}>
        <Stack.Screen options={{ title: 'Book session' }} />
        <Banner text={booked} tone="success" />
        <Button title="Done" onPress={() => router.back()} />
        <Button title="Book another" variant="secondary" onPress={() => setBooked(null)} />
      </Screen>
    );
  }

  return (
    <Screen edges={[]}>
      <Stack.Screen options={{ title: editing ? 'Edit session' : 'Book session' }} />
      {error ? <Banner text={error} /> : null}

      <Card style={styles.card}>
        {editing || !canMulti ? (
          <OptionPills
            label="Therapist"
            options={options.therapists.map((t) => ({ value: t.id, label: t.name }))}
            value={therapists[0] ?? null}
            onChange={(id) => setTherapists(id === null ? [] : [id])}
          />
        ) : (
          <TogglePills
            label="Therapist(s) — one session each"
            options={options.therapists.map((t) => ({ value: t.id, label: t.name }))}
            selected={therapists}
            onToggle={(id) => setTherapists(toggle(therapists, id))}
          />
        )}
      </Card>

      <Card style={styles.card}>
        {isGroup ? (
          <View>
            <AppText variant="bodyStrong">Patient</AppText>
            <AppText variant="caption">{session?.patient_name} · group booking; change the children on the web.</AppText>
          </View>
        ) : (
          <>
            <OptionPills<number | null>
              label="Patient"
              options={[{ value: null, label: 'Other / custom' }, ...options.leads.map((l) => ({ value: l.id, label: l.name }))]}
              value={patientId}
              onChange={setPatientId}
            />
            {patientId === null ? (
              <TextField label="Custom patient / activity" value={custom} onChangeText={setCustom} placeholder="e.g. Team meeting" />
            ) : null}
            {lead ? <PatientInfo lead={lead} type={type} /> : null}
          </>
        )}
        <OptionPills label="Type" options={options.activity_types.map((t) => ({ value: t, label: t }))} value={type} onChange={setType} />
      </Card>

      <Card style={styles.card}>
        <DateField label={repeats === 'none' ? 'Date' : 'Starting on'} value={date} onChange={setDate} required />
        <OptionPills label="Hour" options={HOURS.map((h) => ({ value: h, label: String(h).padStart(2, '0') }))} value={hour} onChange={setHour} />
        <OptionPills label={`Minute · starts ${startTime}`} options={MINUTES.map((m) => ({ value: m, label: m }))} value={minute} onChange={setMinute} />
        <OptionPills
          label="Duration"
          options={options.durations.map((d) => ({ value: d, label: `${d} min` }))}
          value={duration}
          onChange={setDuration}
        />
        <TextField label="Room (optional)" value={room} onChangeText={setRoom} placeholder="e.g. Room 1, Sensory gym" />
      </Card>

      {editing ? (
        <Card style={styles.card}>
          <OptionPills<StatusChoice>
            label="Status"
            options={session?.status === 'completed' ? [{ value: 'keep', label: 'Completed' }, ...STATUS_OPTIONS] : STATUS_OPTIONS}
            value={status}
            onChange={setStatus}
          />
          {status === 'cancelled:family' ? (
            <TextField
              label="Notice given (hours)"
              value={noticeHours}
              onChangeText={setNoticeHours}
              keyboardType="number-pad"
              hint="How long before the session the family cancelled."
            />
          ) : null}
        </Card>
      ) : (
        <Card style={styles.card}>
          <OptionPills<Repeats>
            label="Repeats"
            options={[
              { value: 'none', label: 'Does not repeat' },
              { value: 'weekly', label: 'Every week' },
              { value: 'biweekly', label: 'Every 2 weeks' },
            ]}
            value={repeats}
            onChange={setRepeats}
          />
          {repeats === 'weekly' ? (
            <TogglePills
              label={`On these days (none picked = ${date ? WEEKDAYS[carbonDow(date)] : 'the start day'})`}
              options={WEEKDAYS.map((d, i) => ({ value: i, label: d }))}
              selected={weekdays}
              onToggle={(d) => setWeekdays(toggle(weekdays, d))}
            />
          ) : null}
          {repeats !== 'none' ? (
            <TextField
              label="Number of sessions"
              value={occurrences}
              onChangeText={setOccurrences}
              keyboardType="number-pad"
              hint="Leave blank to use up the patient's remaining package hours."
            />
          ) : null}
        </Card>
      )}

      <Card style={styles.card}>
        <TextField label="Notes (optional)" value={notes} onChangeText={setNotes} multiline />
      </Card>

      <Button title={editing ? 'Save changes' : 'Book session'} onPress={submit} loading={saving} />
      <Button title="Cancel" variant="secondary" onPress={() => router.back()} disabled={saving} />
    </Screen>
  );
}

/** What the booking panel shows about the chosen child: sessions already booked, package and authorization hours. */
function PatientInfo({ lead, type }: { lead: BookingLead; type: string }) {
  const slot = (s: { date: string; time: string }) => `${formatDayMonthShort(s.date)} ${s.time}`;
  const pkg = lead.packages.find((p) => p.types.includes(type));
  return (
    <View style={styles.info}>
      <AppText variant="caption">
        {lead.upcoming_count === 0
          ? 'No upcoming sessions on the calendar.'
          : `${lead.upcoming_count} upcoming ${plural(lead.upcoming_count, 'session')} · next ${slot(lead.next_session!)}${
              lead.last_session ? ` · last ${slot(lead.last_session)}` : ''
            }`}
      </AppText>
      {pkg ? (
        <AppText variant="caption" color={pkg.left > 0 ? colors.textSecondary : colors.danger}>
          Package “{pkg.name}”: {trimNumber(pkg.left)} h left of {trimNumber(pkg.total)} h
        </AppText>
      ) : (
        <AppText variant="caption">No package covers {type} for this patient.</AppText>
      )}
      {lead.auth ? (
        <AppText variant="caption">
          {lead.auth.payer} authorization: {trimNumber(lead.auth.left)} h left of {lead.auth.total} h
        </AppText>
      ) : null}
    </View>
  );
}

/** Pills where several can be on at once. */
function TogglePills({
  label,
  options,
  selected,
  onToggle,
}: {
  label: string;
  options: { value: number; label: string }[];
  selected: number[];
  onToggle: (value: number) => void;
}) {
  return (
    <View style={styles.pillsWrap}>
      <AppText variant="bodyStrong" style={styles.pillsLabel}>
        {label}
      </AppText>
      <View style={styles.pills}>
        {options.map((o) => {
          const active = selected.includes(o.value);
          return (
            <Pressable
              key={o.value}
              onPress={() => onToggle(o.value)}
              accessibilityRole="checkbox"
              accessibilityState={{ checked: active }}
              accessibilityLabel={o.label}
              style={[styles.pill, active && styles.pillActive]}>
              <AppText style={[styles.pillText, active && styles.pillTextActive]}>{o.label}</AppText>
            </Pressable>
          );
        })}
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  card: { gap: spacing.md },
  info: { gap: 2, padding: spacing.md, borderRadius: radius.input, backgroundColor: colors.pageAlt },
  pillsWrap: { gap: 6 },
  pillsLabel: { fontSize: 13 },
  pills: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  pill: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: 12,
    paddingVertical: 7,
  },
  pillActive: { backgroundColor: colors.navy, borderColor: colors.navy },
  pillText: { fontFamily: fonts.bodyBold, fontSize: 12.5, color: colors.textSecondary },
  pillTextActive: { color: colors.white },
});
