/**
 * Colour tokens copied from the Laravel web app's Blade views
 * (resources/views/dashboard/*, calendar/*, patient/*, lead/*).
 * Screens must use these instead of hard-coded hex values.
 */

export const colors = {
  navy: '#16436E',
  pink: '#C8355F',
  pinkPressed: '#A82348',

  text: '#2B3A4C',
  textSecondary: '#5A6B7E',
  textMuted: '#98897A',
  textFaint: '#B0A493',
  textWarm: '#8A7D6C',

  page: '#FFFDFA',
  pageAlt: '#F6F3EE',
  card: '#FFFFFF',
  border: '#EBE4DA',
  divider: '#F3EDE3',

  success: '#2E7D5B',
  successBg: '#E3F1E9',
  warning: '#B97F24',
  warningBg: '#F7EEDD',
  danger: '#B3261E',
  dangerBg: '#F9E4E2',
  info: '#24619C',
  infoBg: '#E7EFF7',
  white: '#FFFFFF',
} as const;

export type ChipColors = { bg: string; fg: string };

/** Activity type chips (calendar/my.blade.php `$typeColors`). */
export const activityTypeColors: Record<string, ChipColors> = {
  ABA: { bg: '#F9E7EC', fg: '#C8355F' },
  Speech: { bg: '#E7EFF7', fg: '#24619C' },
  OT: { bg: '#F7EEDD', fg: '#B97F24' },
  Assessment: { bg: '#EDE7F5', fg: '#6E4FA8' },
  'Parent training': { bg: '#E3F1E9', fg: '#2E7D5B' },
};

/** Non-therapy session categories (calendar/my.blade.php `$categoryColors`). */
export const sessionCategoryColors: Record<string, ChipColors> = {
  supervision: { bg: '#E4E0F7', fg: '#4B3F9E' },
  observation: { bg: '#F0E4F5', fg: '#8A4FA8' },
  admin: { bg: '#F7EEDD', fg: '#8A6A2B' },
};

export const neutralChip: ChipColors = { bg: '#F3EDE3', fg: '#5A6B7E' };

/** Staff leave block on the week view. */
export const leaveColors: ChipColors = { bg: '#FDF3B0', fg: '#7A5C00' };

/** Dashboard "My schedule today" status chips (dashboard/therapist.blade.php). */
export const todayStatusColors = {
  completed: { bg: '#F3EDE3', fg: '#98897A' },
  cancelled: { bg: '#F9E4E2', fg: '#B3261E' },
  no_show: { bg: '#F9E4E2', fg: '#B3261E' },
  in_session: { bg: '#F9E7EC', fg: '#C8355F' },
  awaiting_update: { bg: '#F7EEDD', fg: '#B97F24' },
  upcoming: { bg: '#EEF0F2', fg: '#6B7A8C' },
} satisfies Record<string, ChipColors>;

/** "My calendar" card status tag text colours. */
export const sessionStatusTagColors = {
  completed: '#2E7D5B',
  upcoming: '#B97F24',
  cancelled: '#98897A',
  no_show: '#C8355F',
} as const;

/** Notes awaiting sign-off: pending vs overdue (>= 48h). */
export const noteAgeColors = {
  pending: { bg: '#F7EEDD', fg: '#B97F24' },
  overdue: { bg: '#F9E3EA', fg: '#C8355F' },
} satisfies Record<string, ChipColors>;

/** "My treatment plans due for review" amber card. */
export const planCardColors = { bg: '#FBF3E4', border: '#EBDCBB', fg: '#8A5A10' } as const;

/** "My profile" summary card (profile/index.blade.php). */
export const profileColors = {
  activeChip: { bg: '#E3F1E9', fg: '#2E7D5B' },
  inactiveChip: { bg: '#F1EDE5', fg: '#8A7D6C' },
  roleChip: { bg: '#E9EEF3', fg: '#16436E' },
  activeDot: '#2E9E5B',
  inactiveDot: '#B0A493',
  avatarRing: '#F1E9DC',
  adminNote: '#A79C8E',
} as const;

/** Avatar backgrounds, picked by crc32(id) % 7 like the web app. */
export const avatarPalette = [
  '#C8355F',
  '#24619C',
  '#B97F24',
  '#6E4FA8',
  '#1F8FA8',
  '#A8461F',
  '#2E7D5B',
] as const;

export function colorsForActivity(activityType: string, category?: string): ChipColors {
  return (
    activityTypeColors[activityType] ??
    (category ? sessionCategoryColors[category] : undefined) ??
    neutralChip
  );
}
