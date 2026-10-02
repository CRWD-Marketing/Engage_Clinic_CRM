import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { PatientListItem } from '@/api/types';
import { canWriteIn, levelFor } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useDebouncedValue } from '@/hooks/useDebouncedValue';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, fonts, patientColors, spacing } from '@/theme';

import { hoursLeft } from './patientFormat';

/**
 * Mirrors patient/index.blade.php's mobile layout: patients grouped into
 * "Needs details" and "Active". Therapists only get their own patients
 * (scoped server-side by PatientController::index).
 */
export function PatientsListScreen() {
  const user = useCurrentUser();
  const own = levelFor(user, 'patients') === 'own';
  // PatientController::store(): coordinators and therapists can't add a patient.
  const canAddPatient = canWriteIn(user, 'patients') && user.role !== 'COORDINATOR' && user.role !== 'THERAPIST';
  const [search, setSearch] = useState('');
  const term = useDebouncedValue(search.trim());

  const query = useApiQuery(`patients.list:${term}`, () => api.patients.list(term || undefined));
  useRefetchOnFocus(query.reload);

  const patients = query.data ?? [];
  const groups = [
    {
      label: 'Needs details',
      dot: patientColors.groupDotNeedsDetails,
      items: patients.filter((p) => p.is_profile_incomplete),
    },
    { label: 'Active', dot: patientColors.groupDotActive, items: patients.filter((p) => !p.is_profile_incomplete) },
  ].filter((g) => g.items.length > 0);

  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <View style={styles.header}>
        <AppText variant="title">{own ? 'My patients' : 'Patients'}</AppText>
        <AppText variant="caption">
          {query.data ? `${patients.length} active · ${patients.length} shown` : ' '}
        </AppText>
      </View>

      <TextField
        label="Search"
        icon="search-outline"
        value={search}
        onChangeText={setSearch}
        placeholder="Child, parent or phone"
        autoCapitalize="none"
        autoCorrect={false}
        returnKeyType="search"
        clearButtonMode="while-editing"
      />

      {canAddPatient ? <Button title="+ Add patient" variant="secondary" onPress={() => router.push('/patients/new')} /> : null}

      {!query.data ? (
        query.error ? (
          <ErrorState error={query.error} onRetry={query.refresh} />
        ) : (
          <LoadingState />
        )
      ) : patients.length === 0 ? (
        <Card>
          <EmptyRow
            text={
              term
                ? `No patients match "${term}".`
                : own
                  ? 'No patients assigned to you yet.'
                  : 'No patients yet. Convert an enrolled lead from the Leads pipeline to get started.'
            }
          />
        </Card>
      ) : (
        groups.map((group) => (
          <View key={group.label} style={styles.group}>
            <View style={styles.groupHead}>
              <View style={[styles.groupDot, { backgroundColor: group.dot }]} />
              <AppText variant="label" style={styles.groupTitle}>
                {group.label}
              </AppText>
              <AppText variant="caption">{group.items.length}</AppText>
            </View>
            <Card padded={false}>
              {group.items.map((p, i) => (
                <View key={p.id}>
                  {i > 0 ? <Divider /> : null}
                  <PatientRow patient={p} />
                </View>
              ))}
            </Card>
          </View>
        ))
      )}
    </Screen>
  );
}

function PatientRow({ patient: p }: { patient: PatientListItem }) {
  const lead = p.lead;
  const primary = p.authorizations[0];
  const hasHours = !!primary?.authorized_hours_total;
  const name = lead.child_name ?? 'Unnamed';

  return (
    <Pressable
      onPress={() => router.push({ pathname: '/patients/[id]', params: { id: String(p.id) } })}
      style={({ pressed }) => [styles.row, pressed && styles.pressed]}
      accessibilityRole="button"
      accessibilityLabel={`${name}, ${p.is_profile_incomplete ? 'needs details' : 'active'}`}>
      <Avatar id={p.lead_id} name={name} size={42} />
      <View style={styles.rowMain}>
        <View style={styles.rowTop}>
          <AppText variant="bodyStrong" numberOfLines={1} style={styles.name}>
            {name}
            {lead.child_age ? ` · ${lead.child_age}` : ''}
          </AppText>
          <Chip
            label={p.is_profile_incomplete ? 'Needs details' : 'Active'}
            colors={p.is_profile_incomplete ? patientColors.needsDetailsBadge : patientColors.activeBadge}
          />
        </View>
        <View style={styles.rowBottom}>
          <AppText variant="caption" numberOfLines={1} style={styles.line}>
            {lead.parent_guardian_name ?? 'No parent on file'} · {lead.phone ?? 'no phone'}
          </AppText>
          <AppText style={styles.meta}>
            {hasHours ? `${hoursLeft(primary)}/${primary.authorized_hours_total}h` : 'No auth'}
          </AppText>
        </View>
      </View>
      <Ionicons name="chevron-forward" size={16} color={colors.textFaint} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  header: { gap: 2, marginBottom: spacing.xs },
  group: { gap: spacing.sm },
  groupHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingHorizontal: spacing.xs },
  groupDot: { width: 8, height: 8, borderRadius: 4 },
  groupTitle: { flex: 1 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    minHeight: 64,
  },
  pressed: { backgroundColor: colors.pageAlt },
  rowMain: { flex: 1, minWidth: 0, gap: 3 },
  rowTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  name: { flex: 1 },
  rowBottom: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  line: { flex: 1 },
  meta: { fontFamily: fonts.bodyBold, fontSize: 11.5, color: patientColors.listMeta },
});
