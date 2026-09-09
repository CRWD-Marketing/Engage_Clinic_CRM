<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    public function index()
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $invoicedMtd = (float) Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])->sum('subtotal');
        $collectedMtd = (float) Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])->where('status', 'paid')->sum('subtotal');

        $openClaims = Invoice::where('payer', '!=', 'Self-pay')
            ->whereIn('status', ['submitted', 'pending_info'])
            ->get(['id', 'issue_date', 'insurance_coverage_amount']);

        $outstandingClaims = (float) $openClaims->sum('insurance_coverage_amount');

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

        $paidClaims = Invoice::where('status', 'paid')->get(['issue_date', 'updated_at']);
        $avgClaimCycleDays = $paidClaims->isNotEmpty()
            ? (int) round($paidClaims->avg(fn ($inv) => $inv->issue_date->diffInDays($inv->updated_at)))
            : null;

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

        $recentClaims = Invoice::with('patient.lead')
            ->withSum('lineItems as sessions_count', 'sessions')
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $rejectedAlert = Invoice::with('patient.lead')
            ->where('status', 'rejected')
            ->latest('issue_date')
            ->first();

        $patients = Patient::with(['lead', 'authorizations'])->get()
            ->filter(fn ($p) => $p->lead)
            ->sortBy(fn ($p) => $p->lead->child_name)
            ->values();

        $services = Service::where('is_active', true)->orderBy('name')->get();
        $therapists = User::where('role', 'THERAPIST')->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('billing.index', compact(
            'invoicedMtd',
            'collectedMtd',
            'outstandingClaims',
            'avgClaimCycleDays',
            'agingBuckets',
            'agingMax',
            'revenueByPayer',
            'recentClaims',
            'rejectedAlert',
            'patients',
            'services',
            'therapists',
        ));
    }
}
