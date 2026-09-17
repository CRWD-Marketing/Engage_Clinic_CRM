@extends('layouts.admin-sidebar')

@section('title', 'Reports · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    @php
        // Conic-gradient stops for the lead-sources pie, built from real
        // cumulative percentages rather than hand-picked degree values.
        $pieStops = [];
        $cursor = 0;
        foreach ($leadSources as $s) {
            $start = $cursor;
            $cursor = min(100, $cursor + $s['pct']);
            $pieStops[] = "{$s['color']} {$start}%, {$s['color']} {$cursor}%";
        }
        $pieGradient = $pieStops ? implode(', ', $pieStops) : '#F3EDE3 0%, #F3EDE3 100%';
    @endphp

    <!-- Reports & Analytics -->
    <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">

        <!-- Top Bar -->
        <div style="display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA;">
            <div style="flex: 1;">
                <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Reports &amp; analytics</div>
                <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">{{ $revenueByMonth->first()['label'] ?? '' }} – {{ now()->format('F Y') }} · updated {{ now()->format('d M, H:i') }}</div>
            </div>
            <a href="{{ route('reports.export-pdf') }}" style="background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px; padding: 10px 16px; font: 800 13px 'Nunito Sans'; cursor: pointer; text-decoration: none; display: inline-block;">Export PDF</a>
        </div>

        <!-- Main Content - Grid -->
        <div style="flex: 1; overflow-y: auto; padding: 22px 28px; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px; align-content: start;">

            <!-- VAT Return Summary -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">VAT return summary</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">Current filing period · {{ $vatFilingLabel }} · TRN {{ config('clinic.trn') }}</div>
                <div style="display: flex; flex-direction: column; gap: 10px; font: 700 13px 'Nunito Sans'; color: #2B3A4C;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Standard-rated supplies (net)</span>
                        <span>AED {{ number_format($vatStandardRatedSupplies, 2) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Output tax at {{ rtrim(rtrim(number_format($vatRate, 1), '0'), '.') }}%</span>
                        <span>AED {{ number_format($vatOutputTax, 2) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid #F3EDE3; padding-top: 10px;">
                        <span>Credit notes issued</span>
                        <span>– AED {{ number_format($vatCreditNotesIssued, 2) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid #F3EDE3; padding-top: 10px; font: 800 14px 'Nunito Sans'; color: #16436E;">
                        <span>Net VAT payable</span>
                        <span>AED {{ number_format($vatNetPayable, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Collection Rate -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Collection rate</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 4px;">AED {{ number_format($collectionCollectedTotal, 2) }} collected of AED {{ number_format($collectionInvoicedTotal, 2) }} invoiced</div>
                <div style="font: 600 34px 'Baloo 2'; color: #2E7D5B; margin: 6px 0 10px;">{{ $collectionRatePct }}%</div>
                <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden; margin-bottom: 16px;">
                    <div style="width: {{ max(0, min(100, $collectionRatePct)) }}%; height: 100%; background: #2E7D5B; border-radius: 5px;"></div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #98897A;">Billable session hours</span>
                        <span>{{ rtrim(rtrim(number_format($collectionBillableHours, 1), '0'), '.') }} h</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #98897A;">Effective revenue per billable hour</span>
                        <span>{{ $collectionRevenuePerHour !== null ? 'AED '.number_format($collectionRevenuePerHour, 2) : '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Revenue by Service -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Revenue by service</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">Billable session value from invoiced line items</div>
                @if ($revenueByService->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No invoiced sessions in this period yet.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach ($revenueByService as $row)
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font: 700 13px 'Nunito Sans'; color: #2B3A4C;">
                                    <span>{{ $row['label'] }}</span>
                                    <span style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">AED {{ number_format($row['amount'], 2) }}</span>
                                </div>
                                <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: {{ max(3, round($row['amount'] / $revenueByServiceMax * 100)) }}%; height: 100%; background: {{ $row['color'] }}; border-radius: 5px;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Revenue by Setting & Therapist -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Revenue by setting &amp; therapist</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">Where the work happens and who delivered it</div>
                @if ($revenueBySetting->isEmpty() && $revenueByTherapist->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No invoiced sessions in this period yet.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach ($revenueBySetting as $row)
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font: 700 13px 'Nunito Sans'; color: #2B3A4C;">
                                    <span>{{ $row['label'] }}</span>
                                    <span style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">AED {{ number_format($row['amount'], 2) }}</span>
                                </div>
                                <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: {{ max(3, round($row['amount'] / $revenueSettingTherapistMax * 100)) }}%; height: 100%; background: #16436E; border-radius: 5px;"></div>
                                </div>
                            </div>
                        @endforeach
                        @foreach ($revenueByTherapist as $row)
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font: 700 13px 'Nunito Sans'; color: #2B3A4C;">
                                    <span>{{ $row['label'] }}</span>
                                    <span style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">AED {{ number_format($row['amount'], 2) }}</span>
                                </div>
                                <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: {{ max(3, round($row['amount'] / $revenueSettingTherapistMax * 100)) }}%; height: 100%; background: #B97F24; border-radius: 5px;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Revenue by Month Chart (left) -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Revenue by month</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">AED thousands (invoiced, VAT excl.)</div>
                <div style="display: flex; gap: 14px; align-items: flex-end; height: 150px;">
                    @foreach ($revenueByMonth as $m)
                        @php $isCurrent = str_ends_with($m['label'], '*'); @endphp
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; height: 100%; justify-content: flex-end;">
                            <div style="font: 800 11px 'Nunito Sans'; color: #98897A;">{{ number_format($m['thousands'], $m['thousands'] < 10 ? 1 : 0) }}</div>
                            <div style="width: 100%; height: {{ max(3, round($m['thousands'] / $revenueMaxThousands * 120)) }}px; background: {{ $isCurrent ? '#C8355F' : '#EBD3DB' }}; border-radius: 7px 7px 3px 3px;"></div>
                            <div style="font: 700 11px 'Nunito Sans'; color: #8A7D6C;">{{ $m['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                @if ($revenueMaxThousands <= 1 && $revenueByMonth->sum('amount') == 0)
                    <div style="font: 700 11.5px 'Nunito Sans'; color: #98897A; margin-top: 10px;">No invoices raised in this period yet.</div>
                @endif
            </div>

            <!-- Revenue Summary (right) -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Revenue summary</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">{{ $revenueByMonth->first()['label'] ?? '' }} – {{ now()->format('F Y') }}</div>
                @if ($revenueTotal6mo == 0)
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No invoices raised in this period yet.</div>
                @else
                    <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 4px;">
                        <div style="font: 600 30px 'Baloo 2'; color: #16436E;">AED {{ number_format($revenueTotal6mo, 0) }}</div>
                        @if ($revenueMomDelta !== null)
                            <span style="font: 800 12px 'Nunito Sans'; color: {{ $revenueMomDelta >= 0 ? '#2E7D5B' : '#B3261E' }}; background: {{ $revenueMomDelta >= 0 ? '#E3F1E9' : '#F9E4E2' }}; border-radius: 6px; padding: 2px 7px;">{{ $revenueMomDelta >= 0 ? '↑' : '↓' }} {{ abs($revenueMomDelta) }}%</span>
                        @endif
                    </div>
                    <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">total invoiced, VAT excl.{{ $revenueMomDelta !== null ? ' · vs last month' : '' }}</div>
                    <div style="display: flex; flex-direction: column; gap: 10px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">
                        <div style="display: flex; justify-content: space-between; border-top: 1px solid #F3EDE3; padding-top: 10px;">
                            <span style="color: #98897A;">Monthly average</span>
                            <span>AED {{ number_format($revenueAverage6mo, 0) }}</span>
                        </div>
                        @if ($revenueBestMonth)
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #98897A;">Best month</span>
                                <span>{{ rtrim($revenueBestMonth['label'], '*') }} · AED {{ number_format($revenueBestMonth['amount'], 0) }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Lead Conversion Funnel -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Lead conversion funnel</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">
                    Last 90 days
                    @if ($conversionRate !== null) · {{ $conversionRate }}% lead → enrolled @endif
                </div>
                @if ($capturedCount === 0)
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No leads captured in the last 90 days.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach ($funnelStages as $i => $stage)
                            @php $stageColors = ['#16436E', '#3A6A96', '#7396B8', '#C8355F']; @endphp
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 130px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">{{ $stage['label'] }}</div>
                                <div style="flex: 1;">
                                    <div style="width: {{ max(6, $stage['pct']) }}%; height: 26px; background: {{ $stageColors[$i] }}; border-radius: 7px; display: flex; align-items: center; justify-content: flex-end; padding: 0 10px; font: 800 12px 'Nunito Sans'; color: #FFFFFF;">{{ $stage['count'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($bestSource || $medianEnrollDays !== null)
                        <div style="font: 600 12px/1.5 'Nunito Sans'; color: #98897A; margin-top: 14px;">
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
            </div>

            <!-- Why Leads Are Lost -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Why leads are lost</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">{{ $lostLeadsCount }} terminated {{ Str::plural('lead', $lostLeadsCount) }} · reasons captured at termination</div>
                @if ($lostReasons->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No terminated leads in this period.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach ($lostReasons as $row)
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 5px;">{{ $row['reason'] }}</div>
                                    <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                        <div style="width: {{ max(3, round($row['count'] / $lostReasonsMax * 100)) }}%; height: 100%; background: #B3261E; border-radius: 5px;"></div>
                                    </div>
                                </div>
                                <div style="font: 800 12px 'Nunito Sans'; color: #98897A; width: 50px; text-align: right; flex-shrink: 0;">{{ $row['count'] }} {{ Str::plural('lead', $row['count']) }}</div>
                            </div>
                        @endforeach
                    </div>
                    <div style="font: 600 12px/1.5 'Nunito Sans'; color: #98897A; margin-top: 14px;">
                        AED {{ number_format($lostLeadsValue, 2) }} of estimated monthly value lost
                        @if ($lostReasonsTopDriver) · biggest driver: {{ $lostReasonsTopDriver['reason'] }} @endif
                    </div>
                @endif
            </div>

            <!-- Lead Sources Pie Chart -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; display: flex; gap: 22px; align-items: center;">
                <div style="width: 140px; height: 140px; border-radius: 50%; background: conic-gradient({{ $pieGradient }}); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <div style="width: 84px; height: 84px; border-radius: 50%; background: #FFFFFF; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div style="font: 600 20px 'Baloo 2'; color: #16436E;">{{ $capturedCount }}</div>
                        <div style="font: 700 10px 'Nunito Sans'; color: #98897A;">LEADS · 90d</div>
                    </div>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Lead sources</div>
                    @if ($leadSources->isEmpty())
                        <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No leads captured in the last 90 days.</div>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 7px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">
                            @foreach ($leadSources as $s)
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="width: 10px; height: 10px; border-radius: 3px; background: {{ $s['color'] }}; flex-shrink: 0;"></span>
                                    <span style="flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $s['source'] }}</span>
                                    <span style="color: #16436E;">{{ $s['pct'] }}%</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Therapy Hours Delivered -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Therapy hours delivered · {{ $monthLabel }}</div>
                <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-bottom: 16px;">{{ number_format($totalHours) }} clinical hours</div>
                @if ($therapyHoursByType->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No completed sessions yet this month.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach ($therapyHoursByType as $row)
                            <div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">{{ $row['type'] }}</div>
                                    <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">{{ rtrim(rtrim(number_format($row['hours'], 1), '0'), '.') }}h</div>
                                </div>
                                <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: {{ max(3, round($row['hours'] / $therapyHoursMax * 100)) }}%; height: 100%; background: {{ $row['color'] }}; border-radius: 5px;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
