/**
 * The bearer token for the current session, held in memory. The session
 * layer (src/auth) sets it after login/restore; the HTTP client will send it
 * as `Authorization: Bearer …`, and the mock reads it to know who is calling.
 */

let current: string | null = null;

export function setAuthToken(token: string | null): void {
  current = token;
}

export function getAuthToken(): string | null {
  return current;
}
