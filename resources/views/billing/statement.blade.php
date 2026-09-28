<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Statement · {{ $profile['child'] }} · {{ $clinic['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; font-family: 'Nunito Sans', sans-serif; background: #EDE6D9; color: #2B3A4C; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: center; gap: 10px; padding: 14px; background: #EDE6D9; }
        .toolbar button, .toolbar a { font: 800 13px 'Nunito Sans'; border-radius: 9px; padding: 10px 18px; cursor: pointer; text-decoration: none; border: none; }
        .btn-print { background: #C8355F; color: #fff; }
        .btn-back { background: #fff; color: #16436E; border: 1px solid #E2DACE !important; }
        .sheet { width: 780px; max-width: 100%; margin: 0 auto 40px; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 40px rgba(22,42,60,0.18); }
        .banner { background: #16436E; padding: 24px 40px; display: flex; align-items: center; justify-content: space-between; }
        .banner-title { color: #fff; font: 600 26px 'Baloo 2'; text-align: left; }
        .banner-sub { color: #F7C6D4; font: 700 12.5px 'Nunito Sans'; text-align: left; margin-top: 2px; }
        .accent { height: 4px; background: #C8355F; }
        .body { padding: 30px 40px 40px; }
        .top-grid { display: grid; grid-template-columns: 1.3fr 1fr; gap: 20px; margin-bottom: 20px; }
        .label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 6px; }
        .name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .detail { font: 600 12.5px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .meta-box { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; }
        .meta-row { display: flex; justify-content: space-between; font: 700 12px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 6px; }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-row span:first-child { color: #98897A; }
        .stat-row { display: flex; gap: 14px; margin: 20px 0; }
        .stat { flex: 1; background: #F6F3EE; border-radius: 10px; padding: 12px 14px; }
        .stat .l { font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: uppercase; }
        .stat .v { font: 800 17px 'Baloo 2'; color: #16436E; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        thead th { text-align: left; font: 700 9.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; padding: 0 8px 8px 0; border-bottom: 2px solid #16436E; }
        thead th.num { text-align: right; }
        tbody td { padding: 10px 8px 10px 0; border-bottom: 1px solid #F0EAE0; font: 600 12.5px 'Nunito Sans'; color: #2B3A4C; }
        tbody td.num { text-align: right; font: 800 12.5px 'Nunito Sans'; }
        .credit { color: #1E7A46; }
        .charge { color: #B3261E; }
        .nil { color: #C6BCAE; font-weight: 700 !important; }
        .closing { display: flex; justify-content: space-between; align-items: center; background: #16436E; border-radius: 10px; padding: 14px 18px; margin-top: 18px; }
        .closing .l { color: #BFD2E3; font: 700 12.5px 'Nunito Sans'; }
        .closing .v { color: #fff; font: 700 22px 'Baloo 2'; }
        .oldest { margin-top: 10px; font: 700 12px 'Nunito Sans'; color: #B3261E; }
        .section { margin-top: 26px; padding-top: 18px; border-top: 1px solid #F0EAE0; }
        .section-title { font: 600 18px 'Baloo 2'; color: #16436E; }
        .section-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .sub { font: 700 10.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .pill { display: inline-block; border-radius: 7px; padding: 2px 8px; font: 800 10.5px 'Nunito Sans'; }
        .empty { text-align: center; color: #98897A; font: 600 12.5px 'Nunito Sans'; padding: 16px 0; }
        @page { margin: 0; }
        @media print { .toolbar { display: none; } body { background: #fff; } .sheet { box-shadow: none; border-radius: 0; margin: 0; width: 100%; max-width: none; } table { page-break-inside: auto; } tr { page-break-inside: avoid; } }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('billing.index') }}" class="btn-back">← Back to billing</a>
        <a href="{{ route('billing.statements.pdf', $patient) }}" class="btn-print">Print / Save as PDF</a>
    </div>

    <div class="sheet">
        <div class="banner">
            <div>
                <div class="banner-title">Account statement</div>
                <div class="banner-sub">Not a tax invoice</div>
            </div>
        </div>
        <div class="accent"></div>
        <div class="body">
            <div class="top-grid">
                <div>
                    <div class="label">Account holder</div>
                    <div class="name">{{ $profile['bill_to'] ?? 'Parent / guardian' }}</div>
                    <div class="detail">
                        Patient: {{ $profile['child'] }}<br>
                        @if ($profile['phone']) {{ $profile['phone'] }}<br> @endif
                        Payer: {{ $profile['primary_payer'] }}
                    </div>
                </div>
                <div class="meta-box">
                    <div class="meta-row"><span>As of</span><span>{{ $asOf->format('d M Y') }}</span></div>
                    <div class="meta-row"><span>From</span><span>{{ $clinic['name'] }}</span></div>
                    <div class="meta-row"><span>TRN</span><span>{{ $clinic['trn'] }}</span></div>
                </div>
            </div>

            <div class="stat-row">
                <div class="stat"><div class="l">Charged</div><div class="v">AED {{ number_format($charged, 2) }}</div></div>
                <div class="stat"><div class="l">Credited</div><div class="v">AED {{ number_format($credited, 2) }}</div></div>
                <div class="stat"><div class="l">Entries</div><div class="v">{{ $rows->count() }}</div></div>
            </div>

            <table>
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
                        <tr><td colspan="6" style="text-align:center; color:#98897A;">No billing history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="closing">
                <div class="l">Balance owed</div>
                <div class="v">AED {{ number_format(max(0, $balance), 2) }}</div>
            </div>
            @if ($oldest)
                <div class="oldest">Oldest open item: {{ $oldest->invoice_number }}, {{ max(0, $oldest->daysPastDue()) }} days past due.</div>
            @endif

            {{--
                The sessions behind the charges above. Same delivered-session
                ledger the invoice picker prices from, so a family reading the
                statement can tie every invoice line back to an actual visit.
            --}}
            <div class="section">
                <div class="section-title">Sessions delivered</div>
                <div class="section-sub">Every session attended, cancelled or missed — and how each one was charged</div>

                <div class="stat-row">
                    <div class="stat"><div class="l">Sessions</div><div class="v">{{ $sessions->count() }}</div></div>
                    <div class="stat"><div class="l">Billable hours</div><div class="v">{{ $sessionHours }}</div></div>
                    <div class="stat"><div class="l">Session value</div><div class="v">AED {{ number_format($sessionCharged, 2) }}</div></div>
                </div>

                <table>
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
    </div>

</body>
</html>
