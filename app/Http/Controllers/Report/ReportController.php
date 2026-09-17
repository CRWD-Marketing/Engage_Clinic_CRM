<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\CalendarSession;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Lead;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('report.index', $this->reportData());
    }

    /**
     * All the report's sections, keyed for both report.index (on-screen) and
     * report.print_pdf (Export PDF) - a section added here shows up in both
     * automatically, which is the whole point of not building them separately.
     */
    private function reportData(): array
    {
        [$periodStart, $periodEnd] = $this->reportingPeriod();

        return [
            ...$this->revenueByMonth(),
            ...$this->leadFunnel(),
            ...$this->therapyHours(),
            ...$this->vatReturnSummary(),
            ...$this->collectionRate($periodStart, $periodEnd),
            ...$this->revenueByService($periodStart, $periodEnd),
            ...$this->revenueBySettingAndTherapist($periodStart, $periodEnd),
            ...$this->whyLeadsAreLost($periodStart, $periodEnd),
        ];
    }

    /**
     * The shared trailing-6-month window the money/lead sections report
     * over - same range revenueByMonth already charts, so every section on
     * the page (VAT return summary aside, which is filed monthly) describes
     * the same "January - July 2026" period shown in the page header.
     */
    private function reportingPeriod(): array
    {
        return [now()->subMonthsNoOverflow(5)->startOfMonth(), now()->endOfMonth()];
    }

    /**
     * The same real, live-queried data as the on-screen report, rendered
     * through a dompdf-safe (table-based) template - report.index relies on
     * flexbox/conic-gradient for its charts, which dompdf doesn't reliably
     * render (see billing.print_pdf for the same issue on invoices).
     */
    public function exportPdf()
    {
        $reportData = $this->reportData();

        $data = [
            ...$reportData,
            'clinic' => config('clinic'),
            'leadSourcesPieImage' => $this->leadSourcesPieChart($reportData['leadSources'], $reportData['capturedCount']),
        ];

        return Pdf::loadView('report.print_pdf', $data)
            ->setPaper('a4')
            ->download('engage-clinic-report-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * A real pie chart as a rendered PNG (base64 data URI), for the PDF only
     * - report.index's on-screen pie uses a CSS conic-gradient, but dompdf
     * supports neither that nor SVG in this environment (tested a bare
     * single-circle SVG on its own and it came back a blank page), so the
     * same real per-source percentages are drawn with GD instead, which
     * dompdf renders as reliably as the logo image on invoices.
     */
    private function leadSourcesPieChart($leadSources, int $totalCount): ?string
    {
        $totalPct = $leadSources->sum('pct');
        if ($totalPct <= 0) {
            return null;
        }

        $size = 360;
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        imagealphablending($image, true);
        imageantialias($image, true);

        $cx = $size / 2;
        $cy = $size / 2;
        $diameter = $size - 12;
        $startAngle = 0.0;

        foreach ($leadSources as $source) {
            $sweep = ($source['pct'] / $totalPct) * 360;
            if ($sweep <= 0) {
                continue;
            }
            [$r, $g, $b] = sscanf($source['color'], '#%02x%02x%02x');
            $color = imagecolorallocate($image, $r, $g, $b);
            $endAngle = $startAngle + $sweep;
            // GD's 0deg is 3 o'clock, sweeping clockwise - offset by -90 so
            // the first (largest) slice starts at 12 o'clock like a normal
            // pie chart, and round the very last slice up to a clean 360 so
            // rounding across percentages never leaves a sliver gap.
            $isLast = $source === $leadSources->last();
            imagefilledarc(
                $image, (int) $cx, (int) $cy, (int) $diameter, (int) $diameter,
                (int) round($startAngle - 90), (int) round(($isLast ? 360 : $endAngle) - 90),
                $color, IMG_ARC_PIE
            );
            $startAngle = $endAngle;
        }

        // Donut hole - matches the on-screen pie's white center disc.
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefilledellipse($image, (int) $cx, (int) $cy, (int) round($diameter * 0.52), (int) round($diameter * 0.52), $white);

        // Total count centered in the hole, matching the on-screen pie's
        // "N LEADS · 90d" centre label - reuses dompdf's own bundled font so
        // no extra font asset is needed.
        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');
        if (is_file($font)) {
            $navy = imagecolorallocate($image, 22, 67, 110);
            $grey = imagecolorallocate($image, 152, 137, 122);
            $this->drawCenteredTtfText($image, $font, 34, $navy, $cx, $cy - 8, (string) $totalCount);
            $this->drawCenteredTtfText($image, $font, 11, $grey, $cx, $cy + 20, 'LEADS · 90d');
        }

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return base64_encode($bytes);
    }

    /**
     * imagettftext() positions text by its baseline's left edge, not a
     * center point, so the text's own bounding box is measured first and
     * the origin shifted back by half its width/height to actually center
     * it at ($cx, $cy).
     */
    private function drawCenteredTtfText($image, string $font, int $fontSize, int $color, float $cx, float $cy, string $text): void
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);
        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[5] - $box[1]);

        imagettftext(
            $image, $fontSize, 0,
            (int) round($cx - $textWidth / 2), (int) round($cy + $textHeight / 2),
            $color, $font, $text
        );
    }

    /**
     * Real monthly revenue, trailing 6 months through the current (partial)
     * month - 'subtotal' to match how revenue is defined everywhere else in
     * the app (DashboardController's billing/finance widgets).
     */
    private function revenueByMonth(): array
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonthsNoOverflow($i)->startOfMonth());

        $revenueByMonth = $months->map(function ($start) {
            $end = $start->copy()->endOfMonth();
            $amount = (float) Invoice::whereBetween('issue_date', [$start, $end])->sum('subtotal');

            return [
                'label' => $start->format('M').($start->isCurrentMonth() ? '*' : ''),
                'amount' => $amount,
                'thousands' => round($amount / 1000, 1),
            ];
        });
        $revenueMaxThousands = max(1, $revenueByMonth->max('thousands'));

        // Summary stats for the card next to the chart - derived from the
        // same 6 rows, not a separate query.
        $revenueTotal6mo = $revenueByMonth->sum('amount');
        $revenueAverage6mo = $revenueByMonth->count() > 0 ? $revenueTotal6mo / $revenueByMonth->count() : 0;
        $revenueBestMonth = $revenueByMonth->sortByDesc('amount')->first();
        $revenueRows = $revenueByMonth->values();
        $revenueCurrentMonth = $revenueRows->last();
        $revenuePreviousMonth = $revenueRows->count() > 1 ? $revenueRows[$revenueRows->count() - 2] : null;
        $revenueMomDelta = ($revenuePreviousMonth && $revenuePreviousMonth['amount'] > 0)
            ? round((($revenueCurrentMonth['amount'] - $revenuePreviousMonth['amount']) / $revenuePreviousMonth['amount']) * 100)
            : null;

        return compact(
            'revenueByMonth', 'revenueMaxThousands', 'revenueTotal6mo', 'revenueAverage6mo',
            'revenueBestMonth', 'revenueMomDelta',
        );
    }

    /**
     * Real lead-pipeline funnel over the last 90 days, from Lead::status -
     * a lead "reaches" a stage if its current status is that stage or a
     * later one; a terminated lead only counts at the top (captured), since
     * there's no stage-transition history to say how far it actually got.
     */
    private function leadFunnel(): array
    {
        $since = now()->subDays(90);
        $leads = Lead::with('patient')->where('created_at', '>=', $since)->get();

        $capturedCount = $leads->count();
        $contactedCount = $leads->whereIn('status', [
            Lead::STATUS_CONTACTED, Lead::STATUS_ASSESSMENT_BOOKED, Lead::STATUS_ASSESSMENT_DONE, Lead::STATUS_ENROLLED,
        ])->count();
        $assessmentDoneCount = $leads->whereIn('status', [Lead::STATUS_ASSESSMENT_DONE, Lead::STATUS_ENROLLED])->count();
        $enrolledCount = $leads->where('status', Lead::STATUS_ENROLLED)->count();

        $funnelStages = collect([
            ['label' => 'Leads captured', 'count' => $capturedCount],
            ['label' => 'Contacted', 'count' => $contactedCount],
            ['label' => 'Assessment done', 'count' => $assessmentDoneCount],
            ['label' => 'Enrolled', 'count' => $enrolledCount],
        ])->map(fn ($s) => $s + [
            'pct' => $capturedCount > 0 ? round($s['count'] / $capturedCount * 100) : 0,
        ]);

        $conversionRate = $capturedCount > 0 ? round($enrolledCount / $capturedCount * 100) : null;

        // Assigned by position, not by source name - a fixed name => colour
        // map leaves any source it doesn't recognize (e.g. "Contact Us",
        // "Walk-in") falling back to the same default grey, so two
        // completely different sources could render as the same colour in
        // the chart. Cycling a distinct, high-contrast palette by index
        // guarantees every slice actually shown is visually different from
        // its neighbours, regardless of what the source names happen to be.
        $sourcePalette = ['#C8355F', '#16436E', '#1FA855', '#B97F24', '#6E4FA8', '#C13584', '#1F8FA8', '#A8461F', '#2E7D5B', '#8A7D6C'];
        $leadSources = $leads->groupBy(fn ($l) => $l->source ?: 'Other')
            ->map(fn ($group, $source) => [
                'source' => $source,
                'count' => $group->count(),
                'enrolled' => $group->where('status', Lead::STATUS_ENROLLED)->count(),
            ])
            ->sortByDesc('count')
            ->values();
        $leadSources = $leadSources->map(fn ($s, $i) => $s + [
            'pct' => $capturedCount > 0 ? round($s['count'] / $capturedCount * 100) : 0,
            'conversion_rate' => $s['count'] > 0 ? round($s['enrolled'] / $s['count'] * 100) : null,
            'color' => $sourcePalette[$i % count($sourcePalette)],
        ]);
        // Only a meaningful "best/worst converting source" once more than one
        // source actually has leads in this window - otherwise there's
        // nothing to compare.
        $bestSource = $leadSources->count() > 1 ? $leadSources->sortByDesc('conversion_rate')->first() : null;
        $worstSource = $leadSources->count() > 1 ? $leadSources->sortBy('conversion_rate')->first() : null;

        // Days from lead creation to actually becoming a patient, for leads
        // in this window that did enroll - real elapsed time, not a guess.
        // A negative value only happens from bad/backdated seed timestamps
        // (enrolled_at before the lead's own created_at), which isn't a real
        // "time to enroll" - excluded rather than shown as nonsense.
        $enrollDays = $leads->filter(fn ($l) => $l->status === Lead::STATUS_ENROLLED && $l->patient?->enrolled_at)
            ->map(fn ($l) => $l->created_at->diffInDays($l->patient->enrolled_at))
            ->filter(fn ($days) => $days >= 0)
            ->sort()->values();
        $medianEnrollDays = null;
        if ($enrollDays->isNotEmpty()) {
            $mid = intdiv($enrollDays->count(), 2);
            $medianEnrollDays = $enrollDays->count() % 2 === 1
                ? $enrollDays[$mid]
                : round(($enrollDays[$mid - 1] + $enrollDays[$mid]) / 2, 1);
        }

        return compact(
            'funnelStages', 'capturedCount', 'conversionRate',
            'leadSources', 'bestSource', 'worstSource', 'medianEnrollDays',
        );
    }

    /**
     * Real clinical hours delivered this month, from completed calendar
     * sessions grouped by activity type.
     */
    private function therapyHours(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthLabel = now()->format('F');

        $sessions = CalendarSession::whereBetween('session_date', [$monthStart, $monthEnd])
            ->where('status', 'completed')
            ->whereNotIn('activity_type', CalendarSession::NON_THERAPY_TYPES)
            ->select('activity_type', DB::raw('SUM(duration_minutes) as minutes'))
            ->groupBy('activity_type')
            ->get();

        $totalHours = round($sessions->sum('minutes') / 60);

        $activityColors = [
            'ABA' => '#C8355F', 'Speech' => '#24619C', 'OT' => '#B97F24',
            'Assessment' => '#6E4FA8', 'Supervision' => '#2E7D5B', 'Parent training' => '#8A5A10',
        ];
        $activityLabels = [
            'ABA' => 'ABA (1:1)', 'Speech' => 'Speech therapy', 'OT' => 'Occupational therapy',
            'Assessment' => 'Assessments', 'Supervision' => 'Supervision', 'Parent training' => 'Parent training',
        ];
        $therapyHoursByType = $sessions->map(fn ($row) => [
            'type' => $activityLabels[$row->activity_type] ?? $row->activity_type,
            'hours' => round($row->minutes / 60, 1),
            'color' => $activityColors[$row->activity_type] ?? '#8A7D6C',
        ])->sortByDesc('hours')->values();
        $therapyHoursMax = max(1, $therapyHoursByType->max('hours'));

        return compact('totalHours', 'monthLabel', 'therapyHoursByType', 'therapyHoursMax');
    }

    /**
     * Real UAE VAT return figures for the current filing period, from
     * invoices raised in that month - standard-rated supplies (net),
     * output tax at the configured rate, credit notes issued (VAT backed
     * out of the credited amount the same way it was added when raised),
     * and the resulting net VAT payable. Voided invoices are excluded -
     * they were never actually supplied.
     */
    private function vatReturnSummary(): array
    {
        $filingStart = now()->startOfMonth();
        $filingEnd = now()->endOfMonth();
        $vatFilingLabel = $filingStart->format('F Y');

        $invoices = Invoice::whereBetween('issue_date', [$filingStart, $filingEnd])
            ->whereNull('voided_at')
            ->get(['subtotal', 'vat_amount', 'credit_amount']);

        $vatStandardRatedSupplies = (float) $invoices->sum('subtotal');
        $vatOutputTax = (float) $invoices->sum('vat_amount');
        $vatRate = (float) config('billing.vat_rate', 5);

        // credit_amount is VAT-inclusive (it's capped against the invoice's
        // VAT-inclusive total elsewhere), so the tax portion is backed out
        // the same way it would have been calculated going in: amount / (1 + rate).
        $vatCreditNotesIssued = (float) $invoices->sum('credit_amount');
        $vatCreditNotesTax = $vatRate > 0 ? round($vatCreditNotesIssued * $vatRate / (100 + $vatRate), 2) : 0.0;
        $vatNetPayable = round($vatOutputTax - $vatCreditNotesTax, 2);

        return compact(
            'vatFilingLabel', 'vatRate', 'vatStandardRatedSupplies',
            'vatOutputTax', 'vatCreditNotesIssued', 'vatNetPayable',
        );
    }

    /**
     * Real collected-vs-invoiced totals for the reporting period, plus
     * billable therapy hours (completed sessions of a client-facing type)
     * and the resulting effective revenue per billable hour - what a clinic
     * actually realizes per hour of therapy delivered, after collections.
     */
    private function collectionRate(Carbon $periodStart, Carbon $periodEnd): array
    {
        $invoices = Invoice::whereBetween('issue_date', [$periodStart, $periodEnd])
            ->whereNull('voided_at')
            ->get(['total', 'amount_paid']);

        $collectionInvoicedTotal = (float) $invoices->sum('total');
        $collectionCollectedTotal = (float) $invoices->sum('amount_paid');
        $collectionRatePct = $collectionInvoicedTotal > 0
            ? round($collectionCollectedTotal / $collectionInvoicedTotal * 100)
            : 0;

        $billableMinutes = CalendarSession::whereBetween('session_date', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->whereIn('activity_type', CalendarSession::THERAPY_TYPES)
            ->sum('duration_minutes');
        $collectionBillableHours = round($billableMinutes / 60, 1);

        $collectionRevenuePerHour = $collectionBillableHours > 0
            ? round($collectionCollectedTotal / $collectionBillableHours, 2)
            : null;

        return compact(
            'collectionInvoicedTotal', 'collectionCollectedTotal', 'collectionRatePct',
            'collectionBillableHours', 'collectionRevenuePerHour',
        );
    }

    /**
     * Real revenue by therapy type for the reporting period, from invoice
     * line items joined back to the calendar session they billed (not
     * InvoiceLineItem.service_id, which billing doesn't populate) - the
     * same activity-type grouping therapyHours() uses, so a service reads
     * the same way whether it's counted in hours or in revenue.
     */
    private function revenueByService(Carbon $periodStart, Carbon $periodEnd): array
    {
        $lineItems = InvoiceLineItem::whereHas('invoice', fn ($q) => $q
            ->whereBetween('issue_date', [$periodStart, $periodEnd])
            ->whereNull('voided_at'))
            ->with('session:id,activity_type')
            ->get(['amount', 'calendar_session_id']);

        $activityColors = [
            'ABA' => '#C8355F', 'Speech' => '#24619C', 'OT' => '#B97F24',
            'Assessment' => '#6E4FA8', 'Supervision' => '#2E7D5B', 'Parent training' => '#8A5A10',
        ];
        $activityLabels = [
            'ABA' => 'ABA', 'Speech' => 'Speech', 'OT' => 'OT',
            'Assessment' => 'Assessments', 'Supervision' => 'Supervision', 'Parent training' => 'Parent training',
        ];

        $revenueByService = $lineItems->groupBy(fn ($li) => $li->session->activity_type ?? 'Other')
            ->map(fn ($group, $type) => [
                'label' => $activityLabels[$type] ?? $type,
                'amount' => (float) $group->sum('amount'),
                'color' => $activityColors[$type] ?? '#8A7D6C',
            ])
            ->sortByDesc('amount')
            ->values();
        $revenueByServiceMax = max(1, $revenueByService->max('amount') ?: 0);

        return compact('revenueByService', 'revenueByServiceMax');
    }

    /**
     * Real revenue by setting (client home vs. clinic) and by therapist for
     * the reporting period, from invoice line items - two independent
     * breakdowns of the same line items, shown on one shared bar scale.
     */
    private function revenueBySettingAndTherapist(Carbon $periodStart, Carbon $periodEnd): array
    {
        $lineItems = InvoiceLineItem::whereHas('invoice', fn ($q) => $q
            ->whereBetween('issue_date', [$periodStart, $periodEnd])
            ->whereNull('voided_at'))
            ->with('therapist:id,first_name,last_name')
            ->get(['amount', 'setting', 'therapist_id']);

        $revenueBySetting = $lineItems->groupBy(fn ($li) => $li->setting ?: 'Not specified')
            ->map(fn ($group, $setting) => ['label' => $setting, 'amount' => (float) $group->sum('amount')])
            ->sortByDesc('amount')
            ->values();

        $revenueByTherapist = $lineItems->groupBy('therapist_id')
            ->map(function ($group) {
                $therapist = $group->first()->therapist;
                return [
                    'label' => $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : 'Unassigned',
                    'amount' => (float) $group->sum('amount'),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $revenueSettingTherapistMax = max(
            1, $revenueBySetting->max('amount') ?: 0, $revenueByTherapist->max('amount') ?: 0,
        );

        return compact('revenueBySetting', 'revenueByTherapist', 'revenueSettingTherapistMax');
    }

    /**
     * Real termination reasons for the reporting period, from
     * Lead::termination_reason - captured once at the point a lead is
     * terminated (see Lead\LeadController@update), grouped to show which
     * reason is costing the most leads (and monthly value) so it can
     * actually be acted on. Terminations that predate reason-tracking
     * (terminated_at is null) still count toward the totals, just bucketed
     * as "No reason given" rather than dropped from the report.
     */
    private function whyLeadsAreLost(Carbon $periodStart, Carbon $periodEnd): array
    {
        $terminatedLeads = Lead::where('status', Lead::STATUS_TERMINATED)
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('terminated_at', [$periodStart, $periodEnd])
                    ->orWhereNull('terminated_at');
            })
            ->get();

        $lostLeadsCount = $terminatedLeads->count();
        $lostLeadsValue = (float) $terminatedLeads->sum('estimated_value_numeric');

        $lostReasons = $terminatedLeads->groupBy(fn ($lead) => $lead->termination_reason ?: 'No reason given')
            ->map(fn ($group, $reason) => [
                'reason' => $reason,
                'count' => $group->count(),
                'value' => (float) $group->sum('estimated_value_numeric'),
            ])
            ->sortByDesc('count')
            ->values();
        $lostReasonsMax = max(1, $lostReasons->max('count') ?: 0);
        $lostReasonsTopDriver = $lostReasons->first();

        return compact('lostLeadsCount', 'lostLeadsValue', 'lostReasons', 'lostReasonsMax', 'lostReasonsTopDriver');
    }
}
