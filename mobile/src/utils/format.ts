import { avatarPalette } from '@/theme/colors';

type NameParts = { first_name: string; middle_name?: string | null; last_name: string };

/** User::getFullNameAttribute(): first + middle + last, skipping blanks. */
export function fullName(user: NameParts): string {
  return [user.first_name, user.middle_name, user.last_name].filter(Boolean).join(' ');
}

/** CalendarController::staffName(): first + last. */
export function staffName(user: NameParts): string {
  return [user.first_name, user.last_name].filter(Boolean).join(' ');
}

export function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return '?';
  const first = parts[0][0] ?? '';
  const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
  return (first + last).toUpperCase();
}

/** "FULL_ADMIN" → "Full Admin" (User::roleLabel()). */
export function roleLabel(role: string): string {
  return role
    .toLowerCase()
    .split('_')
    .map((w) => (w ? w[0].toUpperCase() + w.slice(1) : w))
    .join(' ');
}

export function plural(count: number, singular: string, pluralForm = `${singular}s`): string {
  return count === 1 ? singular : pluralForm;
}

let crcTable: number[] | null = null;

/** PHP crc32() over the string form of the value (used for avatar colours). */
export function crc32(value: string): number {
  if (!crcTable) {
    crcTable = [];
    for (let n = 0; n < 256; n++) {
      let c = n;
      for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
      crcTable.push(c >>> 0);
    }
  }
  let crc = 0xffffffff;
  for (let i = 0; i < value.length; i++) {
    crc = crcTable[(crc ^ value.charCodeAt(i)) & 0xff] ^ (crc >>> 8);
  }
  return (crc ^ 0xffffffff) >>> 0;
}

export function avatarColor(id: number | string): string {
  return avatarPalette[crc32(String(id)) % avatarPalette.length];
}

/** PHP number_format(): "12,345.60" */
export function formatMoney(amount: number, decimals = 2): string {
  const [whole, fraction] = Math.abs(amount).toFixed(decimals).split('.');
  const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return `${amount < 0 ? '-' : ''}${grouped}${fraction ? `.${fraction}` : ''}`;
}

/** One decimal with a trailing ".0" dropped: 12.5 → "12.5", 12 → "12". */
export function trimNumber(value: number): string {
  return String(Math.round(value * 10) / 10);
}
