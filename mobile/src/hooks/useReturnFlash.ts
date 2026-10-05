import { useFocusEffect } from 'expo-router';
import { useCallback } from 'react';

const pending = new Map<string, string>();

/** Leave a confirmation for the screen the user goes back to (see useReturnFlash). */
export function leaveFlash(key: string, text: string) {
  pending.set(key, text);
}

/**
 * Shows a message left by a form that closed with `router.back()`, so an
 * edit returns to the existing screen instead of stacking a fresh copy of it.
 */
export function useReturnFlash(key: string, show: (text: string) => void) {
  useFocusEffect(
    useCallback(() => {
      const text = pending.get(key);
      if (text) {
        pending.delete(key);
        show(text);
      }
    }, [key, show]),
  );
}
