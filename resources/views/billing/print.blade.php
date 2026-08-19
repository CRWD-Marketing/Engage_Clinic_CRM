<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }} · Engage Clinic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }
        body { margin: 0; font-family: 'Nunito Sans', sans-serif; background: #EDE6D9; color: #2B3A4C; }
        .toolbar {
            position: sticky; top: 0; z-index: 10; display: flex; justify-content: center; gap: 10px;
            padding: 14px; background: #EDE6D9;
        }
        .toolbar button, .toolbar a {
            font: 800 13px 'Nunito Sans'; border-radius: 9px; padding: 10px 18px; cursor: pointer; text-decoration: none;
        }
        .btn-print { background: #C8355F; color: #fff; border: none; }
        .btn-print:hover { background: #A82348; }
        .btn-back { background: #fff; color: #16436E; border: 1px solid #E2DACE; }
        .btn-back:hover { background: #F6F3EE; }

        .sheet { width: 800px; max-width: 100%; margin: 0 auto 40px; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 40px rgba(22,42,60,0.18); }
        .banner { background: #16436E; padding: 26px 40px; display: flex; align-items: center; justify-content: space-between; }
        .banner-logo { background: #fff; border-radius: 12px; padding: 8px 14px; display: flex; align-items: center; }
        .banner-logo img { height: 34px; display: block; }
        .banner-title { color: #fff; font: 600 30px 'Baloo 2'; text-align: right; }
        .banner-sub { color: #F7C6D4; font: 700 13px 'Nunito Sans'; text-align: right; margin-top: 2px; }
        .accent { height: 4px; background: #C8355F; }

        .body { padding: 32px 40px 40px; }
        .top-grid { display: grid; grid-template-columns: 1.2fr 1.2fr 1fr; gap: 20px; margin-bottom: 26px; }
        .label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
        .from-name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .from-detail { font: 600 12.5px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .bill-name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .bill-detail { font: 600 12.5px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .meta-box { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }
        .meta-row { display: flex; justify-content: space-between; gap: 10px; font: 700 12px 'Nunito Sans'; color: #2B3A4C; }
        .meta-row span:first-child { color: #98897A; font-weight: 700; }
        .status-pill { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; display: inline-block; }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            text-align: left; font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px;
            padding: 0 0 8px; border-bottom: 2px solid #16436E;
        }
        thead th.num { text-align: right; }
        tbody td { padding: 13px 0; border-bottom: 1px solid #F0EAE0; vertical-align: top; font: 600 13px 'Nunito Sans'; color: #2B3A4C; }
        tbody td.num { text-align: right; font: 800 13px 'Nunito Sans'; color: #16436E; white-space: nowrap; }
        .svc-name { font: 800 13px 'Nunito Sans'; color: #2B3A4C; }
        .svc-meta { font: 700 11px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .therapist-name { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .therapist-role { font: 700 11px 'Nunito Sans'; color: #98897A; }

        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 18px; }
        .totals { width: 320px; display: flex; flex-direction: column; gap: 9px; }
        .totals-row { display: flex; justify-content: space-between; font: 700 13px 'Nunito Sans'; color: #5A6B7E; }
        .totals-row .val { color: #2B3A4C; font-weight: 800; }
        .totals-row .val.discount { color: #2E7D5B; }
        .patient-resp { background: #16436E; border-radius: 10px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }
        .patient-resp .lbl { color: #BFD2E3; font: 700 12.5px 'Nunito Sans'; }
        .patient-resp .amt { color: #fff; font: 700 20px 'Baloo 2'; }

        .footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 28px; }
        .footer-box { border-radius: 10px; padding: 14px 16px; font: 600 12.5px/1.6 'Nunito Sans'; }
        .footer-box.payment { background: #F6F3EE; color: #5A6B7E; }
        .footer-box.notes { background: #FCEEF1; color: #7A2E43; }
        .footer-box .title { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 6px; }
        .footer-box.notes .title { color: #7A2E43; }
        .footer-box a { color: inherit; font-weight: 800; }

        .legal { text-align: center; font: 700 11px 'Nunito Sans'; color: #A79C8E; margin-top: 26px; padding-top: 16px; border-top: 1px solid #F0EAE0; }

        @media print {
            .toolbar { display: none; }
            body { background: #fff; }
            .sheet { box-shadow: none; border-radius: 0; margin: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('billing.index') }}" class="btn-back">← Back to billing</a>
        <button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="sheet">
        <div class="banner">
            <div class="banner-logo"><img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic"></div>
            <div>
                <div class="banner-title">Invoice</div>
                <div class="banner-sub">{{ $invoice->invoice_number }}</div>
            </div>
        </div>
        <div class="accent"></div>

        <div class="body">
            <div class="top-grid">
                <div>
                    <div class="label">From</div>
                    <div class="from-name">Engage Child Development Clinic</div>
                    <div class="from-detail">
                        Khalifa City, Abu Dhabi, UAE<br>
                        +971 2 555 0123 · billing@engageclinic.ae<br>
                        TRN 100482619300003
                    </div>
                </div>
                <div>
                    <div class="label">Bill to</div>
                    <div class="bill-name">{{ $invoice->bill_to ?: 'Parent / guardian' }}</div>
                    <div class="bill-detail">
                        Patient: {{ $invoice->patient?->lead?->child_name ?? '—' }} (file EC-{{ str_pad($invoice->patient_id, 4, '0', STR_PAD_LEFT) }})<br>
                        @if ($invoice->patient?->lead?->phone) {{ $invoice->patient->lead->phone }}<br> @endif
                        Insurance: {{ $invoice->payer }}{{ $invoice->claim_reference ? ' · Claim #' . $invoice->claim_reference : '' }}
                    </div>
                </div>
                <div class="meta-box">
                    <div class="meta-row"><span>Issue date</span><span>{{ $invoice->issue_date->format('d M Y') }}</span></div>
                    <div class="meta-row"><span>Due date</span><span>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</span></div>
                    <div class="meta-row"><span>Period</span><span>{{ $invoice->period->format('F Y') }}</span></div>
                    <div class="meta-row">
                        <span>Status</span>
                        @php
                            $colors = ['draft' => ['#F1EDE5', '#8A7D6C'], 'submitted' => ['#E7EFF7', '#24619C'], 'pending_info' => ['#F7EEDD', '#B97F24'], 'paid' => ['#E3F1E9', '#2E7D5B'], 'rejected' => ['#F9E4E2', '#B3261E']][$invoice->status] ?? ['#E7EFF7', '#24619C'];
                        @endphp
                        <span class="status-pill" style="background: {{ $colors[0] }}; color: {{ $colors[1] }};">{{ $invoice->statusLabel() }}</span>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Therapist</th>
                        <th class="num">Sessions</th>
                        <th class="num">Rate (AED)</th>
                        <th class="num">Amount (AED)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->lineItems as $item)
                        <tr>
                            <td>
                                <div class="svc-name">{{ $item->description }}</div>
                                @if ($item->cpt_code)
                                    <div class="svc-meta">CPT {{ $item->cpt_code }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="therapist-name">{{ $item->therapist ? $item->therapist->first_name . ' ' . $item->therapist->last_name : '—' }}</div>
                                @if ($item->therapist)
                                    <div class="therapist-role">Therapist</div>
                                @endif
                            </td>
                            <td class="num">{{ $item->sessions }}</td>
                            <td class="num">{{ number_format($item->rate, 2) }}</td>
                            <td class="num">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals-wrap">
                <div class="totals">
                    <div class="totals-row"><div>Subtotal</div><div class="val">AED {{ number_format($invoice->subtotal, 2) }}</div></div>
                    <div class="totals-row">
                        <div>Insurance coverage — {{ $invoice->payer }} ({{ $invoice->coverage_percent }}%)</div>
                        <div class="val discount">– AED {{ number_format($invoice->insurance_coverage_amount, 2) }}</div>
                    </div>
                    <div class="totals-row"><div>VAT</div><div class="val">Exempt — healthcare</div></div>
                    <div class="patient-resp">
                        <div class="lbl">Patient responsibility</div>
                        <div class="amt">AED {{ number_format($invoice->patient_responsibility, 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="footer-grid">
                <div class="footer-box payment">
                    <div class="title">Payment details</div>
                    Bank transfer: Engage Clinic LLC · FAB<br>
                    IBAN AE07 0350 0000 0012 3456 789<br>
                    Card &amp; Apple Pay at reception, or pay online at <a href="#">engageclinic.ae/pay</a> with reference <strong>{{ $invoice->invoice_number }}</strong>
                </div>
                <div class="footer-box notes">
                    <div class="title">Notes</div>
                    @if ($invoice->claim_reference)
                        Claim {{ $invoice->claim_reference }} for the insured portion was submitted to {{ $invoice->payer }} on {{ $invoice->issue_date->format('d M Y') }}.
                    @endif
                    Payment is due{{ $invoice->due_date ? ' by ' . $invoice->due_date->format('d M Y') : '' }}. For any billing questions, WhatsApp us at +971 2 555 0123.
                </div>
            </div>

            <div class="legal">
                Engage Child Development Clinic · Licensed by the Department of Health — Abu Dhabi · MOH License CN-2894716
            </div>
        </div>
    </div>

</body>
</html>
