<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\PresentsNotes;
use App\Http\Controllers\Dashboard\DashboardController as WebDashboardController;
use App\Models\CalendarSession;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\PatientAuthorization;
use App\Models\PatientNote;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * GET /dashboard for the mobile app: the same per-role metrics the web
 * dashboard views are rendered with (DashboardController's own methods),
 * returned as JSON with snake_case keys.
 */
class DashboardController extends WebDashboardController
{
    use PresentsNotes;

    /** The location line the non-admin dashboard views print under the greeting. */
    private const LOCATION = 'Khalifa City, Abu Dhabi';

    public function index()
    {
        $user = Auth::user();

        $payload = match ($user->role) {
            'FULL_ADMIN' => $this->admin(),
            'HR_STAFF' => $this->hr(),
            'SALES_STAFF' => $this->sales($user),
            'FINANCE_STAFF' => $this->finance(),
            'COORDINATOR' => $this->coordinator(),
            'CLINICAL_SUPERVISOR' => $this->supervisor(),
            'THERAPIST' => $this->therapist($user),
            'OTHER_STAFF' => $this->otherStaff(),
            default => abort(403, 'Unauthorized.'),
        };

        return response()->json(['role' => $user->role, 'user_full_name' => $user->full_name] + $payload);
    }

    private function admin(): array
    {
        // dashboard/admin.blade.php: the city/country tail of the clinic address.
        $parts = array_map('trim', explode(',', config('clinic.address', '')));
        $location = implode(', ', array_slice($parts, -2)) ?: 'Abu Dhabi, UAE';

        return ['location_label' => $location]
            + $this->leadsBlock($this->leadMetrics())
            + $this->scheduleBlock($this->scheduleMetrics())
            + $this->billingBlock($this->billingMetrics())
            + $this->patientBlock($this->patientMetrics(), withAuthorizations: true)
            + $this->inboxBlock($this->whatsappMetrics());
    }

    private function hr(): array
    {
        $m = $this->staffMetrics();

        return ['location_label' => self::LOCATION]
            + $this->scheduleBlock($this->scheduleMetrics())
            + [
                'active_staff_count' => $m['activeStaffCount'],
                'new_hires_this_month' => $m['newHiresThisMonth'],
                'staff_by_role' => $m['staffByRole']->map(fn ($row) => ['role' => $row->role, 'count' => (int) $row->c])->values(),
                'recent_staff' => $m['recentStaff']->map(fn (User $u) => [
                    'id' => $u->id,
                    'full_name' => $u->full_name,
                    'job_title' => $u->job_title,
                    'role' => $u->role,
                    'start_date' => $u->start_date,
                ])->values(),
            ];
    }

    private function sales(User $user): array
    {
        $m = $this->salesMetrics($user);

        return ['location_label' => self::LOCATION]
            + $this->leadsBlock($this->leadMetrics())
            + $this->inboxBlock($this->whatsappMetrics())
            + [
                'my_active_leads_count' => $m['myActiveLeadsCount'],
                'awaiting_contact_count' => $m['awaitingContactCount'],
                'my_leads_pipeline' => $m['myLeadsPipeline']->values(),
            ];
    }

    private function finance(): array
    {
        $m = $this->financeMetrics();

        return ['location_label' => self::LOCATION]
            + $this->billingBlock($m)
            + [
                'collected_mtd' => $m['collectedMtd'],
                'aging_buckets' => collect($m['agingBuckets'])->map(fn (array $bucket, string $label) => [
                    'label' => $label,
                    'amount' => (float) $bucket['amount'],
                    'count' => $bucket['count'],
                ])->values(),
                'revenue_by_payer' => $m['revenueByPayer']->values(),
            ];
    }

    private function coordinator(): array
    {
        $m = $this->coordinatorMetrics();
        $patients = $this->patientMetrics();

        return ['location_label' => self::LOCATION]
            + collect($this->leadsBlock($this->leadMetrics()))->only(['new_leads_count', 'new_leads_delta'])->all()
            + $this->scheduleBlock($this->scheduleMetrics())
            + $this->inboxBlock($this->whatsappMetrics())
            + [
                'pending_intake_calls_count' => $m['pendingIntakeCallsCount'],
                'overdue_intake_calls_count' => $m['overdueIntakeCallsCount'],
                // The view's own "Overdue" rule, so the app doesn't re-derive it.
                'intake_pipeline' => $m['intakePipeline']->map(fn (Lead $lead) => $lead->toArray() + [
                    'is_overdue' => $lead->follow_up_due_at
                        ? $lead->follow_up_due_at->isPast()
                        : $lead->created_at->lt(now()->subDays(2)),
                ])->values(),
                'no_shows_count' => $m['noShowsCount'],
                'no_show_follow_ups_needed' => $m['noShowFollowUpsNeeded'],
                'no_show_follow_up_list' => $this->sessions($m['noShowFollowUpList']),
                'waitlist_count' => $patients['waitlistCount'],
                'openings_this_week' => $m['openingsThisWeek'],
            ];
    }

    private function supervisor(): array
    {
        $m = $this->clinicalSupervisorMetrics();

        return ['location_label' => self::LOCATION]
            + $this->scheduleBlock($this->scheduleMetrics())
            + $this->patientBlock($this->patientMetrics())
            + [
                'pending_notes_count' => $m['pendingNotesCount'],
                'overdue_notes_count' => $m['overdueNotesCount'],
                'notes_awaiting_signoff' => $this->notes($m['notesAwaitingSignoff']),
                'flagged_notes' => $this->notes($m['flaggedNotes']),
                'active_treatment_plans_count' => $m['activeTreatmentPlansCount'],
                'plans_due_for_review_count' => $m['plansDueForReviewCount'],
                'treatment_plans_due_list' => $m['treatmentPlansDueList']->values(),
                'avg_caseload_per_therapist' => $m['avgCaseloadPerTherapist'],
            ];
    }

    private function therapist(User $user): array
    {
        $m = $this->therapistMetrics($user);
        $schedule = $this->scheduleBlock($m);
        unset($schedule['active_therapists_count']);

        return ['location_label' => self::LOCATION]
            + $schedule
            + [
                'my_active_patients_count' => $m['myActivePatientsCount'],
                'my_pending_notes_count' => $m['myPendingNotesCount'],
                'my_notes_awaiting_signoff_list' => $this->notes($m['myNotesAwaitingSignoffList']),
                'my_treatment_plans_due_count' => $m['myTreatmentPlansDueCount'],
                'my_treatment_plans_due_list' => $m['myTreatmentPlansDueList']->values(),
            ];
    }

    private function otherStaff(): array
    {
        $m = $this->otherStaffMetrics();

        return [
            'location_label' => self::LOCATION,
            'draft_quotations_count' => $m['draftQuotationsCount'],
            'awaiting_payment_count' => $m['awaitingPaymentCount'],
            'paid_this_month_count' => $m['paidThisMonthCount'],
            'recent_invoices' => $m['recentInvoices']->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'name' => $invoice->patient->lead->child_name ?? $invoice->bill_to ?? 'Unknown',
                'payer' => $invoice->payer,
                'issue_date' => $invoice->issue_date,
                'subtotal' => $invoice->subtotal,
                'status' => $invoice->status,
            ])->values(),
        ];
    }

    // ---- Shared blocks -------------------------------------------------

    private function leadsBlock(array $m): array
    {
        return [
            'new_leads_count' => $m['newLeadsCount'],
            'new_leads_delta' => (int) $m['newLeadsDelta'],
            'lead_sources' => $m['leadSources']->map(fn ($row) => ['source' => (string) $row->source, 'count' => (int) $row->c])->values(),
            'lead_sources_scale' => $m['leadSourcesScale'],
        ];
    }

    private function scheduleBlock(array $m): array
    {
        return [
            'sessions_today_count' => $m['sessionsTodayCount'],
            'rooms_in_use_count' => $m['roomsInUseCount'],
            'active_therapists_count' => $m['activeTherapistsCount'],
            'attendance_rate' => $m['attendanceRate'],
            'attendance_delta' => $m['attendanceDelta'],
            'today_sessions' => $this->sessions($m['todaySessionsList']),
        ];
    }

    private function billingBlock(array $m): array
    {
        return [
            'revenue_mtd' => $m['revenueMtd'],
            'revenue_delta' => $m['revenueDelta'] === null ? null : (int) $m['revenueDelta'],
            'last_month_name' => $m['lastMonthName'],
            'claims_pending_amount' => $m['claimsPendingAmount'],
            'claims_pending_count' => $m['claimsPendingCount'],
            // diffInDays() is fractional in Carbon 3; the dashboards show whole days.
            'oldest_claim_days' => $m['oldestClaimDays'] === null ? null : (int) floor($m['oldestClaimDays']),
        ];
    }

    private function patientBlock(array $m, bool $withAuthorizations = false): array
    {
        $block = [
            'waitlist_count' => $m['waitlistCount'],
            'avg_wait_weeks' => $m['avgWaitWeeks'],
            'waitlist_next_up' => $m['waitlistNextUp']->map(fn (Lead $lead) => $lead->toArray() + [
                'waiting_weeks' => $lead->waitingWeeks(),
            ])->values(),
        ];

        if ($withAuthorizations) {
            $block['authorizations_expiring'] = $m['authorizationsExpiring']->map(fn (PatientAuthorization $auth) => [
                'id' => $auth->id,
                'patient_id' => $auth->patient_id,
                'child_name' => $auth->patient->lead->child_name ?? 'Unknown',
                'payer_name' => $auth->payer_name ?? 'Insurance',
                'hours_left' => max(0, ($auth->authorized_hours_total ?? 0) - $auth->hoursUsed()),
                'renews_at' => $auth->renews_at,
                'days_to_renew' => (int) today()->diffInDays($auth->renews_at, false),
            ])->values();
        }

        return $block;
    }

    private function inboxBlock(array $m): array
    {
        return ['whatsapp_inbox' => $m['whatsappInbox']->values()];
    }

    private function notes(Collection $notes): Collection
    {
        return $notes->map(fn (PatientNote $note) => $this->presentNote($note))->values();
    }

    /** Calendar sessions in the same shape the calendar endpoints return. */
    private function sessions(Collection $sessions): Collection
    {
        $calendar = app(CalendarController::class);

        return $sessions->map(function (CalendarSession $session) use ($calendar) {
            $session->loadMissing(['therapist', 'child', 'coverFor', 'supervisor']);

            return $calendar->payloadFor($session);
        })->values();
    }
}
