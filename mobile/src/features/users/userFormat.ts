/** "HUMAN_RESOURCES" → "Human resources" (the web's str_replace('_', ' ', ucwords(strtolower($x)))). */
export function enumLabel(value: string | null | undefined): string {
  if (!value) return '—';
  const lower = value.toLowerCase();
  return (lower.charAt(0).toUpperCase() + lower.slice(1)).replace(/_/g, ' ');
}

export function fullName(u: { first_name: string; middle_name?: string | null; last_name: string }, withMiddle = false): string {
  return [u.first_name, withMiddle ? u.middle_name : null, u.last_name].filter(Boolean).join(' ');
}

/**
 * A 12-character password with at least one lower, upper, digit and symbol
 * (generatePassword() in user/create.blade.php; look-alike characters left out).
 */
export function generatePassword(length = 12): string {
  const lower = 'abcdefghijkmnopqrstuvwxyz';
  const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
  const digits = '23456789';
  const symbols = '!@#$%^&*';
  const all = lower + upper + digits + symbols;
  const pick = (set: string) => set[Math.floor(Math.random() * set.length)];
  const chars = [pick(lower), pick(upper), pick(digits), pick(symbols)];
  while (chars.length < length) chars.push(pick(all));
  for (let i = chars.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [chars[i], chars[j]] = [chars[j], chars[i]];
  }
  return chars.join('');
}
