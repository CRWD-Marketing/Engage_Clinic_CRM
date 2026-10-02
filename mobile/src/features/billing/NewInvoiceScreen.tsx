import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BillingPatient, CancelPolicy, InvoicePreview, LedgerRow, NewInvoiceRequest } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, radius, spacing } from '@/theme';

import { ATTENDANCE_OPTIONS, type AttendanceEdit, editForNotice, editForState, repriceRow, takesNotice } from './attendance';
import { aed } from './billingFormat';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** The web's "New invoice" picker: client, their delivered sessions, a preview, then issue. */
export function NewInvoiceScreen() {
  const overview = useApiQuery('billing.overview', () => api.billing.overview());
  const [patientId, setPatientId] = useState<number | null>(null);

  if (!overview.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {overview.error ? <ErrorState error={overview.error} onRetry={overview.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  if (!overview.data.can_invoice) {
    return (
      <Screen edges={[]}>
        <Banner text="Invoices are raised by Finance." tone="info" />
      </Screen>
    );
  }

  const patient = overview.data.patients.find((p) => p.id === patientId);
  if (!patient) return <ClientPicker patients={overview.data.patients} onPick={setPatientId} />;
  return <SessionPicker key={patient.id} patient={patient} policy={overview.data.cancel_policy} onChangeClient={() => setPatientId(null)} />;
}

function ClientPicker({ patients, onPick }: { patients: BillingPatient[]; onPick: (id: number) => void }) {
  const [search, setSearch] = useState('');
  const term = search.trim().toLowerCase();
  const shown = patients.filter((p) => !term || `${p.name} ${p.parent ?? ''} ${p.payer}`.toLowerCase().includes(term));
  return (
    <Screen edges={[]}>
      <AppText variant="caption">Pick the client, then the sessions to bill.</AppText>
      <TextField
        label="Client"
        value={search}
        onChangeText={setSearch}
        placeholder="Child, parent or payer"
        autoCapitalize="none"
        autoCorrect={false}
        clearButtonMode="while-editing"
      />
      <Card padded={false}>
        {shown.length === 0 ? (
          <EmptyRow text="No clients match." />
        ) : (
          shown.map((p, i) => (
            <View key={p.id}>
              {i > 0 ? <Divider /> : null}
              <Pressable
                onPress={() => onPick(p.id)}
                accessibilityRole="button"
                accessibilityLabel={`Bill ${p.name}`}
                style={({ pressed }) => [styles.client, pressed && styles.pressed]}>
                <AppText variant="bodyStrong">{p.name}</AppText>
                <AppText variant="caption">
                  {p.parent || 'No parent on file'} · {p.payer}
                  {p.prepaid ? ' · prepaid' : ''}
                </AppText>
              </Pressable>
            </View>
          ))
        )}
      </Card>
    </Screen>
  );
}

function SessionPicker({ patient, policy, onChangeClient }: { patient: BillingPatient; policy: CancelPolicy; onChangeClient: () => void }) {
  const ledger = useApiQuery(`billing.ledger:${patient.id}`, () => api.billing.ledger(patient.id));
  const [selected, setSelected] = useState<number[]>([]);
  // Attendance corrections, by session id. Saved to the calendar only when the invoice is issued.
  const [edits, setEdits] = useState<Record<number, AttendanceEdit>>({});
  const [editing, setEditing] = useState<number | null>(null);
  const [preview, setPreview] = useState<InvoicePreview | null>(null);
  const [topUp, setTopUp] = useState(false);
  const [hours, setHours] = useState('10');
  const [busy, setBusy] = useState(false);
  const [flash, setFlash] = useState<Flash>(null);

  if (!ledger.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {ledger.error ? <ErrorState error={ledger.error} onRetry={ledger.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const data = ledger.data;
  const rows = data.rows.map((r) => repriceRow(r, edits[r.id], policy, data.patient.vat_rate));
  const chosen = rows.filter((r) => selected.includes(r.id));
  const billable = chosen.reduce((sum, r) => sum + r.bill_hours, 0);

  const request = (): NewInvoiceRequest => ({
    patient_id: patient.id,
    session_ids: selected,
    attendance: Object.fromEntries(
      selected
        .filter((id) => edits[id])
        .map((id) => [id, { state: edits[id].state, ...(edits[id].notice_hours !== null ? { notice_hours: edits[id].notice_hours } : {}) }]),
    ),
  });

  async function run<T>(action: () => Promise<T>, done: (result: T) => void) {
    setBusy(true);
    setFlash(null);
    try {
      done(await action());
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  const toggle = (id: number) => setSelected((ids) => (ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]));

  if (preview) {
    return (
      <PreviewStep
        preview={preview}
        busy={busy}
        flash={flash}
        onBack={() => {
          setPreview(null);
          setFlash(null);
        }}
        onIssue={() =>
          // The preview has already shown how many are on another invoice.
          run(
            () => api.billing.createInvoice({ ...request(), acknowledge_settled: true }),
            (res) => router.replace({ pathname: '/billing/[id]', params: { id: String(res.invoice.id), flash: res.message } }),
          )
        }
      />
    );
  }

  return (
    <Screen edges={[]} refreshing={ledger.refreshing} onRefresh={ledger.refresh}>
      <Card style={styles.gap}>
        <View style={styles.head}>
          <View style={styles.flex}>
            <AppText variant="heading">{data.patient.name}</AppText>
            <AppText variant="caption">
              {data.patient.parent || 'No parent on file'} · {data.patient.payer} · AED {data.patient.rate}/h · {data.patient.setting}
            </AppText>
          </View>
        </View>
        <Button title="Change client" variant="secondary" onPress={onChangeClient} />
      </Card>

      {data.patient.prepaid ? (
        <Card style={styles.gap}>
          <AppText variant="bodyStrong">
            Prepaid package — {data.prepaid_left} h left of {data.patient.prepaid.total}
          </AppText>
          <AppText variant="caption">
            {data.prepaid_used} h drawn by delivered sessions. These sessions draw on the prepaid balance.
          </AppText>
          <Button title="Top up prepaid hours" variant={topUp ? 'primary' : 'secondary'} onPress={() => setTopUp(!topUp)} />
          {topUp ? (
            <View style={styles.form}>
              <TextField label="Hours to add" required value={hours} onChangeText={setHours} keyboardType="number-pad" />
              <Button
                title="Add hours"
                loading={busy}
                onPress={() =>
                  run(
                    () => api.billing.topUpPrepaid(patient.id, Number(hours)),
                    (res) => {
                      setFlash({ text: res.message, tone: 'success' });
                      setTopUp(false);
                      ledger.reload();
                    },
                  )
                }
              />
            </View>
          ) : null}
        </Card>
      ) : null}

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <View style={styles.gap}>
        <AppText variant="bodyStrong">
          {selected.length ? `${selected.length} session${selected.length === 1 ? '' : 's'} · ${billable} h billable selected` : 'No sessions selected'}
        </AppText>
        <View style={styles.toolbar}>
          <ToolbarButton title="Select unbilled" onPress={() => setSelected(rows.filter((r) => !r.invoiced).map((r) => r.id))} />
          <ToolbarButton title="Select all" onPress={() => setSelected(rows.map((r) => r.id))} />
          <ToolbarButton title="Clear" onPress={() => setSelected([])} />
        </View>
      </View>

      <Card padded={false}>
        {rows.length === 0 ? (
          <EmptyRow text="No delivered sessions on file for this client yet." />
        ) : (
          rows.map((row, i) => (
            <View key={row.id}>
              {i > 0 ? <Divider /> : null}
              <SessionRow
                row={row}
                checked={selected.includes(row.id)}
                onToggle={() => toggle(row.id)}
                editing={editing === row.id}
                onEdit={() => setEditing(editing === row.id ? null : row.id)}
                edit={edits[row.id] ?? { state: row.attendance, notice_hours: row.notice_hours }}
                onChange={(edit) => setEdits((all) => ({ ...all, [row.id]: edit }))}
                policy={policy}
              />
            </View>
          ))
        )}
      </Card>

      <Button
        title="Preview invoice"
        disabled={selected.length === 0}
        loading={busy}
        onPress={() => run(() => api.billing.previewInvoice(request()), setPreview)}
      />
    </Screen>
  );
}

function ToolbarButton({ title, onPress }: { title: string; onPress: () => void }) {
  return (
    <Pressable onPress={onPress} accessibilityRole="button" style={({ pressed }) => [styles.tool, pressed && styles.pressed]}>
      <AppText variant="caption" color={colors.textSecondary}>
        {title}
      </AppText>
    </Pressable>
  );
}

function SessionRow({
  row,
  checked,
  onToggle,
  editing,
  onEdit,
  edit,
  onChange,
  policy,
}: {
  row: LedgerRow;
  checked: boolean;
  onToggle: () => void;
  editing: boolean;
  onEdit: () => void;
  edit: AttendanceEdit;
  onChange: (edit: AttendanceEdit) => void;
  policy: CancelPolicy;
}) {
  const label = ATTENDANCE_OPTIONS.find((o) => o.value === row.attendance)?.label ?? row.attendance_label;
  return (
    <View style={styles.session}>
      <Pressable
        onPress={onToggle}
        accessibilityRole="checkbox"
        accessibilityState={{ checked }}
        accessibilityLabel={`${row.service_label}, ${row.date_label} ${row.start_time}`}
        style={({ pressed }) => [styles.sessionTop, pressed && styles.pressed]}>
        <View style={[styles.box, checked && styles.boxOn]}>{checked ? <AppText style={styles.tick}>✓</AppText> : null}</View>
        <View style={styles.flex}>
          <AppText variant="bodyStrong">{row.service_label}</AppText>
          <AppText variant="caption">
            {row.date_label} · {row.start_time}–{row.end_time}
          </AppText>
          <AppText variant="caption">
            {row.setting} by {row.therapist_name}
            {row.trainee_note ? ` (${row.trainee_note})` : ''} · {row.payer} {row.coverage_pct}%{row.invoiced ? ` · ${row.invoice_number}` : ''}
          </AppText>
          {row.insufficient_authorization ? (
            <AppText variant="caption" color={colors.warning}>
              {row.insufficient_message}
            </AppText>
          ) : null}
        </View>
        <View style={styles.right}>
          <AppText variant="bodyStrong">{aed(row.gross)}</AppText>
          <AppText variant="caption" color={row.invoiced ? colors.warning : colors.textMuted}>
            {row.billing_status}
          </AppText>
        </View>
      </Pressable>

      <View style={styles.attendance}>
        <Pressable
          onPress={onEdit}
          accessibilityRole="button"
          accessibilityLabel={`Attendance on ${row.date_label} ${row.start_time}: ${label}`}
          style={({ pressed }) => [styles.tool, pressed && styles.pressed]}>
          <AppText variant="caption" color={colors.textSecondary}>
            {label} {editing ? '▴' : '▾'}
          </AppText>
        </Pressable>
        <AppText variant="caption" color={row.bill_hours > 0 ? colors.textSecondary : colors.textMuted} style={styles.flex}>
          {row.charge_rule}
        </AppText>
      </View>
      {editing ? (
        <View style={styles.form}>
          <OptionPills label="Attendance" options={ATTENDANCE_OPTIONS} value={edit.state} onChange={(state) => onChange(editForState(state, policy))} />
          {takesNotice(edit.state) ? (
            <TextField
              label="Hours of notice the family gave"
              value={edit.notice_hours === null ? '' : String(edit.notice_hours)}
              onChangeText={(text) => onChange(editForNotice(edit, text, policy))}
              keyboardType="decimal-pad"
              hint={`${policy.notice_hours} h or more is free; less is charged at ${policy.late_pct}%.`}
            />
          ) : null}
        </View>
      ) : null}
    </View>
  );
}

function PreviewStep({
  preview: p,
  busy,
  flash,
  onBack,
  onIssue,
}: {
  preview: InvoicePreview;
  busy: boolean;
  flash: Flash;
  onBack: () => void;
  onIssue: () => void;
}) {
  return (
    <Screen edges={[]}>
      <Card style={styles.gap}>
        <AppText variant="heading">{p.invoice_number}</AppText>
        <AppText variant="caption">
          {p.child} · bill to {p.bill_to || 'parent / guardian'} · {p.period_label}
        </AppText>
        <AppText variant="caption">
          Issue {p.issue_date} · due {p.due_date} · {p.session_count} session{p.session_count === 1 ? '' : 's'}, {p.bill_hours} h
        </AppText>
      </Card>

      {p.settled_count > 0 ? (
        <Banner
          text={`${p.settled_count} selected ${p.settled_count === 1 ? 'session is' : 'sessions are'} already on an invoice. Issuing bills ${p.settled_count === 1 ? 'it' : 'them'} again.`}
          tone="danger"
        />
      ) : null}
      {p.warnings.length > 0 ? (
        <Banner
          text={`Authorization insufficient — ${p.warnings.join('; ')}. The excess is billed to the family on this invoice, not to insurance.`}
          tone="info"
        />
      ) : null}
      {p.lines.length === 0 ? <Banner text="Nothing billable in the selection — none of the chosen sessions is chargeable." tone="info" /> : null}
      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <Card padded={false}>
        {p.lines.map((l, i) => (
          <View key={l.line_no}>
            {i > 0 ? <Divider /> : null}
            <View style={styles.line}>
              <View style={styles.flex}>
                <AppText variant="bodyStrong">
                  {l.line_no}. {l.description}
                </AppText>
                <AppText variant="caption">{l.note}</AppText>
                <AppText variant="caption">
                  {l.from_label} to {l.to_label.split(' · ')[1] ?? l.to_label}
                </AppText>
                {l.exceeds_authorization ? (
                  <AppText variant="caption" color={colors.warning}>
                    Exceeds authorization — billed to family
                  </AppText>
                ) : null}
              </View>
              <View style={styles.right}>
                <AppText variant="bodyStrong">{aed(l.total)}</AppText>
                <AppText variant="caption">incl. VAT {aed(l.vat_amount)}</AppText>
              </View>
            </View>
          </View>
        ))}
      </Card>

      <Card>
        <Total label="Subtotal — VAT excluded" value={aed(p.net)} />
        <Total label="VAT 5%" value={aed(p.vat)} />
        <Total label="Invoice total" value={aed(p.total)} strong />
        {p.insurer_share > 0 ? (
          <>
            <Divider />
            <Total label="Insurance coverage" value={`– ${aed(p.insurer_share)}`} />
            {p.splits.map((s) => (
              <AppText key={s.payer} variant="caption">
                {s.payer} — {s.pct}% · {aed(s.amount)}
              </AppText>
            ))}
          </>
        ) : null}
        <Divider />
        <Total label="Amount due" value={aed(p.amount_due)} strong />
      </Card>

      <Button title="Issue invoice" loading={busy} disabled={p.lines.length === 0} onPress={onIssue} />
      <Button title="Back to sessions" variant="secondary" onPress={onBack} />
    </Screen>
  );
}

function Total({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <View style={styles.total}>
      <AppText variant={strong ? 'bodyStrong' : 'body'} style={styles.flex}>
        {label}
      </AppText>
      <AppText variant="bodyStrong" color={strong ? colors.navy : undefined}>
        {value}
      </AppText>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  client: { paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  pressed: { backgroundColor: colors.pageAlt },
  form: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  toolbar: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  tool: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  session: { paddingBottom: spacing.md, gap: spacing.sm },
  sessionTop: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingHorizontal: spacing.lg, paddingTop: spacing.md },
  box: {
    width: 22,
    height: 22,
    marginTop: 2,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: colors.border,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
  boxOn: { backgroundColor: colors.navy, borderColor: colors.navy },
  tick: { color: colors.white, fontSize: 13, lineHeight: 16 },
  right: { alignItems: 'flex-end', gap: 2 },
  attendance: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingHorizontal: spacing.lg },
  line: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  total: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.md, paddingVertical: 5 },
});
