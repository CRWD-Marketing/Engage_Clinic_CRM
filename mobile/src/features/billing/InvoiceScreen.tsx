import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BillingInvoice, BillingOverview } from '@/api/types';
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
import { colors, spacing } from '@/theme';
import { todayYmd } from '@/utils/dates';

import { aed, invoiceEmail, statusColors } from './billingFormat';

type Panel = 'pay' | 'credit' | 'void' | 'email' | 'reminder' | null;
type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** One invoice: the amounts, receipts and the actions of the web's per-invoice "Actions…" menu. */
export function InvoiceScreen() {
  // `flash` is the confirmation carried over when the invoice was just raised.
  const { id, flash: raised } = useLocalSearchParams<{ id: string; flash?: string }>();
  const invoice = useApiQuery(`billing.invoice:${id}`, () => api.billing.invoice(Number(id)));
  const overview = useApiQuery('billing.overview', () => api.billing.overview());
  // Kept here so the message survives the form resetting after a save.
  const [flash, setFlash] = useState<Flash>(raised ? { text: raised, tone: 'success' } : null);

  if (!invoice.data || !overview.data) {
    const error = invoice.error ?? overview.error;
    return (
      <Screen scroll={false} edges={[]}>
        {error ? <ErrorState error={error} onRetry={() => { invoice.refresh(); overview.refresh(); }} /> : <LoadingState />}
      </Screen>
    );
  }

  return (
    <Detail
      key={`${invoice.data.id}:${invoice.data.paid}:${invoice.data.credit}:${invoice.data.voided}`}
      invoice={invoice.data}
      billing={overview.data}
      flash={flash}
      setFlash={setFlash}
      refreshing={invoice.refreshing}
      onRefresh={invoice.refresh}
      onChanged={invoice.reload}
    />
  );
}

function Detail({
  invoice: i,
  billing,
  flash,
  setFlash,
  refreshing,
  onRefresh,
  onChanged,
}: {
  invoice: BillingInvoice;
  billing: BillingOverview;
  flash: Flash;
  setFlash: (flash: Flash) => void;
  refreshing: boolean;
  onRefresh: () => void;
  onChanged: () => void;
}) {
  const [panel, setPanel] = useState<Panel>(null);
  const [busy, setBusy] = useState(false);
  const canAct = billing.can_invoice;
  const open = i.balance > 0.01 && !i.voided;

  async function run(action: () => Promise<{ message: string }>, after?: () => void) {
    setBusy(true);
    setFlash(null);
    try {
      const res = await action();
      setFlash({ text: res.message, tone: 'success' });
      setPanel(null);
      after?.();
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  const toggle = (next: Exclude<Panel, null>) => setPanel(panel === next ? null : next);

  return (
    <Screen edges={[]} refreshing={refreshing} onRefresh={onRefresh}>
      <Stack.Screen options={{ title: i.number }} />

      <Card style={styles.gap}>
        <View style={styles.head}>
          <View style={styles.flex}>
            <AppText variant="heading">{i.patient}</AppText>
            <AppText variant="caption">
              {i.parent || 'No parent on file'} · {i.payer}
            </AppText>
          </View>
          <Chip label={i.status_label} colors={statusColors(i)} />
        </View>
        <AppText variant="caption">
          {i.period} · issued {i.issued_label} · due {i.due_label}
          {open ? ` · ${i.age_label}` : ''}
        </AppText>
        {i.voided ? (
          <AppText variant="caption" color={colors.danger}>
            Voided {i.voided_on} — {i.void_reason}
            {i.replaced_by ? ` · reissued as ${i.replaced_by}` : ''}
          </AppText>
        ) : null}
        {i.replaces ? <AppText variant="caption">Replaces {i.replaces}</AppText> : null}
      </Card>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <Card>
        <Line label="Net (VAT excl.)" value={aed(i.net)} />
        <Line label="VAT" value={aed(i.vat)} />
        <Line label="Total" value={aed(i.total)} strong />
        <Divider />
        <Line label="Insurer share" value={aed(i.insurer_share)} />
        <Line label="Family share" value={aed(i.family_share)} />
        <Divider />
        <Line label="Paid" value={aed(i.paid)} />
        {i.credit > 0 ? <Line label="Credit notes" value={`– ${aed(i.credit)}`} /> : null}
        <Line label="Balance" value={aed(i.balance)} strong />
        {i.credit_reason ? <AppText variant="caption">Credit: {i.credit_reason.replace(/\n/g, ' · ')}</AppText> : null}
        {i.claim_reference ? <AppText variant="caption">Claim {i.claim_reference}</AppText> : null}
      </Card>

      {canAct ? (
        <Card style={styles.gap}>
          <AppText variant="heading">Actions</AppText>
          {open ? <Button title="Record payment" variant={panel === 'pay' ? 'primary' : 'secondary'} onPress={() => toggle('pay')} /> : null}
          {panel === 'pay' ? (
            <PaymentForm invoice={i} methods={billing.methods} busy={busy} onSubmit={(input) => run(() => api.billing.recordPayment(i.id, input))} />
          ) : null}

          {!i.voided ? <Button title="Credit note" variant={panel === 'credit' ? 'primary' : 'secondary'} onPress={() => toggle('credit')} /> : null}
          {panel === 'credit' ? <CreditForm busy={busy} onSubmit={(input) => run(() => api.billing.creditNote(i.id, input))} /> : null}

          <Button title="Send by email" variant={panel === 'email' ? 'primary' : 'secondary'} onPress={() => toggle('email')} />
          {panel === 'email' ? (
            <EmailForm invoice={i} kind="invoice" clinic={billing.clinic_name} busy={busy} onSubmit={(input) => run(() => api.billing.sendEmail(i.id, input))} />
          ) : null}

          {open ? <Button title="Send reminder" variant={panel === 'reminder' ? 'primary' : 'secondary'} onPress={() => toggle('reminder')} /> : null}
          {panel === 'reminder' ? (
            <EmailForm invoice={i} kind="reminder" clinic={billing.clinic_name} busy={busy} onSubmit={(input) => run(() => api.billing.sendEmail(i.id, input))} />
          ) : null}

          {!i.voided ? <Button title="Void / reissue" variant={panel === 'void' ? 'primary' : 'secondary'} onPress={() => toggle('void')} /> : null}
          {panel === 'void' ? (
            <VoidForm
              busy={busy}
              onSubmit={(input) => {
                let reissuedId: number | null = null;
                run(
                  async () => {
                    const res = await api.billing.voidInvoice(i.id, input);
                    reissuedId = res.reissued?.id ?? null;
                    return res;
                  },
                  // Straight to the corrected invoice when one was raised.
                  () => reissuedId && router.replace({ pathname: '/billing/[id]', params: { id: String(reissuedId) } }),
                );
              }}
            />
          ) : null}
        </Card>
      ) : null}

      <Card>
        <AppText variant="heading">Receipts</AppText>
        {i.receipts.length === 0 ? (
          <AppText variant="caption" style={styles.pad}>
            No payments recorded.
          </AppText>
        ) : (
          i.receipts.map((r) => (
            <View key={r.id} style={styles.receipt}>
              <View style={styles.flex}>
                <AppText variant="bodyStrong">{r.number}</AppText>
                <AppText variant="caption">
                  {r.date} · {r.method}
                  {r.reference ? ` · ref ${r.reference}` : ''}
                </AppText>
              </View>
              <AppText variant="bodyStrong">{aed(r.amount)}</AppText>
            </View>
          ))
        )}
      </Card>

      {i.sent_at || i.reminders_count > 0 ? (
        <Card>
          <AppText variant="heading">Emails</AppText>
          {i.sent_at ? (
            <AppText variant="caption" style={styles.pad}>
              Invoice emailed to {i.sent_to} on {i.sent_at}
            </AppText>
          ) : null}
          {i.reminders_count > 0 ? (
            <AppText variant="caption" style={styles.pad}>
              {i.reminders_count} reminder{i.reminders_count === 1 ? '' : 's'} sent · last {i.reminder_sent_at}
            </AppText>
          ) : null}
        </Card>
      ) : null}
    </Screen>
  );
}

function Line({ label, value, strong }: { label: string; value: string; strong?: boolean }) {
  return (
    <View style={styles.line}>
      <AppText variant={strong ? 'bodyStrong' : 'body'} style={styles.flex}>
        {label}
      </AppText>
      <AppText variant="bodyStrong" color={strong ? colors.navy : undefined}>
        {value}
      </AppText>
    </View>
  );
}

function PaymentForm({
  invoice,
  methods,
  busy,
  onSubmit,
}: {
  invoice: BillingInvoice;
  methods: string[];
  busy: boolean;
  onSubmit: (input: { amount: number; method: string; received_on: string; reference: string | null }) => void;
}) {
  const [amount, setAmount] = useState(invoice.balance.toFixed(2));
  const [method, setMethod] = useState(methods[0]);
  const [date, setDate] = useState(todayYmd());
  const [reference, setReference] = useState('');
  return (
    <View style={styles.form}>
      <TextField label="Amount (AED)" required value={amount} onChangeText={setAmount} keyboardType="decimal-pad" />
      <OptionPills label="Method" options={methods.map((m) => ({ value: m, label: m }))} value={method} onChange={setMethod} />
      <DateField label="Received on" value={date} onChange={setDate} required />
      <TextField label="Reference" value={reference} onChangeText={setReference} placeholder="Transfer or cheque number" />
      <Button
        title="Save payment"
        loading={busy}
        onPress={() => onSubmit({ amount: Number(amount), method, received_on: date, reference: reference || null })}
      />
    </View>
  );
}

function CreditForm({ busy, onSubmit }: { busy: boolean; onSubmit: (input: { amount: number; reason: string }) => void }) {
  const [amount, setAmount] = useState('');
  const [reason, setReason] = useState('');
  return (
    <View style={styles.form}>
      <TextField label="Credit amount (AED)" required value={amount} onChangeText={setAmount} keyboardType="decimal-pad" hint="VAT inclusive" />
      <TextField label="Reason" required value={reason} onChangeText={setReason} multiline />
      <Button title="Issue credit note" loading={busy} onPress={() => onSubmit({ amount: Number(amount), reason })} />
    </View>
  );
}

function VoidForm({ busy, onSubmit }: { busy: boolean; onSubmit: (input: { reason: string; reissue: boolean }) => void }) {
  const [reason, setReason] = useState('');
  const [reissue, setReissue] = useState(true);
  return (
    <View style={styles.form}>
      <AppText variant="caption">
        An issued tax invoice is never edited or deleted. Voiding credits the open balance and, if you choose, raises a corrected
        invoice under a new number.
      </AppText>
      <TextField label="Reason" required value={reason} onChangeText={setReason} multiline />
      <OptionPills
        label="After voiding"
        options={[
          { value: 'yes', label: 'Reissue a corrected invoice' },
          { value: 'no', label: 'Void only' },
        ]}
        value={reissue ? 'yes' : 'no'}
        onChange={(v) => setReissue(v === 'yes')}
      />
      <Button title={reissue ? 'Void and reissue' : 'Void invoice'} loading={busy} onPress={() => onSubmit({ reason, reissue })} />
    </View>
  );
}

function EmailForm({
  invoice,
  kind,
  clinic,
  busy,
  onSubmit,
}: {
  invoice: BillingInvoice;
  kind: 'invoice' | 'reminder';
  clinic: string;
  busy: boolean;
  onSubmit: (input: { to: string; cc: string | null; subject: string; message: string; kind: 'invoice' | 'reminder' }) => void;
}) {
  const [draft] = useState(() => invoiceEmail(invoice, kind, clinic));
  const [to, setTo] = useState(invoice.parent_email ?? '');
  const [cc, setCc] = useState('');
  const [subject, setSubject] = useState(draft.subject);
  const [message, setMessage] = useState(draft.message);
  return (
    <View style={styles.form}>
      <AppText variant="caption">The invoice PDF is attached automatically.</AppText>
      <TextField label="To" required value={to} onChangeText={setTo} keyboardType="email-address" autoCapitalize="none" />
      <TextField label="Cc" value={cc} onChangeText={setCc} keyboardType="email-address" autoCapitalize="none" />
      <TextField label="Subject" required value={subject} onChangeText={setSubject} />
      <TextField label="Message" required value={message} onChangeText={setMessage} multiline />
      <Button
        title={kind === 'reminder' ? 'Send reminder' : 'Send email'}
        loading={busy}
        onPress={() => onSubmit({ to, cc: cc || null, subject, message, kind })}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  line: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.md, paddingVertical: 5 },
  form: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  pad: { marginTop: spacing.sm },
  receipt: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingTop: spacing.md },
});
