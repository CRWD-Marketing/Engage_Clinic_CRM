import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';

/**
 * Persists the auth token when "Remember me" is ticked. SecureStore
 * (Keychain / Keystore) on device; SecureStore has no web support, so web
 * falls back to localStorage (dev convenience only).
 */

const KEY = 'engage.auth.token';

export async function loadToken(): Promise<string | null> {
  try {
    if (Platform.OS === 'web') return globalThis.localStorage?.getItem(KEY) ?? null;
    return await SecureStore.getItemAsync(KEY);
  } catch {
    return null;
  }
}

export async function saveToken(token: string): Promise<void> {
  try {
    if (Platform.OS === 'web') globalThis.localStorage?.setItem(KEY, token);
    else await SecureStore.setItemAsync(KEY, token);
  } catch {
    // Not fatal: the session still works until the app is closed.
  }
}

export async function clearToken(): Promise<void> {
  try {
    if (Platform.OS === 'web') globalThis.localStorage?.removeItem(KEY);
    else await SecureStore.deleteItemAsync(KEY);
  } catch {
    // ignore
  }
}
