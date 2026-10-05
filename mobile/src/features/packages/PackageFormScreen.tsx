import { router, Stack, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { StyleSheet } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { PackageRow, PackagesPage } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { OptionPills } from '@/components/OptionPills';
import { Screen } from '@/components/Screen';
import { Banner, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { leaveFlash } from '@/hooks/useReturnFlash';
import { colors, spacing } from '@/theme';
import { formatMoney } from '@/utils/format';

/** The New / Edit package modal of package/index.blade.php. `id` means edit. */
export function PackageFormScreen() {
  const { id } = useLocalSearchParams<{ id?: string }>();
  const query = useApiQuery('packages.list', () => api.packages.list());

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }
  const pkg = id ? (query.data.packages.find((p) => p.id === Number(id)) ?? null) : null;
  return <Form page={query.data} pkg={pkg} />;
}

function Form({ page: d, pkg }: { page: PackagesPage; pkg: PackageRow | null }) {
  const [name, setName] = useState(pkg?.name ?? '');
  const [serviceId, setServiceId] = useState<number | null>(pkg?.service_id ?? null);
  const [locationId, setLocationId] = useState<number | null>(pkg?.location_id ?? null);
  // New packages start as Self pay, Home base (pkOpenCreate()).
  const [funding, setFunding] = useState(pkg?.funding_type ?? 'Self pay');
  const [mode, setMode] = useState(pkg?.delivery_mode ?? 'Home base');
  const [hours, setHours] = useState(pkg ? String(pkg.hours_per_week) : '');
  const [rate, setRate] = useState(pkg ? String(pkg.rate) : '');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
  const [confirming, setConfirming] = useState(false);
  const total = Math.round((parseFloat(hours) || 0) * (parseFloat(rate) || 0));

  async function save() {
    setBusy(true);
    setError(null);
    setFieldErrors({});
    const input = { name, service_id: serviceId, location_id: locationId, funding_type: funding, delivery_mode: mode, hours_per_week: hours || null, rate: rate || null };
    try {
      const res = pkg ? await api.packages.update(pkg.id, input) : await api.packages.store(input);
      leaveFlash('packages', res.message);
      router.back();
    } catch (e) {
      setError(errorMessage(e));
      if (isApiError(e)) setFieldErrors(Object.fromEntries(Object.keys(e.errors).map((k) => [k, e.fieldError(k)!])));
      setBusy(false);
    }
  }

  async function remove() {
    if (!pkg) return;
    setBusy(true);
    setError(null);
    try {
      const res = await api.packages.destroy(pkg.id);
      leaveFlash('packages', res.message);
      router.back();
    } catch (e) {
      setError(errorMessage(e));
      setBusy(false);
    }
  }

  return (
    <Screen edges={[]}>
      <Stack.Screen options={{ title: pkg ? 'Edit package' : 'New package' }} />
      {error ? <Banner text={error} /> : null}

      <Card style={styles.section}>
        <TextField label="Package name" required value={name} onChangeText={setName} placeholder="e.g. P-20 hrs ABA and 10 hr speech" error={fieldErrors.name} />
        <OptionPills<number | null>
          label="Service"
          options={[{ value: null, label: 'None' }, ...d.services.map((s) => ({ value: s.id, label: s.name }))]}
          value={serviceId}
          onChange={setServiceId}
        />
        <OptionPills<number | null>
          label="Location"
          options={[{ value: null, label: 'None' }, ...d.locations.map((l) => ({ value: l.id, label: l.name }))]}
          value={locationId}
          onChange={setLocationId}
        />
        <OptionPills label="Funding" options={d.funding_types.map((f) => ({ value: f, label: f }))} value={funding} onChange={setFunding} />
        <OptionPills label="Setting" options={d.delivery_modes.map((m) => ({ value: m, label: m }))} value={mode} onChange={setMode} />
        <TextField label="Hours" value={hours} onChangeText={setHours} keyboardType="decimal-pad" placeholder="20" error={fieldErrors.hours_per_week} />
        <TextField label="Rate per hour (AED)" value={rate} onChangeText={setRate} keyboardType="decimal-pad" placeholder="450" error={fieldErrors.rate} />
        <AppText variant="bodyStrong">Total excl. VAT: AED {formatMoney(total, 0)}</AppText>
        <AppText variant="caption" color={colors.textMuted}>
          VAT is added at billing time.
        </AppText>
      </Card>

      <Button title={pkg ? 'Save changes' : 'Create package'} loading={busy} onPress={save} />
      {pkg ? (
        <>
          <Button title="Remove package" variant="secondary" onPress={() => setConfirming(!confirming)} />
          {confirming ? (
            <Card style={styles.section}>
              <AppText variant="bodyStrong">Remove this package?</AppText>
              <Button title="Remove" loading={busy} onPress={remove} />
            </Card>
          ) : null}
        </>
      ) : null}
    </Screen>
  );
}

const styles = StyleSheet.create({
  section: { gap: spacing.md },
});
