<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Report\ReportController as WebReportController;

/**
 * Reports & analytics for the mobile app: the same sections the web page and
 * its PDF are built from (ReportController::reportData()), as JSON.
 */
class ReportController extends WebReportController
{
    /** GET /reports */
    public function index()
    {
        $d = $this->reportData();

        // The web marks the month still in progress with a trailing "*".
        $month = fn (array $row) => [
            'label' => rtrim($row['label'], '*'),
            'amount' => $row['amount'],
            'thousands' => $row['thousands'],
            'is_current' => str_ends_with($row['label'], '*'),
        ];

        return response()->json([
            'period_start_label' => rtrim($d['revenueByMonth']->first()['label'] ?? '', '*'),
            'period_end_label' => now()->format('F Y'),
            'updated_at' => now(),

            'vat_filing_label' => $d['vatFilingLabel'],
            'vat_trn' => (string) config('clinic.trn'),
            'vat_rate' => $d['vatRate'],
            'vat_standard_rated_supplies' => $d['vatStandardRatedSupplies'],
            'vat_output_tax' => $d['vatOutputTax'],
            'vat_credit_notes_issued' => $d['vatCreditNotesIssued'],
            'vat_net_payable' => $d['vatNetPayable'],

            'collection_invoiced_total' => $d['collectionInvoicedTotal'],
            'collection_collected_total' => $d['collectionCollectedTotal'],
            'collection_rate_pct' => (int) $d['collectionRatePct'],
            'collection_billable_hours' => $d['collectionBillableHours'],
            'collection_revenue_per_hour' => $d['collectionRevenuePerHour'],

            'revenue_by_service' => $d['revenueByService']->values(),
            'revenue_by_setting' => $d['revenueBySetting']->values(),
            'revenue_by_therapist' => $d['revenueByTherapist']->values(),

            'revenue_by_month' => $d['revenueByMonth']->map($month)->values(),
            'revenue_total_6mo' => $d['revenueTotal6mo'],
            'revenue_average_6mo' => $d['revenueAverage6mo'],
            'revenue_best_month' => $month($d['revenueBestMonth']),
            'revenue_mom_delta' => $d['revenueMomDelta'] === null ? null : (int) $d['revenueMomDelta'],

            'funnel_stages' => $d['funnelStages']->values(),
            'captured_count' => $d['capturedCount'],
            'conversion_rate' => $d['conversionRate'] === null ? null : (int) $d['conversionRate'],
            'lead_sources' => $d['leadSources']->values(),
            'best_source' => $d['bestSource'],
            'worst_source' => $d['worstSource'],
            'median_enroll_days' => $d['medianEnrollDays'] === null ? null : round($d['medianEnrollDays'], 1),

            'lost_leads_count' => $d['lostLeadsCount'],
            'lost_leads_value' => $d['lostLeadsValue'],
            'lost_reasons' => $d['lostReasons']->values(),

            'therapy_month_label' => $d['monthLabel'],
            'therapy_total_hours' => (int) $d['totalHours'],
            'therapy_hours_by_type' => $d['therapyHoursByType']->values(),
        ]);
    }
}
