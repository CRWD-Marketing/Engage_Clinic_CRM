import { useCurrentUser } from '@/auth/session';
import { AdminDashboard } from '@/features/dashboard/AdminDashboard';
import { CoordinatorDashboard } from '@/features/dashboard/CoordinatorDashboard';
import { DashboardPlaceholder } from '@/features/dashboard/DashboardPlaceholder';
import { FinanceDashboard } from '@/features/dashboard/FinanceDashboard';
import { HrDashboard } from '@/features/dashboard/HrDashboard';
import { OtherStaffDashboard } from '@/features/dashboard/OtherStaffDashboard';
import { SalesDashboard } from '@/features/dashboard/SalesDashboard';
import { SupervisorDashboard } from '@/features/dashboard/SupervisorDashboard';
import { TherapistDashboard } from '@/features/dashboard/TherapistDashboard';

/** DashboardController::index picks the view by role. */
export default function DashboardRoute() {
  const user = useCurrentUser();
  if (user.role === 'THERAPIST') return <TherapistDashboard />;
  if (user.role === 'CLINICAL_SUPERVISOR') return <SupervisorDashboard />;
  if (user.role === 'COORDINATOR') return <CoordinatorDashboard />;
  if (user.role === 'FULL_ADMIN') return <AdminDashboard />;
  if (user.role === 'SALES_STAFF') return <SalesDashboard />;
  if (user.role === 'HR_STAFF') return <HrDashboard />;
  if (user.role === 'FINANCE_STAFF') return <FinanceDashboard />;
  if (user.role === 'OTHER_STAFF') return <OtherStaffDashboard />;
  return <DashboardPlaceholder />;
}
