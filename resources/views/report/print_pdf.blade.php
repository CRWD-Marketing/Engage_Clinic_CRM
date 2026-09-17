<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reports · {{ $clinic['name'] }}</title>
    <style>
        {{--
            Table-based layout throughout, deliberately - dompdf doesn't
            reliably render flexbox/grid (see billing.print_pdf for the same
            issue on invoices), so the on-screen report.index charts are
            rebuilt here with tables and plain block bars instead, to render
            correctly as a real PDF. Sections pair up left/right via
            .split-table, matching report.index's 2-column grid exactly -
            a plain HTML table's cells are one layout dompdf renders just
            as reliably as a single stacked column.
        --}}
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Nunito Sans', 'Helvetica', 'Arial', sans-serif; color: #2B3A4C; background: #fff; }
        table { border-collapse: collapse; }

        .banner-table { width: 100%; background: #16436E; padding: 22px 40px; }
        .banner-title { color: #fff; font: 600 24px 'Baloo 2', 'Georgia', serif; }
        .banner-sub { color: #BFD2E3; font: 700 12px 'Nunito Sans'; margin-top: 2px; }
        .accent { height: 4px; background: #C8355F; font-size: 0; line-height: 0; }

        .body { padding: 30px 40px 40px; }

        .card-title { font: 600 16px 'Baloo 2', 'Georgia', serif; color: #16436E; }
        .card-sub { font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-bottom: 14px; margin-top: 2px; }
        .card-empty { font: 700 12px 'Nunito Sans'; color: #98897A; }
        .card-note { font: 600 11.5px/1.5 'Nunito Sans'; color: #98897A; margin-top: 14px; }

        /* Revenue bar chart - plain stacked blocks, no flex needed */
        .rev-table { width: 100%; }
        .rev-table td { text-align: center; vertical-align: bottom; padding: 0 10px; }
        .rev-amount { font: 800 11px 'Nunito Sans'; color: #98897A; margin-bottom: 5px; }
        .rev-bar { width: 100%; border-radius: 5px 5px 2px 2px; }
        .rev-label { font: 700 11px 'Nunito Sans'; color: #8A7D6C; margin-top: 5px; }

        /* Funnel / therapy-hours bars - label + bar as a two-column table row */
        .bar-row-table { width: 100%; }
        .bar-row-table td { padding: 5px 0; vertical-align: middle; }
        .bar-label-cell { width: 160px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .bar-track { background: #F3EDE3; border-radius: 5px; height: 12px; }
        .bar-track-outer { width: 100%; }
        .bar-fill { height: 12px; border-radius: 5px; }
        .funnel-bar-outer { width: 100%; }
        .funnel-bar-fill { height: 26px; border-radius: 6px; color: #fff; font: 800 12px 'Nunito Sans'; text-align: right; padding: 0 10px; }

        /* Lead sources: a real pie chart rendered server-side with GD and
           embedded as a PNG (dompdf renders no SVG and no CSS
           conic-gradient in this environment - tested a bare single-circle
           SVG on its own and it came back a blank page - but handles a
           raster image exactly like the invoice logo), centred above its
           full legend, with breathing room between the two. */
        .pie-wrap { text-align: center; padding: 6px 0 22px; }
        .legend-row td { padding: 5px 0; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 3px; font-size: 0; }
        .legend-pct { text-align: right; color: #16436E; }
        .legend-count { text-align: right; color: #98897A; font-weight: 600; }

        .hours-value { font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right; }

        /* VAT return summary - plain label/value rows, last one totalled */
        .kv-table { width: 100%; }
        .kv-table td { padding: 5px 0; font: 700 13px 'Nunito Sans'; color: #2B3A4C; }
        .kv-table td.kv-value { text-align: right; }
        .kv-table tr.kv-divider td { border-top: 1px solid #EBE4DA; padding-top: 10px; }
        .kv-table tr.kv-total td { font: 800 14px 'Nunito Sans'; color: #16436E; }

        /* Collection rate - big percentage above a single progress bar */
        .collection-sub { font: 600 11.5px 'Nunito Sans'; color: #98897A; }
        .collection-pct { font: 600 30px 'Baloo 2', 'Georgia', serif; color: #2E7D5B; margin: 4px 0 10px; }
        .collection-track { background: #F3EDE3; border-radius: 5px; height: 10px; margin-bottom: 16px; }
        .collection-fill { height: 10px; border-radius: 5px; background: #2E7D5B; }
        .stat-row td { padding: 3px 0; font: 700 12.5px 'Nunito Sans'; }
        .stat-label { color: #98897A; }
        .stat-value { text-align: right; color: #2B3A4C; }

        /* Every pair of sections below sits in one of these, two cards side
           by side - the plain-table equivalent of report.index's 2-column
           grid. The card look (background/border/radius/padding) is on the
           <td> itself, not a div inside it: sibling cells in a table row
           are ALWAYS equal height natively (no percentage-height trick
           needed - that one blew up into a page of blank space, since
           dompdf resolves height:100% against the page, not the row). A
           spacer cell provides the gap between the two, since padding on
           the cell itself would land inside its own border instead. */
        .split-table { width: 100%; margin-bottom: 24px; }
        .split-table.page-break { page-break-after: always; }
        .split-cell {
            width: 50%; vertical-align: top;
            background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 10px;
            padding: 24px; page-break-inside: avoid;
        }
        .split-spacer { width: 24px; }

        /* .body's own top padding only renders once, at the very start of
           that (one, continuous) box - the gap under the banner on page 1.
           A pair that starts a later page (right after a page-break-after
           table) gets none of that, so it'd otherwise sit flush against
           the page's physical top edge - applied below to the two pairs
           that happen to start page 2 and page 3. */
        .split-table.page-start { margin-top: 40px; }
    </style>
</head>
<body>

    <table class="banner-table"><tr>
        <td>
            <div class="banner-title">Reports &amp; analytics</div>
            <div class="banner-sub">{{ $clinic['name'] }} · generated {{ now()->format('d M Y, H:i') }}</div>
        </td>
    </tr></table>
    <div class="accent">&nbsp;</div>

    <div class="body">

        <!-- VAT return summary + Collection rate -->
        <table class="split-table"><tr>
            <td class="split-cell">
                <div class="card-title">VAT return summary</div>
                <div class="card-sub">Current filing period · {{ $vatFilingLabel }} · TRN {{ $clinic['trn'] }}</div>
                <table class="kv-table">
                    <tr>
                        <td>Standard-rated supplies (net)</td>
                        <td class="kv-value">AED {{ number_format($vatStandardRatedSupplies, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Output tax at {{ rtrim(rtrim(number_format($vatRate, 1), '0'), '.') }}%</td>
                        <td class="kv-value">AED {{ number_format($vatOutputTax, 2) }}</td>
                    </tr>
                    <tr class="kv-divider">
                        <td>Credit notes issued</td>
                        <td class="kv-value">– AED {{ number_format($vatCreditNotesIssued, 2) }}</td>
                    </tr>
                    <tr class="kv-divider kv-total">
                        <td>Net VAT payable</td>
                        <td class="kv-value">AED {{ number_format($vatNetPayable, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td class="split-spacer"></td>
            <td class="split-cell">
                <div class="card-title">Collection rate</div>
                <div class="collection-sub">AED {{ number_format($collectionCollectedTotal, 2) }} collected of AED {{ number_format($collectionInvoicedTotal, 2) }} invoiced</div>
                <div class="collection-pct">{{ $collectionRatePct }}%</div>
                <div class="collection-track"><div class="collection-fill" style="width: {{ max(0, min(100, $collectionRatePct)) }}%;">&nbsp;</div></div>
                <table class="stat-row" style="width:100%;">
                    <tr>
                        <td class="stat-label">Billable session hours</td>
                        <td class="stat-value">{{ rtrim(rtrim(number_format($collectionBillableHours, 1), '0'), '.') }} h</td>
                    </tr>
                    <tr>
                        <td class="stat-label">Effective revenue per billable hour</td>
                        <td class="stat-value">{{ $collectionRevenuePerHour !== null ? 'AED '.number_format($collectionRevenuePerHour, 2) : '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr></table>

        <!-- Revenue by service + Revenue by setting & therapist -->
        <table class="split-table"><tr>
            <td class="split-cell">
                <div class="card-title">Revenue by service</div>
                <div class="card-sub">Billable session value from invoiced line items</div>
                @if ($revenueByService->isEmpty())
                    <div class="card-empty">No invoiced sessions in this period yet.</div>
                @else
                    <table style="width:100%;">
                        @foreach ($revenueByService as $row)
                            <tr>
                                <td class="bar-label-cell" style="width:auto; padding-bottom:3px;">{{ $row['label'] }}</td>
                                <td class="hours-value" style="width:90px; padding-bottom:3px;">AED {{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-bottom:12px;">
                                    <table class="bar-track-outer"><tr>
                                        <td class="bar-track" style="width: {{ max(3, round($row['amount'] / $revenueByServiceMax * 100)) }}%;"><div class="bar-fill" style="background: {{ $row['color'] }}; width:100%;">&nbsp;</div></td>
                                        <td class="bar-track" style="background:transparent;"></td>
                                    </tr></table>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
            <td class="split-spacer"></td>
            <td class="split-cell">
                <div class="card-title">Revenue by setting &amp; therapist</div>
                <div class="card-sub">Where the work happens and who delivered it</div>
                @if ($revenueBySetting->isEmpty() && $revenueByTherapist->isEmpty())
                    <div class="card-empty">No invoiced sessions in this period yet.</div>
                @else
                    <table style="width:100%;">
                        @foreach ($revenueBySetting as $row)
                            <tr>
                                <td class="bar-label-cell" style="width:auto; padding-bottom:3px;">{{ $row['label'] }}</td>
                                <td class="hours-value" style="width:90px; padding-bottom:3px;">AED {{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-bottom:12px;">
                                    <table class="bar-track-outer"><tr>
                                        <td class="bar-track" style="width: {{ max(3, round($row['amount'] / $revenueSettingTherapistMax * 100)) }}%;"><div class="bar-fill" style="background: #16436E; width:100%;">&nbsp;</div></td>
                                        <td class="bar-track" style="background:transparent;"></td>
                                    </tr></table>
                                </td>
                            </tr>
                        @endforeach
                        @foreach ($revenueByTherapist as $row)
                            <tr>
                                <td class="bar-label-cell" style="width:auto; padding-bottom:3px;">{{ $row['label'] }}</td>
                                <td class="hours-value" style="width:90px; padding-bottom:3px;">AED {{ number_format($row['amount'], 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-bottom:12px;">
                                    <table class="bar-track-outer"><tr>
                                        <td class="bar-track" style="width: {{ max(3, round($row['amount'] / $revenueSettingTherapistMax * 100)) }}%;"><div class="bar-fill" style="background: #B97F24; width:100%;">&nbsp;</div></td>
                                        <td class="bar-track" style="background:transparent;"></td>
                                    </tr></table>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
        </tr></table>

        <!-- Revenue by month + Revenue summary - 6 cards max per page, so
             this is the 3rd pair on page 1, and forces the break to page 2. -->
        <table class="split-table page-break"><tr>
            <td class="split-cell">
                <div class="card-title">Revenue by month</div>
                <div class="card-sub">AED thousands (invoiced, VAT excl.)</div>
                @if ($revenueByMonth->sum('amount') == 0)
                    <div class="card-empty">No invoices raised in this period yet.</div>
                @else
                    <table class="rev-table"><tr>
                        @foreach ($revenueByMonth as $m)
                            @php $isCurrent = str_ends_with($m['label'], '*'); @endphp
                            <td>
                                <div class="rev-amount">{{ number_format($m['thousands'], $m['thousands'] < 10 ? 1 : 0) }}</div>
                                <div class="rev-bar" style="height: {{ max(4, round($m['thousands'] / $revenueMaxThousands * 130)) }}px; background: {{ $isCurrent ? '#C8355F' : '#EBD3DB' }};">&nbsp;</div>
                                <div class="rev-label">{{ $m['label'] }}</div>
                            </td>
                        @endforeach
                    </tr></table>
                @endif
            </td>
            <td class="split-spacer"></td>
            <td class="split-cell">
                <div class="card-title">Revenue summary</div>
                <div class="card-sub">{{ $revenueByMonth->first()['label'] ?? '' }} – {{ now()->format('F Y') }}</div>
                @if ($revenueTotal6mo == 0)
                    <div class="card-empty">No invoices raised in this period yet.</div>
                @else
                    <div class="collection-pct" style="color:#16436E;">AED {{ number_format($revenueTotal6mo, 0) }}</div>
                    <div class="collection-sub" style="margin-bottom:16px;">
                        total invoiced, VAT excl.
                        @if ($revenueMomDelta !== null) · {{ $revenueMomDelta >= 0 ? '+' : '' }}{{ $revenueMomDelta }}% vs last month @endif
                    </div>
                    <table class="kv-table">
                        <tr class="kv-divider">
                            <td>Monthly average</td>
                            <td class="kv-value">AED {{ number_format($revenueAverage6mo, 0) }}</td>
                        </tr>
                        @if ($revenueBestMonth)
                            <tr>
                                <td>Best month</td>
                                <td class="kv-value">{{ rtrim($revenueBestMonth['label'], '*') }} · AED {{ number_format($revenueBestMonth['amount'], 0) }}</td>
                            </tr>
                        @endif
                    </table>
                @endif
            </td>
        </tr></table>

        <!-- Lead conversion funnel + Why leads are lost - starts page 2 -->
        <table class="split-table page-start"><tr>
            <td class="split-cell">
                <div class="card-title">Lead conversion funnel</div>
                <div class="card-sub">Last 90 days{{ $conversionRate !== null ? ' · '.$conversionRate.'% lead to enrolled' : '' }}</div>
                @if ($capturedCount === 0)
                    <div class="card-empty">No leads captured in the last 90 days.</div>
                @else
                    @php $stageColors = ['#16436E', '#3A6A96', '#7396B8', '#C8355F']; @endphp
                    <table class="bar-row-table">
                        @foreach ($funnelStages as $i => $stage)
                            <tr>
                                <td class="bar-label-cell">{{ $stage['label'] }}</td>
                                <td>
                                    <table class="funnel-bar-outer"><tr>
                                        <td style="width: {{ max(6, $stage['pct']) }}%;">
                                            <div class="funnel-bar-fill" style="background: {{ $stageColors[$i] }};">{{ $stage['count'] }}</div>
                                        </td>
                                        <td></td>
                                    </tr></table>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    @if ($bestSource || $medianEnrollDays !== null)
                        <div class="card-note">
                            @if ($bestSource && $worstSource && $bestSource['source'] !== $worstSource['source'])
                                {{ $bestSource['source'] }} leads convert best ({{ $bestSource['conversion_rate'] }}%) — {{ $worstSource['source'] }} lowest ({{ $worstSource['conversion_rate'] }}%).
                            @elseif ($bestSource)
                                {{ $bestSource['source'] }} converts at {{ $bestSource['conversion_rate'] }}%.
                            @endif
                            @if ($medianEnrollDays !== null)
                                Median time from first contact to enrollment: {{ rtrim(rtrim(number_format($medianEnrollDays, 1), '0'), '.') }} days.
                            @endif
                        </div>
                    @endif
                @endif
            </td>
            <td class="split-spacer"></td>
            <td class="split-cell">
                <div class="card-title">Why leads are lost</div>
                <div class="card-sub">{{ $lostLeadsCount }} terminated {{ Str::plural('lead', $lostLeadsCount) }} · reasons captured at termination</div>
                @if ($lostReasons->isEmpty())
                    <div class="card-empty">No terminated leads in this period.</div>
                @else
                    <table style="width:100%;">
                        @foreach ($lostReasons as $row)
                            <tr>
                                <td class="bar-label-cell" style="width:auto; padding-bottom:3px;">{{ $row['reason'] }}</td>
                                <td class="hours-value" style="width:70px; padding-bottom:3px;">{{ $row['count'] }} {{ Str::plural('lead', $row['count']) }}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-bottom:12px;">
                                    <table class="bar-track-outer"><tr>
                                        <td class="bar-track" style="width: {{ max(3, round($row['count'] / $lostReasonsMax * 100)) }}%;"><div class="bar-fill" style="background: #B3261E; width:100%;">&nbsp;</div></td>
                                        <td class="bar-track" style="background:transparent;"></td>
                                    </tr></table>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    <div class="card-note">
                        AED {{ number_format($lostLeadsValue, 2) }} of estimated monthly value lost
                        @if ($lostReasonsTopDriver) · biggest driver: {{ $lostReasonsTopDriver['reason'] }} @endif
                    </div>
                @endif
            </td>
        </tr></table>

        <!-- Lead sources + Therapy hours delivered -->
        <table class="split-table"><tr>
            <td class="split-cell">
                <div class="card-title">Lead sources</div>
                <div class="card-sub">Last 90 days · {{ $capturedCount }} {{ Str::plural('lead', $capturedCount) }} captured</div>
                @if ($leadSources->isEmpty())
                    <div class="card-empty">No leads captured in the last 90 days.</div>
                @else
                    <div class="pie-wrap">
                        @if ($leadSourcesPieImage)
                            <img src="data:image/png;base64,{{ $leadSourcesPieImage }}" width="160" height="160" alt="Lead sources pie chart">
                        @endif
                    </div>
                    <table style="width:100%;">
                        @foreach ($leadSources as $s)
                            <tr class="legend-row">
                                <td style="width:16px;"><span class="legend-dot" style="background: {{ $s['color'] }};">&nbsp;</span></td>
                                <td>{{ $s['source'] }}</td>
                                <td class="legend-count" style="width:60px;">{{ $s['count'] }} {{ Str::plural('lead', $s['count']) }}</td>
                                <td class="legend-pct" style="width:40px;">{{ $s['pct'] }}%</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
            <td class="split-spacer"></td>
            <td class="split-cell">
                <div class="card-title">Therapy hours delivered · {{ $monthLabel }}</div>
                <div class="card-sub">{{ number_format($totalHours) }} clinical hours</div>
                @if ($therapyHoursByType->isEmpty())
                    <div class="card-empty">No completed sessions yet this month.</div>
                @else
                    <table style="width:100%;">
                        @foreach ($therapyHoursByType as $row)
                            <tr>
                                <td class="bar-label-cell" style="width:auto; padding-bottom:3px;">{{ $row['type'] }}</td>
                                <td class="hours-value" style="width:50px; padding-bottom:3px;">{{ rtrim(rtrim(number_format($row['hours'], 1), '0'), '.') }}h</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-bottom:12px;">
                                    <table class="bar-track-outer"><tr>
                                        <td class="bar-track" style="width: {{ max(3, round($row['hours'] / $therapyHoursMax * 100)) }}%;"><div class="bar-fill" style="background: {{ $row['color'] }}; width:100%;">&nbsp;</div></td>
                                        <td class="bar-track" style="background:transparent;"></td>
                                    </tr></table>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
        </tr></table>

    </div>

</body>
</html>
