<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }} · {{ $clinic['name'] }}</title>
    <style>
        {{--
            This is a separate template from billing.print, deliberately - dompdf
            (used to build the emailed/attached PDF) only reliably supports CSS2.1
            table/block layout, not flexbox or grid. billing.print uses flex/grid
            throughout for the on-screen + browser "Print / Save as PDF" view,
            which real browsers render correctly; feeding that same markup to
            dompdf collapsed every label/value pair (flex justify-content isn't
            honored), producing a visibly different, broken-looking PDF. Every
            flex/grid section below is rebuilt as a table so dompdf renders it
            exactly like the browser's own print output does.
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
        .voided-band { background: #B3261E; color: #fff; text-align: center; font: 800 12.5px 'Nunito Sans'; padding: 8px; letter-spacing: .04em; text-transform: uppercase; }

        .body { padding: 30px 40px 40px; }
        .notice { border-radius: 10px; padding: 10px 14px; font: 700 12.5px 'Nunito Sans'; margin-bottom: 20px; }
        .notice.live { background: #E4F6EB; color: #1E7A46; }

        .top-table { width: 100%; margin-bottom: 24px; }
        .top-table > tr > td { vertical-align: top; padding-right: 20px; }
        .top-table > tr > td:last-child { padding-right: 0; }
        .label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
        .from-name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .from-detail, .bill-detail { font: 600 12.5px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .bill-name { font: 700 15px 'Nunito Sans'; color: #16436E; }
        .meta-box { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; }
        .meta-table { width: 100%; }
        .meta-table td { padding: 4px 0; font: 700 12px 'Nunito Sans'; color: #2B3A4C; }
        .meta-table td:first-child { color: #98897A; }
        .meta-table td:last-child { text-align: right; }
        .status-pill { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; display: inline-block; }
        .replace-note { font: 700 11.5px 'Nunito Sans'; margin-top: 8px; }
        .replace-note.warn { color: #B3261E; }
        .replace-note.ok { color: #24619C; }

        .items { width: 100%; margin-top: 4px; }
        .items thead th { text-align: left; font: 700 9.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px; padding: 0 6px 8px 0; border-bottom: 2px solid #16436E; }
        .items thead th.num { text-align: right; }
        .items tbody td { padding: 11px 6px 11px 0; border-bottom: 1px solid #F0EAE0; vertical-align: top; font: 600 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .items tbody td.num { text-align: right; font: 800 12.5px 'Nunito Sans'; color: #16436E; white-space: nowrap; }
        .line-n { color: #98897A; font-weight: 700; }
        .svc-name { font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .svc-meta { font: 700 10.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .svc-payer { font: 700 10.5px 'Nunito Sans'; color: #8A7D6C; margin-top: 1px; }

        .totals-wrap { width: 100%; margin-top: 18px; }
        .totals { width: 340px; margin-left: auto; }
        .totals-row td { padding: 4px 0; font: 700 13px 'Nunito Sans'; color: #5A6B7E; }
        .totals-row td.val { color: #2B3A4C; font-weight: 800; text-align: right; }
        .totals-row td.val.discount { color: #2E7D5B; }
        .totals-row.divider td { border-top: 1px solid #EBE4DA; padding-top: 9px; }
        .patient-resp { width: 340px; margin-left: auto; background: #16436E; border-radius: 10px; margin-top: 4px; }
        .patient-resp.settled { background: #1E7A46; }
        .patient-resp td { padding: 14px 18px; }
        .patient-resp .lbl { color: #BFD2E3; font: 700 12.5px 'Nunito Sans'; }
        .patient-resp .amt { color: #fff; font: 700 20px 'Baloo 2', 'Georgia', serif; text-align: right; }
        .patient-resp .amt small { font: 700 12px 'Nunito Sans'; margin-left: 6px; }

        .split-notes, .receipts { background: #F6F3EE; border-radius: 10px; padding: 12px 14px; margin-top: 14px; font: 600 12px/1.6 'Nunito Sans'; color: #5A6B7E; }
        .split-notes .title, .receipts .title { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 4px; }
        .receipts-table { width: 100%; }
        .receipts-table td { padding: 1px 0; }
        .receipts-table td:last-child { text-align: right; white-space: nowrap; }

        .footer-table { width: 100%; margin-top: 22px; }
        .footer-table > tr > td { width: 50%; vertical-align: top; padding-right: 16px; }
        .footer-table > tr > td:last-child { padding-right: 0; padding-left: 16px; }
        .footer-box { border-radius: 10px; padding: 14px 16px; font: 600 12.5px/1.6 'Nunito Sans'; }
        .footer-box.payment { background: #F6F3EE; color: #5A6B7E; }
        .footer-box.notes { background: #FCEEF1; color: #7A2E43; }
        .footer-box .title { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 6px; }
        .footer-box.notes .title { color: #7A2E43; }
        .footer-box a { color: inherit; font-weight: 800; }

        .terms-table { width: 100%; margin-top: 22px; padding-top: 16px; border-top: 1px solid #F0EAE0; font: 600 10.5px/1.6 'Nunito Sans'; color: #8A7D6C; }
        .terms-table > tr > td { width: 50%; vertical-align: top; padding-right: 16px; }
        .terms-table > tr > td:last-child { padding-right: 0; padding-left: 16px; }
        .terms-table .t-title { font: 800 10.5px 'Nunito Sans'; color: #5A6B7E; margin-bottom: 3px; margin-top: 8px; }
        .terms-table .t-title-first { margin-top: 0; }
        .legal { text-align: center; font: 700 11px 'Nunito Sans'; color: #A79C8E; margin-top: 20px; }
    </style>
</head>
<body>

    @php
        $lead = $invoice->patient?->lead;
        $balance = max(0, $invoice->balance());
        $isVoided = $invoice->isVoided();
    @endphp

    <table class="banner-table"><tr>
        <td style="width:1%;"><span class="banner-logo-box"><img src="{{ public_path('uploads/engage.png') }}" alt="{{ $clinic['name'] }}"></span></td>
        <td>
            <div class="banner-title">Tax invoice</div>
            <div class="banner-sub">{{ $invoice->invoice_number }}</div>
        </td>
    </tr></table>
    <div class="accent">&nbsp;</div>
    @if ($isVoided)
        <div class="voided-band">Voided — {{ $invoice->void_reason }} · {{ $invoice->voided_at->format('d M Y') }}</div>
    @endif

    <div class="body">
        @if (! $isVoided && $balance <= 0.01)
            <div class="notice live">Settled in full — nothing further owed on this invoice.</div>
        @endif

        <table class="top-table"><tr>
            <td style="width:35%;">
                <div class="label">From</div>
                <div class="from-name">{{ $clinic['name'] }}</div>
                <div class="from-detail">
                    {{ $clinic['address'] }}<br>
                    {{ $clinic['phone'] }} · {{ $clinic['billing_email'] }}<br>
                    TRN {{ $clinic['trn'] }}
                </div>
            </td>
            <td style="width:35%;">
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
            </td>
            <td style="width:30%;">
                <div class="meta-box">
                    <table class="meta-table">
                        <tr><td>Issue date</td><td>{{ $invoice->issue_date->format('d M Y') }}</td></tr>
                        <tr><td>Due date</td><td>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><td>Period</td><td>{{ $invoice->periodLabel() }}</td></tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                @php
                                    $colors = ['voided' => ['#EEEEEE', '#6B6B6B'], 'paid' => ['#E3F1E9', '#2E7D5B'], 'partly_paid' => ['#F7EEDD', '#B97F24'], 'outstanding' => ['#F9E4E2', '#B3261E']][$invoice->billingStatus()];
                                @endphp
                                <span class="status-pill" style="background: {{ $colors[0] }}; color: {{ $colors[1] }};">{{ $invoice->billingStatusLabel() }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr></table>

        <table class="items">
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

        <table class="totals-wrap"><tr><td>
            <table class="totals">
                <tr class="totals-row"><td>Subtotal — VAT excluded</td><td class="val">AED {{ number_format($invoice->subtotal, 2) }}</td></tr>
                <tr class="totals-row"><td>VAT 5%</td><td class="val">AED {{ number_format($invoice->vat_amount, 2) }}</td></tr>
                <tr class="totals-row divider"><td>Invoice total</td><td class="val">AED {{ number_format($invoice->total, 2) }}</td></tr>
                @if ((float) $invoice->insurance_coverage_amount > 0)
                    <tr class="totals-row"><td>Insurance coverage</td><td class="val discount">– AED {{ number_format($invoice->insurance_coverage_amount, 2) }}</td></tr>
                @endif
                @if ((float) $invoice->credit_amount > 0)
                    <tr class="totals-row"><td>Credit note</td><td class="val discount">– AED {{ number_format($invoice->credit_amount, 2) }}</td></tr>
                @endif
                @if ($invoice->paidAmount() > 0)
                    <tr class="totals-row"><td>Payments received</td><td class="val discount">– AED {{ number_format($invoice->paidAmount(), 2) }}</td></tr>
                @endif
            </table>
            <table class="patient-resp {{ $balance <= 0.01 && !$isVoided ? 'settled' : '' }}"><tr>
                <td class="lbl">{{ $isVoided ? 'Voided — credited in full' : ($balance <= 0.01 ? 'Settled in full' : 'Amount due') }}</td>
                <td class="amt">AED {{ number_format($isVoided ? 0 : $balance, 2) }} @if(!$isVoided && $balance <= 0.01)<small>(paid)</small>@endif</td>
            </tr></table>
        </td></tr></table>

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
                <table class="receipts-table">
                    @foreach ($invoice->payments as $p)
                        <tr><td>{{ $p->receipt_number }} — {{ $p->method }}{{ $p->reference ? ' · ref ' . $p->reference : '' }}</td><td>AED {{ number_format($p->amount, 2) }} · {{ $p->received_on->format('d M Y') }}</td></tr>
                    @endforeach
                </table>
                @if ($invoice->credit_reason)
                    <div style="margin-top:4px;">Credit note — {{ $invoice->credit_reason }}</div>
                @endif
            </div>
        @endif

        <table class="footer-table"><tr>
            <td>
                <div class="footer-box payment">
                    <div class="title">Payable to</div>
                    Bank: {{ $clinic['bank'] }}<br>
                    Name: {{ $clinic['account_name'] }}<br>
                    IBAN {{ $clinic['iban'] }}<br>
                    Account {{ $clinic['account_number'] }}<br>
                    Quote reference <strong>{{ $invoice->invoice_number }}</strong> with any payment.<br>
                    <span style="opacity:.7;">Pay online — payment link coming soon.</span>
                </div>
            </td>
            <td>
                <div class="footer-box notes">
                    <div class="title">Notes</div>
                    Thanks for your business.
                    @if ($invoice->claim_reference)
                        <br>Claim {{ $invoice->claim_reference }} for the insured portion was submitted on {{ $invoice->issue_date->format('d M Y') }}.
                    @endif
                    <br>Payment is due{{ $invoice->due_date ? ' by ' . $invoice->due_date->format('d M Y') : '' }}. For billing questions, WhatsApp us at {{ $clinic['phone'] }}.
                </div>
            </td>
        </tr></table>

        <table class="terms-table"><tr>
            <td>
                <div class="t-title t-title-first">Payment options</div>
                1) Cash. 2) Direct bank deposit or transfer — please send a copy of the deposit or transfer slip by email or WhatsApp.
                <div class="t-title">Payment terms</div>
                In all cases we invoice and request 100% prepayment of fees in advance, according to the agreed schedule. You agree to pay the prepayment according to the invoice issued prior to the commencement of the session. Should additional sessions occur within the same month, a new invoice is generated and charged according to the number of sessions.
                <div class="t-title">Use of prepayment</div>
                The prepayment is used to cover the cost of materials and to reserve services.
                <div class="t-title">Refunds</div>
                Prepayments are non-refundable unless Engage BL is unable to deliver the services as agreed. Any unused amount may be carried forward to sessions in the following month, or to replacement sessions within the same month. In the event of a refund, Engage BL will deduct any costs incurred up to the time of cancellation.
            </td>
            <td>
                <div class="t-title t-title-first">Late payment penalties</div>
                If the prepayment is not received by the due date, the insurer may require Engage BL to disclose information regarding treatment in order to determine whether it will pay for or reimburse those services, provided the policy covers them. Engage BL will support the family to the best of its ability in providing the information the insurer needs, but is not responsible for any agreement with, or the final decision of, your insurance provider.
                <div class="t-title">Insurance reimbursement</div>
                If you are using health insurance to pay for services, the insurer may require Engage BL to disclose information regarding treatment in order to determine whether it will pay for or reimburse those services, provided the policy covers them.
                <div class="t-title">Fee updates</div>
                Engage BL reserves the right to modify service fees at any time. Written notice is given in advance to all clients.
            </td>
        </tr></table>

        <div class="legal">
            {{ $clinic['name'] }} · {{ $clinic['license'] }}<br>
            VAT charged at {{ config('billing.vat_rate') }}% under UAE Federal Decree-Law No. 8 of 2017 on Value Added Tax
        </div>
    </div>

</body>
</html>
