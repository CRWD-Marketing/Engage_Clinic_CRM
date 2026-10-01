import { useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useRef } from 'react';

/**
 * Calls `tick` every `ms` while the screen is focused (the web inbox polls
 * every 4s and skips hidden tabs). Never runs two ticks at once.
 */
export function usePolling(tick: () => Promise<unknown>, ms: number) {
  const tickRef = useRef(tick);
  useEffect(() => {
    tickRef.current = tick;
  });

  useFocusEffect(
    useCallback(() => {
      let inFlight = false;
      const id = setInterval(async () => {
        if (inFlight) return;
        inFlight = true;
        try {
          await tickRef.current();
        } catch {
          // A failed poll just waits for the next tick.
        } finally {
          inFlight = false;
        }
      }, ms);
      return () => clearInterval(id);
    }, [ms]),
  );
}
