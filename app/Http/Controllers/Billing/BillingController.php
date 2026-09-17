<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PatientAuthorization;
use App\Models\Payment;
use App\Models\PreAuthorization;
use App\Models\RoleTemplate;
use App\Models\Service;
use App\Services\Billing\InvoicePresenter;
use App\Services\Billing\SessionLedger;

class BillingController extends Controller
{
    public function __construct(private SessionLedger $ledger)
    {
    }

    /**
     * Billing & insurance: one data source feeding three tabs (Invoices &
     * claims, Aging & statements, Bulk run). Everything is derived from
     * invoices, receipts and claims on every load.
     */
    public function index()
    {
        $user = auth()->user();
        $monthStart = now()->startOfMonth();

        $invoices = Invoice::with(['patient.lead', 'payments', 'claims', 'replaces', 'replacedBy'])
            ->orderByDesc('created_at')->orderByDesc('id')->limit(120)->get();
        $rows = $invoices->map(fn (Invoice $i) => InvoicePresenter::row($i))->values();

        $live = $invoices->reject(fn (Invoice $i) => $i->isVoided());
        $invoicedMtd = (float) Invoice::whereNull('voided_at')->where('issue_date', '>=', $monthStart)->sum('total');
        $collectedMtd = (float) Payment::where('received_on', '>=', $monthStart)->sum('amount');

        $claims = InsuranceClaim::with('patient.lead')->orderByRaw("CASE WHEN status = 'settled' THEN 1 ELSE 0 END")->orderByDesc('submitted_on')->limit(40)->get();
        $openClaims = $claims->filter->isOpen();
        $settledCycle = InsuranceClaim::where('status', 'settled')->whereNotNull('settled_on')->get()
            ->map(fn ($c) => $c->submitted_on->diffInDays($c->settled_on));
        $avgClaimCycle = $settledCycle->isNotEmpty() ? (int) round($settledCycle->avg()) : ($openClaims->isNotEmpty() ? (int) round($openClaims->avg(fn ($c) => $c->ageDays())) : 0);

        $claimAging = collect([['label' => '0–14 days', 'min' => 0, 'max' => 14], ['label' => '15–30 days', 'min' => 15, 'max' => 30], ['label' => '31–60 days', 'min' => 31, 'max' => 60], ['label' => '60+ days', 'min' => 61, 'max' => null]])
            ->map(function ($b) use ($openClaims) {
                $in = $openClaims->filter(fn ($c) => $c->ageDays() >= $b['min'] && ($b['max'] === null || $c->ageDays() <= $b['max']));

                return $b + ['count' => $in->count(), 'amount' => (float) $in->sum('amount')];
            })->values();

        // Revenue by payer this month: insurer shares from the splits, family
        // share as Self-pay.
        $mtd = $live->filter(fn ($i) => $i->issue_date && $i->issue_date->gte($monthStart));
        $byPayer = [];
        foreach ($mtd as $i) {
            foreach ($i->payer_splits ?? [] as $s) {
                $byPayer[$s['payer']] = ($byPayer[$s['payer']] ?? 0) + (float) $s['amount'];
            }
            if (! $i->payer_splits && (float) $i->insurance_coverage_amount > 0) {
                $byPayer[$i->payer] = ($byPayer[$i->payer] ?? 0) + (float) $i->insurance_coverage_amount;
            }
            $byPayer['Self-pay'] = ($byPayer['Self-pay'] ?? 0) + (float) $i->patient_responsibility;
        }
        $payerTotal = array_sum($byPayer) ?: 1;
        $revenueByPayer = collect($byPayer)->map(fn ($v, $k) => ['payer' => $k, 'amount' => round($v, 2), 'pct' => (int) round($v / $payerTotal * 100)])->sortByDesc('amount')->values();

        $rejected = InsuranceClaim::with('patient.lead')->where('status', 'rejected')->latest('submitted_on')->first();

        // Aging & statements.
        $open = Invoice::with(['patient.lead', 'payments'])->whereNull('voided_at')->get()->filter(fn ($i) => $i->balance() > 0.01);
        $agingRows = $open->sortByDesc(fn ($i) => $i->daysPastDue())->values()->map(fn ($i) => InvoicePresenter::row($i));
        $buckets = collect(config('billing.aging_buckets'))->map(function ($b, $idx) use ($open) {
            $prevMax = $idx === 0 ? null : config('billing.aging_buckets')[$idx - 1]['max'];
            $in = $open->filter(function ($i) use ($b, $prevMax) {
                $d = $i->daysPastDue();
                if ($b['max'] === 0) return $d <= 0;
                if ($b['max'] === null) return $d > $prevMax;
                return $d > $prevMax && $d <= $b['max'];
            });

            return ['key' => $b['key'], 'label' => $b['label'], 'count' => $in->count(), 'amount' => round($in->sum(fn ($i) => $i->balance()), 2)];
        })->values();
        $outstandingByPayer = $open->groupBy(fn ($i) => $i->payer ?: 'Self-pay')->map(fn ($g, $k) => ['payer' => $k, 'amount' => round($g->sum(fn ($i) => $i->balance()), 2)])->sortByDesc('amount')->values();
        $oldest = $open->sortByDesc(fn ($i) => $i->daysPastDue())->first();

        $families = Patient::with(['lead', 'authorizations', 'invoices.payments'])->get()
            ->filter(fn ($p) => $p->invoices->isNotEmpty())
            ->map(function (Patient $p) {
                $liveInv = $p->invoices->reject->isVoided();

                return [
                    'patient_id' => $p->id,
                    'patient' => $p->lead?->child_name ?? '—',
                    'parent' => $p->lead?->parent_guardian_name,
                    'payer' => $p->primaryAuthorization()?->payer_name ?? 'Self-pay',
                    'invoices' => $liveInv->count(),
                    'billed' => round($liveInv->sum('total'), 2),
                    'balance' => round($p->invoices->sum(fn ($i) => $i->isVoided() ? 0 : $i->balance()), 2),
                    'statement_url' => route('billing.statements.show', $p),
                ];
            })->sortByDesc('balance')->values();

        $patients = Patient::with(['lead', 'authorizations'])->get()
            ->map(fn (Patient $p) => InvoicePresenter::patient($p, $this->ledger))
            ->sortBy('name')->values();

        // The Insurances CMS list (Settings) is the source of truth for payer
        // options; any payer name already on an authorization but not (or no
        // longer) in that list is still included, so a historical/deactivated
        // payer never silently vanishes from filters and pickers.
        $existingPayerNames = PatientAuthorization::query()->distinct()->pluck('payer_name')->filter()->reject(fn ($n) => preg_match('/self/i', $n));
        $payers = Insurance::where('is_active', true)->orderBy('name')->pluck('name')
            ->concat($existingPayerNames)->unique()->sort()->values()->push('Self-pay');

        return view('billing.index', [
            // A "View only" level on the Billing module overrides the
            // create_invoice action - every mutating control on this page
            // (new invoice, record payment, credit note, void/reissue,
            // pre-auth request/resubmit, bulk issue) is gated on this one
            // flag, so a view-only viewer genuinely can't act on anything
            // here, not just have it hidden as a courtesy (the write routes
            // themselves are also blocked by FeatureMiddleware regardless).
            'canInvoice' => $user->canDo('create_invoice') && $user->levelFor('billing') !== 'view',
            'capabilities' => RoleTemplate::capabilitiesForUser($user, 'view invoices'),
            'tiles' => [
                'invoiced_mtd' => $invoicedMtd,
                'collected_mtd' => $collectedMtd,
                'outstanding_claims' => (float) $openClaims->sum('amount'),
                'avg_claim_cycle' => $avgClaimCycle,
            ],
            'monthLabel' => now()->format('F Y'),
            'payerSummary' => $payers->reject(fn ($p) => $p === 'Self-pay')->take(3)->implode(', ').' claims',
            'invoicesForJs' => $rows,
            'claimsForJs' => $claims->map(fn ($c) => self::claimRow($c))->values(),
            'claimAging' => $claimAging,
            'revenueByPayer' => $revenueByPayer,
            'rejectedAlert' => $rejected ? ['patient' => $rejected->patient?->lead?->child_name, 'insurer' => $rejected->insurer, 'reference' => $rejected->reference, 'notes' => $rejected->notes] : null,
            'preAuthsForJs' => PreAuthorization::with('patient.lead')->orderByDesc('submitted_on')->limit(30)->get()->map(fn ($p) => self::preAuthRow($p))->values(),
            'aging' => [
                'total_outstanding' => round($open->sum(fn ($i) => $i->balance()), 2),
                'open_count' => $open->count(),
                'past_due' => round($open->filter(fn ($i) => $i->daysPastDue() > 0)->sum(fn ($i) => $i->balance()), 2),
                'past_due_count' => $open->filter(fn ($i) => $i->daysPastDue() > 0)->count(),
                'oldest_days' => $oldest ? max(0, $oldest->daysPastDue()) : 0,
                'oldest_ref' => $oldest ? $oldest->invoice_number.' · '.($oldest->patient?->lead?->child_name ?? '') : null,
                'reminders_month' => (int) Invoice::where('reminder_sent_at', '>=', $monthStart)->sum('reminders_count'),
                'rows' => $agingRows,
                'buckets' => $buckets,
                'by_payer' => $outstandingByPayer,
                'families' => $families,
            ],
            'patientsForJs' => $patients,
            'payers' => $payers,
            'services' => Service::where('is_active', true)->orderBy('name')->pluck('name'),
            'methods' => Payment::METHODS,
            'claimStatuses' => InsuranceClaim::LABELS,
            'clinic' => config('clinic'),
            'cancelPolicy' => config('billing.cancel_policy'),
            'bulkDefaults' => ['from' => now()->subWeeks(3)->toDateString(), 'to' => now()->toDateString()],
        ]);
    }

    public static function claimRow(InsuranceClaim $c): array
    {
        return [
            'id' => $c->id,
            'reference' => $c->reference,
            'patient' => $c->patient?->lead?->child_name ?? '—',
            'insurer' => $c->insurer,
            'amount' => (float) $c->amount,
            'period' => $c->period_label,
            'status' => $c->status,
            'status_label' => $c->statusLabel(),
            'age' => $c->ageDays(),
            'open' => $c->isOpen(),
            'notes' => $c->notes,
            'invoice' => $c->invoice?->invoice_number,
        ];
    }

    public static function preAuthRow(PreAuthorization $p): array
    {
        return [
            'id' => $p->id,
            'reference' => $p->reference,
            'patient_id' => $p->patient_id,
            'patient' => $p->patient?->lead?->child_name ?? '—',
            'payer' => $p->payer,
            'service' => $p->service,
            'hours' => $p->hours,
            'from' => $p->valid_from->format('d M Y'),
            'to' => $p->valid_to->format('d M Y'),
            'from_iso' => $p->valid_from->format('Y-m-d'),
            'to_iso' => $p->valid_to->format('Y-m-d'),
            'submitted' => $p->submitted_on->format('d M Y'),
            'status' => $p->status,
            'status_label' => $p->statusLabel(),
            'payer_reference' => $p->payer_reference,
            'denial_reason' => $p->denial_reason,
            'justification' => $p->justification,
            'resubmitted_from' => $p->resubmitted_from_id,
        ];
    }
}
