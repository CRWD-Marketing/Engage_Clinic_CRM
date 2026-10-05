import { Pressable, StyleSheet, View } from 'react-native';

import type { AccessGrantInput, ModuleLevels, RolesAccessPage } from '@/api/types';
import type { ActionKey, Level, ModuleKey } from '@/auth/roles';
import { AppText } from '@/components/AppText';
import { OptionPills } from '@/components/OptionPills';
import { accessChipColors, colors, fonts, radius, spacing } from '@/theme';

/** Laravel sends an empty level map as `[]`. */
export function levelsOf(levels: ModuleLevels | null | undefined): Partial<Record<ModuleKey, Level>> {
  return levels && !Array.isArray(levels) ? levels : {};
}

/**
 * The module and action toggles of the template and access dialogs. A module
 * that is on gets a level (full / own / view / edit); a level means nothing
 * for a module that is off, so it is dropped with it.
 */
export function AccessEditor({
  page,
  value,
  onChange,
  disabled,
  locked = [],
}: {
  page: Pick<RolesAccessPage, 'modules' | 'levels' | 'actions'>;
  value: AccessGrantInput;
  onChange: (value: AccessGrantInput) => void;
  disabled?: boolean;
  /** Grants that can be taken away but not switched on here (Full Admin only). */
  locked?: string[];
}) {
  const modules = Object.keys(page.modules) as ModuleKey[];
  const actions = Object.keys(page.actions) as ActionKey[];
  const levels = Object.keys(page.levels) as Level[];

  const toggleModule = (m: ModuleKey) => {
    const on = value.module_levels;
    if (value.modules.includes(m)) {
      const { [m]: _dropped, ...rest } = on;
      onChange({ ...value, modules: value.modules.filter((x) => x !== m), module_levels: rest });
    } else {
      onChange({ ...value, modules: [...value.modules, m] });
    }
  };
  const toggleAction = (a: ActionKey) =>
    onChange({ ...value, actions: value.actions.includes(a) ? value.actions.filter((x) => x !== a) : [...value.actions, a] });

  return (
    <View style={styles.wrap}>
      <AppText variant="bodyStrong">
        Modules · {value.modules.length} of {modules.length}
      </AppText>
      {modules.map((m) => {
        const on = value.modules.includes(m);
        return (
          <View key={m} style={styles.module}>
            <Toggle label={page.modules[m]} on={on} kind="module" disabled={disabled || (!on && locked.includes(m))} onPress={() => toggleModule(m)} />
            {on ? (
              <OptionPills<Level>
                options={levels.map((l) => ({ value: l, label: page.levels[l] }))}
                value={value.module_levels[m] ?? 'full'}
                onChange={(level) => onChange({ ...value, module_levels: { ...value.module_levels, [m]: level } })}
                disabled={disabled}
              />
            ) : null}
          </View>
        );
      })}

      <AppText variant="bodyStrong" style={styles.section}>
        Actions · {value.actions.length} of {actions.length}
      </AppText>
      <View style={styles.chips}>
        {actions.map((a) => (
          <Toggle
            key={a}
            label={page.actions[a]}
            on={value.actions.includes(a)}
            kind="action"
            disabled={disabled || (!value.actions.includes(a) && locked.includes(a))}
            onPress={() => toggleAction(a)}
          />
        ))}
      </View>
    </View>
  );
}

/** A grant chip: green for a module, pink for an action (`.ra-chip.on` / `.ra-chip.act.on`). */
export function Toggle({ label, on, kind, disabled, onPress }: { label: string; on: boolean; kind: 'module' | 'action'; disabled?: boolean; onPress?: () => void }) {
  const tone = kind === 'module' ? accessChipColors.module : accessChipColors.action;
  return (
    <Pressable
      onPress={onPress}
      disabled={disabled || !onPress}
      accessibilityRole="checkbox"
      accessibilityState={{ checked: on, disabled: !!disabled }}
      accessibilityLabel={label}
      style={[styles.chip, on && { backgroundColor: tone.bg, borderColor: tone.border }]}>
      <AppText style={[styles.chipText, on && { color: tone.fg }]}>
        {on ? '✓ ' : ''}
        {label}
      </AppText>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  wrap: { gap: spacing.sm },
  module: { gap: 6 },
  section: { marginTop: spacing.sm },
  chips: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  chip: {
    alignSelf: 'flex-start',
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: spacing.md,
    paddingVertical: 7,
  },
  chipText: { fontFamily: fonts.bodyBold, fontSize: 12.5, color: colors.textSecondary },
});

/** Accounts only a Full Admin may change (RoleController::SENSITIVE_ROLES). */
export const PROTECTED_ROLES = ['FULL_ADMIN', 'CLINICAL_SUPERVISOR'];
/** Grants only a Full Admin may add (RoleController::PROTECTED_MODULES / PROTECTED_ACTIONS). */
export const PROTECTED_GRANTS = ['billing', 'settings', 'manage_users_roles'];
