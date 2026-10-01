import { useCallback, useEffect, useRef, useState } from 'react';

type QueryState<T> = {
  data: T | undefined;
  error: unknown;
  loading: boolean;
  refreshing: boolean;
};

type Mode = 'initial' | 'refresh' | 'silent';

/**
 * Loads data from the `api` client and re-runs whenever `key` changes.
 * - `refresh()` shows the pull-to-refresh spinner.
 * - `reload()` refetches silently (e.g. when a screen regains focus).
 * Out-of-order responses are ignored, so fast week-switching can't show stale data.
 */
export function useApiQuery<T>(key: string, fetcher: () => Promise<T>) {
  const [state, setState] = useState<QueryState<T>>({
    data: undefined,
    error: undefined,
    loading: true,
    refreshing: false,
  });
  const requestId = useRef(0);
  const fetcherRef = useRef(fetcher);

  useEffect(() => {
    fetcherRef.current = fetcher;
  });

  const run = useCallback(async (mode: Mode) => {
    const id = ++requestId.current;
    setState((s) => ({
      ...s,
      error: mode === 'silent' ? s.error : undefined,
      loading: mode === 'initial' ? true : s.loading,
      refreshing: mode === 'refresh',
    }));
    try {
      const data = await fetcherRef.current();
      if (id === requestId.current) setState({ data, error: undefined, loading: false, refreshing: false });
    } catch (error) {
      if (id === requestId.current) setState((s) => ({ ...s, error, loading: false, refreshing: false }));
    }
  }, []);

  useEffect(() => {
    run('initial');
  }, [key, run]);

  const refresh = useCallback(() => run('refresh'), [run]);
  const reload = useCallback(() => run('silent'), [run]);

  return { ...state, refresh, reload };
}
