import { useCurrentUser } from '@/auth/session';
import { DashboardPlaceholder } from '@/features/dashboard/DashboardPlaceholder';
import { TherapistDashboard } from '@/features/dashboard/TherapistDashboard';

/** DashboardController::index picks the view by role. */
export default function DashboardRoute() {
  const user = useCurrentUser();
  return user.role === 'THERAPIST' ? <TherapistDashboard /> : <DashboardPlaceholder />;
}
