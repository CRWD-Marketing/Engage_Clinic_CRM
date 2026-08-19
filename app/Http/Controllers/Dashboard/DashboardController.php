<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\PatientNote;
use App\Models\User;
use App\Models\Waitlist;
use App\Models\WhatsappContact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $common = $this->commonMetrics();

        $sharedViews = [
            'FULL_ADMIN' => 'dashboard.admin',
            'HR_STAFF' => 'dashboard.hr_staff',
            'SALES_STAFF' => 'dashboard.sales_staff',
            'FINANCE_STAFF' => 'dashboard.finance_staff',
            'THERAPIST' => 'dashboard.therapist',
            'OTHER_STAFF' => 'dashboard.other_staff',
        ];

        if (isset($sharedViews[$user->role])) {
            return view($sharedViews[$user->role], [...$common, 'user' => $user]);
        }

        if ($user->role === 'COORDINATOR') {
            return view('dashboard.coordinator', [...$common, ...$this->coordinatorMetrics(), 'user' => $user]);
        }

        if ($user->role === 'CLINICAL_SUPERVISOR') {
            return view('dashboard.clinical_supervisor', [...$common, ...$this->clinicalSupervisorMetrics(), 'user' => $user]);
        }

        abort(403, 'Unauthorized.');
    }

    /**
     * Metrics shown (in some combination) on every role's dashboard.
     */
    private function commonMetrics(): array
    {
        $weekStart = now()->startOfWeek();
        $prevWeekStart = now()->subWeek()->startOfWeek();
        $prevWeekEnd = now()->subWeek()->endOfWeek();

        $newLeadsCount = Lead::where('created_at', '>=', $weekStart)->count();
        $newLeadsPrevWeek = Lead::whereBetween('created_at', [$prevWeekStart, $prevWeekEnd])->count();
        $newLeadsDelta = $newLeadsPrevWeek > 0
            ? round((($newLeadsCount - $newLeadsPrevWeek) / $newLeadsPrevWeek) * 100)
            : ($newLeadsCount > 0 ? 100 : 0);

        $todaySessionsList = CalendarSession::whereDate('session_date', today())
            ->notCancelled()
            ->with(['therapist', 'child'])
            ->orderBy('start_time')
            ->get();
        $sessionsTodayCount = $todaySessionsList->count();
        $roomsInUseCount = $todaySessionsList->pluck('room')->filter()->unique()->count();
        $activeTherapistsCount = $todaySessionsList->pluck('therapist_id')->unique()->count();

        $past30 = CalendarSession::whereBetween('session_date', [now()->subDays(30), today()])
            ->whereIn('status', ['completed', 'no_show'])
            ->get();
        $attendanceRate = $past30->isNotEmpty()
            ? (int) round($past30->where('status', 'completed')->count() / $past30->count() * 100)
            : null;

        $prev30 = CalendarSession::whereBetween('session_date', [now()->subDays(60), now()->subDays(31)])
            ->whereIn('status', ['completed', 'no_show'])
            ->get();
        $prevAttendanceRate = $prev30->isNotEmpty()
            ? (int) round($prev30->where('status', 'completed')->count() / $prev30->count() * 100)
            : null;
        $attendanceDelta = ($attendanceRate !== null && $prevAttendanceRate !== null)
            ? $attendanceRate - $prevAttendanceRate
            : null;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $revenueMtd = (float) Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])->sum('subtotal');
        $revenueLastMonth = (float) Invoice::whereBetween('issue_date', [$lastMonthStart, $lastMonthEnd])->sum('subtotal');
        $revenueDelta = $revenueLastMonth > 0
            ? round((($revenueMtd - $revenueLastMonth) / $revenueLastMonth) * 100)
            : null;
        $lastMonthName = $lastMonthStart->format('F');

        $openClaims = Invoice::where('payer', '!=', 'Self-pay')
            ->whereIn('status', ['submitted', 'pending_info'])
            ->get(['issue_date', 'insurance_coverage_amount']);
        $claimsPendingAmount = (float) $openClaims->sum('insurance_coverage_amount');
        $claimsPendingCount = $openClaims->count();
        $oldestClaimDays = $openClaims->isNotEmpty()
            ? $openClaims->max(fn ($c) => $c->issue_date->diffInDays(now()))
            : null;

        $waitingEntries = Waitlist::waiting()->orderBy('joined_at')->get();
        $waitlistCount = $waitingEntries->count();
        $avgWaitWeeks = $waitingEntries->isNotEmpty()
            ? round($waitingEntries->avg(fn ($w) => $w->joined_at->diffInWeeks(now())), 1)
            : null;
        $waitlistNextUp = $waitingEntries->take(3);

        $leadSources = Lead::where('created_at', '>=', $weekStart)
            ->select('source', DB::raw('count(*) as c'))
            ->groupBy('source')
            ->orderByDesc('c')
            ->get();
        $leadSourcesMax = max(1, (int) $leadSources->max('c'));

        $whatsappInbox = WhatsappContact::orderByDesc('last_message_at')->take(3)->get();

        $authorizationsExpiring = Patient::with('lead')
            ->whereNotNull('authorization_renews_at')
            ->where('authorization_renews_at', '<=', now()->addDays(45))
            ->orderBy('authorization_renews_at')
            ->take(4)
            ->get();

        return compact(
            'newLeadsCount', 'newLeadsDelta',
            'todaySessionsList', 'sessionsTodayCount', 'roomsInUseCount', 'activeTherapistsCount',
            'attendanceRate', 'attendanceDelta',
            'revenueMtd', 'revenueDelta', 'lastMonthName',
            'claimsPendingAmount', 'claimsPendingCount', 'oldestClaimDays',
            'waitlistCount', 'avgWaitWeeks', 'waitlistNextUp',
            'leadSources', 'leadSourcesMax',
            'whatsappInbox',
            'authorizationsExpiring',
        );
    }

    /**
     * Clinical Supervisor-only widgets: note sign-off queue, flagged notes,
     * treatment plan review cycle, and average therapist caseload.
     */
    private function clinicalSupervisorMetrics(): array
    {
        $unsignedNotes = PatientNote::unsigned()->with(['patient.lead', 'user'])->oldest()->get();
        $pendingNotesCount = $unsignedNotes->count();
        $overdueNotesCount = $unsignedNotes->where('created_at', '<=', now()->subHours(48))->count();
        $notesAwaitingSignoff = $unsignedNotes->take(3);

        $flaggedNotes = PatientNote::flagged()->with(['patient.lead', 'user'])->latest()->take(3)->get();

        $activeTreatmentPlansCount = Patient::whereNotNull('programme')->count();

        $plansDueForReview = Patient::with('lead')
            ->whereNotNull('treatment_plan_review_due_at')
            ->where('treatment_plan_review_due_at', '<=', now()->addDays(7))
            ->orderBy('treatment_plan_review_due_at')
            ->get();
        $plansDueForReviewCount = $plansDueForReview->count();
        $treatmentPlansDueList = $plansDueForReview->take(3);

        $caseloadCounts = User::where('role', 'THERAPIST')->pluck('id')
            ->map(fn ($id) => CalendarSession::where('therapist_id', $id)->distinct('patient_id')->count('patient_id'))
            ->filter(fn ($count) => $count > 0);
        $avgCaseloadPerTherapist = $caseloadCounts->isNotEmpty() ? round($caseloadCounts->avg(), 1) : null;

        return compact(
            'pendingNotesCount', 'overdueNotesCount', 'notesAwaitingSignoff',
            'flaggedNotes',
            'activeTreatmentPlansCount', 'plansDueForReviewCount', 'treatmentPlansDueList',
            'avgCaseloadPerTherapist',
        );
    }

    /**
     * Coordinator-only widgets: intake call queue, no-show follow-ups, and
     * an estimated weekly opening count (see openingsThisWeek note below).
     */
    private function coordinatorMetrics(): array
    {
        $pendingIntakeCalls = Lead::where('status', Lead::STATUS_NEW)->orderBy('created_at')->get();
        $pendingIntakeCallsCount = $pendingIntakeCalls->count();
        $overdueIntakeCallsCount = $pendingIntakeCalls->filter(function (Lead $lead) {
            return $lead->follow_up_due_at
                ? $lead->follow_up_due_at->isPast()
                : $lead->created_at->lt(now()->subDays(2));
        })->count();
        $intakePipeline = $pendingIntakeCalls->take(3);

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();
        $noShows = CalendarSession::where('status', 'no_show')
            ->whereBetween('session_date', [$weekStart, $weekEnd])
            ->with('child')
            ->get();
        $noShowsCount = $noShows->count();
        $noShowFollowUpList = $noShows->whereNull('follow_up_completed_at')->take(3);
        $noShowFollowUpsNeeded = $noShows->whereNull('follow_up_completed_at')->count();

        // No capacity/room-schedule model exists, so this is an estimate:
        // distinct rooms ever used x an assumed 8am-4pm/8-slot day x 5 working days,
        // minus sessions already booked this week. Documented assumption, not exact.
        $roomsCount = max(1, CalendarSession::whereNotNull('room')->distinct('room')->count('room'));
        $weeklyCapacity = $roomsCount * 8 * 5;
        $bookedThisWeek = CalendarSession::whereBetween('session_date', [$weekStart, $weekEnd])->notCancelled()->count();
        $openingsThisWeek = max(0, $weeklyCapacity - $bookedThisWeek);

        return compact(
            'pendingIntakeCallsCount', 'overdueIntakeCallsCount', 'intakePipeline',
            'noShowsCount', 'noShowFollowUpsNeeded', 'noShowFollowUpList',
            'openingsThisWeek',
        );
    }
}
