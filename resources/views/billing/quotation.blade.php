<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quotation->quote_number }} · {{ $clinic['name'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700&family=Nunito+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; background: #F4F0E8; font: 600 13px/1.5 'Nunito Sans', Arial, sans-serif; color: #2B3A4C; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: center; gap: 10px; padding: 14px; background: #EDE6D9; }
        .btn-print { background: #16436E; color: #fff; border: none; border-radius: 9px; padding: 10px 18px; font: 800 12.5px 'Nunito Sans'; cursor: pointer; }
        .sheet { width: 820px; max-width: 100%; margin: 24px auto 40px; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 40px rgba(22,42,60,0.18); }
        .banner { background: #16436E; color: #fff; padding: 22px 32px; display: flex; justify-content: space-between; align-items: center; }
        .banner img { height: 46px; background: #fff; border-radius: 8px; padding: 4px 8px; }
        .banner-title { font: 700 24px 'Baloo 2'; text-align: right; }
        .banner-sub { font: 700 13px 'Nunito Sans'; opacity: .85; text-align: right; }
        .accent { height: 5px; background: #C8355F; }
        .body { padding: 26px 32px 30px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 22px; }
        .label { font: 800 10.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #98897A; margin-bottom: 4px; }
        .strong { font: 800 14px 'Nunito Sans'; color: #16436E; }
        .meta-row { display: flex; justify-content: space-between; padding: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { text-align: left; font: 800 10.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #98897A; padding: 9px 10px; border-bottom: 2px solid #EBE4DA; }
        td { padding: 11px 10px; border-bottom: 1px solid #F3EDE3; font-weight: 700; }
        .num { text-align: right; }
        .totals { width: 300px; margin-left: auto; }
        .totals .row { display: flex; justify-content: space-between; padding: 5px 0; font-weight: 700; }
        .totals .grand { border-top: 2px solid #16436E; margin-top: 6px; padding-top: 9px; font: 800 16px 'Nunito Sans'; color: #16436E; }
        .box { background: #FAF6EF; border-radius: 10px; padding: 14px 16px; margin-top: 18px; }
        .box .title { font: 800 11px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #8A7D6C; margin-bottom: 4px; }
        .legal { text-align: center; color: #98897A; font-size: 11px; margin-top: 22px; }
        @media print { body { background: #fff; } .toolbar { display: none; } .sheet { box-shadow: none; border-radius: 0; margin: 0; width: 100%; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button></div>

    @php $lead = $quotation->patient?->lead; @endphp

    <div class="sheet">
        <div class="banner">
            <img src="{{ asset('uploads/engage.png') }}" alt="{{ $clinic['name'] }}">
            <div>
                <div class="banner-title">Quotation</div>
                <div class="banner-sub">{{ $quotation->quote_number }}</div>
            </div>
        </div>
        <div class="accent"></div>

        <div class="body">
            <div class="grid">
                <div>
                    <div class="label">From</div>
                    <div class="strong">{{ $clinic['name'] }}</div>
                    {{ $clinic['address'] }}<br>
                    {{ $clinic['phone'] }} · {{ $clinic['billing_email'] }}<br>
                    TRN {{ $clinic['trn'] }}
                </div>
                <div>
                    <div class="label">Prepared for</div>
                    <div class="strong">{{ $quotation->customer_name ?: 'Parent / guardian' }}</div>
                    Client: {{ $lead?->child_name ?? '—' }}<br>
                    Location: {{ $quotation->location }}<br>
                    Payment mode: {{ $quotation->payment_mode }}{{ $quotation->payer ? ' · ' . $quotation->payer : '' }}
                </div>
                <div>
                    <div class="meta-row"><span>Date</span><span>{{ $quotation->created_at->format('d M Y') }}</span></div>
                    <div class="meta-row"><span>Valid until</span><span>{{ $quotation->valid_until->format('d M Y') }}</span></div>
                    <div class="meta-row"><span>Status</span><span>{{ $quotation->statusLabel() }}</span></div>
                </div>
            </div>

            <table>
                <thead><tr><th>Service</th><th>Pricing basis</th><th class="num">Quantity</th><th class="num">Rate</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    <tr>
                        <td>{{ $quotation->service }}</td>
                        <td>{{ $quotation->pricing_basis }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $quotation->quantity, 2), '0'), '.') }} {{ $quotation->billing_unit }}{{ (float) $quotation->quantity == 1 ? '' : 's' }}</td>
                        <td class="num">AED {{ number_format((float) $quotation->rate, 2) }}</td>
                        <td class="num">AED {{ number_format((float) $quotation->subtotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="totals">
                <div class="row"><span>Subtotal</span><span>AED {{ number_format((float) $quotation->subtotal, 2) }}</span></div>
                <div class="row"><span>VAT {{ config('billing.vat_rate') }}%</span><span>AED {{ number_format((float) $quotation->vat_amount, 2) }}</span></div>
                <div class="row grand"><span>Total</span><span>AED {{ number_format((float) $quotation->total, 2) }}</span></div>
            </div>

            <div class="box">
                <div class="title">Payment terms</div>
                {{ $quotation->payment_terms }}
                @unless ($quotation->isInsurance())
                    <br>Card payments carry a POS fee of {{ config('billing.pos_fee_pct') }}% of the service value plus {{ config('billing.vat_rate') }}% VAT on the fee (AED {{ number_format(\App\Models\Quotation::posFee((float) $quotation->subtotal), 2) }} on this quotation), under a signed POS fee agreement.
                @else
                    <br>Direct insurance billing requires a signed NOC. The customer remains 100% personally liable for any amount the insurer rejects.
                @endunless
                @if ($quotation->notes)
                    <br>{{ $quotation->notes }}
                @endif
            </div>

            <div class="box">
                <div class="title">Payable to</div>
                {{ $clinic['account_name'] }} · {{ $clinic['bank'] }} · IBAN {{ $clinic['iban'] }}<br>
                Quote reference <strong>{{ $quotation->quote_number }}</strong> with any payment.
            </div>

            <div class="legal">{{ $clinic['name'] }} · {{ $clinic['license'] }} · Sessions are scheduled once payment is confirmed.</div>
        </div>
    </div>
</body>
</html>
