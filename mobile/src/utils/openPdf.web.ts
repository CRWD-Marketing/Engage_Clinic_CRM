import { ApiError } from '@/api/errors';
import type { RemoteFile } from '@/api/types';

/** Browser build of openPdf: fetch the PDF with the caller's token and save it as a download. */
export async function openPdf(file: RemoteFile, name: string = file.filename): Promise<void> {
  const response = await fetch(file.url, { headers: file.headers });
  if (!response.ok) throw new ApiError(response.status, { message: 'The PDF could not be downloaded.' });
  const url = URL.createObjectURL(await response.blob());
  const link = document.createElement('a');
  link.href = url;
  link.download = name;
  link.click();
  setTimeout(() => URL.revokeObjectURL(url), 60_000);
}
