import Ionicons from '@expo/vector-icons/Ionicons';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BoardLead, LeadActivity, LeadDetail, LeadsBoard, LeadUpdateRequest } from '@/api/types';
import { canDo, canWriteIn } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, fonts, leadColors, spacing } from '@/theme';
import { addDays, diffDays, formatDateTimeShort, isoToYmd, timeAgo, todayYmd } from '@/utils/dates';

import {
  dueTag,
  FOLLOW_UP_PRESETS,
  formatAed,
  NEXT_STATUS,
  sourceBadge,
  STATUS_LABEL,
  STATUS_TAG_LABEL,
  TERMINATION_REASONS,
  valueNumber,
} from './leadFormat';
import { INTAKE_STEPS } from './intakeSteps';

type Flash = { text: string; tone: 'success' | 'danger' } | null;
/** Follow-up choice: a preset in days, "none", or a custom YYYY-MM-DD. */
type FollowUp = { kind: 'preset'; days: number | null } | { kind: 'custom'; date: string };

/** The lead action panel (lead/index.blade.php "ap-" panel) as a screen. */
export function LeadPanelScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const leadId = Number(id);
  const detail = useApiQuery(`leads.show:${id}`, () => api.leads.show(leadId));
  const board = useApiQuery('leads.board', () => api.leads.board());
  // Coming back from an intake step form: show the step as done.
  useRefetchOnFocus(detail.reload);
  // Kept here (not in Panel) so it survives the panel resetting after a save.
  const [flash, setFlash] = useState<Flash>(null);

  if (!detail.data || !board.data) {
    const error = detail.error ?? board.error;
    return (
      <Screen scroll={false} edges={[]}>
        {error ? <ErrorState error={error} onRetry={() => { detail.refresh(); board.refresh(); }} /> : <LoadingState />}
      </Screen>
    );
  }

  return (
    <Panel
      // Reset the staged owner / follow-up when the server copy changes.
      key={`${detail.data.lead.updated_at}:${detail.data.lead.status}`}
      detail={detail.data}
      board={board.data}
      refreshing={detail.refreshing}
      onRefresh={detail.refresh}
      onChanged={detail.reload}
      flash={flash}
      setFlash={setFlash}
    />
  );
}

function initialFollowUp(lead: BoardLead): FollowUp {
  if (!lead.follow_up_due_at) return { kind: 'preset', days: null };
  return { kind: 'custom', date: isoToYmd(lead.follow_up_due_at) };
}

function Panel({
  detail,
  board,
  refreshing,
  onRefresh,
  onChanged,
  flash,
  setFlash,
}: {
  detail: LeadDetail;
  board: LeadsBoard;
  refreshing: boolean;
  onRefresh: () => void;
  onChanged: () => void;
  flash: Flash;
  setFlash: (flash: Flash) => void;
}) {
  const user = useCurrentUser();
  const lead = detail.lead;
  const canWrite = canWriteIn(user, 'leads');
  const due = dueTag(lead);

  const [owner, setOwner] = useState<number | null>(lead.assigned_to);
  const [followUp, setFollowUp] = useState<FollowUp>(() => initialFollowUp(lead));
  const [busy, setBusy] = useState<string | null>(null);
  const [terminating, setTerminating] = useState(false);

  // Changing the owner needs assign (no owner yet) or reassign (already owned).
  const canChangeOwner = canWrite && canDo(user, lead.assigned_to ? 'reassign_lead_owner' : 'assign_lead_owner');
  const followUpDate =
    followUp.kind === 'custom' ? followUp.date : followUp.days === null ? '' : addDays(todayYmd(), followUp.days);

  /** The fields the web's panel resends with every save. */
  const baseFields = (): LeadUpdateRequest => ({
    child_name: lead.child_name,
    child_age: lead.child_age,
    parent_guardian_name: lead.parent_guardian_name,
    phone: lead.phone,
    source: lead.source,
    interested_in: lead.interested_in,
    insurance: lead.insurance,
    estimated_value: lead.estimated_value,
    notes: lead.notes,
    status: lead.status,
  });

  async function run(name: string, fn: () => Promise<string>, fallback: string) {
    setBusy(name);
    setFlash(null);
    try {
      setFlash({ text: await fn(), tone: 'success' });
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e) || fallback, tone: 'danger' });
    } finally {
      setBusy(null);
    }
  }

  // "Success": save owner + follow-up, then move to the next stage.
  const success = () =>
    run(
      'success',
      async () => {
        await api.leads.update(lead.id, {
          ...baseFields(),
          ...(owner !== lead.assigned_to ? { assigned_to: owner } : {}),
          follow_up_due_at: followUpDate,
        });
        const next = NEXT_STATUS[lead.status];
        if (!next) return 'Lead updated successfully.';
        await api.leads.updateStatus(lead.id, next);
        return `Lead moved to ${STATUS_LABEL[next]}.`;
      },
      'Could not move lead to the next stage.',
    );

  // "Follow-up": log the call and clear the due date.
  const logFollowUp = () =>
    run(
      'followup',
      async () => {
        await api.leads.addNote(lead.id, 'Followed up with the family.');
        await api.leads.update(lead.id, { ...baseFields(), follow_up_due_at: '' });
        return 'Follow-up logged.';
      },
      'Could not log the follow-up.',
    );

  const convert = () =>
    run(
      'convert',
      async () => {
        const res = await api.leads.convertToPatient(lead.id);
        router.push({ pathname: '/patients/[id]', params: { id: String(res.patient_id) } }, { withAnchor: true });
        return res.message;
      },
      'Could not convert this lead.',
    );

  const needsOwner = lead.status === 'new' && owner === null;
  const stepsDone = lead.intake_steps_complete;

  return (
    <Screen edges={['bottom']} refreshing={refreshing} onRefresh={onRefresh}>
      <Stack.Screen options={{ title: lead.child_name ?? 'Lead' }} />

      <Card style={styles.section}>
        <View style={styles.row}>
          <AppText variant="title" style={styles.flex}>
            {lead.child_name ?? 'N/A'} · {lead.child_age ?? 'N/A'}
          </AppText>
          <Chip label={lead.source ?? 'N/A'} colors={sourceBadge(lead.source)} />
        </View>
        <View style={styles.tags}>
          <Chip label={STATUS_TAG_LABEL[lead.status]} colors={leadColors.statusTag[lead.status]} />
          {due ? <Chip label={due.label} colors={due.colors} /> : null}
          {lead.has_patient ? <Chip label="Converted to client" colors={leadColors.statusTag.enrolled} /> : null}
        </View>
        <Field label="Parent / Guardian" value={lead.parent_guardian_name} />
        <Field label="Est. monthly value" value={`${formatAed(valueNumber(lead))}/mo`} />
        <Field label="Enquiry notes" value={lead.notes ?? 'No notes'} />
      </Card>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      {lead.status === 'terminated' ? (
        <Card style={styles.section}>
          <AppText variant="heading">Terminated</AppText>
          <Field label="Reason" value={lead.termination_reason ?? 'No reason given'} />
          <Field label="Note" value={lead.termination_note} />
        </Card>
      ) : lead.has_patient ? null : (
        <Card style={styles.section}>
          <AppText variant="heading">Assigned to</AppText>
          <OptionPills
            options={[{ value: null, label: 'Unassigned' }, ...board.assignable_users.map((u) => ({ value: u.id, label: u.name }))]}
            value={owner}
            onChange={setOwner}
            disabled={!canChangeOwner}
          />
          {!canChangeOwner && canWrite ? (
            <AppText variant="caption">
              Your access level can’t {lead.assigned_to ? 'reassign' : 'assign'} a lead owner.
            </AppText>
          ) : null}

          <Divider />
          <AppText variant="heading">Follow-up</AppText>
          <OptionPills
            options={FOLLOW_UP_PRESETS.map((p) => ({ value: p.days === null ? 'none' : String(p.days), label: p.label }))}
            value={followUp.kind === 'preset' ? (followUp.days === null ? 'none' : String(followUp.days)) : 'custom'}
            onChange={(v) => setFollowUp({ kind: 'preset', days: v === 'none' ? null : Number(v) })}
            disabled={!canWrite}
          />
          <DateField
            label="Or pick a date"
            value={followUp.kind === 'custom' ? followUp.date : ''}
            onChange={(date) => setFollowUp(date ? { kind: 'custom', date } : { kind: 'preset', days: null })}
            disabled={!canWrite}
            hint={followUpDate ? followUpHint(followUpDate) : undefined}
          />

          {canWrite ? (
            <>
              <Divider />
              {lead.status === 'enrolled' ? (
                <>
                  <Button
                    title={stepsDone === 7 ? 'Convert to client' : `Finish intake first (${stepsDone}/7)`}
                    onPress={convert}
                    loading={busy === 'convert'}
                    disabled={stepsDone !== 7 || !canDo(user, 'convert_to_client')}
                  />
                  {stepsDone !== 7 ? (
                    <AppText variant="caption">
                      Complete the remaining intake checklist steps before converting this lead.
                    </AppText>
                  ) : null}
                </>
              ) : (
                <>
                  <Button
                    title={needsOwner ? 'Assign first' : 'Success'}
                    onPress={success}
                    loading={busy === 'success'}
                    disabled={needsOwner || !!busy}
                  />
                  {needsOwner ? (
                    <AppText variant="caption">A lead must have an owner before it can move to Contacted.</AppText>
                  ) : null}
                  {lead.status === 'contacted' ? (
                    <Button title="Follow-up" variant="secondary" onPress={logFollowUp} loading={busy === 'followup'} disabled={!!busy} />
                  ) : null}
                  {canDo(user, 'terminate_lead') ? (
                    <Button title="Terminate" variant="secondary" onPress={() => setTerminating(true)} disabled={!!busy} />
                  ) : null}
                </>
              )}
            </>
          ) : null}
        </Card>
      )}

      {terminating ? (
        <TerminateForm
          leadId={lead.id}
          onCancel={() => setTerminating(false)}
          onDone={(text) => {
            setTerminating(false);
            setFlash({ text, tone: 'success' });
            onChanged();
          }}
        />
      ) : null}

      <Card style={styles.section}>
        <View style={styles.row}>
          <AppText variant="heading" style={styles.flex}>
            Intake checklist before conversion
          </AppText>
          <AppText variant="caption">{stepsDone} of 7 complete</AppText>
        </View>
        {INTAKE_STEPS.map((step) => {
          const done = lead[step.column] !== null;
          return (
            <View key={step.key} style={styles.step}>
              <Ionicons
                name={done ? 'checkmark-circle' : 'ellipse-outline'}
                size={20}
                color={done ? colors.success : colors.textFaint}
              />
              <View style={styles.flex}>
                <AppText variant="bodyStrong">{step.title}</AppText>
                <AppText variant="caption">{done ? step.summary(detail) || 'Done' : step.subtitle}</AppText>
              </View>
              {canWrite && lead.status !== 'terminated' ? (
                <Pressable
                  onPress={() => router.push({ pathname: '/leads/intake', params: { id: String(lead.id), step: step.key } })}
                  accessibilityRole="button"
                  accessibilityLabel={`${done ? 'Edit' : 'Fill in'} ${step.title}`}
                  hitSlop={8}
                  style={styles.stepBtn}>
                  <AppText variant="link">{done ? 'Edit' : 'Fill in'}</AppText>
                </Pressable>
              ) : null}
            </View>
          );
        })}
      </Card>

      <Card style={styles.section}>
        <AppText variant="heading">Where this lead came from</AppText>
        <Field label="Channel" value={lead.source} />
        <Field label="Created" value={formatDateTimeShort(lead.created_at)} />
        <Field label="Campaign" value={lead.campaign} />
        <Field label="Ad" value={lead.ad_name} />
        <Field label="Lead form" value={lead.lead_form_name} />
        <Field label="City" value={lead.city} />
        <Field label="Child age band" value={lead.child_age_band} />
        <Field label="Main concern" value={lead.main_concern} />
        <Field label="Phone" value={lead.phone} />
        <Field label="Email" value={lead.email} />
      </Card>

      <NotesSection leadId={lead.id} notes={detail.notes_log} canAdd={canWrite && canDo(user, 'add_lead_notes')} />

      <Card style={styles.section}>
        <AppText variant="heading">Assignment log ({detail.assignment_log.length})</AppText>
        {detail.assignment_log.length === 0 ? (
          <AppText variant="caption">No assignment changes yet.</AppText>
        ) : (
          detail.assignment_log.map((a) => <ActivityRow key={a.id} activity={a} />)
        )}
      </Card>
    </Screen>
  );
}

function followUpHint(date: string): string | undefined {
  const days = diffDays(todayYmd(), date);
  return days === 0 ? 'Due today' : days > 0 ? `Due in ${days} day${days === 1 ? '' : 's'}` : `${Math.abs(days)} day${days === -1 ? '' : 's'} ago`;
}

function Field({ label, value }: { label: string; value: string | null }) {
  return (
    <View style={styles.field}>
      <AppText variant="caption" style={styles.fieldLabel}>
        {label}
      </AppText>
      <AppText variant="body" style={styles.flex}>
        {value || '—'}
      </AppText>
    </View>
  );
}

function ActivityRow({ activity: a }: { activity: LeadActivity }) {
  return (
    <View style={styles.activity}>
      <View style={styles.row}>
        <AppText variant="bodyStrong" style={styles.flex}>
          {a.author_name}
        </AppText>
        <AppText variant="caption">{timeAgo(a.created_at)}</AppText>
      </View>
      <AppText variant="body">{a.body}</AppText>
    </View>
  );
}

function NotesSection({ leadId, notes, canAdd }: { leadId: number; notes: LeadActivity[]; canAdd: boolean }) {
  const [draft, setDraft] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | undefined>();
  const [added, setAdded] = useState<LeadActivity[]>([]);
  const list = [...added, ...notes.filter((n) => !added.some((a) => a.id === n.id))];

  async function add() {
    setSaving(true);
    setError(undefined);
    try {
      const res = await api.leads.addNote(leadId, draft);
      setAdded((cur) => [res.note, ...cur]);
      setDraft('');
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  return (
    <Card style={styles.section}>
      <AppText variant="heading">Notes</AppText>
      {canAdd ? (
        <>
          <TextField
            label="New note"
            value={draft}
            onChangeText={setDraft}
            placeholder="Add a note — call outcome, parent questions, next step…"
            multiline
            maxLength={2000}
            hint="Saved against this lead with your name and time."
            error={error}
          />
          <Button title="Add note" onPress={add} loading={saving} disabled={!draft.trim()} />
        </>
      ) : null}
      {list.length === 0 ? (
        <AppText variant="caption">No notes yet.</AppText>
      ) : (
        list.map((n) => <ActivityRow key={n.id} activity={n} />)
      )}
    </Card>
  );
}

function TerminateForm({ leadId, onCancel, onDone }: { leadId: number; onCancel: () => void; onDone: (message: string) => void }) {
  const [reason, setReason] = useState<string | null>(null);
  const [note, setNote] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function terminate() {
    setSaving(true);
    setError(null);
    try {
      await api.leads.update(leadId, { status: 'terminated', termination_reason: reason, termination_note: note });
      onDone('Lead terminated.');
    } catch (e) {
      setError(errorMessage(e));
      setSaving(false);
    }
  }

  return (
    <Card style={styles.section}>
      <AppText variant="heading">Terminate lead</AppText>
      <OptionPills
        label="Reason *"
        options={TERMINATION_REASONS.map((r) => ({ value: r, label: r }))}
        value={reason}
        onChange={setReason}
      />
      <TextField label="Note" value={note} onChangeText={setNote} multiline />
      {error ? <Banner text={error} /> : null}
      <View style={styles.actions}>
        <View style={styles.flex}>
          <Button title="Cancel" variant="secondary" onPress={onCancel} disabled={saving} />
        </View>
        <View style={styles.flex}>
          <Button title="Terminate lead" onPress={terminate} loading={saving} />
        </View>
      </View>
    </Card>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  section: { gap: spacing.sm },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  tags: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  field: { flexDirection: 'row', gap: spacing.sm },
  fieldLabel: { width: 118, fontFamily: fonts.bodyBold },
  step: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, minHeight: 44 },
  stepBtn: { paddingVertical: 6, paddingLeft: spacing.sm },
  activity: { gap: 2, paddingTop: spacing.sm, borderTopWidth: 1, borderTopColor: colors.divider },
  actions: { flexDirection: 'row', gap: spacing.sm },
});
