import Ionicons from '@expo/vector-icons/Ionicons';
import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { FundingServiceRow, IntakeOptions, LeadDetail, LeadUpdateRequest, PackageOption } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, patientColors, radius, spacing } from '@/theme';
import { isoToYmd } from '@/utils/dates';

import { INTAKE_STEPS, optionsFor, type IntakeField, type IntakeStep } from './intakeSteps';

/** A funding service row while it is being edited (numbers as typed text). */
type ServiceDraft = {
  service: string | null;
  payer: FundingServiceRow['payer'] | null;
  hours: string;
  approved: string;
  reference: string;
};

type Value = string | number | null | number[] | ServiceDraft[];
type Values = Record<string, Value>;

const DATE = /^\d{4}-\d{2}-\d{2}$/;
const blankService = (): ServiceDraft => ({ service: null, payer: null, hours: '', approved: '', reference: '' });
const serviceComplete = (r: ServiceDraft) => !!r.service && !!r.payer && r.hours.trim() !== '';

/** One intake checklist step as a form (the web's "ic-modal"s). Saves via PUT /admin/leads/{id} with `intake_step`. */
export function IntakeStepScreen() {
  const { id, step: stepKey } = useLocalSearchParams<{ id: string; step: string }>();
  const detail = useApiQuery(`leads.show:${id}`, () => api.leads.show(Number(id)));
  const board = useApiQuery('leads.board', () => api.leads.board());
  const step = INTAKE_STEPS.find((s) => s.key === stepKey);

  if (!step) {
    return (
      <Screen scroll={false} edges={[]}>
        <ErrorState error={new Error('Unknown intake step')} />
      </Screen>
    );
  }
  if (!detail.data || !board.data) {
    const error = detail.error ?? board.error;
    return (
      <Screen scroll={false} edges={[]}>
        <Stack.Screen options={{ title: step.title }} />
        {error ? <ErrorState error={error} onRetry={() => { detail.refresh(); board.refresh(); }} /> : <LoadingState />}
      </Screen>
    );
  }

  return <StepForm step={step} detail={detail.data} options={board.data.intake_options} />;
}

/** Form values for a step, read from the lead. */
function initialValues(step: IntakeStep, detail: LeadDetail): Values {
  const lead = detail.lead as unknown as Record<string, unknown>;
  const values: Values = {};
  for (const field of step.fields) {
    const raw = lead[field.name];
    switch (field.kind) {
      case 'date':
        values[field.name] = typeof raw === 'string' ? isoToYmd(raw) : '';
        break;
      case 'services': {
        const rows = (detail.lead.funding_services_needed ?? []).map<ServiceDraft>((r) => ({
          service: r.service,
          payer: r.payer,
          hours: r.hours_per_week === null ? '' : String(r.hours_per_week),
          approved: r.approved_hours === null ? '' : String(r.approved_hours),
          reference: r.approval_reference ?? '',
        }));
        values[field.name] = rows.length > 0 ? rows : [blankService()];
        break;
      }
      case 'packages':
        values[field.name] = detail.lead.package_ids ?? [];
        break;
      case 'select':
      case 'select-insurer':
      case 'select-clinician':
      case 'select-location':
        values[field.name] = (raw as string | number | null) ?? null;
        break;
      default:
        values[field.name] = raw === null || raw === undefined ? '' : String(raw);
    }
  }
  return values;
}

function validate(step: IntakeStep, values: Values): Record<string, string> {
  const errors: Record<string, string> = {};
  for (const field of step.fields) {
    const value = values[field.name];
    if (field.kind === 'services') {
      if (!(value as ServiceDraft[]).some(serviceComplete)) errors[field.name] = 'Add at least one service with its hours and payer.';
      continue;
    }
    if (field.kind === 'packages') {
      if ((value as number[]).length === 0) errors[field.name] = 'Pick at least one package.';
      continue;
    }
    const empty = value === null || value === '';
    if (field.required && empty) errors[field.name] = 'Required';
    else if (field.kind === 'date' && !empty && !DATE.test(String(value))) errors[field.name] = 'Use the format YYYY-MM-DD.';
    else if (field.kind === 'number' && !empty && !/^\d+$/.test(String(value))) errors[field.name] = 'Enter a whole number.';
  }
  return errors;
}

function toRequest(step: IntakeStep, values: Values): LeadUpdateRequest {
  const body: Record<string, unknown> = { intake_step: step.key };
  for (const field of step.fields) {
    const value = values[field.name];
    if (field.kind === 'services') {
      body[field.name] = (value as ServiceDraft[]).filter(serviceComplete).map<FundingServiceRow>((r) => ({
        service: r.service!,
        payer: r.payer!,
        hours_per_week: Number(r.hours),
        approved_hours: r.payer === 'Insurance' && r.approved.trim() ? Number(r.approved) : null,
        approval_reference: r.payer === 'Insurance' && r.reference.trim() ? r.reference.trim() : null,
      }));
    } else if (field.kind === 'number') {
      body[field.name] = value === '' ? null : Number(value);
    } else {
      body[field.name] = value;
    }
  }
  return body as LeadUpdateRequest;
}

function StepForm({ step, detail, options }: { step: IntakeStep; detail: LeadDetail; options: IntakeOptions }) {
  const [values, setValues] = useState<Values>(() => initialValues(step, detail));
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const set = (name: string, value: Value) => setValues((v) => ({ ...v, [name]: value }));

  async function save() {
    const found = validate(step, values);
    setErrors(found);
    setFormError(null);
    if (Object.keys(found).length > 0) return;

    setSaving(true);
    try {
      await api.leads.update(detail.lead.id, toRequest(step, values));
      // The lead screen reloads when it regains focus and shows the step as done.
      router.back();
    } catch (e) {
      setFormError(errorMessage(e));
      setSaving(false);
    }
  }

  return (
    <Screen edges={['bottom']}>
      <Stack.Screen options={{ title: step.title }} />
      <Card style={styles.card}>
        <View>
          <AppText variant="heading">{step.title}</AppText>
          <AppText variant="caption">
            {step.subtitle} · {detail.lead.child_name ?? 'Lead'}
          </AppText>
        </View>

        {step.fields.map((field) => (
          <FieldInput
            key={field.name}
            field={field}
            value={values[field.name]}
            error={errors[field.name]}
            options={options}
            onChange={(v) => set(field.name, v)}
          />
        ))}

        {formError ? <Banner text={formError} /> : null}
        {Object.keys(errors).length > 0 ? <Banner text="Fill every field marked * to complete this step" /> : null}
        <Button title="Save & close" onPress={save} loading={saving} />
        <Button title="Cancel" variant="secondary" onPress={() => router.back()} disabled={saving} />
      </Card>
    </Screen>
  );
}

function FieldInput({
  field,
  value,
  error,
  options,
  onChange,
}: {
  field: IntakeField;
  value: Value;
  error: string | undefined;
  options: IntakeOptions;
  onChange: (v: Value) => void;
}) {
  const label = `${field.label}${field.required ? ' *' : ''}`;

  switch (field.kind) {
    case 'services':
      return <ServicesEditor label={label} rows={value as ServiceDraft[]} services={options.services} error={error} onChange={onChange} />;
    case 'packages':
      return <PackagesPicker label={label} selected={value as number[]} packages={options.packages} error={error} onChange={onChange} />;
    case 'select':
    case 'select-insurer':
    case 'select-clinician':
    case 'select-location':
      return (
        <View style={styles.group}>
          <OptionPills
            label={label}
            options={optionsFor(field, options)}
            value={value as string | number | null}
            onChange={(v) => onChange(v === value && !field.required ? null : v)}
          />
          {error ? <FieldError text={error} /> : null}
        </View>
      );
    default:
      return (
        <TextField
          label={field.kind === 'date' ? `${label} (YYYY-MM-DD)` : label}
          value={String(value ?? '')}
          onChangeText={onChange}
          error={error}
          placeholder={field.placeholder}
          multiline={field.kind === 'multiline'}
          autoCapitalize={field.kind === 'email' ? 'none' : undefined}
          keyboardType={
            field.kind === 'email'
              ? 'email-address'
              : field.kind === 'phone'
                ? 'phone-pad'
                : field.kind === 'number'
                  ? 'number-pad'
                  : field.kind === 'date'
                    ? 'numbers-and-punctuation'
                    : 'default'
          }
        />
      );
  }
}

function FieldError({ text }: { text: string }) {
  return (
    <AppText variant="caption" color={colors.danger}>
      {text}
    </AppText>
  );
}

/** "Services needed — who pays for each": one card per service row. */
function ServicesEditor({
  label,
  rows,
  services,
  error,
  onChange,
}: {
  label: string;
  rows: ServiceDraft[];
  services: string[];
  error: string | undefined;
  onChange: (rows: ServiceDraft[]) => void;
}) {
  const update = (i: number, patch: Partial<ServiceDraft>) => onChange(rows.map((r, n) => (n === i ? { ...r, ...patch } : r)));

  return (
    <View style={styles.group}>
      <AppText variant="bodyStrong" style={styles.label}>
        {label}
      </AppText>
      {rows.map((row, i) => (
        <View key={i} style={styles.serviceRow}>
          <View style={styles.serviceHead}>
            <AppText variant="bodyStrong" style={styles.flex}>
              Service {i + 1}
            </AppText>
            {!serviceComplete(row) ? <AppText variant="caption">Needs service, hours and payer</AppText> : null}
            {rows.length > 1 ? (
              <Pressable
                onPress={() => onChange(rows.filter((_, n) => n !== i))}
                accessibilityRole="button"
                accessibilityLabel={`Remove service ${i + 1}`}
                hitSlop={8}>
                <Ionicons name="close" size={18} color={colors.textMuted} />
              </Pressable>
            ) : null}
          </View>
          <OptionPills
            label="Service / therapy"
            options={services.map((s) => ({ value: s, label: s }))}
            value={row.service}
            onChange={(service) => update(i, { service })}
          />
          <OptionPills
            label="Paid by"
            options={[
              { value: 'Insurance', label: 'Insurance' },
              { value: 'Self pay', label: 'Self pay' },
            ]}
            value={row.payer}
            onChange={(payer) => update(i, { payer: payer as ServiceDraft['payer'] })}
          />
          <TextField label="Hours / week" value={row.hours} onChangeText={(hours) => update(i, { hours })} keyboardType="number-pad" />
          {row.payer === 'Insurance' ? (
            <>
              <TextField
                label="Approved hours"
                value={row.approved}
                onChangeText={(approved) => update(i, { approved })}
                keyboardType="number-pad"
                placeholder="e.g. 96"
              />
              <TextField
                label="Approval reference"
                value={row.reference}
                onChangeText={(reference) => update(i, { reference })}
                placeholder="e.g. PA-2026-77341"
              />
            </>
          ) : null}
        </View>
      ))}
      <Pressable onPress={() => onChange([...rows, blankService()])} accessibilityRole="button" style={styles.dashed}>
        <AppText variant="link">+ Add another service</AppText>
      </Pressable>
      {error ? <FieldError text={error} /> : null}
    </View>
  );
}

/** "Package(s) agreed — pick one or more": searchable checkbox list. */
function PackagesPicker({
  label,
  selected,
  packages,
  error,
  onChange,
}: {
  label: string;
  selected: number[];
  packages: PackageOption[];
  error: string | undefined;
  onChange: (ids: number[]) => void;
}) {
  const [search, setSearch] = useState('');
  const term = search.trim().toLowerCase();
  const visible = term ? packages.filter((p) => p.name.toLowerCase().includes(term)) : packages;

  return (
    <View style={styles.group}>
      <AppText variant="bodyStrong" style={styles.label}>
        {label}
        {selected.length > 0 ? ` · ${selected.length} selected` : ''}
      </AppText>
      <TextField label="Search packages" icon="search-outline" value={search} onChangeText={setSearch} placeholder="Search packages…" />
      {visible.map((p) => {
        const checked = selected.includes(p.id);
        return (
          <Pressable
            key={p.id}
            onPress={() => onChange(checked ? selected.filter((x) => x !== p.id) : [...selected, p.id])}
            accessibilityRole="checkbox"
            accessibilityState={{ checked }}
            style={[styles.package, checked && styles.packageChecked]}>
            <Ionicons name={checked ? 'checkbox' : 'square-outline'} size={22} color={checked ? colors.pink : colors.textFaint} />
            <View style={styles.flex}>
              <AppText variant="bodyStrong">{p.name}</AppText>
              <AppText variant="caption">{p.summary}</AppText>
            </View>
          </Pressable>
        );
      })}
      {error ? <FieldError text={error} /> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  card: { gap: spacing.md },
  group: { gap: spacing.sm },
  label: { fontSize: 13 },
  serviceRow: {
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radius.input,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.pageAlt,
  },
  serviceHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  dashed: {
    borderWidth: 1.5,
    borderStyle: 'dashed',
    borderColor: colors.pink,
    borderRadius: radius.input,
    paddingVertical: spacing.md,
    alignItems: 'center',
  },
  package: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radius.input,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
  },
  packageChecked: { borderColor: colors.pink, backgroundColor: patientColors.goalSelectedBg },
});
