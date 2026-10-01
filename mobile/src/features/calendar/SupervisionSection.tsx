import { useState } from 'react';
import { StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage, isApiError } from '@/api/errors';
import type { CalendarSessionPayload } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Banner } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { colors, fonts, sessionCategoryColors, spacing } from '@/theme';

const NOTES_MAX = 2000;

type Supervision = Pick<CalendarSessionPayload, 'supervised' | 'supervised_by_name' | 'supervised_at' | 'supervision_notes'>;

/**
 * Supervision on a session (calendar/index.blade.php "Log supervision"):
 * everyone sees a logged supervision; managers (CalendarController::canManage)
 * can log one on a session from a previous day (POST /calendar/{id}/supervision)
 * or remove it (DELETE). The rules for when it's allowed come from the
 * server's `can_supervise`.
 */
export function SupervisionSection({ session, canManage }: { session: CalendarSessionPayload; canManage: boolean }) {
  const [state, setState] = useState<Supervision>(session);
  const [draft, setDraft] = useState('');
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | undefined>();
  const [flash, setFlash] = useState<string | null>(null);

  const sup = sessionCategoryColors.supervision;
  const inactive = session.status === 'cancelled' || session.status === 'closed';

  async function log() {
    setSaving(true);
    setError(undefined);
    try {
      const res = await api.calendar.supervise(session.id, draft);
      setState(res.session);
      setFlash(res.message);
      setEditing(false);
      setDraft('');
    } catch (e) {
      setError(isApiError(e) ? (e.fieldError('notes') ?? e.message) : errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  async function remove() {
    setSaving(true);
    setError(undefined);
    try {
      const res = await api.calendar.unsupervise(session.id);
      setState({ supervised: false, supervised_by_name: null, supervised_at: null, supervision_notes: null });
      setFlash(res.message);
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  if (state.supervised) {
    return (
      <Card style={[styles.section, { backgroundColor: sup.bg, borderColor: sup.bg }]}>
        <AppText style={[styles.title, { color: sup.fg }]}>★ Supervised</AppText>
        {state.supervised_by_name ? (
          <AppText variant="caption" color={sup.fg}>
            {state.supervised_by_name}
            {state.supervised_at ? ` · ${state.supervised_at}` : ''}
          </AppText>
        ) : null}
        {state.supervision_notes ? <AppText variant="body">{state.supervision_notes}</AppText> : null}
        {flash ? <Banner text={flash} tone="success" /> : null}
        {error ? <Banner text={error} /> : null}
        {canManage ? <Button title="Remove supervision" variant="secondary" onPress={remove} loading={saving} /> : null}
      </Card>
    );
  }

  if (!canManage || inactive) {
    return flash ? <Banner text={flash} tone="success" /> : null;
  }

  return (
    <Card style={styles.section}>
      <AppText variant="heading">Supervision</AppText>
      {flash ? <Banner text={flash} tone="success" /> : null}
      {!session.can_supervise ? (
        <AppText variant="caption">Supervision can be logged once the day of the session has passed.</AppText>
      ) : editing ? (
        <>
          <TextField
            label="Supervision note"
            value={draft}
            onChangeText={setDraft}
            placeholder="What did you observe? This is the comment carried onto the invoice."
            multiline
            maxLength={NOTES_MAX}
            autoFocus
            error={error}
          />
          <View style={styles.actions}>
            <View style={styles.flex}>
              <Button title="Cancel" variant="secondary" onPress={() => setEditing(false)} disabled={saving} />
            </View>
            <View style={styles.flex}>
              <Button title="Log supervision" onPress={log} loading={saving} disabled={!draft.trim()} />
            </View>
          </View>
        </>
      ) : (
        <Button title="★ Log supervision" variant="secondary" onPress={() => setEditing(true)} />
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  section: { gap: spacing.sm },
  title: { fontFamily: fonts.bodyExtraBold, fontSize: 14, color: colors.text },
  actions: { flexDirection: 'row', gap: spacing.sm },
  flex: { flex: 1 },
});
