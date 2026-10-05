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
  /** Scrim behind modal sheets. */
  overlay: 'rgba(22, 67, 110, 0.45)',
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

/** Month grid (calendar/index.blade.php `.month-*`). */
export const monthColors = {
  cellBorder: '#EBE4DA',
  weekendBg: '#F6F3EE',
  today: '#C8355F',
  selected: '#16436E',
  countBg: '#C8355F',
  countFg: '#FFFFFF',
  dow: '#98897A',
  more: '#98897A',
  noteMark: '#5A6B7E',
  cardOverlay: 'rgba(255,255,255,0.7)',
} as const;

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

/** Patients list + detail (patient/index.blade.php, patient/show.blade.php). */
export const patientColors = {
  activeBadge: { bg: '#E4F6EB', fg: '#1E8A4C' },
  needsDetailsBadge: { bg: '#FDF6E9', fg: '#8A5A10' },
  groupDotNeedsDetails: '#C8355F',
  groupDotActive: '#1E8A4C',
  programmeChip: { bg: '#F9E7EC', fg: '#C8355F' },
  payerChip: { bg: '#E7EFF7', fg: '#24619C' },
  coverageBadge: { bg: '#E7EFF7', fg: '#24619C' },
  incompleteBanner: { bg: '#FDF6E9', border: '#EBDCC2', title: '#8A5A10', sub: '#8A7D6C' },
  attentionBanner: { bg: '#FBEAE8', border: '#EFC7C2', fg: '#B3261E' },
  conversionBanner: { bg: '#F6F3EE', border: '#E2DACE', fg: '#5A6B7E' },
  progressTrack: '#F3EDE3',
  progressOk: '#1E8A4C',
  progressLow: '#B3261E',
  listMeta: '#B0A493',
  goalSelectedBg: '#FDF5F7',
} as const;

/** Session-note review states (mobile sign-off). */
export const noteStatusColors = {
  signed: { bg: '#E3F1E9', fg: '#2E7D5B' },
  awaiting: { bg: '#F7EEDD', fg: '#B97F24' },
  flagged: { bg: '#F9E3EA', fg: '#C8355F' },
} as const;

/** WhatsApp / social inbox (whatsapp/index.blade.php, dashboard inbox cards). */
export const inboxColors = {
  whatsappGreen: '#1FA855',
  outboundBubble: '#DDF3E0',
  bubbleTime: '#9AA79B',
  aiChip: { bg: '#E9EEF3', fg: '#16436E' },
  staffChip: { bg: '#EEF0F2', fg: '#5A6B7E' },
  attention: '#C8355F',
  failed: '#B3261E',
  /** Channel badge on the avatar (WhatsappContact::channelBadgeHtml; IG/FB gradients flattened). */
  channel: { whatsapp: '#1FA855', instagram: '#D62976', facebook: '#0662FE', voice: '#B97F24' },
  /** WhatsappContact::avatarColor() palette (6 colours, crc32(wa_id) % 6). */
  avatarPalette: ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F'],
} as const;

/** Invoice money-status chips on the billing screens (billing/index.blade.php). */
export const billingStatusColors: Record<'voided' | 'paid' | 'partly_paid' | 'outstanding', ChipColors> = {
  paid: { bg: '#E3F1E9', fg: '#2E7D5B' },
  partly_paid: { bg: '#F7EEDD', fg: '#B97F24' },
  outstanding: { bg: '#F9E4E2', fg: '#B3261E' },
  voided: { bg: '#EEF0F2', fg: '#6B7A8C' },
};

/** Aging bucket bars on the billing "Aging & statements" tab (billing/index.blade.php), by bucket key. */
export const agingBucketColors: Record<string, string> = {
  current: '#2E7D5B',
  d1_30: '#B97F24',
  d31_60: '#C8355F',
  d61_90: '#C8355F',
  d90: '#8A2020',
};

/** Claim status chips on the billing claims tab (CLAIM_COLORS in billing/index.blade.php). */
export const claimStatusColors: Record<'draft' | 'submitted' | 'pending_info' | 'rejected' | 'settled', ChipColors> = {
  draft: { bg: '#F1EDE5', fg: '#8A7D6C' },
  submitted: { bg: '#E7EFF7', fg: '#24619C' },
  pending_info: { bg: '#F7EEDD', fg: '#B97F24' },
  rejected: { bg: '#F9E4E2', fg: '#B3261E' },
  settled: { bg: '#E3F1E9', fg: '#1E7A46' },
};

/** Pre-authorization status chips (PA_COLORS in billing/index.blade.php). */
export const preAuthStatusColors: Record<'requested' | 'approved' | 'denied', ChipColors> = {
  requested: { bg: '#E7EFF7', fg: '#24619C' },
  approved: { bg: '#E3F1E9', fg: '#1E7A46' },
  denied: { bg: '#F9E4E2', fg: '#B3261E' },
};

/** Claims aging bars, youngest bucket first. */
export const claimAgingColors = ['#2E7D5B', '#B97F24', '#C8355F', '#8A2020'];

/** Account status chips in User Management (user/index.blade.php). */
export const userStatusColors: Record<'active' | 'inactive', ChipColors> = {
  active: { bg: '#E3F1E9', fg: '#2E7D5B' },
  inactive: { bg: '#EEF0F2', fg: '#6B7A8C' },
};

/** Invoice status chips on the other-staff dashboard (dashboard/other_staff.blade.php `$statusColors`). */
export const invoiceStatusColors: Record<string, ChipColors & { label: string }> = {
  draft: { bg: '#EEF0F2', fg: '#6B7A8C', label: 'Draft' },
  submitted: { bg: '#F7EEDD', fg: '#B97F24', label: 'Submitted' },
  pending_info: { bg: '#F7EEDD', fg: '#B97F24', label: 'Pending info' },
  paid: { bg: '#E3F1E9', fg: '#2E7D5B', label: 'Paid' },
  rejected: { bg: '#F9E4E2', fg: '#B3261E', label: 'Rejected' },
};

/** Patient document expiry badges (patient/show.blade.php `.pt-doc-badge-*`). */
export const documentColors: Record<'neutral' | 'warn' | 'ok', ChipColors> = {
  neutral: { bg: '#F3EDE3', fg: '#5A6B7E' },
  warn: { bg: '#F7EEDD', fg: '#B97F24' },
  ok: { bg: '#E3F1E9', fg: '#2E7D5B' },
};

/** Reports page (report/index.blade.php): funnel stage bars, top to bottom. */
export const reportColors = {
  funnel: ['#16436E', '#3A6A96', '#7396B8', '#C8355F'],
} as const;

/** Contact submission status badges (contact/index.blade.php `$statusTokens`). */
export const contactStatusColors: Record<string, ChipColors> = {
  new: { bg: '#eff6ff', fg: '#1d4ed8' },
  approved: { bg: '#f0fdf4', fg: '#15803d' },
  rejected: { bg: '#fef2f2', fg: '#b91c1c' },
  contacted: { bg: '#fefce8', fg: '#a16207' },
  converted: { bg: '#f0fdfa', fg: '#0f766e' },
  closed: { bg: '#fafafa', fg: '#52525b' },
};

/** Admin dashboard "Lead sources" bars (dashboard/admin.blade.php `$sourceColors`). */
export const leadSourceBarColors: Record<string, string> = {
  WhatsApp: '#1FA855',
  Instagram: '#C13584',
  Website: '#24619C',
  Referral: '#B97F24',
  Google: '#6E4FA8',
  'Google Ads': '#6E4FA8',
  Facebook: '#1877F2',
};
export const leadSourceBarDefault = '#8A7D6C';

/** Leads board (lead/index.blade.php, lead/_card.blade.php). */
export const leadColors = {
  sourceBadge: {
    WhatsApp: { bg: '#E3F4E9', fg: '#178A45' },
    Website: { bg: '#E7EFF7', fg: '#24619C' },
    Instagram: { bg: '#FAE7F2', fg: '#C13584' },
    Referral: { bg: '#F7EEDD', fg: '#B97F24' },
    Google: { bg: '#EEE9F7', fg: '#6E4FA8' },
    'Walk-in': { bg: '#EDEFF1', fg: '#5A6B7E' },
    'Phone call': { bg: '#E3F1E9', fg: '#2E7D5B' },
    Event: { bg: '#F7EEDD', fg: '#8A5A10' },
  } as Record<string, ChipColors>,
  defaultBadge: { bg: '#EDEFF1', fg: '#5A6B7E' },
  statusTag: {
    new: { bg: '#E7EFF7', fg: '#24619C' },
    contacted: { bg: '#FBF0DC', fg: '#8A5A10' },
    assessment_booked: { bg: '#EDE7F5', fg: '#6E4FA8' },
    assessment_done: { bg: '#EDE7F5', fg: '#6E4FA8' },
    enrolled: { bg: '#E3F1E9', fg: '#2E7D5B' },
    terminated: { bg: '#F9E4E2', fg: '#B3261E' },
  } as Record<string, ChipColors>,
  due: { bg: '#FBF0DC', fg: '#8A5A10' },
  overdue: { bg: '#F9E4E2', fg: '#B3261E' },
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
