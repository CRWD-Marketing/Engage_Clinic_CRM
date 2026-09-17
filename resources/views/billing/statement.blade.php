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
        .banner-title { color: #fff; font: 600 26px 'Baloo 2'; text-align: right; }
        .banner-sub { color: #F7C6D4; font: 700 12.5px 'Nunito Sans'; text-align: right; margin-top: 2px; }
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
        .closing { display: flex; justify-content: space-between; align-items: center; background: #16436E; border-radius: 10px; padding: 14px 18px; margin-top: 18px; }
        .closing .l { color: #BFD2E3; font: 700 12.5px 'Nunito Sans'; }
        .closing .v { color: #fff; font: 700 22px 'Baloo 2'; }
        .oldest { margin-top: 10px; font: 700 12px 'Nunito Sans'; color: #B3261E; }
        .legal { text-align: center; font: 700 11px 'Nunito Sans'; color: #A79C8E; margin-top: 22px; padding-top: 14px; border-top: 1px solid #F0EAE0; }
        @media print { .toolbar { display: none; } body { background: #fff; } .sheet { box-shadow: none; border-radius: 0; margin: 0; width: 100%; } }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('billing.index') }}" class="btn-back">← Back to billing</a>
        <button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button>
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
                            <td class="num charge">{{ $r['charge'] > 0 ? number_format($r['charge'], 2) : '—' }}</td>
                            <td class="num credit">{{ $r['credit'] > 0 ? number_format($r['credit'], 2) : '—' }}</td>
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

            <div class="legal">
                This is a statement of account, not a tax invoice. {{ $clinic['name'] }} · {{ $clinic['license'] }}
            </div>
        </div>
    </div>

</body>
</html>
