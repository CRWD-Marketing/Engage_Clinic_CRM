import { useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { NoteReviewFilter, PatientNote } from '@/api/types';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Button } from '@/components/Button';
import { Card } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { Banner, EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { colors, fonts, radius, spacing } from '@/theme';

import { NoteReviewCard } from './NoteReviewCard';

/**
 * PROPOSED (mobile-only for now): the supervisor's session-note review queue.
 * The web's supervisor dashboard lists unsigned and flagged notes but has no
 * way to act on them; here they can be signed off (one or many) and
 * flagged/unflagged. Backed by the proposed /patient-notes endpoints.
 */
export function NotesReviewScreen() {
  const user = useCurrentUser();
  const params = useLocalSearchParams<{ filter?: string }>();
  const [filter, setFilter] = useState<NoteReviewFilter>(params.filter === 'flagged' ? 'flagged' : 'unsigned');
  const query = useApiQuery(`notes.review:${filter}`, () => api.notes.review(filter));

  const [selected, setSelected] = useState<number[]>([]);
  const [busyIds, setBusyIds] = useState<number[]>([]);
  const [flash, setFlash] = useState<{ text: string; tone: 'success' | 'danger' } | null>(null);
  // Local edits on top of the loaded list, until the next refresh.
  const [overrides, setOverrides] = useState<Record<number, PatientNote | null>>({});

  const notes = (query.data ?? [])
    .map((n) => (n.id in overrides ? overrides[n.id] : n))
    .filter((n): n is PatientNote => n !== null);
  const selectable = filter === 'unsigned';

  const switchFilter = (next: NoteReviewFilter) => {
    if (next === filter) return;
    setFilter(next);
    setSelected([]);
    setOverrides({});
    setFlash(null);
  };

  /** Runs an action on some notes with their cards showing busy, then reports the outcome. */
  async function act(ids: number[], fn: () => Promise<string>) {
    setBusyIds((b) => [...b, ...ids]);
    setFlash(null);
    try {
      setFlash({ text: await fn(), tone: 'success' });
    } catch (e) {
      setFlash({ text: errorMessage(e), tone: 'danger' });
    } finally {
      setBusyIds((b) => b.filter((id) => !ids.includes(id)));
    }
  }

  const signOff = (ids: number[]) =>
    act(ids, async () => {
      const res = await api.notes.signOff(ids);
      // Signed notes leave the "awaiting" queue; on the flagged tab they stay, marked signed.
      setOverrides((o) => {
        const next = { ...o };
        for (const id of ids) {
          const current = notes.find((n) => n.id === id);
          next[id] =
            filter === 'unsigned' || !current
              ? null
              : {
                  ...current,
                  signed_off_at: new Date().toISOString(),
                  signed_off_by: user.id,
                  signed_off_by_name: `${user.first_name} ${user.last_name}`,
                };
        }
        return next;
      });
      setSelected((s) => s.filter((id) => !ids.includes(id)));
      return res.message;
    });

  const flag = async (id: number, reason: string) => {
    let ok = false;
    await act([id], async () => {
      const res = await api.notes.flag(id, reason);
      setOverrides((o) => ({ ...o, [id]: res.note }));
      ok = true;
      return 'Note flagged for review.';
    });
    return ok;
  };

  const unflag = (id: number) =>
    act([id], async () => {
      const res = await api.notes.unflag(id);
      setOverrides((o) => ({ ...o, [id]: filter === 'flagged' ? null : res.note }));
      return 'Flag removed.';
    });

  const toggleSelect = (id: number) =>
    setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));
  const allSelected = notes.length > 0 && notes.every((n) => selected.includes(n.id));

  const footer =
    selectable && selected.length > 0 ? (
      <SafeAreaView edges={['bottom']} style={styles.footer}>
        <Button
          title={`Sign off ${selected.length} selected`}
          onPress={() => signOff(selected)}
          loading={selected.some((id) => busyIds.includes(id))}
        />
      </SafeAreaView>
    ) : null;

  return (
    <View style={styles.flex}>
      <Screen edges={[]} refreshing={query.refreshing} onRefresh={() => { setOverrides({}); query.refresh(); }}>
        <View style={styles.tabs} accessibilityRole="tablist">
          {(['unsigned', 'flagged'] as const).map((f) => (
            <Pressable
              key={f}
              onPress={() => switchFilter(f)}
              accessibilityRole="tab"
              accessibilityState={{ selected: filter === f }}
              style={[styles.tab, filter === f && styles.tabActive]}>
              <AppText style={[styles.tabText, filter === f && styles.tabTextActive]}>
                {f === 'unsigned' ? 'Awaiting sign-off' : 'Flagged'}
              </AppText>
            </Pressable>
          ))}
        </View>

        {flash ? <Banner text={flash.text} tone={flash.tone} /> : null}

        {!query.data ? (
          query.error ? (
            <ErrorState error={query.error} onRetry={query.refresh} />
          ) : (
            <LoadingState />
          )
        ) : notes.length === 0 ? (
          <Card>
            <EmptyRow text={filter === 'unsigned' ? 'Nothing awaiting sign-off.' : 'Nothing flagged right now.'} />
          </Card>
        ) : (
          <>
            <View style={styles.listHead}>
              <AppText variant="caption" style={styles.flex}>
                {notes.length} {notes.length === 1 ? 'note' : 'notes'}
                {filter === 'unsigned' ? ' · oldest first' : ' · newest first'}
              </AppText>
              {selectable ? (
                <Pressable
                  onPress={() => setSelected(allSelected ? [] : notes.map((n) => n.id))}
                  accessibilityRole="button"
                  hitSlop={8}>
                  <AppText variant="link">{allSelected ? 'Clear selection' : 'Select all'}</AppText>
                </Pressable>
              ) : null}
            </View>
            {notes.map((note) => (
              <NoteReviewCard
                key={note.id}
                note={note}
                selectable={selectable}
                selected={selected.includes(note.id)}
                busy={busyIds.includes(note.id)}
                onToggleSelect={() => toggleSelect(note.id)}
                onSignOff={() => signOff([note.id])}
                onFlag={(reason) => flag(note.id, reason)}
                onUnflag={() => unflag(note.id)}
              />
            ))}
          </>
        )}
      </Screen>
      {footer}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  tabs: {
    flexDirection: 'row',
    backgroundColor: colors.pageAlt,
    borderRadius: radius.input,
    padding: 4,
    gap: 4,
  },
  tab: { flex: 1, minHeight: 40, alignItems: 'center', justifyContent: 'center', borderRadius: 8 },
  tabActive: { backgroundColor: colors.card },
  tabText: { fontFamily: fonts.bodyBold, fontSize: 13, color: colors.textMuted },
  tabTextActive: { color: colors.navy },
  listHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm },
  footer: {
    paddingHorizontal: spacing.lg,
    paddingTop: spacing.sm,
    paddingBottom: spacing.sm,
    backgroundColor: colors.page,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
});
