<?php

namespace App\Services\Billing;

use App\Models\CalendarSession;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * The session ledger: every delivered, billable session-instance for a
 * client, priced and routed to a payer. Nothing here is stored - an invoice
 * freezes the rows it bills at issue. The therapist calendar is the only
 * place hours are created; this is the only place they become money.
 */
class SessionLedger
{
    private float $vatRate;
    private array $policy;

    public function __construct()
    {
        $this->vatRate = (float) config('billing.vat_rate', 5) / 100;
        $this->policy = config('billing.cancel_policy');
    }

    /**
     * Everything the ledger needs to know about a client that isn't on the
     * session itself: rate, setting, payers, prepaid balance, bill-to.
     */
    public function profile(Patient $patient): array
    {
        $lead = $patient->lead;
        $packages = $lead ? $lead->packages()->get() : collect();
        $rate = (float) ($packages->first(fn ($p) => (float) $p->rate > 0)?->rate ?? config('billing.default_rate'));
        $mode = $packages->pluck('delivery_mode')->filter()->first();
        $setting = $mode && stripos($mode, 'clinic') !== false ? 'Clinic' : 'Client home';

        $authorizations = $patient->relationLoaded('authorizations') ? $patient->authorizations : $patient->authorizations()->get();
        $prepaidAuth = $authorizations->first(fn (PatientAuthorization $a) => preg_match('/self/i', (string) $a->payer_name) && (int) $a->authorized_hours_total > 0);
        $insurers = $authorizations->reject(fn (PatientAuthorization $a) => preg_match('/self/i', (string) $a->payer_name))->values();

        return [
            'rate' => $rate,
            'setting' => $setting,
            'vat_rate' => $this->vatRate,
            'insurers' => $insurers,
            'primary_payer' => $insurers->first()?->payer_name ?? 'Self-pay',
            'prepaid' => $prepaidAuth ? [
                'authorization_id' => $prepaidAuth->id,
                'total' => (int) $prepaidAuth->authorized_hours_total,
            ] : null,
            'bill_to' => $lead?->parent_guardian_name,
            'parent_email' => $lead?->email,
            'phone' => $lead?->phone,
            'child' => $lead?->child_name,
            'package_label' => $packages->pluck('name')->implode(', '),
        ];
    }

    /**
     * Ledger rows for one client, newest first. Only sessions that have
     * happened (date on/before today) and whose type is client-facing.
     */
    public function forPatient(Patient $patient, ?string $from = null, ?string $to = null): Collection
    {
        $query = $patient->calendarSessions()
            ->with(['therapist', 'supervisor', 'invoice'])
            ->whereIn('status', ['completed', 'no_show', 'cancelled'])
            ->whereNotIn('activity_type', CalendarSession::NON_THERAPY_TYPES)
            ->where('session_date', '<=', $to ?? now()->toDateString());

        if ($from) {
            $query->where('session_date', '>=', $from);
        }

        $sessions = $query->orderByDesc('session_date')->orderByDesc('start_time')->get();

        return $this->rows($patient, $sessions);
    }

    public function rows(Patient $patient, Collection $sessions, ?array $profile = null): Collection
    {
        $profile ??= $this->profile($patient);
        $prepaidUsed = 0;

        // Prepaid drawdown counts every delivered hour in date order.
        if ($profile['prepaid']) {
            $prepaidUsed = $sessions->sortBy(fn ($s) => $s->session_date->format('Y-m-d').$s->start_time)
                ->sum(fn ($s) => $this->billing($s)['bill_hours']);
        }

        $rows = $sessions->map(fn (CalendarSession $s) => $this->row($s, $profile));

        if ($profile['prepaid']) {
            $left = max(0, $profile['prepaid']['total'] - $prepaidUsed);
            $rows = $rows->map(function ($r) use ($left) {
                $r['prepaid_left'] = $left;
                return $r;
            });
        }

        return $rows->values();
    }

    public function row(CalendarSession $s, array $profile): array
    {
        $b = $this->billing($s);
        $route = $this->routeSession($s, $b['bill_hours'], $profile['insurers']);
        $rate = $profile['rate'];
        $net = $b['bill_hours'] * $rate * $b['factor'];
        $vat = $net * $this->vatRate;
        $gross = $net + $vat;
        $pct = $route['pct'] / 100;

        $coveredGross = $route['covered_hours'] * $rate * (1 + $this->vatRate) * $b['factor'];
        $excessGross = $route['excess_hours'] * $rate * (1 + $this->vatRate) * $b['factor'];

        $trainee = $s->supervisor ? 'supervised by '.trim($s->supervisor->first_name.' '.$s->supervisor->last_name) : null;

        return [
            'id' => $s->id,
            'session_date' => $s->session_date->format('Y-m-d'),
            'date_label' => $s->session_date->format('j M Y'),
            'start_time' => substr($s->start_time, 0, 5),
            'end_time' => substr($s->end_time, 0, 5),
            'duration_minutes' => (int) $s->duration_minutes,
            'hours' => $b['hours'],
            'bill_hours' => $b['bill_hours'],
            'attendance' => $b['state'],
            'attendance_label' => $b['label'],
            'charge_rule' => $b['rule'],
            'factor' => $b['factor'],
            'notice_hours' => $s->cancel_notice_hours,
            'activity_type' => $s->activity_type,
            'service_label' => config('billing.service_labels.'.$s->activity_type, $s->activity_type),
            'therapist_id' => $s->therapist_id,
            'therapist_name' => $s->therapist ? trim($s->therapist->first_name.' '.$s->therapist->last_name) : '—',
            'trainee_note' => $trainee,
            'setting' => $profile['setting'],
            'rate' => $rate,
            'net' => $net,
            'vat' => $vat,
            'gross' => $gross,
            'payer' => $route['payer'],
            'coverage_pct' => $route['pct'],
            'authorization_id' => $route['authorization_id'],
            'covered_bill_hours' => $route['covered_hours'],
            'excess_bill_hours' => $route['excess_hours'],
            'insufficient_authorization' => $route['excess_hours'] > 0 && ! $route['no_matching_authorization'],
            'insufficient_message' => ($route['excess_hours'] > 0 && ! $route['no_matching_authorization'])
                ? "Only {$route['covered_hours']} of {$b['bill_hours']} h covered — {$route['excess_hours']} h exceeds the ".($route['blocked_payer'] ?? $route['payer']).' authorization'
                : null,
            'insurer_amount' => $coveredGross * $pct,
            'family_amount' => $coveredGross * (1 - $pct) + $excessGross,
            'invoiced' => $s->invoice_id !== null,
            'invoice_number' => $s->invoice?->invoice_number,
            'billing_status' => $s->invoice_id ? ($s->invoice && $s->invoice->billingStatus() === 'paid' ? 'Paid' : 'Invoiced') : ($profile['prepaid'] ? 'Prepaid' : 'Pending'),
        ];
    }

    /**
     * Attendance → charge. Whole hours only for the session's own duration
     * (a 90-minute booking is two billable hours) - but the cancellation/
     * no-show percentage is applied to the price of each of those hours, not
     * to the hour count, so a 1 h session still gets a proper half-price line
     * instead of rounding the discount away.
     */
    public function billing(CalendarSession $s): array
    {
        $hours = max(1, (int) round($s->duration_minutes / 60));
        $notice = (int) $this->policy['notice_hours'];

        [$state, $factor, $label, $rule] = match (true) {
            $s->status === 'completed' => ['completed', 1.0, 'Completed', 'Attended — 100% charged'],
            $s->status === 'no_show' => ['no_show', $this->policy['no_show_pct'] / 100, 'No-show — charged', "No-show — {$this->policy['no_show_pct']}% charged"],
            $s->status === 'cancelled' && $s->cancel_reason === 'clinic' => ['cancelled_clinic', 0.0, 'Cancelled — clinic', 'Cancelled by clinic — not charged'],
            $s->status === 'cancelled' && $s->cancel_notice_hours !== null && (float) $s->cancel_notice_hours >= $notice
                => ['cancelled_notice', 0.0, 'Cancelled — with notice', sprintf('Cancelled %s h ahead — not charged', rtrim(rtrim(number_format((float) $s->cancel_notice_hours, 1), '0'), '.'))],
            $s->status === 'cancelled' && $s->cancel_notice_hours !== null
                => ['cancelled_late', $this->policy['late_pct'] / 100, 'Cancelled — late', sprintf('Cancelled %s h ahead — %d%% charged', rtrim(rtrim(number_format((float) $s->cancel_notice_hours, 1), '0'), '.'), $this->policy['late_pct'])],
            $s->status === 'cancelled' => ['cancelled_notice', 0.0, 'Cancelled — with notice', 'Cancelled by family — notice not recorded, not charged'],
            default => ['scheduled', 0.0, 'Scheduled', 'Not yet delivered'],
        };

        $billHours = $factor === 0.0 ? 0 : $hours;

        return compact('hours', 'factor', 'state', 'label', 'rule') + ['bill_hours' => $billHours];
    }

    /**
     * Per-session payer routing: the first insurer whose covered services
     * include this session's type, that is still active on the session's
     * date and still has hours left as of (i.e. strictly before) this
     * session. If that authorization's remaining balance is less than the
     * session's billable hours, only the covered portion routes to it - the
     * rest falls through to the next matching authorization, or to
     * self-pay. An authorization that's expired or fully used is skipped
     * entirely, same as if it didn't match at all, so a second authorization
     * covering the same service still gets a chance to pick up the slack.
     */
    public function routeSession(CalendarSession $s, int $billHours, Collection $insurers): array
    {
        $matchedAny = false;
        $blockedPayer = null;

        foreach ($insurers as $auth) {
            if (! $this->coversType($auth, $s->activity_type)) {
                continue;
            }
            $matchedAny = true;

            if (! $auth->isActiveOn($s->session_date->toDateString())) {
                $blockedPayer ??= $auth->payer_name.' (expired)';
                continue;
            }

            $remainingHours = $auth->authorized_hours_total
                ? (int) floor($auth->minutesRemainingBefore($s) / 60)
                : 0;

            if ($remainingHours <= 0) {
                $blockedPayer ??= $auth->payer_name;
                continue;
            }

            $coveredHours = min($billHours, $remainingHours);

            return [
                'payer' => $auth->payer_name,
                'pct' => (int) $auth->coverage_percent,
                'policy' => $auth->policy_number,
                'authorization_id' => $auth->id,
                'covered_hours' => $coveredHours,
                'excess_hours' => $billHours - $coveredHours,
                'no_matching_authorization' => false,
            ];
        }

        // Either nothing covers this activity type at all (ordinary
        // self-pay, unchanged from before), or every matching authorization
        // is expired/exhausted - either way the remainder bills to the
        // family, but only the latter is an "authorization insufficient"
        // condition worth flagging to staff.
        return [
            'payer' => 'Self-pay',
            'pct' => 0,
            'policy' => null,
            'authorization_id' => null,
            'covered_hours' => 0,
            'excess_hours' => $billHours,
            'no_matching_authorization' => ! $matchedAny,
            'blocked_payer' => $blockedPayer,
        ];
    }

    private function coversType(PatientAuthorization $auth, string $type): bool
    {
        foreach ($auth->covers_services ?? [] as $cover) {
            if (stripos($type, $cover) !== false || stripos($cover, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attendance states the invoice picker may set on a session, mapped back
     * to the calendar's status/reason/notice fields.
     */
    public static function applyAttendance(CalendarSession $s, string $state, ?float $noticeHours = null): void
    {
        // "Completed" and "no-show" assert the session actually happened -
        // never true for a session whose end time hasn't passed yet.
        if (in_array($state, ['completed', 'no_show'], true) && ! $s->isPast()) {
            return;
        }

        $changes = match ($state) {
            'completed' => ['status' => 'completed', 'cancel_reason' => null, 'cancel_notice_hours' => null],
            'no_show' => ['status' => 'no_show', 'cancel_reason' => null, 'cancel_notice_hours' => null],
            'cancelled_clinic' => ['status' => 'cancelled', 'cancel_reason' => 'clinic', 'cancel_notice_hours' => null],
            'cancelled_late' => ['status' => 'cancelled', 'cancel_reason' => 'family', 'cancel_notice_hours' => $noticeHours ?? max(0, (int) config('billing.cancel_policy.notice_hours') - 1)],
            'cancelled_notice' => ['status' => 'cancelled', 'cancel_reason' => 'family', 'cancel_notice_hours' => $noticeHours ?? (int) config('billing.cancel_policy.notice_hours')],
            default => [],
        };

        if ($changes) {
            $s->forceFill($changes)->save();
        }
    }
}
