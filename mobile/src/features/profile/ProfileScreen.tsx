import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useEffect, useRef, useState, type ComponentProps } from 'react';
import { Pressable, StyleSheet, View, type TextInput } from 'react-native';

import { api } from '@/api/client';
import { PROFILE_TIMEZONES } from '@/api/constants';
import { errorMessage, isApiError } from '@/api/errors';
import type { ProfileUpdateRequest, User } from '@/api/types';
import { effectiveModules, levelFor } from '@/auth/permissions';
import { LEGACY_ROLE_PERMISSIONS, MODULES, type ModuleKey } from '@/auth/roles';
import { useCurrentUser, useSession } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card, Divider } from '@/components/Card';
import { Chip } from '@/components/Chip';
import { Screen } from '@/components/Screen';
import { Banner } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { colors, fonts, neutralChip, profileColors, radius, spacing } from '@/theme';
import { timeAgo } from '@/utils/dates';
import { fullName, roleLabel } from '@/utils/format';

type IconName = ComponentProps<typeof Ionicons>['name'];
type FormValues = Record<keyof ProfileUpdateRequest, string>;
type FieldErrors = Partial<Record<keyof ProfileUpdateRequest, string>>;

const TOTAL_MODULES = Object.keys(MODULES).length;

/** Mirrors resources/views/profile/index.blade.php (GET/PUT /profile). */
export function ProfileScreen() {
  const user = useCurrentUser();
  const { signOut } = useSession();
  const [signingOut, setSigningOut] = useState(false);

  return (
    <Screen>
      <View style={styles.header}>
        <AppText variant="title">My profile</AppText>
        <AppText variant="caption">Your details — role and permissions are managed by an admin</AppText>
      </View>

      <SummaryCard user={user} />
      <DetailsForm user={user} />
      <ModuleAccess user={user} />

      <Button
        title="Log out"
        variant="secondary"
        loading={signingOut}
        onPress={async () => {
          setSigningOut(true);
          await signOut();
        }}
      />
    </Screen>
  );
}

// ---------------------------------------------------------------------------
// Summary card
// ---------------------------------------------------------------------------

/** Modules the user actually holds (the web counts the legacy role map instead). */
function heldModules(user: User): ModuleKey[] {
  const modules = effectiveModules(user) ?? (LEGACY_ROLE_PERMISSIONS[user.role] as ModuleKey[]);
  return modules.filter((m): m is ModuleKey => m in MODULES);
}

function departmentLabel(department: string): string {
  return department
    .toLowerCase()
    .split('_')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}

function SummaryCard({ user }: { user: User }) {
  const active = user.is_active;
  const moduleCount = heldModules(user).length;

  return (
    <Card style={styles.summary}>
      <View style={styles.summaryTop}>
        <View>
          <View style={styles.avatar}>
            <AppText style={styles.avatarText}>{user.first_name ? user.first_name[0].toUpperCase() : 'U'}</AppText>
          </View>
          <View
            style={[styles.avatarDot, { backgroundColor: active ? profileColors.activeDot : profileColors.inactiveDot }]}
            accessibilityLabel={active ? 'Active' : 'Inactive'}
          />
        </View>
        <View style={styles.summaryName}>
          <AppText variant="title">{fullName(user)}</AppText>
          <StatusChip active={active} />
        </View>
      </View>

      <View style={styles.chipRow}>
        <IconChip icon="shield-checkmark-outline" label={roleLabel(user.role)} colors={profileColors.roleChip} />
        <IconChip icon="business-outline" label={departmentLabel(user.department)} colors={neutralChip} />
        <IconChip icon="grid-outline" label={`${moduleCount} of ${TOTAL_MODULES} modules`} colors={neutralChip} />
      </View>

      <View style={styles.contact}>
        <ContactItem icon="mail-outline" text={user.email} />
        {user.phone_number ? <ContactItem icon="call-outline" text={user.phone_number} /> : null}
        {user.last_login_at ? <ContactItem icon="time-outline" text={`Active ${timeAgo(user.last_login_at)}`} /> : null}
      </View>

      <AppText variant="caption" color={profileColors.adminNote}>
        Role, department and module access are managed by your admin.
      </AppText>
    </Card>
  );
}

function StatusChip({ active }: { active: boolean }) {
  const c = active ? profileColors.activeChip : profileColors.inactiveChip;
  return (
    <View style={[styles.statusChip, { backgroundColor: c.bg }]}>
      <View style={[styles.statusDot, { backgroundColor: c.fg }]} />
      <AppText variant="chip" color={c.fg}>
        {active ? 'Active' : 'Inactive'}
      </AppText>
    </View>
  );
}

function IconChip({ icon, label, colors: c }: { icon: IconName; label: string; colors: { bg: string; fg: string } }) {
  return (
    <View style={[styles.iconChip, { backgroundColor: c.bg }]}>
      <Ionicons name={icon} size={12} color={c.fg} />
      <AppText variant="chip" color={c.fg}>
        {label}
      </AppText>
    </View>
  );
}

function ContactItem({ icon, text }: { icon: IconName; text: string }) {
  return (
    <View style={styles.contactItem}>
      <Ionicons name={icon} size={14} color={colors.textFaint} />
      <AppText variant="caption" color={colors.textSecondary}>
        {text}
      </AppText>
    </View>
  );
}

// ---------------------------------------------------------------------------
// Personal details form (PUT /profile)
// ---------------------------------------------------------------------------

function valuesFrom(user: User): FormValues {
  return {
    first_name: user.first_name ?? '',
    last_name: user.last_name ?? '',
    job_title: user.job_title ?? '',
    email: user.email ?? '',
    phone_number: user.phone_number ?? '',
    // A <select> with no matching option shows (and submits) the first one.
    timezone:
      user.timezone && (PROFILE_TIMEZONES as readonly string[]).includes(user.timezone)
        ? user.timezone
        : PROFILE_TIMEZONES[0],
    message_signature: user.message_signature ?? '',
  };
}

function DetailsForm({ user }: { user: User }) {
  const { updateUser } = useSession();
  const [initial, setInitial] = useState<FormValues>(() => valuesFrom(user));
  const [values, setValues] = useState<FormValues>(initial);
  const [errors, setErrors] = useState<FieldErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);

  const lastName = useRef<TextInput>(null);
  const jobTitle = useRef<TextInput>(null);
  const email = useRef<TextInput>(null);
  const phone = useRef<TextInput>(null);
  const signature = useRef<TextInput>(null);

  // "Saved" shows for 2.5s, as on the web.
  useEffect(() => {
    if (!saved) return;
    const t = setTimeout(() => setSaved(false), 2500);
    return () => clearTimeout(t);
  }, [saved]);

  const dirty = (Object.keys(values) as (keyof FormValues)[]).some((k) => values[k] !== initial[k]);

  const set = (field: keyof FormValues) => (text: string) => {
    setValues((v) => ({ ...v, [field]: text }));
    setSaved(false);
  };

  const cancel = () => {
    setValues(initial);
    setErrors({});
    setFormError(null);
    setSaved(false);
  };

  async function save() {
    setSaving(true);
    setErrors({});
    setFormError(null);
    setSaved(false);
    try {
      const res = await api.profile.update({
        ...values,
        job_title: values.job_title || null,
        phone_number: values.phone_number || null,
        timezone: values.timezone || null,
        message_signature: values.message_signature || null,
      });
      updateUser(res.user);
      const fresh = valuesFrom(res.user);
      setInitial(fresh);
      setValues(fresh);
      setSaved(true);
    } catch (e) {
      if (isApiError(e) && e.status === 422) {
        const next: FieldErrors = {};
        for (const key of Object.keys(values) as (keyof FormValues)[]) next[key] = e.fieldError(key);
        setErrors(next);
      } else {
        setFormError(errorMessage(e));
      }
    } finally {
      setSaving(false);
    }
  }

  return (
    <Card style={styles.form}>
      <View>
        <AppText variant="heading">Personal details</AppText>
        <AppText variant="caption">These appear across the app under your name.</AppText>
      </View>

      {formError ? <Banner text={formError} /> : null}

      <TextField
        label="First name"
        required
        value={values.first_name}
        onChangeText={set('first_name')}
        autoComplete="given-name"
        textContentType="givenName"
        returnKeyType="next"
        onSubmitEditing={() => lastName.current?.focus()}
        error={errors.first_name}
      />
      <TextField
        ref={lastName}
        label="Last name"
        required
        value={values.last_name}
        onChangeText={set('last_name')}
        autoComplete="family-name"
        textContentType="familyName"
        returnKeyType="next"
        onSubmitEditing={() => jobTitle.current?.focus()}
        error={errors.last_name}
      />
      <TextField
        ref={jobTitle}
        label="Job title"
        icon="briefcase-outline"
        value={values.job_title}
        onChangeText={set('job_title')}
        placeholder="e.g. Clinic Manager"
        returnKeyType="next"
        onSubmitEditing={() => email.current?.focus()}
        error={errors.job_title}
      />
      <TextField
        ref={email}
        label="Work email"
        required
        icon="mail-outline"
        value={values.email}
        onChangeText={set('email')}
        autoCapitalize="none"
        autoCorrect={false}
        autoComplete="email"
        keyboardType="email-address"
        returnKeyType="next"
        onSubmitEditing={() => phone.current?.focus()}
        error={errors.email}
      />
      <TextField
        ref={phone}
        label="Mobile"
        icon="call-outline"
        value={values.phone_number}
        onChangeText={set('phone_number')}
        autoComplete="tel"
        keyboardType="phone-pad"
        textContentType="telephoneNumber"
        error={errors.phone_number}
      />

      <View style={styles.fieldGroup}>
        <AppText variant="bodyStrong" style={styles.fieldLabel}>
          Timezone
        </AppText>
        <View style={styles.tzList} accessibilityRole="radiogroup">
          {PROFILE_TIMEZONES.map((tz) => {
            const selected = values.timezone === tz;
            return (
              <Pressable
                key={tz}
                onPress={() => set('timezone')(tz)}
                accessibilityRole="radio"
                accessibilityState={{ checked: selected }}
                style={[styles.tzOption, selected && styles.tzOptionSelected]}>
                <Ionicons
                  name={selected ? 'radio-button-on' : 'radio-button-off'}
                  size={18}
                  color={selected ? colors.pink : colors.textFaint}
                />
                <AppText variant="body">{tz}</AppText>
              </Pressable>
            );
          })}
        </View>
        {errors.timezone ? (
          <AppText variant="caption" color={colors.danger}>
            {errors.timezone}
          </AppText>
        ) : null}
      </View>

      <TextField
        ref={signature}
        label="Message signature"
        icon="create-outline"
        value={values.message_signature}
        onChangeText={set('message_signature')}
        placeholder="e.g. Engage Clinic · Al Wasl Road, Dubai"
        hint="Optional line with your name, title, and clinic contact info."
        returnKeyType="done"
        error={errors.message_signature}
      />

      <Divider />
      <AppText variant="caption" color={profileColors.adminNote}>
        Role, status and module access can only be changed by an admin.
      </AppText>
      {saved ? (
        <View style={styles.saved} accessibilityLiveRegion="polite">
          <Ionicons name="checkmark" size={15} color={colors.success} />
          <AppText variant="bodyStrong" color={colors.success}>
            Saved
          </AppText>
        </View>
      ) : null}
      <View style={styles.actions}>
        <View style={styles.flex}>
          <Button title="Cancel" variant="secondary" onPress={cancel} disabled={saving || !dirty} />
        </View>
        <View style={styles.flex}>
          <Button title={saving ? 'Saving…' : 'Save changes'} onPress={save} loading={saving} disabled={!dirty} />
        </View>
      </View>
    </Card>
  );
}

// ---------------------------------------------------------------------------
// Module access (mobile addition: which modules are on mobile yet)
// ---------------------------------------------------------------------------

/** Whether this module has a real mobile screen for this user yet. */
function onMobile(user: User, module: ModuleKey): boolean {
  if (module === 'dashboard') return true;
  // The Therapists page is for people who see the whole roster; a therapist's own schedule is their Calendar tab.
  if (module === 'therapists') return user.role !== 'THERAPIST' && levelFor(user, 'therapists') !== 'own';
  return ['calendar', 'patients', 'whatsapp', 'leads', 'contacts', 'reports', 'billing', 'users'].includes(module);
}

/** Modules without a tab of their own: they open as a screen from here (and from their related tab). */
const PUSHED_SCREENS = { contacts: '/contacts', therapists: '/therapists', reports: '/reports', billing: '/billing', users: '/users' } as const;

const LEVEL_LABEL = { full: 'Full', own: 'Own only', view: 'View only', edit: 'Edit' } as const;

function ModuleAccess({ user }: { user: User }) {
  return (
    <Card style={styles.form}>
      <View>
        <AppText variant="heading">Module access</AppText>
        <AppText variant="caption">What you can open, and what is on mobile so far.</AppText>
      </View>
      {heldModules(user).map((m) => {
        const level = levelFor(user, m);
        return (
          <Pressable
            key={m}
            style={styles.moduleRow}
            disabled={!(m in PUSHED_SCREENS) || !onMobile(user, m)}
            onPress={() => router.push(PUSHED_SCREENS[m as keyof typeof PUSHED_SCREENS])}
            accessibilityRole={m in PUSHED_SCREENS && onMobile(user, m) ? 'button' : undefined}
            accessibilityLabel={m in PUSHED_SCREENS && onMobile(user, m) ? `Open ${MODULES[m]}` : undefined}>
            <AppText variant="body" style={styles.flex}>
              {MODULES[m]}
            </AppText>
            {level !== 'full' ? <Chip label={LEVEL_LABEL[level]} colors={neutralChip} /> : null}
            {onMobile(user, m) ? null : (
              <AppText variant="caption" color={colors.textFaint}>
                Web only
              </AppText>
            )}
            {m in PUSHED_SCREENS && onMobile(user, m) ? <AppText variant="link">Open →</AppText> : null}
          </Pressable>
        );
      })}
    </Card>
  );
}

const styles = StyleSheet.create({
  header: { gap: 2, marginBottom: spacing.xs },
  flex: { flex: 1 },

  summary: { gap: spacing.md },
  summaryTop: { flexDirection: 'row', alignItems: 'center', gap: spacing.lg },
  avatar: {
    width: 62,
    height: 62,
    borderRadius: 31,
    backgroundColor: colors.navy,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 3,
    borderColor: profileColors.avatarRing,
  },
  avatarText: { fontFamily: fonts.heading, fontSize: 24, lineHeight: 30, color: colors.white },
  avatarDot: {
    position: 'absolute',
    right: 2,
    bottom: 2,
    width: 14,
    height: 14,
    borderRadius: 7,
    borderWidth: 2,
    borderColor: colors.card,
  },
  summaryName: { flex: 1, gap: 6, alignItems: 'flex-start' },
  statusChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    borderRadius: radius.pill,
    paddingHorizontal: 9,
    paddingVertical: 3,
  },
  statusDot: { width: 6, height: 6, borderRadius: 3 },
  chipRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  iconChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    borderRadius: radius.pill,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  contact: { gap: 6 },
  contactItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },

  form: { gap: spacing.lg },
  fieldGroup: { gap: 6 },
  fieldLabel: { fontSize: 13 },
  tzList: { gap: spacing.xs },
  tzOption: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    minHeight: 44,
    paddingHorizontal: spacing.md,
    borderRadius: radius.input,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
  },
  tzOptionSelected: { borderColor: colors.pink },
  saved: { flexDirection: 'row', alignItems: 'center', gap: 4, alignSelf: 'flex-end' },
  actions: { flexDirection: 'row', gap: spacing.sm },
  moduleRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, minHeight: 28 },
});
