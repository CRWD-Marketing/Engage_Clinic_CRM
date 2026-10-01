import { createContext, use, useCallback, useEffect, useMemo, useState, type PropsWithChildren } from 'react';

import { api } from '@/api/client';
import { isApiError } from '@/api/errors';
import { setAuthToken } from '@/api/token';
import type { LoginRequest, User } from '@/api/types';

import { clearToken, loadToken, saveToken } from './tokenStorage';

type SessionState = (
  | { status: 'loading'; user: null }
  | { status: 'signedOut'; user: null }
  | { status: 'signedIn'; user: User }
) & {
  /** Last signed-in user; lets signed-in screens render while they unmount after sign-out. */
  lastUser: User | null;
};

type SessionContextValue = SessionState & {
  signIn(input: LoginRequest): Promise<void>;
  signOut(): Promise<void>;
  /** Replace the signed-in user after the server returns a fresh copy (e.g. profile save). */
  updateUser(user: User): void;
};

const SessionContext = createContext<SessionContextValue | null>(null);

export function SessionProvider({ children }: PropsWithChildren) {
  const [state, setState] = useState<SessionState>({ status: 'loading', user: null, lastUser: null });

  // Restore a remembered session on launch.
  useEffect(() => {
    let cancelled = false;
    (async () => {
      const token = await loadToken();
      if (!token) {
        if (!cancelled) setState({ status: 'signedOut', user: null, lastUser: null });
        return;
      }
      setAuthToken(token);
      try {
        const user = await api.auth.me();
        if (!cancelled) setState({ status: 'signedIn', user, lastUser: user });
      } catch (error) {
        // An invalid/expired token (401) ends the session; anything else
        // (e.g. offline) also signs out for now — there is no offline mode yet.
        if (isApiError(error) && error.status === 401) await clearToken();
        setAuthToken(null);
        if (!cancelled) setState({ status: 'signedOut', user: null, lastUser: null });
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const signIn = useCallback(async (input: LoginRequest) => {
    const { token, user } = await api.auth.login(input);
    setAuthToken(token);
    // "Remember me" keeps the session across app restarts; otherwise it
    // lives only in memory, like a browser session cookie.
    if (input.remember) await saveToken(token);
    else await clearToken();
    setState({ status: 'signedIn', user, lastUser: user });
  }, []);

  const signOut = useCallback(async () => {
    try {
      await api.auth.logout();
    } catch {
      // Sign out locally even if the server call fails.
    }
    setAuthToken(null);
    await clearToken();
    setState((prev) => ({ status: 'signedOut', user: null, lastUser: prev.lastUser }));
  }, []);

  const updateUser = useCallback((user: User) => {
    setState((prev) => (prev.status === 'signedIn' ? { status: 'signedIn', user, lastUser: user } : prev));
  }, []);

  const value = useMemo(
    () => ({ ...state, signIn, signOut, updateUser }),
    [state, signIn, signOut, updateUser],
  );

  return <SessionContext value={value}>{children}</SessionContext>;
}

export function useSession(): SessionContextValue {
  const value = use(SessionContext);
  if (!value) throw new Error('useSession must be used inside <SessionProvider>');
  return value;
}

/** The signed-in user. Only call from screens inside the (app) group. */
export function useCurrentUser(): User {
  const session = useSession();
  const user = session.user ?? session.lastUser;
  if (!user) throw new Error('useCurrentUser called without a signed-in user');
  return user;
}
