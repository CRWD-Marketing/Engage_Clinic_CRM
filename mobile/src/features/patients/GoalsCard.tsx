import Ionicons from '@expo/vector-icons/Ionicons';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import { errorMessage } from '@/api/errors';
import type { RecommendedGoal } from '@/api/types';
import { AppText } from '@/components/AppText';
import { Card } from '@/components/Card';
import { Banner } from '@/components/StateViews';
import { TextField } from '@/components/TextField';
import { colors, fonts, patientColors, radius, spacing } from '@/theme';
import { formatDayMonthShort } from '@/utils/dates';

type Props = {
  patientId: number;
  hasSessionToday: boolean;
  recommended: RecommendedGoal[];
  todaysGoalIds: number[];
  /** Coordinators/therapists may save; view-only users may not. */
  canEdit: boolean;
};

/**
 * "Session goals — worked on today" (patient/show.blade.php). Like the web,
 * every tick saves straight away (POST /patient/{id}/goals/today with the
 * full selection), and a typed goal that matches nothing can be added as a
 * custom goal.
 */
export function GoalsCard({ patientId, hasSessionToday, recommended, todaysGoalIds, canEdit }: Props) {
  const [goals, setGoals] = useState(recommended);
  const [selected, setSelected] = useState<number[]>(todaysGoalIds);
  const [sessionLogged, setSessionLogged] = useState(hasSessionToday);
  const [search, setSearch] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [savedFlash, setSavedFlash] = useState(false);

  const term = search.trim().toLowerCase();
  const visible = term ? goals.filter((g) => g.goal.title.toLowerCase().includes(term)) : goals;
  const exactMatch = goals.some((g) => g.goal.title.trim().toLowerCase() === term);

  async function save(nextIds: number[], newTitles: string[] = []) {
    const previous = selected;
    setSelected(nextIds);
    setSaving(true);
    setError(null);
    setSavedFlash(false);
    try {
      const res = await api.patients.saveTodayGoals(patientId, { goal_ids: nextIds, new_goal_titles: newTitles });
      if (res.created_goals.length > 0) {
        const createdIds = res.created_goals.map((g) => g.id);
        setGoals((current) => [
          ...current,
          ...res.created_goals
            .filter((g) => !current.some((c) => c.goal.id === g.id))
            .map((g) => ({
              goal: { id: g.id, patient_id: patientId, title: g.title, progress_percent: 0, created_at: '', updated_at: '' },
              used: 0,
              last_used_at: null,
            })),
        ]);
        setSelected([...new Set([...nextIds, ...createdIds])]);
      }
      setSessionLogged(true);
      setSavedFlash(true);
    } catch (e) {
      setSelected(previous);
      setError(errorMessage(e));
    } finally {
      setSaving(false);
    }
  }

  const toggle = (goalId: number) => {
    if (!canEdit || saving) return;
    save(selected.includes(goalId) ? selected.filter((id) => id !== goalId) : [...selected, goalId]);
  };

  const addCustom = async () => {
    const title = search.trim();
    if (!title || saving) return;
    await save(selected, [title]);
    setSearch('');
  };

  return (
    <Card style={styles.card}>
      <View style={styles.titleRow}>
        <AppText variant="heading" style={styles.flex}>
          Session goals — worked on today
        </AppText>
        {selected.length > 0 ? (
          <AppText style={styles.count}>{selected.length} selected</AppText>
        ) : null}
      </View>

      {!sessionLogged ? (
        <AppText variant="caption">
          No session scheduled today for this patient — saving goals below will log one automatically.
        </AppText>
      ) : null}
      {error ? <Banner text={error} /> : null}

      {canEdit ? (
        <TextField
          label="Find or add a goal"
          icon="search-outline"
          value={search}
          onChangeText={setSearch}
          placeholder="Add a goal worked on this session…"
          returnKeyType="done"
        />
      ) : null}

      {term && !exactMatch && canEdit ? (
        <View style={styles.noMatch}>
          {visible.length === 0 ? (
            <AppText variant="caption">No goal on record matches that — add it as a custom goal below.</AppText>
          ) : null}
          <Pressable
            onPress={addCustom}
            disabled={saving}
            style={({ pressed }) => [styles.dashedBtn, pressed && styles.pressed]}
            accessibilityRole="button">
            <AppText variant="link">+ Add &quot;{search.trim()}&quot; as a custom goal</AppText>
          </Pressable>
        </View>
      ) : null}

      <View style={styles.listHead}>
        <AppText variant="label" style={styles.flex}>
          Recommended from previous sessions
        </AppText>
        {saving ? (
          <AppText variant="caption">Saving…</AppText>
        ) : savedFlash ? (
          <AppText variant="caption" color={colors.success}>
            Saved
          </AppText>
        ) : null}
      </View>

      {goals.length === 0 ? (
        <AppText variant="caption">
          No goals selected yet — pick what was targeted this session, or type a new one.
        </AppText>
      ) : null}

      {visible.map(({ goal, used, last_used_at }) => {
        const checked = selected.includes(goal.id);
        return (
          <Pressable
            key={goal.id}
            onPress={() => toggle(goal.id)}
            disabled={!canEdit || saving}
            accessibilityRole="checkbox"
            accessibilityState={{ checked, disabled: !canEdit || saving }}
            style={({ pressed }) => [styles.goal, checked && styles.goalChecked, pressed && styles.pressed]}>
            <Ionicons
              name={checked ? 'checkbox' : 'square-outline'}
              size={22}
              color={checked ? colors.pink : colors.textFaint}
            />
            <View style={styles.flex}>
              <AppText variant="body">{goal.title}</AppText>
              <AppText variant="caption">
                used in {used} of the last 10 sessions
                {last_used_at ? ` · last ${formatDayMonthShort(last_used_at)}` : ''}
              </AppText>
            </View>
          </Pressable>
        );
      })}
    </Card>
  );
}

const styles = StyleSheet.create({
  card: { gap: spacing.md },
  flex: { flex: 1 },
  titleRow: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.sm },
  count: { fontFamily: fonts.bodyExtraBold, fontSize: 12, color: colors.pink },
  noMatch: { gap: spacing.sm },
  dashedBtn: {
    borderWidth: 1.5,
    borderStyle: 'dashed',
    borderColor: colors.pink,
    borderRadius: radius.input,
    paddingVertical: spacing.md,
    paddingHorizontal: spacing.md,
    alignItems: 'center',
  },
  listHead: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, marginTop: spacing.xs },
  goal: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: spacing.sm,
    padding: spacing.md,
    borderRadius: radius.input,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
  },
  goalChecked: { borderColor: colors.pink, backgroundColor: patientColors.goalSelectedBg },
  pressed: { opacity: 0.75 },
});
