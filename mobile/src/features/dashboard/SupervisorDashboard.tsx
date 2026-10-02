import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';

import { api } from '@/api/client';
import type { PatientNote } from '@/api/types';
import { canReviewNotes } from '@/auth/permissions';
import { useCurrentUser } from '@/auth/session';
import { AppText } from '@/components/AppText';
import { Avatar } from '@/components/Avatar';
import { Card, CardHeader, Divider } from '@/components/Card';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { EmptyRow, ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors, fonts, noteStatusColors, spacing } from '@/theme';
import { plural } from '@/utils/format';

import {
  AttendanceTile,
  DashboardHeader,
  NotesAwaitingCard,
  WaitlistCard,
  openPatient,
  panelStyles,
  ScheduleTodayCard,
  StatsGrid,
  TreatmentPlansCard,
} from './panels';

/** Mirrors resources/views/dashboard/clinical_supervisor.blade.php. */
export function SupervisorDashboard() {
  const user = useCurrentUser();
  const query = useApiQuery('dashboard.supervisor', () => api.dashboard.supervisor());
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  const canReview = canReviewNotes(user);
  // The web links these panels to the patients list; on mobile they open the review queue.
  const openReview = (filter: 'unsigned' | 'flagged') =>
    canReview ? router.push({ pathname: '/notes-review', params: { filter } }) : router.navigate('/patients');

  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <DashboardHeader name={d.user_full_name} location={d.location_label} />

      <StatsGrid>
        <StatTile
          label="Sessions today"
          value={String(d.sessions_today_count)}
          caption={`${d.rooms_in_use_count} ${plural(d.rooms_in_use_count, 'room')} · ${d.active_therapists_count} ${plural(d.active_therapists_count, 'therapist')}`}
        />
        <AttendanceTile label="Attendance · 30d" rate={d.attendance_rate} delta={d.attendance_delta} />
        <StatTile
          label="Notes pending review"
          value={String(d.pending_notes_count)}
          caption={d.overdue_notes_count > 0 ? `${d.overdue_notes_count} overdue 48h+` : 'None overdue'}
          captionColor={colors.warning}
        />
        <StatTile
          label="Active treatment plans"
          value={String(d.active_treatment_plans_count)}
          caption={d.plans_due_for_review_count > 0 ? `${d.plans_due_for_review_count} due for review` : 'None due soon'}
          captionColor={colors.warning}
        />
        <StatTile
          label="Therapist caseload"
          value={d.avg_caseload_per_therapist !== null ? String(d.avg_caseload_per_therapist) : '—'}
          caption="avg. patients / therapist"
        />
        <StatTile
          label="Waitlist"
          value={String(d.waitlist_count)}
          caption={d.waitlist_count > 0 ? `avg. wait ${d.avg_wait_weeks} wks` : 'No one waiting'}
          captionColor={colors.warning}
        />
      </StatsGrid>

      <ScheduleTodayCard title="Today's schedule" sessions={d.today_sessions} showTherapist />
      <NotesAwaitingCard
        title="Session notes awaiting sign-off"
        notes={d.notes_awaiting_signoff}
        showAuthor
        actionLabel={canReview ? 'Review all →' : 'Open →'}
        onAction={() => openReview('unsigned')}
      />
      <FlaggedNotesCard notes={d.flagged_notes} onOpen={() => openReview('flagged')} />
      <TreatmentPlansCard title="Treatment plans due for review" patients={d.treatment_plans_due_list} />
      <WaitlistCard entries={d.waitlist_next_up} />
    </Screen>
  );
}

function FlaggedNotesCard({ notes, onOpen }: { notes: PatientNote[]; onOpen: () => void }) {
  return (
    <Card padded={false}>
      <View style={[panelStyles.cardHead, styles.flaggedHead]}>
        <View style={styles.flagDot} />
        <View style={styles.flex}>
          <CardHeader title="Flagged for supervisor review" actionLabel="Open →" onAction={onOpen} />
        </View>
      </View>
      {notes.length === 0 ? (
        <EmptyRow text="Nothing flagged right now." />
      ) : (
        notes.map((note) => (
          <View key={note.id}>
            <Divider />
            <Pressable
              onPress={() => openPatient(note.patient_id)}
              style={({ pressed }) => [styles.flagRow, pressed && panelStyles.pressed]}
              accessibilityRole="button">
              <Avatar id={note.user_id ?? 0} name={note.author_name} size={36} />
              <View style={panelStyles.rowMain}>
                <AppText variant="bodyStrong">{note.author_name}</AppText>
                <AppText variant="caption" numberOfLines={1}>
                  Flagged note on {note.patient?.lead?.child_name ?? 'patient'}
                  {note.flag_reason ? ` · ${note.flag_reason}` : ''}
                </AppText>
              </View>
              <View style={styles.bang}>
                <AppText style={styles.bangText}>!</AppText>
              </View>
            </Pressable>
          </View>
        ))
      )}
    </Card>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  flaggedHead: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.sm },
  flagDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: colors.pink },
  flagRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
  },
  bang: {
    backgroundColor: noteStatusColors.flagged.fg,
    borderRadius: 9,
    paddingHorizontal: 7,
    paddingVertical: 1,
  },
  bangText: { fontFamily: fonts.bodyExtraBold, fontSize: 11, color: colors.white },
});
