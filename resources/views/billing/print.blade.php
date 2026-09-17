<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }} · {{ $clinic['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }
        body { margin: 0; font-family: 'Nunito Sans', sans-serif; background: #EDE6D9; color: #2B3A4C; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: center; gap: 10px; padding: 14px; background: #EDE6D9; }
        .toolbar button, .toolbar a { font: 800 13px 'Nunito Sans'; border-radius: 9px; padding: 10px 18px; cursor: pointer; text-decoration: none; border: none; }
        .btn-print { background: #C8355F; color: #fff; }
        .btn-print:hover { background: #A82348; }
        .btn-back { background: #fff; color: #16436E; border: 1px solid #E2DACE !important; }
        .btn-back:hover { background: #F6F3EE; }

        .sheet { width: 820px; max-width: 100%; margin: 0 auto 40px; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 40px rgba(22,42,60,0.18); }
        .banner { background: #16436E; padding: 26px 40px; display: flex; align-items: center; justify-content: space-between; }
        .banner-logo { background: #fff; border-radius: 12px; padding: 8px 14px; display: flex; align-items: center; }
        .banner-logo img { height: 34px; display: block; }
        .banner-title { color: #fff; font: 600 28px 'Baloo 2'; text-align: right; }
        .banner-sub { color: #F7C6D4; font: 700 13px 'Nunito Sans'; text-align: right; margin-top: 2px; }
        .accent { height: 4px; background: #C8355F; }
        .voided-band { background: #B3261E; color: #fff; text-align: center; font: 800 12.5px 'Nunito Sans'; padding: 8px; letter-spacing: .04em; text-transform: uppercase; }

        .body { padding: 30px 40px 40px; }
        .notice { border-radius: 10px; padding: 10px 14px; font: 700 12.5px 'Nunito Sans'; margin-bottom: 20px; }
        .notice.live { background: #E4F6EB; color: #1E7A46; }

        .top-grid { display: grid; grid-template-columns: 1.2fr 1.2fr 1fr; gap: 20px; margin-bottom: 24px; }
        .label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
        .from-name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .from-detail, .bill-detail { font: 600 12.5px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .bill-name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .meta-box { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; }
        .meta-row { display: flex; justify-content: space-between; gap: 10px; font: 700 12px 'Nunito Sans'; color: #2B3A4C; }
        .meta-row span:first-child { color: #98897A; }
        .status-pill { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; display: inline-block; }
        .replace-note { font: 700 11.5px 'Nunito Sans'; margin-top: 8px; }
        .replace-note.warn { color: #B3261E; }
        .replace-note.ok { color: #24619C; }

        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; font: 700 9.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px; padding: 0 6px 8px 0; border-bottom: 2px solid #16436E; }
        thead th.num { text-align: right; }
        tbody td { padding: 11px 6px 11px 0; border-bottom: 1px solid #F0EAE0; vertical-align: top; font: 600 12.5px 'Nunito Sans'; color: #2B3A4C; }
        tbody td.num { text-align: right; font: 800 12.5px 'Nunito Sans'; color: #16436E; white-space: nowrap; }
        .line-n { color: #98897A; font-weight: 700; }
        .svc-name { font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .svc-meta { font: 700 10.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .svc-payer { font: 700 10.5px 'Nunito Sans'; color: #8A7D6C; margin-top: 1px; }

        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 18px; }
        .totals { width: 340px; display: flex; flex-direction: column; gap: 9px; }
        .totals-row { display: flex; justify-content: space-between; font: 700 13px 'Nunito Sans'; color: #5A6B7E; }
        .totals-row .val { color: #2B3A4C; font-weight: 800; }
        .totals-row .val.discount { color: #2E7D5B; }
        .totals-row.divider { border-top: 1px solid #EBE4DA; padding-top: 9px; }
        .patient-resp { background: #16436E; border-radius: 10px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; margin-top: 4px; }
        .patient-resp.settled { background: #1E7A46; }
        .patient-resp .lbl { color: #BFD2E3; font: 700 12.5px 'Nunito Sans'; }
        .patient-resp .amt { color: #fff; font: 700 20px 'Baloo 2'; }
        .patient-resp .amt small { font: 700 12px 'Nunito Sans'; margin-left: 6px; }

        .split-notes, .receipts { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; margin-top: 14px; font: 600 12px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .split-notes .title, .receipts .title { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 4px; }
        .receipt-row { display: flex; justify-content: space-between; }

        .footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 22px; }
        .footer-box { border-radius: 10px; padding: 14px 16px; font: 600 12.5px/1.6 'Nunito Sans'; }
        .footer-box.payment { background: #F6F3EE; color: #5A6B7E; }
        .footer-box.notes { background: #FCEEF1; color: #7A2E43; }
        .footer-box .title { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 6px; }
        .footer-box.notes .title { color: #7A2E43; }
        .footer-box a { color: inherit; font-weight: 800; }

        .terms { margin-top: 22px; padding-top: 16px; border-top: 1px solid #F0EAE0; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font: 600 10.5px/1.6 'Nunito Sans'; color: #8A7D6C; }
        .terms .t-title { font: 800 10.5px 'Nunito Sans'; color: #5A6B7E; margin-bottom: 3px; margin-top: 8px; }
        .terms .t-title:first-child { margin-top: 0; }
        .legal { text-align: center; font: 700 11px 'Nunito Sans'; color: #A79C8E; margin-top: 20px; }

        @media print {
            .toolbar { display: none; }
            body { background: #fff; }
            .sheet { box-shadow: none; border-radius: 0; margin: 0; width: 100%; }
        }
    </style>
</head>
<body>

    @unless(($embedded ?? false))
    <div class="toolbar">
        <a href="{{ route('billing.index') }}" class="btn-back">← Back to billing</a>
        <button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button>
    </div>
    @endunless

    @php
        $lead = $invoice->patient?->lead;
        $balance = max(0, $invoice->balance());
        $isVoided = $invoice->isVoided();
    @endphp

    <div class="sheet">
        <div class="banner">
            <div class="banner-logo"><img src="{{ asset('uploads/engage.png') }}" alt="{{ $clinic['name'] }}"></div>
            <div>
                <div class="banner-title">Tax invoice</div>
                <div class="banner-sub">{{ $invoice->invoice_number }}</div>
            </div>
        </div>
        <div class="accent"></div>
        @if ($isVoided)
            <div class="voided-band">Voided — {{ $invoice->void_reason }} · {{ $invoice->voided_at->format('d M Y') }}</div>
        @endif

        <div class="body">
            @if (! $isVoided && $balance <= 0.01)
                <div class="notice live">Settled in full — nothing further owed on this invoice.</div>
            @endif

            <div class="top-grid">
                <div>
                    <div class="label">From</div>
                    <div class="from-name">{{ $clinic['name'] }}</div>
                    <div class="from-detail">
                        {{ $clinic['address'] }}<br>
                        {{ $clinic['phone'] }} · {{ $clinic['billing_email'] }}<br>
                        TRN {{ $clinic['trn'] }}
                    </div>
                </div>
                <div>
                    <div class="label">Bill to</div>
                    <div class="bill-name">{{ $invoice->bill_to ?: ($lead?->parent_guardian_name ?? 'Parent / guardian') }}</div>
                    <div class="bill-detail">
                        Patient: {{ $lead?->child_name ?? '—' }} (file EC-{{ str_pad((string) $invoice->patient_id, 4, '0', STR_PAD_LEFT) }})<br>
                        @if ($lead?->phone) {{ $lead->phone }}<br> @endif
                        Payer: {{ $invoice->payer ?: 'Self-pay' }}{{ $invoice->claim_reference ? ' · Claim ' . $invoice->claim_reference : '' }}
                    </div>
                    @if ($invoice->replaces)
                        <div class="replace-note ok">Replaces voided invoice {{ $invoice->replaces->invoice_number }}</div>
                    @endif
                    @if ($invoice->replacedBy)
                        <div class="replace-note warn">Voided — reissued as {{ $invoice->replacedBy->invoice_number }}</div>
                    @endif
                </div>
                <div class="meta-box">
                    <div class="meta-row"><span>Issue date</span><span>{{ $invoice->issue_date->format('d M Y') }}</span></div>
                    <div class="meta-row"><span>Due date</span><span>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</span></div>
                    <div class="meta-row"><span>Period</span><span>{{ $invoice->periodLabel() }}</span></div>
                    <div class="meta-row">
                        <span>Status</span>
                        @php
                            $colors = ['voided' => ['#EEEEEE', '#6B6B6B'], 'paid' => ['#E3F1E9', '#2E7D5B'], 'partly_paid' => ['#F7EEDD', '#B97F24'], 'outstanding' => ['#F9E4E2', '#B3261E']][$invoice->billingStatus()];
                        @endphp
                        <span class="status-pill" style="background: {{ $colors[0] }}; color: {{ $colors[1] }};">{{ $invoice->billingStatusLabel() }}</span>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Item &amp; description</th><th>Session from</th><th>Session to</th>
                        <th class="num">Qty</th><th class="num">Rate</th><th class="num">Amount</th><th class="num">Tax</th><th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->lineItems as $item)
                        <tr>
                            <td class="line-n">{{ $item->line_no }}</td>
                            <td>
                                <div class="svc-name">{{ $item->description }}</div>
                                @if ($item->note)<div class="svc-meta">{{ $item->note }}</div>@endif
                                <div class="svc-payer">Billed to {{ $item->payer ?: 'Self-pay' }}</div>
                            </td>
                            <td>{{ $item->session_from?->format('d M Y · g:i A') }}</td>
                            <td>{{ $item->session_to?->format('d M Y · g:i A') }}</td>
                            <td class="num">{{ number_format($item->qty, 2) }}</td>
                            <td class="num">{{ number_format($item->rate, 2) }}</td>
                            <td class="num">{{ number_format($item->amount, 2) }}</td>
                            <td class="num">{{ number_format($item->vat_amount, 2) }}</td>
                            <td class="num">{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals-wrap">
                <div class="totals">
                    <div class="totals-row"><div>Subtotal — VAT excluded</div><div class="val">AED {{ number_format($invoice->subtotal, 2) }}</div></div>
                    <div class="totals-row"><div>VAT 5%</div><div class="val">AED {{ number_format($invoice->vat_amount, 2) }}</div></div>
                    <div class="totals-row divider"><div>Invoice total</div><div class="val">AED {{ number_format($invoice->total, 2) }}</div></div>
                    @if ((float) $invoice->insurance_coverage_amount > 0)
                        <div class="totals-row"><div>Insurance coverage</div><div class="val discount">– AED {{ number_format($invoice->insurance_coverage_amount, 2) }}</div></div>
                    @endif
                    @if ((float) $invoice->credit_amount > 0)
                        <div class="totals-row"><div>Credit note</div><div class="val discount">– AED {{ number_format($invoice->credit_amount, 2) }}</div></div>
                    @endif
                    @if ($invoice->paidAmount() > 0)
                        <div class="totals-row"><div>Payments received</div><div class="val discount">– AED {{ number_format($invoice->paidAmount(), 2) }}</div></div>
                    @endif
                    <div class="patient-resp {{ $balance <= 0.01 && !$isVoided ? 'settled' : '' }}">
                        <div class="lbl">{{ $isVoided ? 'Voided — credited in full' : ($balance <= 0.01 ? 'Settled in full' : 'Amount due') }}</div>
                        <div class="amt">AED {{ number_format($isVoided ? 0 : $balance, 2) }} @if(!$isVoided && $balance <= 0.01)<small>(paid)</small>@endif</div>
                    </div>
                </div>
            </div>

            @if (count($invoice->payer_splits ?? []) > 1)
                <div class="split-notes">
                    <div class="title">Payer split</div>
                    @foreach ($invoice->payer_splits as $s)
                        {{ $s['payer'] }} — {{ $s['pct'] }}% · AED {{ number_format($s['amount'], 2) }}<br>
                    @endforeach
                    Family (self-pay) — AED {{ number_format((float) $invoice->patient_responsibility, 2) }}
                </div>
            @endif

            @if ($invoice->payments->isNotEmpty())
                <div class="receipts">
                    <div class="title">Receipts &amp; notes</div>
                    @foreach ($invoice->payments as $p)
                        <div class="receipt-row"><span>{{ $p->receipt_number }} — {{ $p->method }}{{ $p->reference ? ' · ref ' . $p->reference : '' }}</span><span>AED {{ number_format($p->amount, 2) }} · {{ $p->received_on->format('d M Y') }}</span></div>
                    @endforeach
                    @if ($invoice->credit_reason)
                        <div style="margin-top:4px;">Credit note — {{ $invoice->credit_reason }}</div>
                    @endif
                </div>
            @endif

            <div class="footer-grid">
                <div class="footer-box payment">
                    <div class="title">Payable to</div>
                    Bank: {{ $clinic['bank'] }}<br>
                    Name: {{ $clinic['account_name'] }}<br>
                    IBAN {{ $clinic['iban'] }}<br>
                    Account {{ $clinic['account_number'] }}<br>
                    Quote reference <strong>{{ $invoice->invoice_number }}</strong> with any payment.<br>
                    <span style="opacity:.7;">Pay online — payment link coming soon.</span>
                </div>
                <div class="footer-box notes">
                    <div class="title">Notes</div>
                    Thanks for your business.
                    @if ($invoice->claim_reference)
                        <br>Claim {{ $invoice->claim_reference }} for the insured portion was submitted on {{ $invoice->issue_date->format('d M Y') }}.
                    @endif
                    <br>Payment is due{{ $invoice->due_date ? ' by ' . $invoice->due_date->format('d M Y') : '' }}. For billing questions, WhatsApp us at {{ $clinic['phone'] }}.
                </div>
            </div>

            <div class="terms">
                <div>
                    <div class="t-title">Payment options</div>
                    1) Cash. 2) Direct bank deposit or transfer — please send a copy of the deposit or transfer slip by email or WhatsApp.
                    <div class="t-title">Payment terms</div>
                    In all cases we invoice and request 100% prepayment of fees in advance, according to the agreed schedule. You agree to pay the prepayment according to the invoice issued prior to the commencement of the session. Should additional sessions occur within the same month, a new invoice is generated and charged according to the number of sessions.
                    <div class="t-title">Use of prepayment</div>
                    The prepayment is used to cover the cost of materials and to reserve services.
                    <div class="t-title">Refunds</div>
                    Prepayments are non-refundable unless Engage BL is unable to deliver the services as agreed. Any unused amount may be carried forward to sessions in the following month, or to replacement sessions within the same month. In the event of a refund, Engage BL will deduct any costs incurred up to the time of cancellation.
                </div>
                <div>
                    <div class="t-title">Late payment penalties</div>
                    If the prepayment is not received by the due date, the insurer may require Engage BL to disclose information regarding treatment in order to determine whether it will pay for or reimburse those services, provided the policy covers them. Engage BL will support the family to the best of its ability in providing the information the insurer needs, but is not responsible for any agreement with, or the final decision of, your insurance provider.
                    <div class="t-title">Insurance reimbursement</div>
                    If you are using health insurance to pay for services, the insurer may require Engage BL to disclose information regarding treatment in order to determine whether it will pay for or reimburse those services, provided the policy covers them.
                    <div class="t-title">Fee updates</div>
                    Engage BL reserves the right to modify service fees at any time. Written notice is given in advance to all clients.
                </div>
            </div>

            <div class="legal">
                {{ $clinic['name'] }} · {{ $clinic['license'] }}<br>
                VAT charged at {{ config('billing.vat_rate') }}% under UAE Federal Decree-Law No. 8 of 2017 on Value Added Tax
            </div>
        </div>
    </div>

</body>
</html>
