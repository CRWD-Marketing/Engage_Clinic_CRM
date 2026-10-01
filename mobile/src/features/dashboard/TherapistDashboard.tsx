import { router } from 'expo-router';

import { api } from '@/api/client';
import { Screen } from '@/components/Screen';
import { StatTile } from '@/components/StatTile';
import { ErrorState, LoadingState } from '@/components/StateViews';
import { useApiQuery } from '@/hooks/useApiQuery';
import { useRefetchOnFocus } from '@/hooks/useRefetchOnFocus';
import { colors } from '@/theme';
import { plural } from '@/utils/format';

import {
  AttendanceTile,
  DashboardHeader,
  NotesAwaitingCard,
  ScheduleTodayCard,
  StatsGrid,
  TreatmentPlansCard,
} from './panels';

/** Mirrors resources/views/dashboard/therapist.blade.php. */
export function TherapistDashboard() {
  const query = useApiQuery('dashboard.therapist', () => api.dashboard.therapist());

  // Refresh quietly when returning to the tab (e.g. after adding a session note).
  useRefetchOnFocus(query.reload);

  if (!query.data) {
    return (
      <Screen scroll={false}>
        {query.error ? <ErrorState error={query.error} onRetry={query.refresh} /> : <LoadingState />}
      </Screen>
    );
  }

  const d = query.data;
  return (
    <Screen refreshing={query.refreshing} onRefresh={query.refresh}>
      <DashboardHeader name={d.user_full_name} location={d.location_label} />

      <StatsGrid>
        <StatTile
          label="My sessions today"
          value={String(d.sessions_today_count)}
          caption={`${d.rooms_in_use_count} ${plural(d.rooms_in_use_count, 'room')}`}
        />
        <AttendanceTile label="My attendance · 30d" rate={d.attendance_rate} delta={d.attendance_delta} />
        <StatTile label="My active patients" value={String(d.my_active_patients_count)} caption="assigned to you" />
        <StatTile
          label="My notes pending"
          value={String(d.my_pending_notes_count)}
          caption={d.my_pending_notes_count > 0 ? 'awaiting sign-off' : 'All caught up'}
          captionColor={colors.warning}
        />
      </StatsGrid>

      <ScheduleTodayCard title="My schedule today" sessions={d.today_sessions} showTherapist={false} />
      <NotesAwaitingCard
        title="My notes awaiting sign-off"
        notes={d.my_notes_awaiting_signoff_list}
        showAuthor={false}
        actionLabel="Review →"
        onAction={() => router.navigate('/patients')}
      />
      <TreatmentPlansCard title="My treatment plans due for review" patients={d.my_treatment_plans_due_list} />
    </Screen>
  );
}
