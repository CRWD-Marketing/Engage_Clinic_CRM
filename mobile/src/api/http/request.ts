/**
 * The one place the app talks HTTP. Sends JSON to the Laravel mobile API
 * (`/api/v1`) with the bearer token, and turns every failure into the same
 * ApiError the mock throws, so screens handle 401/403/422/429 identically.
 */

import { ApiError } from '../errors';
import { getAuthToken } from '../token';
import type { RemoteFile } from '../types';

type Query = Record<string, string | number | null | undefined>;

export type Http = {
  get<T>(path: string, query?: Query): Promise<T>;
  post<T>(path: string, body?: unknown): Promise<T>;
  put<T>(path: string, body?: unknown): Promise<T>;
  patch<T>(path: string, body?: unknown): Promise<T>;
  delete<T>(path: string): Promise<T>;
  /** A GET the caller downloads itself (a PDF): the full URL and the headers that authorise it. */
  file(path: string, filename: string): RemoteFile;
};

/** Laravel's `{message, errors}`; some web actions send only `{success: false, errors}`. */
function errorFrom(status: number, body: unknown): ApiError {
  const data = (body && typeof body === 'object' ? body : {}) as { message?: unknown; errors?: unknown };
  const errors = (data.errors && typeof data.errors === 'object' ? data.errors : {}) as Record<string, string[]>;
  const first = Object.values(errors)[0]?.[0];
  const message = typeof data.message === 'string' && data.message ? data.message : (first ?? fallbackMessage(status));
  return new ApiError(status, { message, errors });
}

function fallbackMessage(status: number): string {
  if (status === 401) return 'Unauthenticated.';
  if (status === 403) return 'You do not have access to this.';
  if (status === 404) return 'Not found.';
  if (status === 429) return 'Too many requests. Please wait a moment and try again.';
  return 'Something went wrong. Please try again.';
}

export function createHttp(baseUrl: string): Http {
  const root = `${baseUrl.replace(/\/+$/, '')}/api/v1`;

  async function send<T>(method: string, path: string, body?: unknown, query?: Query): Promise<T> {
    const params = Object.entries(query ?? {})
      .filter(([, value]) => value !== undefined && value !== null && value !== '')
      .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`)
      .join('&');
    const token = getAuthToken();

    const response = await fetch(`${root}/${path.replace(/^\/+/, '')}${params ? `?${params}` : ''}`, {
      method,
      headers: {
        Accept: 'application/json',
        // Lets requests through an ngrok free tunnel without its browser warning page.
        'ngrok-skip-browser-warning': '1',
        ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    const text = await response.text();
    let data: unknown = null;
    try {
      data = text ? JSON.parse(text) : null;
    } catch {
      // A non-JSON body (an HTML error page from a proxy, say) is treated as an empty one.
    }

    if (!response.ok) throw errorFrom(response.status, data);
    return data as T;
  }

  return {
    get: (path, query) => send('GET', path, undefined, query),
    post: (path, body) => send('POST', path, body ?? {}),
    put: (path, body) => send('PUT', path, body ?? {}),
    patch: (path, body) => send('PATCH', path, body ?? {}),
    delete: (path) => send('DELETE', path),
    file: (path, filename) => {
      const token = getAuthToken();
      return {
        url: `${root}/${path.replace(/^\/+/, '')}`,
        headers: { 'ngrok-skip-browser-warning': '1', ...(token ? { Authorization: `Bearer ${token}` } : {}) },
        filename,
      };
    },
  };
}
