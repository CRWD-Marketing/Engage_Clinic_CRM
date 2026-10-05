import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Linking, Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { VendorPage, VendorStatus } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, CardHeader } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { leaveFlash } from '@/hooks/useReturnFlash';
import { colors, spacing } from '@/theme';
import { formatDayMonthYear, isoToYmd, timeAgo } from '@/utils/dates';
import { formatMoney } from '@/utils/format';
import { openPdf } from '@/utils/openPdf';

import { COMPLIANCE_LABELS, vendorBadge } from './vendorBadge';

type Flash = { text: string; tone: 'success' | 'danger' } | null;
const STATE_LABELS = { valid: 'Valid', expiring: 'Expiring soon', expired: 'Expired', pending: 'Pending expiry date', on_file: 'On file' } as const;

/** vendor/show.blade.php: the registration, compliance documents, and the fields staff manage. */
export function VendorScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const query = useApiQuery(`vendors.show:${id}`, () => api.vendors.show(Number(id)));
  const [flash, setFlash] = useState<Flash>(null);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  return (
    <Detail
      key={`${query.data.id}:${query.data.status}:${query.data.is_critical}:${query.data.internal_owner_id}:${query.data.notes}`}
      vendor={query.data}
      flash={flash}
      setFlash={setFlash}
      refreshing={query.refreshing}
      onRefresh={query.refresh}
      onChanged={query.reload}
    />
  );
}

function Detail({
  vendor: v,
  flash,
  setFlash,
  refreshing,
  onRefresh,
  onChanged,
}: {
  vendor: VendorPage;
  flash: Flash;
  setFlash: (f: Flash) => void;
  refreshing: boolean;
  onRefresh: () => void;
  onChanged: () => void;
}) {
  const [status, setStatus] = useState<VendorStatus>(v.status);
  const [critical, setCritical] = useState(v.is_critical);
  const [ownerId, setOwnerId] = useState<number | null>(v.internal_owner_id);
  const [notes, setNotes] = useState(v.notes ?? '');
  const [busy, setBusy] = useState(false);
  const [confirming, setConfirming] = useState(false);
  const date = (d: string | null) => (d ? formatDayMonthYear(d) : null);
  const yesNo = (b: boolean | null) => (b === null ? null : b ? 'Yes' : 'No');

  async function save() {
    setBusy(true);
    setFlash(null);
    try {
      const res = await api.vendors.update(v.id, { status, is_critical: critical, internal_owner_id: ownerId, notes: notes || null });
      setFlash({ text: res.message, tone: 'success' });
      onChanged();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  async function remove() {
    setBusy(true);
    setFlash(null);
    try {
      const res = await api.vendors.destroy(v.id);
      leaveFlash('vendors', res.message);
      router.back();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
      setBusy(false);
    }
  }

  async function openDocument(documentId: number, name: string) {
    setFlash(null);
    try {
      await openPdf(await api.vendors.document(v.id, documentId, name));
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    }
  }

  const sections: [string, [string, string | null][]][] = [
    ['Company details', [
      ['Registered company name', v.legal_name],
      ['Trade name', v.trade_name],
      ['Website', v.website],
      ['Address', v.address],
      ['City', v.city],
      ['Emirate', v.emirate],
    ]],
    ['Contact person', [
      ['Full name', v.contact_name],
      ['Position', v.position],
      ['Mobile number', v.phone],
      ['Email', v.email],
      ['Alternate contact', v.alt_contact],
      ['Accounts / finance email', v.finance_email],
    ]],
    ['Category & services', [
      ['Vendor category', v.category_label],
      ['Primary service or product', v.primary_service],
      ['Description of offer', v.description],
      ['Years in business', v.years_in_business],
      ['Referred by', v.referred_by],
    ]],
    ['Commercial terms', [
      ['Pricing / rate', v.pricing],
      ['Estimated monthly cost', v.monthly_cost !== null ? `AED ${formatMoney(v.monthly_cost)}` : null],
      ['Payment terms', v.payment_terms],
      ['Preferred payment method', v.payment_method],
      ['VAT registered', yesNo(v.vat_registered)],
      ['TRN', v.trn],
      ['Works against purchase orders', yesNo(v.accepts_po)],
      ['Proposed contract start', date(v.contract_start)],
      ['Proposed contract end', date(v.contract_end)],
    ]],
    ['Bank details', [
      ['Bank name', v.bank_name],
      ['Account holder name', v.account_holder],
      ['IBAN', v.iban],
      ['SWIFT / BIC', v.swift],
    ]],
    ['Declaration', [
      ['Declarations', 'All three accepted'],
      ['Authorised signatory', v.signatory_name],
      ['Signed', v.declared_at ? formatDayMonthYear(isoToYmd(v.declared_at)) : null],
    ]],
  ];

  return (
    <Screen edges={[]} refreshing={refreshing} onRefresh={onRefresh}>
      <Stack.Screen options={{ title: v.reference ?? 'Vendor' }} />

      <Card style={styles.gap}>
        <View style={styles.head}>
          <Avatar id={v.legal_name} name={v.legal_name} size={52} />
          <View style={styles.flex}>
            <AppText variant="heading">{v.legal_name}</AppText>
            <AppText variant="caption">
              {v.reference} · {v.category_label}
            </AppText>
            <AppText variant="caption">Submitted {timeAgo(v.created_at)}</AppText>
          </View>
        </View>
        <View style={styles.chips}>
          <Chip label={v.status_label} colors={vendorBadge(v.status)} />
          {v.is_critical ? <Chip label="Critical vendor" colors={vendorBadge('critical')} /> : null}
        </View>
        <View style={styles.contact}>
          <Button title={`Email ${v.contact_name}`} variant="secondary" onPress={() => Linking.openURL(`mailto:${v.email}`)} />
          <Button title={`Call ${v.phone}`} variant="secondary" onPress={() => Linking.openURL(`tel:${v.phone.replace(/[^0-9+]/g, '')}`)} />
        </View>
        <View style={styles.contact}>
          <Button title="Newer" variant="secondary" disabled={!v.neighbours.newer} onPress={() => router.replace({ pathname: '/vendors/[id]', params: { id: String(v.neighbours.newer) } })} />
          <Button title="Older" variant="secondary" disabled={!v.neighbours.older} onPress={() => router.replace({ pathname: '/vendors/[id]', params: { id: String(v.neighbours.older) } })} />
        </View>
      </Card>

      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      <Card style={styles.gap}>
        <View style={styles.head}>
          <View style={styles.flex}>
            <CardHeader title="Compliance documents" />
          </View>
          <Chip label={`Compliance: ${COMPLIANCE_LABELS[v.compliance_status]}`} colors={vendorBadge(v.compliance_status)} />
        </View>
        {v.documents.length === 0 ? <AppText variant="caption">No documents uploaded.</AppText> : null}
        {v.documents.map((doc) => (
          <View key={doc.id} style={styles.doc}>
            <View style={styles.head}>
              <View style={styles.flex}>
                <AppText variant="bodyStrong">{doc.label}</AppText>
                <AppText variant="caption">
                  {[doc.document_number ? `No. ${doc.document_number}` : null, doc.issue_date ? `Issued ${formatDayMonthYear(doc.issue_date)}` : null, doc.expiry_date ? `Expires ${formatDayMonthYear(doc.expiry_date)}` : null]
                    .filter(Boolean)
                    .join(' · ') || doc.file_name}
                </AppText>
              </View>
              <Chip
                label={doc.state === 'expiring' ? `Expires in ${doc.days_to_expiry} day${doc.days_to_expiry === 1 ? '' : 's'}` : STATE_LABELS[doc.state]}
                colors={vendorBadge(doc.state)}
              />
            </View>
            <Pressable onPress={() => openDocument(doc.id, doc.file_name ?? `${doc.label}.pdf`)} accessibilityRole="button" accessibilityLabel={`Open ${doc.label}`}>
              <AppText variant="link">Open document →</AppText>
            </Pressable>
          </View>
        ))}
      </Card>

      {sections.map(([title, fields]) => (
        <Card key={title} style={styles.gap}>
          <CardHeader title={title} />
          {fields.map(([label, value]) => (
            <View key={label} style={styles.field}>
              <AppText variant="caption">{label}</AppText>
              <AppText variant="bodyStrong" color={value ? undefined : colors.textMuted} selectable={label === 'IBAN'}>
                {value || 'Not provided'}
              </AppText>
            </View>
          ))}
        </Card>
      ))}

      <Card style={styles.gap}>
        <CardHeader title="Managed by the clinic" />
        <OptionPills<VendorStatus>
          label="Status"
          options={(Object.keys(v.statuses) as VendorStatus[]).map((s) => ({ value: s, label: v.statuses[s] }))}
          value={status}
          onChange={setStatus}
          disabled={!v.can_edit}
        />
        <OptionPills
          label="Critical vendor — affects patient safety, compliance or essential services"
          options={[
            { value: 'yes', label: 'Critical' },
            { value: 'no', label: 'Not critical' },
          ]}
          value={critical ? 'yes' : 'no'}
          onChange={(x) => setCritical(x === 'yes')}
          disabled={!v.can_edit}
        />
        <OptionPills<number | null>
          label="Internal owner"
          options={[{ value: null, label: 'Unassigned' }, ...v.owners.map((o) => ({ value: o.id, label: o.name }))]}
          value={ownerId}
          onChange={setOwnerId}
          disabled={!v.can_edit}
        />
        <TextField
          label="Internal notes"
          value={notes}
          onChangeText={setNotes}
          multiline
          maxLength={5000}
          placeholder="Due diligence, approvals, follow-ups…"
          editable={v.can_edit}
        />
        {v.can_edit ? <Button title="Save changes" loading={busy} onPress={save} /> : null}
      </Card>

      {v.can_edit ? (
        <>
          <Button title="Delete vendor" variant="secondary" onPress={() => setConfirming(!confirming)} />
          {confirming ? (
            <Card style={styles.gap}>
              <AppText variant="bodyStrong">Delete this vendor?</AppText>
              <AppText variant="caption">This removes the registration and its uploaded documents.</AppText>
              <Button title="Delete" loading={busy} onPress={remove} />
            </Card>
          ) : null}
        </>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  gap: { gap: spacing.sm },
  head: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  contact: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  doc: { gap: 6, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  field: { paddingVertical: 4, gap: 2 },
});
