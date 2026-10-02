import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { useState, type ReactNode } from 'react';
import { Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { api } from '@/api/client';
import type { CalendarFeed, CalendarFeedLeave, MyCalendarDay, MySessionPayload, User } from '@/api/types';
import { canAccessFeature, canManageCalendar, levelFor } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Card } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, colorsForActivity, fonts, leaveColors, monthColors, radius, spacing } from '@/theme';
import {
  addDays,
  addMonths,
  dayOfMonth,
  daysInMonth,
  formatDayMonth,
  formatDayMonthYear,
  formatLongDate,
  formatMonthLong,
  formatWeekdayAbbrev,
  mondayOf,
  monthEndOf,
  monthStartOf,
  todayYmd,
  weekdayIndex,
  type Ymd,
} from '@/utils/dates';

import { SessionCard } from './SessionCard';
import { isDirectTherapy } from './sessionStatus';

type Mode = 'week' | 'month';
/** "own": a therapist's own schedule. "clinic": every therapist (managers). */
type Scope = 'own' | 'clinic';

/**
 * The calendar tab.
 * - Own scope (calendar level "own", i.e. therapists): week from
 *   CalendarController::myCalendar (calendar/my.blade.php), month from the feed.
 * - Clinic scope (everyone else with calendar access): week and month from
 *   GET /calendar/feed across all therapists, with a therapist filter.
 *   Booking and rescheduling stay on the web; managers can log supervision
 *   from a past session's detail screen.
 * The month grid mirrors calendar/index.blade.php; tapping a day lists it below.
 */
export function MyScheduleScreen() {
  const user = useCurrentUser();
  const scope: Scope = levelFor(user, 'calendar') === 'own' ? 'own' : 'clinic';
  const [mode, setMode] = useState<Mode>('week');
  const [selected, setSelected] = useState<Ymd>(todayYmd());
  const [therapistId, setTherapistId] = useState<number | null>(null);

  const props: ViewProps = { user, scope, selected, onSelect: setSelected, onMode: setMode, therapistId, onTherapist: setTherapistId };
  return mode === 'week' ? <WeekView {...props} /> : <MonthView {...props} />;
}

type ViewProps = {
  user: User;
  scope: Scope;
  selected: Ymd;
  onSelect: (d: Ymd) => void;
  onMode: (m: Mode) => void;
  therapistId: number | null;
  onTherapist: (id: number | null) => void;
};

// ---------------------------------------------------------------------------
// Data helpers
// ---------------------------------------------------------------------------

/**
 * One day in the shape the agenda renders, built from a feed. `personal` is
 * true when the feed is one person's calendar (own scope, or a therapist
 * filter); only then does a leave entry mean "this day is leave".
 */
function dayFromFeed(feed: CalendarFeed, iso: Ymd, today: Ymd, personal: boolean): MyCalendarDay {
  const leave = personal ? feed.leaves.find((l) => l.leave_date === iso) : undefined;
  return {
    iso,
    is_today: iso === today,
    is_weekend: weekdayIndex(iso) >= 5,
    sessions: feed.sessions.filter((s) => s.session_date === iso),
    leave: leave
      ? { id: leave.id, user_id: leave.user_id, leave_date: leave.leave_date, leave_type: leave.leave_type, reason: leave.reason }
      : null,
  };
}

/** Narrow a feed to one therapist (client-side; Laravel's feed takes ?therapist_id= too). */
function filterFeed(feed: CalendarFeed, therapistId: number | null): CalendarFeed {
  if (therapistId === null) return feed;
  return {
    sessions: feed.sessions.filter((s) => s.therapist_id === therapistId),
    leaves: feed.leaves.filter((l) => l.user_id === therapistId),
  };
}

/** Direct therapy hours (completed + scheduled), like the web's "therapy hours this week". */
function therapyHours(sessions: MySessionPayload[]): number {
  const minutes = sessions
    .filter((s) => isDirectTherapy(s) && (s.status === 'completed' || s.status === 'scheduled'))
    .reduce((sum, s) => sum + s.duration_minutes, 0);
  return Math.round((minutes / 60) * 10) / 10;
}

function therapistsIn(feed: CalendarFeed | undefined): { id: number; name: string }[] {
  const byId = new Map<number, string>();
  for (const s of feed?.sessions ?? []) byId.set(s.therapist_id, s.therapist_name ?? `#${s.therapist_id}`);
  return [...byId].map(([id, name]) => ({ id, name })).sort((a, b) => a.name.localeCompare(b.name));
}

/** Clinic scope with all therapists: who is on leave that day (shown as a list, not as a leave day). */
function staffLeaveOn(feed: CalendarFeed | undefined, iso: Ymd, scope: Scope, therapistId: number | null) {
  if (!feed || scope === 'own' || therapistId !== null) return [];
  return feed.leaves.filter((l) => l.leave_date === iso);
}

function clinicSubtitle(sessions: MySessionPayload[], span: 'week' | 'month', who: string | null): string {
  const count = sessions.filter((s) => s.status !== 'cancelled').length;
  return `${who ?? 'All therapists'} · ${count} ${count === 1 ? 'session' : 'sessions'} · ${therapyHours(sessions)} therapy hours this ${span}`;
}

// ---------------------------------------------------------------------------
// Week view
// ---------------------------------------------------------------------------

type WeekData = { days: MyCalendarDay[]; subtitle: string; feed?: CalendarFeed };

function WeekView({ user, scope, selected, onSelect, onMode, therapistId, onTherapist }: ViewProps) {
  const today = todayYmd();
  const monday = mondayOf(selected);
  const sunday = addDays(monday, 6);

  const query = useApiQuery<WeekData>(`calendar.week:${scope}:${monday}`, async () => {
    if (scope === 'own') {
      const week = await api.calendar.myWeek(monday);
      return {
        days: week.days,
        subtitle: `${week.therapist_name} · ${week.designation} · ${week.therapy_hours} therapy hours this week`,
      };
    }
    const feed = await api.calendar.feed(monday, sunday);
    return { days: [], subtitle: '', feed };
  });
  useRefetchOnFocus(query.reload);

  let data: WeekData | undefined = query.data;
  if (data?.feed) {
    const feed = filterFeed(data.feed, therapistId);
    const who = therapistsIn(data.feed).find((t) => t.id === therapistId)?.name ?? null;
    data = {
      ...data,
      days: Array.from({ length: 7 }, (_, i) => dayFromFeed(feed, addDays(monday, i), today, therapistId !== null)),
      subtitle: clinicSubtitle(feed.sessions, 'week', who),
    };
  }
  // Ignore a response for a different week while the next one loads.
  const ready = data && data.days[0]?.iso === monday ? data : undefined;

  const goToWeek = (offsetWeeks: number) => {
    const nextMonday = addDays(monday, offsetWeeks * 7);
    // Land on today when it's in the new week, else on its Monday.
    onSelect(mondayOf(today) === nextMonday ? today : nextMonday);
  };

  const header = (
    <TopBar
      user={user}
      scope={scope}
      mode="week"
      onMode={onMode}
      rangeLabel={`${formatDayMonth(monday)} – ${formatDayMonthYear(sunday)}`}
      subtitle={ready ? ready.subtitle : ' '}
      onPrev={() => goToWeek(-1)}
      onNext={() => goToWeek(1)}
      prevLabel="Previous week"
      nextLabel="Next week"
      showToday={mondayOf(today) !== monday}
      onToday={() => onSelect(today)}
      therapists={scope === 'clinic' ? therapistsIn(query.data?.feed) : null}
      therapistId={therapistId}
      onTherapist={onTherapist}>
      <WeekStrip days={ready?.days} monday={monday} selected={selected} today={today} onSelect={onSelect} />
    </TopBar>
  );

  if (!ready) {
    return (
      <Screen scroll={false} header={header} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const day = ready.days.find((d) => d.iso === selected)!;
  return (
    <Screen header={header} refreshing={query.refreshing} onRefresh={query.refresh} edges={[]}>
      <DayHeading day={day} />
      <DayAgenda
        day={day}
        viewerId={user.id}
        showTherapist={scope === 'clinic'}
        staffOnLeave={staffLeaveOn(ready.feed, selected, scope, therapistId)}
      />
    </Screen>
  );
}

function WeekStrip({
  days,
  monday,
  selected,
  today,
  onSelect,
}: {
  days: MyCalendarDay[] | undefined;
  monday: Ymd;
  selected: Ymd;
  today: Ymd;
  onSelect: (d: Ymd) => void;
}) {
  return (
    <View style={styles.strip}>
      {Array.from({ length: 7 }, (_, i) => addDays(monday, i)).map((iso) => {
        const info = days?.find((d) => d.iso === iso);
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
  );
}

// ---------------------------------------------------------------------------
// Month view
// ---------------------------------------------------------------------------

function MonthView({ user, scope, selected, onSelect, onMode, therapistId, onTherapist }: ViewProps) {
  const today = todayYmd();
  const first = monthStartOf(selected);
  const last = monthEndOf(selected);
  const query = useApiQuery(`calendar.feed:${first}`, () => api.calendar.feed(first, last));
  useRefetchOnFocus(query.reload);

  const raw = query.data;
  const feed = raw ? filterFeed(raw, scope === 'clinic' ? therapistId : null) : undefined;
  const who = therapistsIn(raw).find((t) => t.id === therapistId)?.name ?? null;

  const goToMonth = (offset: number) => {
    const target = addMonths(first, offset);
    // Land on today when it's in the new month, else on its 1st.
    onSelect(monthStartOf(today) === target ? today : target);
  };

  const subtitle = !feed
    ? ' '
    : scope === 'own'
      ? `${user.first_name} ${user.last_name} · ${user.job_title ?? 'Therapist'} · ${therapyHours(feed.sessions)} therapy hours this month`
      : clinicSubtitle(feed.sessions, 'month', who);

  const header = (
    <TopBar
      user={user}
      scope={scope}
      mode="month"
      onMode={onMode}
      rangeLabel={formatMonthLong(first)}
      subtitle={subtitle}
      onPrev={() => goToMonth(-1)}
      onNext={() => goToMonth(1)}
      prevLabel="Previous month"
      nextLabel="Next month"
      showToday={monthStartOf(today) !== first}
      onToday={() => onSelect(today)}
      therapists={scope === 'clinic' ? therapistsIn(raw) : null}
      therapistId={therapistId}
      onTherapist={onTherapist}
    />
  );

  if (!feed) {
    return (
      <Screen scroll={false} header={header} edges={[]}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const personal = scope === 'own' || therapistId !== null;
  const day = dayFromFeed(feed, selected, today, personal);
  return (
    <Screen header={header} refreshing={query.refreshing} onRefresh={query.refresh} edges={[]}>
      <MonthGrid first={first} feed={feed} selected={selected} today={today} onSelect={onSelect} personal={personal} />
      <DayHeading day={day} />
      <DayAgenda
        day={day}
        viewerId={user.id}
        showTherapist={scope === 'clinic'}
        staffOnLeave={staffLeaveOn(raw, selected, scope, therapistId)}
      />
    </Screen>
  );
}

const DOW = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
const MONTH_CHIPS = 3;

function MonthGrid({
  first,
  feed,
  selected,
  today,
  onSelect,
  personal,
}: {
  first: Ymd;
  feed: CalendarFeed;
  selected: Ymd;
  today: Ymd;
  onSelect: (d: Ymd) => void;
  personal: boolean;
}) {
  const cells: (Ymd | null)[] = [
    ...Array.from({ length: weekdayIndex(first) }, () => null),
    ...Array.from({ length: daysInMonth(first) }, (_, i) => addDays(first, i)),
  ];
  while (cells.length % 7 !== 0) cells.push(null);
  const weeks = Array.from({ length: cells.length / 7 }, (_, w) => cells.slice(w * 7, w * 7 + 7));

  return (
    <View style={styles.month}>
      <View style={styles.monthRow}>
        {DOW.map((d) => (
          <AppText key={d} style={styles.dow}>
            {d}
          </AppText>
        ))}
      </View>
      {weeks.map((week, w) => (
        <View key={w} style={styles.monthRow}>
          {week.map((iso, i) =>
            iso ? (
              <MonthCell
                key={iso}
                iso={iso}
                feed={feed}
                isToday={iso === today}
                isSelected={iso === selected}
                personal={personal}
                onPress={() => onSelect(iso)}
              />
            ) : (
              <View key={`blank-${w}-${i}`} style={styles.blankCell} />
            ),
          )}
        </View>
      ))}
    </View>
  );
}

function MonthCell({
  iso,
  feed,
  isToday,
  isSelected,
  personal,
  onPress,
}: {
  iso: Ymd;
  feed: CalendarFeed;
  isToday: boolean;
  isSelected: boolean;
  personal: boolean;
  onPress: () => void;
}) {
  const sessions = feed.sessions.filter((s) => s.session_date === iso);
  const onLeave = personal && feed.leaves.some((l) => l.leave_date === iso);
  const hasNote = sessions.some((s) => s.therapist_note);
  const more = sessions.length - MONTH_CHIPS;

  return (
    <Pressable
      onPress={onPress}
      accessibilityRole="button"
      accessibilityState={{ selected: isSelected }}
      accessibilityLabel={`${formatLongDate(iso)}${isToday ? ', today' : ''}, ${sessions.length} sessions${
        onLeave ? ', on leave' : ''
      }${hasNote ? ', has session notes' : ''}`}
      style={({ pressed }) => [
        styles.cell,
        weekdayIndex(iso) >= 5 && styles.cellWeekend,
        onLeave && { backgroundColor: leaveColors.bg },
        isToday && styles.cellToday,
        isSelected && styles.cellSelected,
        pressed && styles.cellPressed,
      ]}>
      <View style={styles.cellTop}>
        <AppText style={[styles.cellDate, isToday && styles.todayText]}>{dayOfMonth(iso)}</AppText>
        {sessions.length > 0 ? (
          <View style={styles.countBadge}>
            <AppText style={styles.countText}>{sessions.length}</AppText>
          </View>
        ) : null}
      </View>
      {sessions.slice(0, MONTH_CHIPS).map((s) => {
        const c = colorsForActivity(s.activity_type, s.category);
        return (
          <View key={s.id} style={[styles.monthChip, { backgroundColor: c.bg }]}>
            <AppText
              numberOfLines={1}
              style={[styles.monthChipText, { color: c.fg }, s.status === 'cancelled' && styles.strike]}>
              {s.patient_name}
            </AppText>
          </View>
        );
      })}
      {more > 0 || hasNote ? (
        <View style={styles.cellBottom}>
          {more > 0 ? <AppText style={styles.more}>+{more}</AppText> : <View />}
          {hasNote ? <Ionicons name="document-text" size={10} color={monthColors.noteMark} /> : null}
        </View>
      ) : null}
    </Pressable>
  );
}

// ---------------------------------------------------------------------------
// Shared pieces
// ---------------------------------------------------------------------------

type TopBarProps = {
  user: User;
  scope: Scope;
  mode: Mode;
  onMode: (m: Mode) => void;
  rangeLabel: string;
  subtitle: string;
  onPrev: () => void;
  onNext: () => void;
  prevLabel: string;
  nextLabel: string;
  showToday: boolean;
  onToday: () => void;
  /** Clinic scope only: therapists to filter by (null hides the filter). */
  therapists: { id: number; name: string }[] | null;
  therapistId: number | null;
  onTherapist: (id: number | null) => void;
  children?: ReactNode;
};

function TopBar(props: TopBarProps) {
  const { user, scope, mode, onMode, rangeLabel, subtitle, therapists, therapistId, onTherapist, children } = props;
  const lockLine =
    scope === 'own'
      ? 'Your schedule is set by your supervisor'
      : canManageCalendar(user)
        ? 'Booking and changes are on the web · open a past session to log supervision'
        : 'View only · booking and changes are on the web';

  return (
    <SafeAreaView edges={['top']} style={styles.headerWrap}>
      <View style={styles.titleRow}>
        <View style={styles.titleText}>
          <AppText variant="title">{scope === 'own' ? 'My calendar' : 'Calendar'}</AppText>
          <AppText variant="caption">{rangeLabel}</AppText>
        </View>
        {props.showToday ? (
          <Pressable onPress={props.onToday} style={styles.todayBtn} accessibilityRole="button" hitSlop={6}>
            <AppText variant="link">Today</AppText>
          </Pressable>
        ) : null}
        <NavArrow icon="chevron-back" label={props.prevLabel} onPress={props.onPrev} />
        <NavArrow icon="chevron-forward" label={props.nextLabel} onPress={props.onNext} />
      </View>

      <View style={styles.toggle} accessibilityRole="tablist">
        {(['week', 'month'] as const).map((m) => (
          <Pressable
            key={m}
            onPress={() => onMode(m)}
            accessibilityRole="tab"
            accessibilityState={{ selected: mode === m }}
            style={[styles.toggleBtn, mode === m && styles.toggleBtnActive]}>
            <AppText style={[styles.toggleText, mode === m && styles.toggleTextActive]}>
              {m === 'week' ? 'Week' : 'Month'}
            </AppText>
          </Pressable>
        ))}
      </View>

      {therapists ? (
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterRow}>
          {[{ id: null as number | null, name: 'All therapists' }, ...therapists].map((t) => {
            const active = therapistId === t.id;
            return (
              <Pressable
                key={t.id ?? 'all'}
                onPress={() => onTherapist(t.id)}
                accessibilityRole="button"
                accessibilityState={{ selected: active }}
                style={[styles.filterChip, active && styles.filterChipActive]}>
                <AppText style={[styles.filterText, active && styles.filterTextActive]}>{t.name}</AppText>
              </Pressable>
            );
          })}
        </ScrollView>
      ) : null}

      <AppText variant="caption" numberOfLines={2}>
        {subtitle}
      </AppText>
      <View style={styles.locked}>
        <Ionicons name={scope === 'own' ? 'lock-closed-outline' : 'information-circle-outline'} size={12} color={colors.textMuted} />
        <AppText variant="caption" style={styles.flex}>
          {lockLine}
        </AppText>
      </View>
      {canAccessFeature(user, 'therapists') && user.role !== 'THERAPIST' && levelFor(user, 'therapists') !== 'own' ? (
        <Pressable onPress={() => router.push('/therapists')} accessibilityRole="button" hitSlop={6}>
          <AppText variant="link">Therapists &amp; schedules →</AppText>
        </Pressable>
      ) : null}
      {children}
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

function DayHeading({ day }: { day: MyCalendarDay }) {
  return (
    <AppText variant="heading">
      {formatLongDate(day.iso)}
      {day.is_today ? ' · today' : ''}
    </AppText>
  );
}

function DayAgenda({
  day,
  viewerId,
  showTherapist,
  staffOnLeave,
}: {
  day: MyCalendarDay;
  viewerId: number;
  showTherapist: boolean;
  staffOnLeave: CalendarFeedLeave[];
}) {
  return (
    <View style={styles.agenda}>
      {staffOnLeave.map((l) => (
        <View key={l.id} style={[styles.leave, { backgroundColor: leaveColors.bg }]}>
          <AppText style={[styles.leaveTitle, { color: leaveColors.fg }]}>
            {l.user_name ?? 'Staff member'} on leave · {l.leave_type}
          </AppText>
          <AppText variant="caption" color={leaveColors.fg}>
            {l.reason}
          </AppText>
        </View>
      ))}
      {day.leave ? (
        <View style={[styles.leave, { backgroundColor: leaveColors.bg }]}>
          <AppText style={[styles.leaveTitle, { color: leaveColors.fg }]}>On leave · {day.leave.leave_type}</AppText>
          <AppText variant="caption" color={leaveColors.fg}>
            {day.leave.reason}
          </AppText>
        </View>
      ) : null}
      {!day.leave && day.is_weekend && day.sessions.length === 0 ? (
        <Card>
          <AppText style={styles.off}>OFF</AppText>
        </Card>
      ) : !day.leave && day.sessions.length === 0 ? (
        <Card>
          <EmptyRow text="No sessions" />
        </Card>
      ) : null}

      {day.sessions.map((s) => (
        <SessionCard
          key={s.id}
          session={s}
          viewerId={viewerId}
          showTherapist={showTherapist}
          onPress={() => router.push({ pathname: '/calendar/[id]', params: { id: String(s.id) } })}
        />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  headerWrap: {
    backgroundColor: colors.page,
    paddingHorizontal: spacing.lg,
    paddingBottom: spacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
    gap: 4,
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
  toggle: {
    flexDirection: 'row',
    backgroundColor: colors.pageAlt,
    borderRadius: radius.input,
    padding: 3,
    gap: 3,
    marginVertical: 2,
  },
  toggleBtn: { flex: 1, minHeight: 34, alignItems: 'center', justifyContent: 'center', borderRadius: 8 },
  toggleBtnActive: { backgroundColor: colors.card },
  toggleText: { fontFamily: fonts.bodyBold, fontSize: 13, color: colors.textMuted },
  toggleTextActive: { color: colors.navy },
  filterRow: { gap: 6, paddingVertical: 2 },
  filterChip: {
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.card,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  filterChipActive: { backgroundColor: colors.navy, borderColor: colors.navy },
  filterText: { fontFamily: fonts.bodyBold, fontSize: 12, color: colors.textSecondary },
  filterTextActive: { color: colors.white },
  locked: { flexDirection: 'row', alignItems: 'center', gap: 4 },

  strip: { flexDirection: 'row', gap: 4, marginTop: spacing.xs },
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

  month: { gap: 4 },
  monthRow: { flexDirection: 'row', gap: 4 },
  dow: {
    flex: 1,
    textAlign: 'center',
    fontFamily: fonts.bodyExtraBold,
    fontSize: 9.5,
    color: monthColors.dow,
  },
  blankCell: { flex: 1 },
  cell: {
    flex: 1,
    minHeight: 72,
    padding: 3,
    gap: 2,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: monthColors.cellBorder,
    backgroundColor: colors.card,
    overflow: 'hidden',
  },
  cellWeekend: { backgroundColor: monthColors.weekendBg },
  cellToday: { borderColor: monthColors.today, borderWidth: 1.5 },
  cellSelected: { borderColor: monthColors.selected, borderWidth: 2 },
  cellPressed: { opacity: 0.7 },
  cellTop: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 3 },
  cellDate: { fontFamily: fonts.bodyExtraBold, fontSize: 11.5, color: colors.text },
  countBadge: {
    minWidth: 15,
    height: 15,
    paddingHorizontal: 3,
    borderRadius: 8,
    backgroundColor: monthColors.countBg,
    alignItems: 'center',
    justifyContent: 'center',
  },
  countText: { fontFamily: fonts.bodyExtraBold, fontSize: 9, lineHeight: 12, color: monthColors.countFg },
  monthChip: { borderRadius: 4, paddingHorizontal: 2, paddingVertical: 1 },
  monthChipText: { fontFamily: fonts.bodyBold, fontSize: 8.5, lineHeight: 12 },
  strike: { textDecorationLine: 'line-through' },
  cellBottom: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 'auto' },
  more: { fontFamily: fonts.bodyBold, fontSize: 8.5, color: monthColors.more },

  agenda: { gap: spacing.sm },
  leave: { borderRadius: radius.input, padding: spacing.md, gap: 2 },
  leaveTitle: { fontFamily: fonts.bodyExtraBold, fontSize: 14 },
  off: { textAlign: 'center', fontFamily: fonts.bodyExtraBold, color: colors.textFaint, letterSpacing: 2 },
});
