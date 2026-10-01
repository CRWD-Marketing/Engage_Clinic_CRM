import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '@/api/client';
import type { MyCalendarDay, MyCalendarWeek } from '@/api/types';
import { levelFor } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Card } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, fonts, leaveColors, radius, spacing } from '@/theme';
import {
  addDays,
  dayOfMonth,
  formatDayMonth,
  formatDayMonthYear,
  formatLongDate,
  formatWeekdayAbbrev,
  mondayOf,
  todayYmd,
  type Ymd,
} from '@/utils/dates';

import { CalendarPlaceholder } from './CalendarPlaceholder';
import { SessionCard } from './SessionCard';

/**
 * "My calendar" (CalendarController::myCalendar / calendar/my.blade.php):
 * the read-only week for users whose calendar level is "own". Managers get
 * the full clinic calendar on the web; it is not on mobile yet.
 */
export function MyScheduleScreen() {
  const user = useCurrentUser();
  if (levelFor(user, 'calendar') !== 'own') return <CalendarPlaceholder />;
  return <MyWeek viewerId={user.id} />;
}

function MyWeek({ viewerId }: { viewerId: number }) {
  const today = todayYmd();
  const [selected, setSelected] = useState<Ymd>(today);
  const monday = mondayOf(selected);

  const query = useApiQuery(`calendar.myWeek:${monday}`, () => api.calendar.myWeek(monday));
  useRefetchOnFocus(query.reload);

  const week = query.data?.monday === monday ? query.data : undefined;

  const goToWeek = (offsetWeeks: number) => {
    const nextMonday = addDays(monday, offsetWeeks * 7);
    // Land on today when it's in the new week, else on its Monday.
    setSelected(mondayOf(today) === nextMonday ? today : nextMonday);
  };

  const header = (
    <WeekHeader
      monday={monday}
      week={week}
      selected={selected}
      today={today}
      onSelect={setSelected}
      onPrev={() => goToWeek(-1)}
      onNext={() => goToWeek(1)}
      onToday={() => setSelected(today)}
    />
  );

  if (!week) {
    return (
      <Screen scroll={false} header={header} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const day = week.days.find((d) => d.iso === selected)!;

  return (
    <Screen header={header} refreshing={query.refreshing} onRefresh={query.refresh} edges={[]}>
      <AppText variant="heading">
        {formatLongDate(day.iso)}
        {day.is_today ? ' · today' : ''}
      </AppText>
      <DayAgenda day={day} viewerId={viewerId} />
    </Screen>
  );
}

type HeaderProps = {
  monday: Ymd;
  week: MyCalendarWeek | undefined;
  selected: Ymd;
  today: Ymd;
  onSelect: (d: Ymd) => void;
  onPrev: () => void;
  onNext: () => void;
  onToday: () => void;
};

function WeekHeader({ monday, week, selected, today, onSelect, onPrev, onNext, onToday }: HeaderProps) {
  const sunday = addDays(monday, 6);
  const days = Array.from({ length: 7 }, (_, i) => addDays(monday, i));
  const inThisWeek = mondayOf(today) === monday;

  return (
    <SafeAreaView edges={['top']} style={styles.headerWrap}>
      <View style={styles.titleRow}>
        <View style={styles.titleText}>
          <AppText variant="title">My calendar</AppText>
          <AppText variant="caption">
            {formatDayMonth(monday)} – {formatDayMonthYear(sunday)}
          </AppText>
        </View>
        {!inThisWeek ? (
          <Pressable onPress={onToday} style={styles.todayBtn} accessibilityRole="button" hitSlop={6}>
            <AppText variant="link">Today</AppText>
          </Pressable>
        ) : null}
        <NavArrow icon="chevron-back" label="Previous week" onPress={onPrev} />
        <NavArrow icon="chevron-forward" label="Next week" onPress={onNext} />
      </View>

      <AppText variant="caption" numberOfLines={2}>
        {week
          ? `${week.therapist_name} · ${week.designation} · ${week.therapy_hours} therapy hours this week`
          : ' '}
      </AppText>
      <View style={styles.locked}>
        <Ionicons name="lock-closed-outline" size={12} color={colors.textMuted} />
        <AppText variant="caption">Your schedule is set by your supervisor</AppText>
      </View>

      <View style={styles.strip}>
        {days.map((iso) => {
          const info = week?.days.find((d) => d.iso === iso);
          const active = iso === selected;
          const isToday = iso === today;
          const count = info?.sessions.filter((s) => s.status !== 'cancelled').length ?? 0;
          return (
            <Pressable
              key={iso}
              onPress={() => onSelect(iso)}
              style={[styles.dayPill, active && styles.dayPillActive]}
              accessibilityRole="button"
              accessibilityState={{ selected: active }}
              accessibilityLabel={`${formatLongDate(iso)}${isToday ? ', today' : ''}, ${count} sessions`}>
              <AppText style={[styles.dayName, isToday && styles.todayText, active && styles.activeText]}>
                {formatWeekdayAbbrev(iso)}
              </AppText>
              <AppText style={[styles.dayNum, isToday && styles.todayText, active && styles.activeText]}>
                {dayOfMonth(iso)}
              </AppText>
              <View
                style={[
                  styles.dot,
                  info?.leave ? { backgroundColor: leaveColors.fg } : count > 0 ? styles.dotOn : null,
                  active && count > 0 && !info?.leave && styles.dotActive,
                ]}
              />
            </Pressable>
          );
        })}
      </View>
    </SafeAreaView>
  );
}

function NavArrow({ icon, label, onPress }: { icon: 'chevron-back' | 'chevron-forward'; label: string; onPress: () => void }) {
  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityLabel={label}
      hitSlop={6}
      style={({ pressed }) => [styles.arrow, pressed && styles.arrowPressed]}>
      <Ionicons name={icon} size={18} color={colors.navy} />
    </Pressable>
  );
}

function DayAgenda({ day, viewerId }: { day: MyCalendarDay; viewerId: number }) {
  return (
    <View style={styles.agenda}>
      {day.leave ? (
        <View style={[styles.leave, { backgroundColor: leaveColors.bg }]}>
          <AppText style={[styles.leaveTitle, { color: leaveColors.fg }]}>On leave · {day.leave.leave_type}</AppText>
          <AppText variant="caption" color={leaveColors.fg}>
            {day.leave.reason}
          </AppText>
        </View>
      ) : day.is_weekend && day.sessions.length === 0 ? (
        <Card>
          <AppText style={styles.off}>OFF</AppText>
        </Card>
      ) : day.sessions.length === 0 ? (
        <Card>
          <EmptyRow text="No sessions" />
        </Card>
      ) : null}

      {day.sessions.map((s) => (
        <SessionCard
          key={s.id}
          session={s}
          viewerId={viewerId}
          onPress={() => router.push({ pathname: '/calendar/[id]', params: { id: String(s.id) } })}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  headerWrap: {
    backgroundColor: colors.page,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    gap: 2,
  },
  titleRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm, paddingTop: spacing.sm },
  titleText: { flex: 1 },
  todayBtn: { paddingHorizontal: spacing.sm, paddingVertical: 6 },
  arrow: {
    width: 36,
    height: 36,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    alignItems: 'center',
    justifyContent: 'center',
  },
  arrowPressed: { borderColor: colors.pink },
  locked: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  strip: { flexDirection: 'row', gap: 4, marginTop: spacing.sm },
  dayPill: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 6,
    borderRadius: radius.input,
    gap: 1,
    minHeight: 56,
  },
  dayPillActive: { backgroundColor: colors.navy },
  dayName: { fontFamily: fonts.bodyBold, fontSize: 10.5, color: colors.textMuted, letterSpacing: 0.5 },
  dayNum: { fontFamily: fonts.heading, fontSize: 18, lineHeight: 22, color: colors.navy },
  todayText: { color: colors.pink },
  activeText: { color: colors.white },
  dot: { width: 5, height: 5, borderRadius: 3, marginTop: 2 },
  dotOn: { backgroundColor: colors.pink },
  dotActive: { backgroundColor: colors.white },
  agenda: { gap: spacing.sm },
  leave: { borderRadius: radius.input, padding: spacing.md, gap: 2 },
  leaveTitle: { fontFamily: fonts.bodyExtraBold, fontSize: 14 },
  off: { textAlign: 'center', fontFamily: fonts.bodyExtraBold, color: colors.textFaint, letterSpacing: 2 },
});
