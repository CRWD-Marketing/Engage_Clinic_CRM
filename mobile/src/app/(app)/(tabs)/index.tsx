import { useCurrentUser } from '@/auth/session';
import { DashboardPlaceholder } from '@/features/dashboard/DashboardPlaceholder';
import { SupervisorDashboard } from '@/features/dashboard/SupervisorDashboard';
import { TherapistDashboard } from '@/features/dashboard/TherapistDashboard';

/** DashboardController::index picks the view by role. */
export default function DashboardRoute() {
  const user = useCurrentUser();
  if (user.role === 'THERAPIST') return <TherapistDashboard />;
  if (user.role === 'CLINICAL_SUPERVISOR') return <SupervisorDashboard />;
  return <DashboardPlaceholder />;
}
