/**
 * Clinic-local date/time helpers. Laravel runs with APP_TIMEZONE=Asia/Dubai,
 * so session dates/times ("2026-09-30", "09:30") are clinic-local wall-clock
 * values. Dubai is UTC+4 with no DST, so a fixed offset is exact and avoids
 * relying on Intl time-zone support in the JS engine.
 *
 * Conventions:
 * - `Ymd` = "YYYY-MM-DD" clinic-local date string.
 * - Times are "HH:mm" or "HH:mm:ss" clinic-local strings (compare as strings).
 * - Timestamps (created_at etc.) are ISO-8601 UTC strings, as Laravel sends them.
 */

export const CLINIC_TIMEZONE = 'Asia/Dubai';
const OFFSET_MS = 4 * 60 * 60 * 1000;
const DAY_MS = 24 * 60 * 60 * 1000;

export type Ymd = string;

const DAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const MONTH_NAMES = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

const pad = (n: number) => String(n).padStart(2, '0');

/** A Date whose UTC fields read as the clinic's wall clock. */
function clinicClock(at: Date = new Date()): Date {
  return new Date(at.getTime() + OFFSET_MS);
}

function ymdFromClock(clock: Date): Ymd {
  return `${clock.getUTCFullYear()}-${pad(clock.getUTCMonth() + 1)}-${pad(clock.getUTCDate())}`;
}

/** UTC-midnight Date for a Ymd (used only for calendar arithmetic). */
function parseYmd(value: Ymd): Date {
  const [y, m, d] = value.split('-').map(Number);
  return new Date(Date.UTC(y, m - 1, d));
}

export function todayYmd(at: Date = new Date()): Ymd {
  return ymdFromClock(clinicClock(at));
}

/** Current clinic time as "HH:mm:ss". */
export function nowTime(at: Date = new Date()): string {
  const c = clinicClock(at);
  return `${pad(c.getUTCHours())}:${pad(c.getUTCMinutes())}:${pad(c.getUTCSeconds())}`;
}

export function clinicHour(at: Date = new Date()): number {
  return clinicClock(at).getUTCHours();
}

export function addDays(value: Ymd, days: number): Ymd {
  return ymdFromClock(new Date(parseYmd(value).getTime() + days * DAY_MS));
}

/** 0 = Monday … 6 = Sunday (Carbon::MONDAY-based weeks, as the web app uses). */
export function weekdayIndex(value: Ymd): number {
  return (parseYmd(value).getUTCDay() + 6) % 7;
}

export function mondayOf(value: Ymd): Ymd {
  return addDays(value, -weekdayIndex(value));
}

/** Whole days from `from` to `to` (negative when `to` is earlier). */
export function diffDays(from: Ymd, to: Ymd): number {
  return Math.round((parseYmd(to).getTime() - parseYmd(from).getTime()) / DAY_MS);
}

/** Clinic-local wall-clock date + time → ISO UTC timestamp. */
export function clinicToIso(date: Ymd, time: string): string {
  const [h, mi, s = 0] = time.split(':').map(Number);
  const utc = parseYmd(date).getTime() + ((h * 60 + mi) * 60 + s) * 1000 - OFFSET_MS;
  return new Date(utc).toISOString();
}

/** ISO UTC timestamp → clinic-local date. */
export function isoToYmd(iso: string): Ymd {
  return ymdFromClock(clinicClock(new Date(iso)));
}

export function hoursSince(iso: string, at: Date = new Date()): number {
  return Math.max(0, Math.floor((at.getTime() - new Date(iso).getTime()) / (60 * 60 * 1000)));
}

/** "Wednesday, 30 September 2026" (PHP `l, j F Y`). */
export function formatLongDate(value: Ymd): string {
  const d = parseYmd(value);
  return `${DAY_NAMES[d.getUTCDay()]}, ${d.getUTCDate()} ${MONTH_NAMES[d.getUTCMonth()]} ${d.getUTCFullYear()}`;
}

/** "Wed 30 Sep" */
export function formatDayShort(value: Ymd): string {
  const d = parseYmd(value);
  return `${DAY_NAMES[d.getUTCDay()].slice(0, 3)} ${d.getUTCDate()} ${MONTH_NAMES[d.getUTCMonth()].slice(0, 3)}`;
}

/** "30 Sep" (PHP `d M`) */
export function formatDayMonth(value: Ymd): string {
  const d = parseYmd(value);
  return `${pad(d.getUTCDate())} ${MONTH_NAMES[d.getUTCMonth()].slice(0, 3)}`;
}

/** "30 Sep 2026" */
export function formatDayMonthYear(value: Ymd): string {
  return `${formatDayMonth(value)} ${parseYmd(value).getUTCFullYear()}`;
}

/** ISO timestamp → "30 Sep, 9:30am" in clinic time (PHP `d M, g:ia`). */
export function formatDateTimeShort(iso: string): string {
  const c = clinicClock(new Date(iso));
  const h = c.getUTCHours();
  const hour12 = h % 12 === 0 ? 12 : h % 12;
  return `${pad(c.getUTCDate())} ${MONTH_NAMES[c.getUTCMonth()].slice(0, 3)}, ${hour12}:${pad(c.getUTCMinutes())}${h < 12 ? 'am' : 'pm'}`;
}

/** "30 Sep 2026" without a leading zero on the day (PHP `j M Y`). */
export function formatDayMonthYearShort(value: Ymd): string {
  const d = parseYmd(value);
  return `${d.getUTCDate()} ${MONTH_NAMES[d.getUTCMonth()].slice(0, 3)} ${d.getUTCFullYear()}`;
}

/** "3 Oct" (PHP `j M`). */
export function formatDayMonthShort(value: Ymd): string {
  const d = parseYmd(value);
  return `${d.getUTCDate()} ${MONTH_NAMES[d.getUTCMonth()].slice(0, 3)}`;
}

/** "Sep 2026" (PHP `M Y`). */
export function formatMonthYear(value: Ymd): string {
  const d = parseYmd(value);
  return `${MONTH_NAMES[d.getUTCMonth()].slice(0, 3)} ${d.getUTCFullYear()}`;
}

/** "Mon" (PHP `D`). */
export function formatWeekdayShort(value: Ymd): string {
  return DAY_NAMES[parseYmd(value).getUTCDay()].slice(0, 3);
}

/** First day of the month containing `value`. */
export function monthStartOf(value: Ymd): Ymd {
  return `${value.slice(0, 7)}-01`;
}

/** First day of the month `months` after the one containing `value`. */
export function addMonths(value: Ymd, months: number): Ymd {
  const d = parseYmd(monthStartOf(value));
  return ymdFromClock(new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth() + months, 1)));
}

export function daysInMonth(value: Ymd): number {
  const d = parseYmd(value);
  return new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth() + 1, 0)).getUTCDate();
}

/** Last day of the month containing `value`. */
export function monthEndOf(value: Ymd): Ymd {
  return `${value.slice(0, 7)}-${pad(daysInMonth(value))}`;
}

/** "October 2026" */
export function formatMonthLong(value: Ymd): string {
  const d = parseYmd(value);
  return `${MONTH_NAMES[d.getUTCMonth()]} ${d.getUTCFullYear()}`;
}

/** "WED" */
export function formatWeekdayAbbrev(value: Ymd): string {
  return DAY_NAMES[parseYmd(value).getUTCDay()].slice(0, 3).toUpperCase();
}

/** Day-of-month number, e.g. 30. */
export function dayOfMonth(value: Ymd): number {
  return parseYmd(value).getUTCDate();
}

/** "09:30:00" → "09:30" */
export function formatTime(time: string): string {
  return time.slice(0, 5);
}

/** "09:30" → "9:30" (PHP `G:i`) */
export function formatTimeShort(time: string): string {
  const [h, m] = time.split(':');
  return `${Number(h)}:${m}`;
}

/** Laravel `diffForHumans()`-style: "5 minutes ago", "2 days ago". */
export function timeAgo(iso: string, at: Date = new Date()): string {
  const seconds = Math.max(0, Math.floor((at.getTime() - new Date(iso).getTime()) / 1000));
  const units: [number, string][] = [
    [365 * 86400, 'year'],
    [30 * 86400, 'month'],
    [7 * 86400, 'week'],
    [86400, 'day'],
    [3600, 'hour'],
    [60, 'minute'],
  ];
  for (const [size, name] of units) {
    const n = Math.floor(seconds / size);
    if (n >= 1) return `${n} ${name}${n === 1 ? '' : 's'} ago`;
  }
  return 'just now';
}

export function greeting(at: Date = new Date()): string {
  const hour = clinicHour(at);
  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';
  return 'Good evening';
}
