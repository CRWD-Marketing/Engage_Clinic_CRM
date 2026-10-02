import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { BillingOverview, ClaimStatus, InsuranceClaim, PreAuthorization, PreAuthRequest } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { Banner, EmptyRow } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { BarListCard } from '@/features/dashboard/panels';
import { claimAgingColors, claimStatusColors, colors, preAuthStatusColors, radius, spacing } from '@/theme';
import { addDays, todayYmd } from '@/utils/dates';

import { aed } from './billingFormat';

type Flash = { text: string; tone: 'success' | 'danger' } | null;

/** The insurance half of billing/index.blade.php: claims aging, the claims list and pre-authorizations. */
export function ClaimsTab({ billing: d, onChanged }: { billing: BillingOverview; onChanged: () => void }) {
  const [flash, setFlash] = useState<Flash>(null);
  const [busy, setBusy] = useState(false);
  const [editing, setEditing] = useState<number | null>(null);
  // `null` is closed; a pre-authorization is the denied request being resubmitted.
  const [form, setForm] = useState<'new' | PreAuthorization | null>(null);

  async function run(action: () => Promise<{ message: string }>) {
    setBusy(true);
    setFlash(null);
    try {
      const res = await action();
      setFlash({ text: res.message, tone: 'success' });
      setEditing(null);
      setForm(null);
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  const alert = d.rejected_alert;
  return (
    <>
      {alert ? (
        <Banner
          text={`${alert.patient}'s ${alert.insurer} claim (${alert.reference}) was rejected — ${alert.notes || 'resubmit with updated documentation'}.`}
          tone="danger"
        />
      ) : null}
      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <BarListCard
        title="Claims aging"
        empty="No open claims."
        rows={d.claim_aging.map((b, i) => ({
          label: b.label,
          value: b.amount,
          text: `${aed(b.amount, 0)} · ${b.count}`,
          color: claimAgingColors[i % claimAgingColors.length],
        }))}
      />

      <Card padded={false}>
        <View style={styles.header}>
          <CardHeader title="Insurance claims" />
          <AppText variant="caption">Settling a claim records the insurer&apos;s payment on the invoice.</AppText>
        </View>
        {d.claims.length === 0 ? (
          <EmptyRow text="No claims submitted yet." />
        ) : (
          d.claims.map((claim) => (
            <View key={claim.id}>
              <Divider />
              <ClaimItem
                claim={claim}
                statuses={d.claim_statuses}
                canChange={d.can_invoice}
                editing={editing === claim.id}
                busy={busy}
                onEdit={() => setEditing(editing === claim.id ? null : claim.id)}
                onChange={(status) => run(() => api.billing.updateClaim(claim.id, { status }))}
              />
            </View>
          ))
        )}
      </Card>

      <Card padded={false}>
        <View style={styles.header}>
          <CardHeader title="Pre-authorizations" />
          <AppText variant="caption">Requests, approvals and denials by payer.</AppText>
          {d.can_invoice ? (
            <View style={styles.headerAction}>
              <Button title="Request pre-auth" variant={form === 'new' ? 'primary' : 'secondary'} onPress={() => setForm(form === 'new' ? null : 'new')} />
            </View>
          ) : null}
          {form ? (
            <PreAuthForm
              key={form === 'new' ? 'new' : form.id}
              billing={d}
              from={form === 'new' ? null : form}
              busy={busy}
              onSubmit={(input) => run(() => api.billing.requestPreAuth(input))}
            />
          ) : null}
        </View>
        {d.pre_auths.length === 0 ? (
          <EmptyRow text="No pre-authorization requests on file." />
        ) : (
          d.pre_auths.map((p) => (
            <View key={p.id}>
              <Divider />
              <View style={styles.row}>
                <View style={styles.flex}>
                  <AppText variant="bodyStrong">
                    {p.patient} — {p.service}
                  </AppText>
                  <AppText variant="caption">
                    {p.payer} · {p.hours} h · {p.from} → {p.to} · submitted {p.submitted}
                  </AppText>
                  <AppText variant="caption">
                    Ref {p.reference}
                    {p.payer_reference ? ` · payer ref ${p.payer_reference}` : ''}
                  </AppText>
                  {p.status === 'denied' && p.denial_reason ? (
                    <AppText variant="caption" color={colors.danger}>
                      {p.denial_reason}
                    </AppText>
                  ) : null}
                </View>
                <View style={styles.right}>
                  <Chip label={p.status_label} colors={preAuthStatusColors[p.status]} />
                  {d.can_invoice && p.status === 'denied' ? (
                    <Pressable
                      onPress={() => setForm(p)}
                      accessibilityRole="button"
                      accessibilityLabel={`Resubmit ${p.reference}`}
                      style={({ pressed }) => [styles.tool, pressed && styles.pressed]}>
                      <AppText variant="caption" color={colors.textSecondary}>
                        Resubmit
                      </AppText>
                    </Pressable>
                  ) : null}
                </View>
              </View>
            </View>
          ))
        )}
      </Card>
    </>
  );
}

function ClaimItem({
  claim: c,
  statuses,
  canChange,
  editing,
  busy,
  onEdit,
  onChange,
}: {
  claim: InsuranceClaim;
  statuses: Record<ClaimStatus, string>;
  canChange: boolean;
  editing: boolean;
  busy: boolean;
  onEdit: () => void;
  onChange: (status: ClaimStatus) => void;
}) {
  return (
    <View style={styles.claim}>
      <View style={styles.claimTop}>
        <View style={styles.flex}>
          <AppText variant="bodyStrong">{c.patient}</AppText>
          <AppText variant="caption">
            {c.reference}
            {c.period ? ` · ${c.period}` : ''} · {c.insurer}
            {c.invoice ? ` · ${c.invoice}` : ''}
          </AppText>
          <AppText variant="caption" color={c.open && c.age > 30 ? colors.danger : colors.textMuted}>
            {c.open ? `${c.age} d since submitted` : 'closed'}
            {c.notes ? ` · ${c.notes}` : ''}
          </AppText>
        </View>
        <View style={styles.right}>
          <AppText variant="bodyStrong">{aed(c.amount)}</AppText>
          <Chip label={c.status_label} colors={claimStatusColors[c.status]} />
        </View>
      </View>
      {canChange ? (
        <View style={styles.claimAction}>
          <Pressable
            onPress={onEdit}
            accessibilityRole="button"
            accessibilityLabel={`Change status of ${c.reference}`}
            style={({ pressed }) => [styles.tool, pressed && styles.pressed]}>
            <AppText variant="caption" color={colors.textSecondary}>
              Change status {editing ? '▴' : '▾'}
            </AppText>
          </Pressable>
        </View>
      ) : null}
      {editing ? (
        <View style={styles.form}>
          <OptionPills<ClaimStatus>
            label={`Status of ${c.reference}`}
            options={(Object.keys(statuses) as ClaimStatus[]).map((value) => ({ value, label: statuses[value] }))}
            value={c.status}
            onChange={(status) => status !== c.status && onChange(status)}
            disabled={busy}
          />
        </View>
      ) : null}
    </View>
  );
}

function PreAuthForm({
  billing,
  from,
  busy,
  onSubmit,
}: {
  billing: BillingOverview;
  from: PreAuthorization | null;
  busy: boolean;
  onSubmit: (input: PreAuthRequest) => void;
}) {
  const [patientId, setPatientId] = useState<number | null>(from?.patient_id ?? billing.patients[0]?.id ?? null);
  const [payer, setPayer] = useState(from?.payer ?? billing.payers[0] ?? '');
  const [service, setService] = useState(from?.service ?? billing.services[0] ?? '');
  const [hours, setHours] = useState(String(from?.hours ?? 40));
  const [validFrom, setValidFrom] = useState(from?.from_iso ?? todayYmd());
  const [validTo, setValidTo] = useState(from?.to_iso ?? addDays(todayYmd(), 180));
  const [justification, setJustification] = useState(from?.justification ?? '');
  // A resubmitted request may name a payer or service that is no longer on the lists.
  const withCurrent = (list: string[], current: string) => (current && !list.includes(current) ? [current, ...list] : list);

  return (
    <View style={styles.form}>
      <AppText variant="bodyStrong">{from ? `Resubmit ${from.reference}` : 'Request pre-authorization'}</AppText>
      <AppText variant="caption">Submitted to the payer for approval before sessions are billed.</AppText>
      <OptionPills<number | null>
        label="Client"
        options={billing.patients.map((p) => ({ value: p.id, label: p.name }))}
        value={patientId}
        onChange={setPatientId}
      />
      <OptionPills label="Payer" options={withCurrent(billing.payers, payer).map((p) => ({ value: p, label: p }))} value={payer} onChange={setPayer} />
      <OptionPills
        label="Service"
        options={withCurrent(billing.services, service).map((s) => ({ value: s, label: s }))}
        value={service}
        onChange={setService}
      />
      <TextField label="Hours" required value={hours} onChangeText={setHours} keyboardType="number-pad" />
      <DateField label="Valid from" value={validFrom} onChange={setValidFrom} required />
      <DateField label="Valid to" value={validTo} onChange={setValidTo} required />
      <TextField
        label="Clinical justification"
        value={justification}
        onChangeText={setJustification}
        placeholder="e.g. VB-MAPP level 2, September progress review attached"
        multiline
      />
      <Button
        title="Submit request"
        loading={busy}
        disabled={patientId === null}
        onPress={() =>
          onSubmit({
            patient_id: patientId!,
            payer,
            service,
            hours: Number(hours),
            valid_from: validFrom,
            valid_to: validTo,
            justification: justification.trim() || null,
            resubmitted_from_id: from?.id ?? null,
          })
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  header: { paddingHorizontal: spacing.lg, paddingTop: spacing.lg, paddingBottom: spacing.md, gap: spacing.xs },
  headerAction: { marginTop: spacing.sm },
  row: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  right: { alignItems: 'flex-end', gap: 6 },
  claim: { paddingVertical: spacing.md, gap: spacing.sm },
  claimTop: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingHorizontal: spacing.lg },
  claimAction: { flexDirection: 'row', paddingHorizontal: spacing.lg },
  pressed: { backgroundColor: colors.pageAlt },
  tool: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: spacing.md,
    paddingVertical: 6,
  },
  form: { gap: spacing.sm, padding: spacing.md, marginHorizontal: spacing.md, marginTop: spacing.sm, borderRadius: 10, backgroundColor: colors.pageAlt },
});
