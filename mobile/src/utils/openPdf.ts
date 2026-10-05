import { File, Paths } from 'expo-file-system';
import * as Sharing from 'expo-sharing';

import type { RemoteFile } from '@/api/types';

import { mimeTypeOf } from './mimeType';

/**
 * Downloads a file the server produces (a PDF, a resume) with the caller's
 * token into the cache and hands it to the system share sheet, where it can
 * be opened, saved or sent on. The browser build is in openPdf.web.ts.
 */
export async function openPdf(file: RemoteFile, name: string = file.filename): Promise<void> {
  const target = new File(Paths.cache, name);
  if (target.exists) target.delete();
  const saved = await File.downloadFileAsync(file.url, target, { headers: file.headers });
  await Sharing.shareAsync(saved.uri, { mimeType: mimeTypeOf(name), UTI: name.toLowerCase().endsWith('.pdf') ? 'com.adobe.pdf' : undefined, dialogTitle: name });
}
