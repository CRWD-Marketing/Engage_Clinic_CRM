/** The media type for a downloaded file, from its extension (PDF unless it says otherwise). */
export function mimeTypeOf(name: string): string {
  const ext = name.toLowerCase().split('.').pop();
  if (ext === 'doc') return 'application/msword';
  if (ext === 'docx') return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
  return 'application/pdf';
}
