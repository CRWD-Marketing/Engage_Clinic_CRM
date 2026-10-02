import type { ApiErrorBody } from './types';

/**
 * Every API failure (mock or HTTP) is thrown as an ApiError carrying the
 * HTTP status and Laravel's `{message, errors}` body, so screens handle
 * 401/403/422/429 the same way regardless of the implementation.
 */
export class ApiError extends Error {
  readonly status: number;
  readonly errors: Record<string, string[]>;

  constructor(status: number, body: ApiErrorBody) {
    super(body.message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = body.errors ?? {};
  }

  /** First validation message for a field, like Laravel's `$errors->first()`. */
  fieldError(field: string): string | undefined {
    return this.errors[field]?.[0];
  }
}

export function isApiError(error: unknown): error is ApiError {
  return error instanceof ApiError;
}

/** User-facing message for any thrown value. */
export function errorMessage(error: unknown): string {
  if (isApiError(error)) return error.message;
  return 'Something went wrong. Check your connection and try again.';
}
