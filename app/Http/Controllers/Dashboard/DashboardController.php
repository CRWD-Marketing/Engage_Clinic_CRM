<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use App\Models\PatientNote;
use App\Models\User;
use App\Models\WhatsappContact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return match ($user->role) {
            'FULL_ADMIN' => view('dashboard.admin', [
                ...$this->leadMetrics(),
                ...$this->scheduleMetrics(),
                ...$this->billingMetrics(),
                ...$this->patientMetrics(),
                ...$this->whatsappMetrics(),
                'user' => $user,
            ]),

            'HR_STAFF' => view('dashboard.hr_staff', [
                ...$this->scheduleMetrics(),
                ...$this->staffMetrics(),
                'user' => $user,
            ]),

            'SALES_STAFF' => view('dashboard.sales_staff', [
                ...$this->leadMetrics(),
                ...$this->whatsappMetrics(),
                ...$this->salesMetrics($user),
                'user' => $user,
            ]),

            'FINANCE_STAFF' => view('dashboard.finance_staff', [
                ...$this->financeMetrics(),
                'user' => $user,
            ]),

            'COORDINATOR' => view('dashboard.coordinator', [
                ...$this->leadMetrics(),
                ...$this->scheduleMetrics(),
                ...$this->whatsappMetrics(),
                ...$this->patientMetrics(),
                ...$this->coordinatorMetrics(),
                'user' => $user,
            ]),

            'CLINICAL_SUPERVISOR' => view('dashboard.clinical_supervisor', [
                ...$this->scheduleMetrics(),
                ...$this->patientMetrics(),
                ...$this->clinicalSupervisorMetrics(),
                'user' => $user,
            ]),

            'THERAPIST' => view('dashboard.therapist', [
                ...$this->therapistMetrics($user),
                'user' => $user,
            ]),

            'OTHER_STAFF' => view('dashboard.other_staff', [
                ...$this->otherStaffMetrics(),
                'user' => $user,
            ]),

            default => abort(403, 'Unauthorized.'),
        };
    }

    /**
     * Lead-pipeline widgets: new-leads counter and this-week source breakdown.
     * Only for roles with the 'leads' feature (FULL_ADMIN, SALES_STAFF, COORDINATOR).
     */
    private function leadMetrics(): array
    {
        // A rolling seven days, not the calendar week: on a Monday morning
        // startOfWeek() is a few hours old, so every card here would read
        // zero and climb back through the week. Rolling keeps the same span
        // of trading on the chart whichever day it's opened.
        $weekStart = now()->subDays(7);
        $prevWeekStart = now()->subDays(14);

        $newLeadsCount = Lead::where('created_at', '>=', $weekStart)->count();
        $newLeadsPrevWeek = Lead::whereBetween('created_at', [$prevWeekStart, $weekStart])->count();
        $newLeadsDelta = $newLeadsPrevWeek > 0
            ? round((($newLeadsCount - $newLeadsPrevWeek) / $newLeadsPrevWeek) * 100)
            : ($newLeadsCount > 0 ? 100 : 0);

        // WhatsApp/Instagram/Facebook volume comes straight from the unified
        // chat inbox - every inbound conversation, not just the ones a staff
        // member formally converted into a Lead - since counting Lead::source
        // alone undercounts chat-origin interest (most chats never convert).
        $chatCounts = WhatsappContact::where('created_at', '>=', $weekStart)
            ->select('channel', DB::raw('count(*) as c'))
            ->groupBy('channel')
            ->pluck('c', 'channel');

        // Website volume comes from Contact form submissions the same way -
        // every booking/contact-form fill, not only the ones later converted.
        $websiteCount = Contact::where('created_at', '>=', $weekStart)->count();

        // Leads added this week count too - a family who came in through a
        // chat or the form in an earlier week but only became a Lead now, and
        // anything a staff member keyed in by hand. Leads already represented
        // by one of the conversations/submissions counted above are left out
        // so the same enquiry can't land on the chart twice.
        $alreadyCounted = WhatsappContact::where('created_at', '>=', $weekStart)
            ->whereNotNull('lead_id')
            ->pluck('lead_id')
            ->merge(
                Contact::where('created_at', '>=', $weekStart)
                    ->whereNotNull('converted_lead_id')
                    ->pluck('converted_lead_id')
            )
            ->filter()
            ->unique()
            ->all();

        $leadCounts = Lead::where('created_at', '>=', $weekStart)
            ->when($alreadyCounted, fn ($q) => $q->whereNotIn('id', $alreadyCounted))
            ->select('source', DB::raw('count(*) as c'))
            ->groupBy('source')
            ->get()
            ->groupBy(fn ($row) => $this->sourceBucket($row->source))
            ->map(fn ($rows) => (int) $rows->sum('c'));

        $leadSources = collect([
            (object) ['source' => 'WhatsApp', 'c' => (int) ($chatCounts['whatsapp'] ?? 0) + (int) ($leadCounts['whatsapp'] ?? 0)],
            (object) ['source' => 'Instagram', 'c' => (int) ($chatCounts['instagram'] ?? 0) + (int) ($leadCounts['instagram'] ?? 0)],
            (object) ['source' => 'Facebook', 'c' => (int) ($chatCounts['facebook'] ?? 0) + (int) ($leadCounts['facebook'] ?? 0)],
            (object) ['source' => 'Website', 'c' => $websiteCount + (int) ($leadCounts['website'] ?? 0)],
            (object) ['source' => 'Referral', 'c' => (int) ($leadCounts['referral'] ?? 0)],
            (object) ['source' => 'Google', 'c' => (int) ($leadCounts['google'] ?? 0)],
        ])
            ->concat(
                // Anything typed into a Lead's source field that isn't one of
                // the six above keeps its own row rather than being lumped in.
                $leadCounts->reject(fn ($c, $bucket) => in_array($bucket, ['whatsapp', 'instagram', 'facebook', 'website', 'referral', 'google'], true))
                    ->map(fn ($c, $bucket) => (object) ['source' => $bucket ?: 'Other', 'c' => (int) $c])
            )
            ->sortByDesc('c')
            ->values();
        // Bar width sits on a 0-10 lead scale by default (not a ratio against
        // whichever source happens to have the highest count or the week's
        // total - otherwise 2 total leads, both Instagram, renders as a full
        // bar, which reads as "100% of leads" rather than "2 leads"). Grows
        // in steps of 10 only if a source actually exceeds the current scale,
        // so the axis stays fixed and readable in a normal week.
        $leadSourcesScale = max(10, (int) ceil($leadSources->max('c') / 10) * 10);

        return compact('newLeadsCount', 'newLeadsDelta', 'leadSources', 'leadSourcesScale');
    }

    /**
     * A Lead's free-text source onto one of the channels the dashboard
     * charts. Staff type these by hand and the website/ad integrations each
     * spell them their own way ("Whatsapp", "WhatsApp", "Contact Us",
     * "Google Ads"), so matching is loose and case-insensitive. Anything
     * unrecognised keeps its own label and gets its own row.
     */
    private function sourceBucket(?string $source): string
    {
        $key = strtolower(str_replace([' ', '-', '_'], '', (string) $source));

        return match (true) {
            $key === '' => 'Other',
            str_contains($key, 'whatsapp') => 'whatsapp',
            str_contains($key, 'instagram') => 'instagram',
            str_contains($key, 'facebook'), str_contains($key, 'messenger') => 'facebook',
            str_contains($key, 'website'), str_contains($key, 'contactus'), str_contains($key, 'webform') => 'website',
            str_contains($key, 'referral') => 'referral',
            str_contains($key, 'google') => 'google',
            default => $source,
        };
    }

    /**
     * Clinic-wide schedule/attendance widgets. Only for roles with the
     * 'calendar' feature. Pass a therapist id to scope to just their own
     * sessions (used for THERAPIST, whose access is assigned-only).
     */
    private function scheduleMetrics(?int $therapistId = null): array
    {
        // Same catch-up the calendar and the scheduled command do: a session
        // counts as attended the moment its end time passes, not at the end
        // of the day. Run here too so this morning's 9 o'clock reads
        // "Completed" by lunchtime even on a host with no cron.
        CalendarSession::pastDueScheduled()->update(['status' => 'completed']);

        $todaySessionsList = CalendarSession::whereDate('session_date', today())
            ->notCancelled()
            ->when($therapistId, fn ($q) => $q->forTherapist($therapistId))
            ->with(['therapist', 'child'])
            ->orderBy('start_time')
            ->get();
        $sessionsTodayCount = $todaySessionsList->count();
        $roomsInUseCount = $todaySessionsList->pluck('room')->filter()->unique()->count();
        $activeTherapistsCount = $todaySessionsList->pluck('therapist_id')->unique()->count();

        $past30 = CalendarSession::whereBetween('session_date', [now()->subDays(30), today()])
            ->whereIn('status', ['completed', 'no_show'])
            ->when($therapistId, fn ($q) => $q->forTherapist($therapistId))
            ->get();
        $attendanceRate = $past30->isNotEmpty()
            ? (int) round($past30->where('status', 'completed')->count() / $past30->count() * 100)
            : null;

        $prev30 = CalendarSession::whereBetween('session_date', [now()->subDays(60), now()->subDays(31)])
            ->whereIn('status', ['completed', 'no_show'])
            ->when($therapistId, fn ($q) => $q->forTherapist($therapistId))
            ->get();
        $prevAttendanceRate = $prev30->isNotEmpty()
            ? (int) round($prev30->where('status', 'completed')->count() / $prev30->count() * 100)
            : null;
        $attendanceDelta = ($attendanceRate !== null && $prevAttendanceRate !== null)
            ? $attendanceRate - $prevAttendanceRate
            : null;

        return compact(
            'todaySessionsList', 'sessionsTodayCount', 'roomsInUseCount', 'activeTherapistsCount',
            'attendanceRate', 'attendanceDelta',
        );
    }

    /**
     * Revenue/claims widgets. Only for roles with 'billing' and/or 'reports'.
     */
    private function billingMetrics(): array
    {
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

        return compact(
            'revenueMtd', 'revenueDelta', 'lastMonthName',
            'claimsPendingAmount', 'claimsPendingCount', 'oldestClaimDays',
        );
    }

    /**
     * Waitlist/authorization widgets. Only for roles with the 'patients' feature.
     *
     * The waitlist is the "New" column of the leads board - families who have
     * enquired and are waiting to be picked up. That is the only place a
     * waiting family is recorded, so it's the only place this counts from.
     */
    private function patientMetrics(): array
    {
        $waitingEntries = Lead::where('status', Lead::STATUS_NEW)->orderBy('created_at')->get();
        $waitlistCount = $waitingEntries->count();
        $avgWaitWeeks = $waitingEntries->isNotEmpty()
            ? round($waitingEntries->avg(fn (Lead $l) => $l->created_at->diffInWeeks(now())), 1)
            : null;
        $waitlistNextUp = $waitingEntries->take(3);

        $authorizationsExpiring = PatientAuthorization::with('patient.lead')
            ->whereNotNull('renews_at')
            ->where('renews_at', '<=', now()->addDays(45))
            ->orderBy('renews_at')
            ->take(4)
            ->get();

        return compact('waitlistCount', 'avgWaitWeeks', 'waitlistNextUp', 'authorizationsExpiring');
    }

    /**
     * WhatsApp inbox preview. Only for roles with the 'whatsapp' feature.
     */
    private function whatsappMetrics(): array
    {
        $whatsappInbox = WhatsappContact::orderByDesc('last_message_at')->take(3)->get();

        return compact('whatsappInbox');
    }

    /**
     * HR_STAFF-only widgets: headcount, recent starters, and department mix.
     * HR has no leads/billing/patients access, so nothing clinical or
     * financial belongs here - only 'users' and 'calendar' scoped data.
     */
    private function staffMetrics(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $activeStaffCount = User::where('is_active', true)->count();
        $newHiresThisMonth = User::whereBetween('start_date', [$monthStart, $monthEnd])->count();

        $staffByRole = User::select('role', DB::raw('count(*) as c'))
            ->groupBy('role')
            ->orderByDesc('c')
            ->get();
        $staffByRoleMax = max(1, (int) $staffByRole->max('c'));

        $recentStaff = User::orderByDesc('start_date')->take(4)->get();

        return compact(
            'activeStaffCount', 'newHiresThisMonth',
            'staffByRole', 'staffByRoleMax',
            'recentStaff',
        );
    }

    /**
     * SALES_STAFF-only widgets: leads assigned to this rep and the clinic-wide
     * intake queue. Sales has no calendar/billing/patients access.
     */
    private function salesMetrics(User $user): array
    {
        $myActiveLeadsCount = Lead::where('assigned_to', $user->id)->active()->count();
        $awaitingContactCount = Lead::where('status', Lead::STATUS_NEW)->count();
        $myLeadsPipeline = Lead::where('assigned_to', $user->id)
            ->active()
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        return compact('myActiveLeadsCount', 'awaitingContactCount', 'myLeadsPipeline');
    }

    /**
     * FINANCE_STAFF-only widgets: fuller revenue/claims reporting (payer mix,
     * claim aging) matching their 'billing' + 'reports' access. No patient
     * names surface here - payer/amount only, never child identity.
     */
    private function financeMetrics(): array
    {
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

        $collectedMtd = (float) Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])
            ->where('status', 'paid')
            ->sum('subtotal');

        $openClaims = Invoice::where('payer', '!=', 'Self-pay')
            ->whereIn('status', ['submitted', 'pending_info'])
            ->get(['issue_date', 'insurance_coverage_amount']);
        $claimsPendingAmount = (float) $openClaims->sum('insurance_coverage_amount');
        $claimsPendingCount = $openClaims->count();
        $oldestClaimDays = $openClaims->isNotEmpty()
            ? $openClaims->max(fn ($c) => $c->issue_date->diffInDays(now()))
            : null;

        $agingBuckets = [
            '0-14 days' => ['min' => 0, 'max' => 14, 'amount' => 0, 'count' => 0],
            '15-30 days' => ['min' => 15, 'max' => 30, 'amount' => 0, 'count' => 0],
            '31-60 days' => ['min' => 31, 'max' => 60, 'amount' => 0, 'count' => 0],
            '60+ days' => ['min' => 61, 'max' => null, 'amount' => 0, 'count' => 0],
        ];
        foreach ($openClaims as $claim) {
            $age = $claim->issue_date->diffInDays(now());
            foreach ($agingBuckets as $label => $bucket) {
                if ($age >= $bucket['min'] && ($bucket['max'] === null || $age <= $bucket['max'])) {
                    $agingBuckets[$label]['amount'] += (float) $claim->insurance_coverage_amount;
                    $agingBuckets[$label]['count']++;
                    break;
                }
            }
        }
        $agingMax = max(1, ...array_column($agingBuckets, 'amount'));

        $revenueByPayer = Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])
            ->select('payer', DB::raw('SUM(subtotal) as total'))
            ->groupBy('payer')
            ->orderByDesc('total')
            ->get();
        $revenueTotal = max(0.01, $revenueByPayer->sum('total'));
        $payerColors = ['#C8355F', '#16436E', '#B97F24', '#6E4FA8', '#2E7D5B', '#24619C'];
        $revenueByPayer = $revenueByPayer->values()->map(fn ($row, $i) => [
            'payer' => $row->payer,
            'total' => (float) $row->total,
            'percent' => round(($row->total / $revenueTotal) * 100),
            'color' => $payerColors[$i % count($payerColors)],
        ]);

        return compact(
            'revenueMtd', 'revenueDelta', 'lastMonthName', 'collectedMtd',
            'claimsPendingAmount', 'claimsPendingCount', 'oldestClaimDays',
            'agingBuckets', 'agingMax', 'revenueByPayer',
        );
    }

    /**
     * OTHER_STAFF-only widgets: a plain invoice/quotation summary matching
     * their "Invoice and Quotation Only" access - no revenue analytics,
     * payer mix, or claim aging (that's FINANCE_STAFF/reports territory).
     */
    private function otherStaffMetrics(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $draftQuotationsCount = Invoice::where('status', 'draft')->count();
        $awaitingPaymentCount = Invoice::whereIn('status', ['submitted', 'pending_info'])->count();
        $paidThisMonthCount = Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])
            ->where('status', 'paid')
            ->count();

        $recentInvoices = Invoice::with('patient.lead')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        return compact(
            'draftQuotationsCount', 'awaitingPaymentCount', 'paidThisMonthCount',
            'recentInvoices',
        );
    }

    /**
     * THERAPIST-only widgets, scoped strictly to this therapist's own
     * assigned patients and sessions - never clinic-wide data. Mirrors the
     * "assigned only" restriction documented in config/role_permissions.php.
     */
    private function therapistMetrics(User $user): array
    {
        $schedule = $this->scheduleMetrics($user->id);

        $myPatientIds = CalendarSession::forTherapist($user->id)->distinct('patient_id')->pluck('patient_id');
        $myActivePatientsCount = $myPatientIds->count();

        $myNotesAwaitingSignoff = PatientNote::where('user_id', $user->id)
            ->unsigned()
            ->with('patient.lead')
            ->oldest()
            ->get();
        $myPendingNotesCount = $myNotesAwaitingSignoff->count();
        $myNotesAwaitingSignoffList = $myNotesAwaitingSignoff->take(3);

        $myTreatmentPlansDue = Patient::with('lead')
            ->whereIn('lead_id', $myPatientIds)
            ->whereNotNull('treatment_plan_review_due_at')
            ->where('treatment_plan_review_due_at', '<=', now()->addDays(7))
            ->orderBy('treatment_plan_review_due_at')
            ->get();
        $myTreatmentPlansDueCount = $myTreatmentPlansDue->count();
        $myTreatmentPlansDueList = $myTreatmentPlansDue->take(3);

        return [
            ...$schedule,
            ...compact(
                'myActivePatientsCount',
                'myPendingNotesCount', 'myNotesAwaitingSignoffList',
                'myTreatmentPlansDueCount', 'myTreatmentPlansDueList',
            ),
        ];
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
