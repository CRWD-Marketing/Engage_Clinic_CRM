<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Statement · {{ $profile['child'] }} · {{ $clinic['name'] }}</title>
    <style>
        {{--
            A separate template from billing.statement, for the same reason
            billing.print_pdf is separate from billing.print - dompdf (which
            builds the downloaded "Print / Save as PDF" file) only reliably
            supports CSS2.1 table/block layout, not the flex/grid the
            on-screen statement uses, and the browser's own print output adds
            page margins that break the full-bleed banner. Every flex/grid
            section is rebuilt as a table here, and `@page { margin: 0 }`
            keeps the design running edge to edge with no padding around it.
        --}}
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Nunito Sans', 'Helvetica', 'Arial', sans-serif; color: #2B3A4C; background: #fff; }
        table { border-collapse: collapse; }

        .banner-table { width: 100%; background: #16436E; padding: 26px 40px; }
        .banner-logo-box { background: #fff; border-radius: 12px; padding: 8px 14px; }
        .banner-logo-box img { height: 34px; display: block; }
        .banner-title { color: #fff; font: 600 28px 'Baloo 2', 'Georgia', serif; text-align: right; }
        .banner-sub { color: #F7C6D4; font: 700 13px 'Nunito Sans'; text-align: right; margin-top: 2px; }
        .accent { height: 4px; background: #C8355F; font-size: 0; line-height: 0; }

        .body { padding: 30px 40px 40px; }
        .top-table { width: 100%; margin-bottom: 20px; }
        .top-table > tr > td { vertical-align: top; padding-right: 20px; }
        .top-table > tr > td:last-child { padding-right: 0; }
        .label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
        .name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .detail { font: 600 12.5px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .meta-box { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; }
        .meta-table { width: 100%; }
        .meta-table td { padding: 4px 0; font: 700 12px 'Nunito Sans'; color: #2B3A4C; }
        .meta-table td:first-child { color: #98897A; }
        .meta-table td:last-child { text-align: right; }

        .stat-table { width: 100%; margin: 18px 0; }
        .stat-table > tr > td { width: 33.33%; padding-right: 14px; vertical-align: top; }
        .stat-table > tr > td:last-child { padding-right: 0; }
        .stat { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; }
        .stat .l { font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px; }
        .stat .v { font: 800 17px 'Baloo 2', 'Georgia', serif; color: #16436E; margin-top: 2px; }

        .ledger { width: 100%; margin-top: 8px; }
        .ledger thead th { text-align: left; font: 700 9.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px; padding: 0 8px 8px 0; border-bottom: 2px solid #16436E; }
        .ledger thead th.num { text-align: right; }
        .ledger tbody td { padding: 10px 8px 10px 0; border-bottom: 1px solid #F0EAE0; vertical-align: top; font: 600 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .ledger tbody td.num { text-align: right; font: 800 12.5px 'Nunito Sans'; white-space: nowrap; }
        .credit { color: #1E7A46; }
        .charge { color: #B3261E; }
        .nil { color: #C6BCAE; }
        .sub { font: 700 10.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .pill { border-radius: 7px; padding: 2px 8px; font: 800 10.5px 'Nunito Sans'; display: inline-block; }
        .empty { text-align: center; color: #98897A; font: 600 12.5px 'Nunito Sans'; padding: 16px 0; }

        .closing { width: 100%; background: #16436E; border-radius: 10px; margin-top: 18px; }
        .closing td { padding: 14px 18px; }
        .closing .l { color: #BFD2E3; font: 700 12.5px 'Nunito Sans'; }
        .closing .v { color: #fff; font: 700 22px 'Baloo 2', 'Georgia', serif; text-align: right; }
        .oldest { margin-top: 10px; font: 700 12px 'Nunito Sans'; color: #B3261E; }

        .section { margin-top: 26px; padding-top: 18px; border-top: 1px solid #F0EAE0; }
        .section-title { font: 600 18px 'Baloo 2', 'Georgia', serif; color: #16436E; }
        .section-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px; }

        .legal { text-align: center; font: 700 11px 'Nunito Sans'; color: #A79C8E; margin-top: 22px; padding-top: 14px; border-top: 1px solid #F0EAE0; }
    </style>
</head>
<body>

    <table class="banner-table"><tr>
        <td style="width:1%;"><span class="banner-logo-box"><img src="{{ public_path('uploads/engage.png') }}" alt="{{ $clinic['name'] }}"></span></td>
        <td>
            <div class="banner-title">Account statement</div>
            <div class="banner-sub">Not a tax invoice</div>
        </td>
    </tr></table>
    <div class="accent">&nbsp;</div>

    <div class="body">
        <table class="top-table"><tr>
            <td style="width:56%;">
                <div class="label">Account holder</div>
                <div class="name">{{ $profile['bill_to'] ?? 'Parent / guardian' }}</div>
                <div class="detail">
                    Patient: {{ $profile['child'] }}<br>
                    @if ($profile['phone']) {{ $profile['phone'] }}<br> @endif
                    Payer: {{ $profile['primary_payer'] }}
                </div>
            </td>
            <td style="width:44%;">
                <div class="meta-box">
                    <table class="meta-table">
                        <tr><td>As of</td><td>{{ $asOf->format('d M Y') }}</td></tr>
                        <tr><td>From</td><td>{{ $clinic['name'] }}</td></tr>
                        <tr><td>TRN</td><td>{{ $clinic['trn'] }}</td></tr>
                    </table>
                </div>
            </td>
        </tr></table>

        <table class="stat-table"><tr>
            <td><div class="stat"><div class="l">Charged</div><div class="v">AED {{ number_format($charged, 2) }}</div></div></td>
            <td><div class="stat"><div class="l">Credited</div><div class="v">AED {{ number_format($credited, 2) }}</div></div></td>
            <td><div class="stat"><div class="l">Entries</div><div class="v">{{ $rows->count() }}</div></div></td>
        </tr></table>

        <table class="ledger">
            <thead><tr><th>Date</th><th>Ref</th><th>Description</th><th class="num">Charge</th><th class="num">Credit</th><th class="num">Balance</th></tr></thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td>{{ $r['date_label'] }}</td>
                        <td>{{ $r['ref'] }}</td>
                        <td>{{ $r['desc'] }}</td>
                        {{-- A row is either a charge or a credit, never both; the em dash
                             marks the empty side and stays grey so it can't read as an amount. --}}
                        <td class="num {{ $r['charge'] > 0 ? 'charge' : 'nil' }}">{{ $r['charge'] > 0 ? number_format($r['charge'], 2) : '—' }}</td>
                        <td class="num {{ $r['credit'] > 0 ? 'credit' : 'nil' }}">{{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '—' }}</td>
                        <td class="num">{{ number_format($r['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">No billing history yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="closing"><tr>
            <td class="l">Balance owed</td>
            <td class="v">AED {{ number_format(max(0, $balance), 2) }}</td>
        </tr></table>
        @if ($oldest)
            <div class="oldest">Oldest open item: {{ $oldest->invoice_number }}, {{ max(0, $oldest->daysPastDue()) }} days past due.</div>
        @endif

        <div class="section">
            <div class="section-title">Sessions delivered</div>
            <div class="section-sub">Every session attended, cancelled or missed — and how each one was charged</div>

            <table class="stat-table"><tr>
                <td><div class="stat"><div class="l">Sessions</div><div class="v">{{ $sessions->count() }}</div></div></td>
                <td><div class="stat"><div class="l">Billable hours</div><div class="v">{{ $sessionHours }}</div></div></td>
                <td><div class="stat"><div class="l">Session value</div><div class="v">AED {{ number_format($sessionCharged, 2) }}</div></div></td>
            </tr></table>

            <table class="ledger">
                <thead><tr><th>Date</th><th>Session</th><th>Attendance</th><th class="num">Hours</th><th class="num">Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($sessions as $s)
                        @php
                            $pill = match ($s['attendance']) {
                                'completed' => ['#E3F1E9', '#2E7D5B'],
                                'no_show' => ['#F9E4E2', '#B3261E'],
                                'cancelled_late' => ['#F7EEDD', '#B97F24'],
                                default => ['#EFEAE2', '#6B6B6B'],
                            };
                        @endphp
                        <tr>
                            <td>
                                {{ $s['date_label'] }}
                                <div class="sub">{{ $s['start_time'] }}–{{ $s['end_time'] }}</div>
                            </td>
                            <td>
                                {{ $s['service_label'] }}
                                <div class="sub">{{ $s['therapist_name'] }}{{ $s['trainee_note'] ? ' · '.$s['trainee_note'] : '' }} · {{ $s['payer'] }}</div>
                            </td>
                            <td>
                                <span class="pill" style="background: {{ $pill[0] }}; color: {{ $pill[1] }};">{{ $s['attendance_label'] }}</span>
                                <div class="sub">{{ $s['charge_rule'] }}</div>
                            </td>
                            <td class="num">{{ $s['bill_hours'] }}</td>
                            <td class="num {{ $s['gross'] > 0 ? '' : 'nil' }}">{{ $s['gross'] > 0 ? number_format($s['gross'], 2) : '—' }}</td>
                            <td>
                                {{ $s['billing_status'] }}
                                @if ($s['invoice_number'])<div class="sub">{{ $s['invoice_number'] }}</div>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No sessions delivered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="legal">
            This is a statement of account, not a tax invoice. {{ $clinic['name'] }} · {{ $clinic['license'] }}
        </div>
    </div>

</body>
</html>
