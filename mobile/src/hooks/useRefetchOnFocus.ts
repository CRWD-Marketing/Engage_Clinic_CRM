import { useFocusEffect } from 'expo-router';
import { useCallback, useRef } from 'react';

/**
 * Calls `refetch` when the screen regains focus (e.g. returning from a
 * detail screen that changed data). Skips the first focus, since the query
 * has just loaded.
 */
export function useRefetchOnFocus(refetch: () => void) {
  const firstFocus = useRef(true);
  useFocusEffect(
    useCallback(() => {
      if (firstFocus.current) {
        firstFocus.current = false;
        return;
      }
      refetch();
    }, [refetch]),
  );
}
