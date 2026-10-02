import { useState, type ReactNode } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { PatientDetail, PatientDocumentItem } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { DateField } from '@/components/DateField';
import { OptionPills } from '@/components/OptionPills';
import { StatTile } from '@/components/StatTile';
import { Banner } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { approvalReference, approvedLabel } from '@/features/leads/fundingRows';
import { colors, documentColors, neutralChip, spacing } from '@/theme';
import { formatDateTimeShort, formatDayMonthYear, formatMonthYear, isoToYmd } from '@/utils/dates';
import { formatMoney, trimNumber } from '@/utils/format';

const date = (iso: string | null | undefined) => (iso ? formatDayMonthYear(isoToYmd(iso)) : null);
const aed = (amount: number | string) => `AED ${formatMoney(Number(amount), 0)}`;

// ---------------------------------------------------------------------------
// Payments
// ---------------------------------------------------------------------------

/** patient/show.blade.php "Payments" tab. */
export function PaymentsTab({ detail: d }: { detail: PatientDetail }) {
  const p = d.payments;
  return (
    <>
      <View style={styles.stats}>
        <StatTile label="Billed to date" value={aed(p.billed_total)} caption="VAT excl." />
        <StatTile label="Collected" value={aed(p.collected_total)} caption="Payments received" />
        <StatTile label="Outstanding" value={aed(p.outstanding_total)} caption="Billed less collected" captionColor={colors.danger} />
      </View>
      <Card padded={false}>
        <View style={styles.cardHead}>
          <AppText variant="heading">Payment history</AppText>
        </View>
        {p.invoices.length === 0 ? (
          <AppText variant="caption" style={styles.emptyPad}>
            No invoices raised yet.
          </AppText>
        ) : (
          p.invoices.map((i) => (
            <View key={i.id}>
              <Divider />
              <View style={styles.invoice} accessible accessibilityLabel={`${i.invoice_number}, ${i.payment_status_label}`}>
                <View style={styles.rowBetween}>
                  <AppText variant="bodyStrong">{i.invoice_number}</AppText>
                  <AppText variant="bodyStrong" color={i.payment_status_label === 'Paid' ? colors.success : colors.warning}>
                    {i.payment_status_label}
                  </AppText>
                </View>
                <AppText variant="caption">
                  Issued {date(i.issue_date)} · period {formatMonthYear(isoToYmd(i.period))}
                </AppText>
                <AppText variant="caption">
                  Amount {aed(i.subtotal)} · paid {aed(i.amount_paid)} · {i.payment_method || '—'}
                </AppText>
              </View>
            </View>
          ))
        )}
      </Card>
    </>
  );
}

// ---------------------------------------------------------------------------
// Documents
// ---------------------------------------------------------------------------

/** patient/show.blade.php "Documents" tab: a log of records (name, type, expiry), not file uploads. */
export function DocumentsTab({ detail: d, canWrite, onChanged }: { detail: PatientDetail; canWrite: boolean; onChanged: () => void }) {
  const [adding, setAdding] = useState(false);
  const [name, setName] = useState('');
  const [type, setType] = useState(d.edit_options.document_types[0]);
  const [expires, setExpires] = useState('');
  const [busy, setBusy] = useState<string | null>(null);
  const [confirming, setConfirming] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function run(key: string, action: () => Promise<unknown>) {
    setBusy(key);
    setError(null);
    try {
      await action();
      setAdding(false);
      setConfirming(null);
      setName('');
      setExpires('');
      onChanged();
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setBusy(null);
    }
  }

  return (
    <Card style={styles.gap}>
      <View>
        <AppText variant="heading">Documents</AppText>
        <AppText variant="caption">Reports, authorization letters, signed consent and identity records</AppText>
      </View>
      {error ? <Banner text={error} /> : null}
      {canWrite && !adding ? <Button title="+ Add document" variant="secondary" onPress={() => setAdding(true)} /> : null}
      {adding ? (
        <View style={styles.gap}>
          <TextField label="File name" required value={name} onChangeText={setName} placeholder="e.g. September progress review.pdf" />
          <OptionPills label="Type" options={d.edit_options.document_types.map((t) => ({ value: t, label: t }))} value={type} onChange={setType} />
          <DateField label="Expiry" value={expires} onChange={setExpires} placeholder="Optional" />
          <Button
            title="Add to record"
            loading={busy === 'add'}
            onPress={() => run('add', () => api.patients.addDocument(d.patient.id, { name, type, expires_at: expires || null }))}
          />
          <Button title="Cancel" variant="secondary" onPress={() => setAdding(false)} disabled={busy !== null} />
        </View>
      ) : null}

      {d.documents.length === 0 ? (
        <AppText variant="caption">No documents on file yet.</AppText>
      ) : (
        d.documents.map((doc) => (
          <DocumentRow
            key={doc.id}
            doc={doc}
            canWrite={canWrite}
            confirming={confirming === doc.id}
            deleting={busy === `delete-${doc.id}`}
            onAsk={() => setConfirming(doc.id)}
            onCancel={() => setConfirming(null)}
            onDelete={() => run(`delete-${doc.id}`, () => api.patients.deleteDocument(d.patient.id, doc.id))}
          />
        ))
      )}
    </Card>
  );
}

function DocumentRow({
  doc,
  canWrite,
  confirming,
  deleting,
  onAsk,
  onCancel,
  onDelete,
}: {
  doc: PatientDocumentItem;
  canWrite: boolean;
  confirming: boolean;
  deleting: boolean;
  onAsk: () => void;
  onCancel: () => void;
  onDelete: () => void;
}) {
  return (
    <View style={styles.doc}>
      <Divider />
      <AppText variant="bodyStrong">{doc.name}</AppText>
      <AppText variant="caption">
        Added by {doc.uploader_label} · {date(doc.created_at)}
      </AppText>
      <View style={styles.chips}>
        {doc.type ? <Chip label={doc.type} colors={neutralChip} /> : null}
        <Chip label={doc.expiry_label} colors={documentColors[doc.expiry_variant]} />
      </View>
      {doc.has_file ? <AppText variant="caption">The attached file can be downloaded on the web.</AppText> : null}
      {canWrite && !confirming ? (
        <Pressable onPress={onAsk} accessibilityRole="button" accessibilityLabel={`Delete ${doc.name}`} hitSlop={6}>
          <AppText variant="bodyStrong" color={colors.danger}>
            Delete
          </AppText>
        </Pressable>
      ) : null}
      {confirming ? (
        <View style={styles.gap}>
          <AppText variant="bodyStrong">Delete this document record?</AppText>
          <View style={styles.row}>
            <View style={styles.flex}>
              <Button title="Cancel" variant="secondary" onPress={onCancel} disabled={deleting} />
            </View>
            <View style={styles.flex}>
              <Button title="Delete record" onPress={onDelete} loading={deleting} />
            </View>
          </View>
        </View>
      ) : null}
    </View>
  );
}

// ---------------------------------------------------------------------------
// Profile & intake
// ---------------------------------------------------------------------------

function Section({ title, subtitle, children }: { title: string; subtitle?: string; children: ReactNode }) {
  return (
    <Card>
      <AppText variant="heading">{title}</AppText>
      {subtitle ? <AppText variant="caption">{subtitle}</AppText> : null}
      <View style={styles.fields}>{children}</View>
    </Card>
  );
}

function Field({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <View style={styles.field}>
      <AppText variant="caption" style={styles.fieldLabel}>
        {label}
      </AppText>
      <AppText variant="body" style={styles.fieldValue}>
        {value === null || value === undefined || value === '' ? '—' : value}
      </AppText>
    </View>
  );
}

/** patient/show.blade.php "Profile & intake" tab: everything agreed at intake, read-only. */
export function ProfileTab({ detail: d }: { detail: PatientDetail }) {
  const lead = d.patient.lead;
  const p = d.profile;
  const hours = p.package_hours_per_week > 0 ? `${trimNumber(p.package_hours_per_week)} h` : null;
  const packages = p.package_names.join(', ') || null;
  const services = lead.funding_services_needed ?? [];

  return (
    <>
      <Section title="Package & funding" subtitle="Agreed at intake, before conversion">
        <Field label="Package(s)" value={packages} />
        <Field label="Hours purchased" value={hours} />
        <Field label="Hours / week" value={hours} />
        <Field
          label="Rate / hr"
          value={p.package_rate_per_hour === 'Mixed' ? 'Mixed' : p.package_rate_per_hour ? aed(p.package_rate_per_hour) : null}
        />
        <Field label="Package value excl. VAT" value={p.package_value_excl_vat > 0 ? aed(p.package_value_excl_vat) : null} />
        <Field label="Sessions / week" value={lead.package_sessions_per_week} />
        <Field label="Location" value={p.package_location} />
        <Field label="Setting" value={p.package_setting} />
        <Field label="Funding" value={p.funding_summary} />
      </Section>

      <Section title="Services & who pays" subtitle="Agreed at intake — a client can have several therapies with different payers">
        {services.length === 0 ? (
          <AppText variant="caption">No service/payer breakdown on file.</AppText>
        ) : (
          services.map((row, i) => (
            <View key={`${row.service}-${i}`} style={styles.service}>
              <AppText variant="bodyStrong">{row.service || '—'}</AppText>
              <AppText variant="caption">
                {row.hours_per_week != null ? `${row.hours_per_week} h / week` : '—'} · paid by {row.payer || '—'}
              </AppText>
              {approvedLabel(row) || approvalReference(row) ? (
                <AppText variant="caption">
                  {approvedLabel(row) ?? 'No approved hours'}
                  {approvalReference(row) ? ` · ref ${approvalReference(row)}` : ''}
                </AppText>
              ) : null}
            </View>
          ))
        )}
      </Section>

      <Section title="Client">
        <Field label="Child" value={lead.child_name} />
        <Field label="Age" value={lead.child_age} />
        <Field label="Diagnosis" value={d.patient.diagnosis} />
        <Field label="Programme" value={d.patient.programme} />
        <Field label="Client since" value={formatMonthYear(isoToYmd(d.patient.enrolled_at))} />
        <Field label="Attendance" value={d.attendance_rate !== null ? `${d.attendance_rate}%` : null} />
      </Section>

      <Section title="Parent & contact">
        <Field label="Parent / guardian" value={lead.parent_guardian_name} />
        <Field label="Mobile" value={lead.phone} />
        <Field label="Email" value={lead.email} />
      </Section>

      <Section title="Child details">
        <Field label="Full name" value={lead.child_name} />
        <Field label="Emirates ID" value={lead.child_emirates_id} />
        <Field label="Emirates ID expiry" value={date(lead.child_emirates_id_expiry)} />
        <Field label="Date of birth" value={date(lead.child_date_of_birth)} />
        <Field label="Gender" value={lead.child_gender} />
        <Field label="Diagnosis" value={lead.diagnosis_suspected} />
        <Field label="Nursery / school" value={lead.nursery_school} />
        <Field label="Main concern" value={lead.main_concern} />
      </Section>

      <Section title="Intake form">
        <Field label="Received on" value={date(lead.intake_form_received_on)} />
        <Field label="Received via" value={lead.intake_form_received_via} />
        <Field label="Allergies" value={lead.allergies} />
        <Field label="Medical history" value={lead.medical_history} />
      </Section>

      <Section title="Consultation / assessment">
        <Field label="Date" value={date(lead.assessment_date)} />
        <Field label="Clinician" value={p.assessment_clinician_name} />
        <Field label="Tool" value={lead.assessment_tool} />
        <Field label="Report ref" value={lead.assessment_report_reference} />
        <Field label="Summary" value={lead.assessment_report_summary} />
      </Section>

      <Section title="Billing authorizations">
        {d.authorizations.length === 0 ? (
          <AppText variant="caption">No funding on file.</AppText>
        ) : (
          d.authorizations.map((a) => (
            <View key={a.id} style={styles.service}>
              <AppText variant="bodyStrong">{a.payer_name}</AppText>
              <AppText variant="caption">
                {a.authorized_hours_total ?? '—'} approved hours · {a.hours_used} used · renewal {date(a.renews_at) ?? '—'}
              </AppText>
            </View>
          ))
        )}
      </Section>

      <Section title="Funding">
        <Field label="Funding type" value={lead.funding_type} />
        <Field label="Insurer / payer" value={lead.funding_insurer} />
        <Field label="Policy number" value={lead.funding_policy_number} />
        <Field label="Approval valid until" value={date(lead.funding_approval_valid_until)} />
        <Field label="Insurance-funded hours" value={p.funding_hours_needed} />
        <Field label="Funding notes" value={lead.funding_notes} />
      </Section>

      <Section title="Package agreed">
        <Field label="Package(s)" value={packages} />
        <Field label="Start date" value={date(lead.package_start_date)} />
        <Field label="Sessions / week" value={lead.package_sessions_per_week} />
        <Field label="Location" value={p.package_location} />
        <Field label="Agreed by" value={lead.package_agreed_by} />
        <Field label="Notes" value={lead.package_scheduling_notes} />
      </Section>

      <Section title="Consent & terms">
        <Field label="Agreement signed" value={date(lead.consent_signed_date)} />
        <Field label="Signed by" value={lead.consent_signed_by} />
        <Field label="Data & photo consent" value={lead.consent_data_photo} />
        <Field label="Method" value={lead.consent_signature_method} />
        <Field label="Notes" value={lead.consent_notes} />
      </Section>

      <Section title="Lead origin">
        <Field label="Source" value={lead.source} />
        <Field label="Campaign" value={lead.campaign} />
        <Field label="First enquiry" value={formatDateTimeShort(lead.created_at)} />
        <Field label="Lead owner" value={p.lead_owner_name} />
      </Section>
    </>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  gap: { gap: spacing.sm },
  row: { flexDirection: 'row', gap: spacing.sm },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.sm },
  stats: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  cardHead: { padding: spacing.lg, paddingBottom: spacing.md },
  emptyPad: { paddingHorizontal: spacing.lg, paddingBottom: spacing.lg },
  invoice: { paddingHorizontal: spacing.lg, paddingVertical: spacing.md, gap: 2 },
  doc: { gap: 4 },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.xs, marginVertical: 2 },
  fields: { marginTop: spacing.sm },
  field: { flexDirection: 'row', gap: spacing.md, paddingVertical: 6, borderTopWidth: 1, borderTopColor: colors.divider },
  fieldLabel: { width: 128 },
  fieldValue: { flex: 1 },
  service: { paddingVertical: 6, borderTopWidth: 1, borderTopColor: colors.divider, gap: 2 },
});
