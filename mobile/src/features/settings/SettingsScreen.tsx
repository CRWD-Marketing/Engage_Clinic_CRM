import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { SettingsItem, SettingsKind } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, spacing, userStatusColors } from '@/theme';

type Flash = { text: string; tone: 'success' | 'danger' } | null;
/** Which form is open: adding to a list, or editing / removing one item. */
type Editing = { kind: SettingsKind; item: SettingsItem | null; removing?: boolean } | null;

const SECTIONS: { kind: SettingsKind; title: string; caption: string; noun: string; placeholder: string }[] = [
  { kind: 'services', title: 'Services', caption: 'Picked when building a package', noun: 'service', placeholder: 'e.g. ABA therapy session' },
  { kind: 'locations', title: 'Locations', caption: 'Service zones a package is delivered in', noun: 'location', placeholder: 'e.g. Inside Abu Dhabi' },
  { kind: 'insurances', title: 'Insurances', caption: 'Payers for authorizations, with their usual coverage', noun: 'insurance', placeholder: 'e.g. Daman Enhanced' },
];

/** setting/index.blade.php: services, locations and insurances. */
export function SettingsScreen() {
  const query = useApiQuery('settings.page', () => api.settings.page());
  const [flash, setFlash] = useState<Flash>(null);
  const [editing, setEditing] = useState<Editing>(null);
  const [busy, setBusy] = useState(false);

  if (!query.data) {
    return (
      <Screen scroll={false} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  async function run(action: () => Promise<{ message: string }>) {
    setBusy(true);
    setFlash(null);
    try {
      const res = await action();
      setFlash({ text: res.message, tone: 'success' });
      setEditing(null);
      query.reload();
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusy(false);
    }
  }

  const d = query.data;
  return (
    <Screen edges={[]} refreshing={query.refreshing} onRefresh={query.refresh}>
      <AppText variant="caption">The options packages and authorizations pick from. Inactive ones are kept but no longer offered.</AppText>
      {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

      {SECTIONS.map((section) => {
        const items = d[section.kind];
        const adding = editing?.kind === section.kind && editing.item === null;
        return (
          <Card key={section.kind} padded={false}>
            <View style={styles.header}>
              <CardHeader title={section.title} />
              <AppText variant="caption">{section.caption}</AppText>
              <Button
                title={`Add ${section.noun}`}
                variant={adding ? 'primary' : 'secondary'}
                onPress={() => setEditing(adding ? null : { kind: section.kind, item: null })}
              />
              {adding ? (
                <ItemForm
                  kind={section.kind}
                  item={null}
                  placeholder={section.placeholder}
                  busy={busy}
                  onSave={(input) => run(() => api.settings.store(section.kind, input))}
                />
              ) : null}
            </View>
            {items.length === 0 ? (
              <EmptyRow text={`No ${section.kind} yet.`} />
            ) : (
              items.map((item) => {
                const open = editing?.kind === section.kind && editing.item?.id === item.id;
                return (
                  <View key={item.id}>
                    <Divider />
                    <View style={styles.row}>
                      <Pressable
                        style={styles.flex}
                        onPress={() => setEditing(open ? null : { kind: section.kind, item })}
                        accessibilityRole="button"
                        accessibilityLabel={`Edit ${item.name}`}>
                        <AppText variant="bodyStrong" color={item.is_active ? undefined : colors.textMuted}>
                          {item.name}
                        </AppText>
                        {item.default_coverage_percent !== undefined ? (
                          <AppText variant="caption">Default coverage {item.default_coverage_percent}%</AppText>
                        ) : null}
                      </Pressable>
                      <Pressable
                        onPress={() => run(() => api.settings.toggle(section.kind, item.id))}
                        disabled={busy}
                        accessibilityRole="switch"
                        accessibilityState={{ checked: item.is_active }}
                        accessibilityLabel={`${item.name} active`}>
                        <Chip label={item.is_active ? 'Active' : 'Inactive'} colors={userStatusColors[item.is_active ? 'active' : 'inactive']} />
                      </Pressable>
                    </View>
                    {open ? (
                      <View style={styles.inline}>
                        <ItemForm
                          kind={section.kind}
                          item={item}
                          placeholder={section.placeholder}
                          busy={busy}
                          onSave={(input) => run(() => api.settings.update(section.kind, item.id, input))}
                        />
                        <Button title={`Remove ${section.noun}`} variant="secondary" onPress={() => setEditing({ ...editing!, removing: !editing?.removing })} />
                        {editing?.removing ? (
                          <View style={styles.confirm}>
                            <AppText variant="bodyStrong">Remove {item.name}?</AppText>
                            <AppText variant="caption">Packages and invoices that used it keep their details, without the link.</AppText>
                            <Button title="Remove" loading={busy} onPress={() => run(() => api.settings.destroy(section.kind, item.id))} />
                          </View>
                        ) : null}
                      </View>
                    ) : null}
                  </View>
                );
              })
            )}
          </Card>
        );
      })}
    </Screen>
  );
}

function ItemForm({
  kind,
  item,
  placeholder,
  busy,
  onSave,
}: {
  kind: SettingsKind;
  item: SettingsItem | null;
  placeholder: string;
  busy: boolean;
  onSave: (input: { name: string; default_coverage_percent?: string }) => void;
}) {
  const [name, setName] = useState(item?.name ?? '');
  const [coverage, setCoverage] = useState(item?.default_coverage_percent !== undefined ? String(item.default_coverage_percent) : '');
  const insurance = kind === 'insurances';
  return (
    <View style={styles.form}>
      <TextField label="Name" required value={name} onChangeText={setName} placeholder={placeholder} />
      {insurance ? (
        <TextField label="Default coverage (%)" required value={coverage} onChangeText={setCoverage} keyboardType="number-pad" placeholder="e.g. 80" />
      ) : null}
      <Button title={item ? 'Save' : 'Add'} loading={busy} onPress={() => onSave(insurance ? { name, default_coverage_percent: coverage } : { name })} />
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1, minWidth: 0 },
  header: { gap: spacing.sm, paddingHorizontal: spacing.lg, paddingTop: spacing.lg, paddingBottom: spacing.md },
  row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, paddingVertical: spacing.md },
  inline: { gap: spacing.sm, paddingHorizontal: spacing.lg, paddingBottom: spacing.md },
  form: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
  confirm: { gap: spacing.sm, padding: spacing.md, borderRadius: 10, backgroundColor: colors.pageAlt },
});
