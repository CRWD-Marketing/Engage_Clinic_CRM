@extends('layouts.admin-sidebar')

@section('title', 'Billing & insurance · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
<style>
    .bl-page { padding: 0 28px 40px; }
    .role-strip { display: flex; align-items: center; gap: 10px 14px; flex-wrap: wrap; padding: 8px 0; margin: 0 -28px 10px; padding-left: 28px; padding-right: 28px; background: #FFFDFA; border-bottom: 1px solid #EBE4DA; font: 700 12px 'Nunito Sans'; color: #5A6B7E; }
    .role-badge { background: #16436E; color: #fff; border-radius: 7px; padding: 3px 10px; font: 800 11.5px 'Nunito Sans'; }
    .role-can { color: #2E7D5B; }
    .role-locked { color: #98897A; }

    .bl-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin: 18px 0 14px; }
    .bl-title { font: 600 22px/1.2 'Baloo 2'; color: #16436E; }
    .bl-sub { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 3px; }
    .bl-btn { background: #fff; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px; padding: 10px 16px; font: 800 12.5px 'Nunito Sans'; cursor: pointer; white-space: nowrap; }
    .bl-btn:hover { border-color: #C8355F; }
    .bl-btn-sm { padding: 6px 12px; font-size: 11.5px; border-radius: 8px; }
    .bl-btn-primary { background: #C8355F; color: #fff; border-color: #C8355F; }
    .bl-btn-primary:hover { background: #A82348; }
    .bl-btn-danger { color: #B3261E; border-color: #EFC7C2; }
    .bl-btn-danger:hover { background: #FBE1E1; }
    .bl-btn-dark { background: #16436E; color: #fff; border-color: #16436E; }
    .bl-btn:disabled { opacity: .45; cursor: not-allowed; }
    .bl-lock { font: 700 12px 'Nunito Sans'; color: #98897A; }

    .bl-tabs { display: flex; gap: 22px; border-bottom: 1px solid #EBE4DA; margin-bottom: 18px; }
    .bl-tab { background: none; border: none; padding: 4px 0 12px; font: 800 13px 'Nunito Sans'; color: #98897A; cursor: pointer; border-bottom: 2px solid transparent; }
    .bl-tab.active { color: #C8355F; border-color: #C8355F; }

    .bl-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 18px; }
    .bl-tile { background: #fff; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px; }
    .bl-tile-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: .05em; }
    .bl-tile-value { font: 700 22px 'Baloo 2'; color: #16436E; margin-top: 4px; }
    .bl-tile-value.warn { color: #B97F24; }
    .bl-tile-value.danger { color: #B3261E; }

    .bl-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: start; }
    @media (max-width: 1100px) { .bl-grid { grid-template-columns: 1fr; } }
    .bl-card { background: #fff; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; margin-bottom: 16px; }
    .bl-card-title { font: 700 16px 'Baloo 2'; color: #16436E; }
    .bl-card-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
    .bl-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
    .bl-empty { text-align: center; color: #B0A493; font: 700 12px 'Nunito Sans'; padding: 20px 4px; }

    .inv-row { display: grid; grid-template-columns: 1fr auto auto auto auto; gap: 14px; align-items: start; padding: 14px 0; border-bottom: 1px solid #F0EAE0; }
    .inv-row:last-child { border-bottom: none; }
    .inv-name { font: 800 13.5px 'Nunito Sans'; color: #16436E; }
    .inv-meta { font: 700 11.5px 'Nunito Sans'; color: #8A7D6C; margin-top: 2px; }
    .inv-meta.receipt { color: #1E7A46; }
    .inv-meta.voided { color: #B3261E; }
    .inv-meta.link { color: #24619C; }
    .inv-status { border-radius: 7px; padding: 4px 10px; font: 800 10.5px 'Nunito Sans'; white-space: nowrap; }
    .inv-actions { display: flex; flex-direction: column; gap: 5px; min-width: 108px; margin: 0 auto; }
    .inv-pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-top: 14px; margin-top: 4px; }
    .inv-pagination-info { font: 700 11.5px 'Nunito Sans'; color: #98897A; }
    .inv-pagination-nav { display: flex; gap: 6px; }
    .act-btn { border: 1px solid #E2DACE; background: #fff; border-radius: 7px; padding: 5px 10px; font: 800 10.5px 'Nunito Sans'; cursor: pointer; text-align: center; text-decoration: none; display: block; }
    .act-btn.pay { background: #C8355F; color: #fff; border-color: #C8355F; }
    .act-btn.email { background: #16436E; color: #fff; border-color: #16436E; }
    .act-btn.danger { color: #B3261E; border-color: #EFC7C2; }
    .act-dropdown {
        width: 100%; min-width: 132px; padding: 7px 10px; border: 1px solid #E2DACE; border-radius: 9px;
        background: #FFFDFA; font: 800 11.5px 'Nunito Sans'; color: #16436E; outline: none; cursor: pointer;
    }
    .act-dropdown:focus { border-color: #C8355F; }

    .claim-table, .aging-table, .fam-table, .pa-table, .util-tbl, .inv-table { width: 100%; border-collapse: collapse; }
    .claim-table th, .aging-table th, .fam-table th, .pa-table th, .inv-table th { text-align: left; font: 800 10.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #98897A; padding: 8px 10px; border-bottom: 1px solid #EBE4DA; }
    .claim-table td, .aging-table td, .fam-table td, .pa-table td, .inv-table td { padding: 11px 10px; border-bottom: 1px solid #F3EDE3; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; vertical-align: middle; }
    .claim-table tr:last-child td, .aging-table tr:last-child td, .fam-table tr:last-child td, .pa-table tr:last-child td, .inv-table tr:last-child td { border-bottom: none; }
    .inv-table th, .inv-table td { text-align: center; }
    .num { text-align: right; }

    /* Below 700px the invoice table stops trying to fit six columns side by
       side (unreadable at phone width even with horizontal scroll) and each
       row becomes its own stacked card, with a data-label caption standing
       in for the now-hidden header cell. */
    @media (max-width: 700px) {
        .inv-table thead { display: none; }
        .inv-table, .inv-table tbody, .inv-table tr, .inv-table td { display: block; width: 100%; }
        .inv-table tr { border: 1px solid #EBE4DA; border-radius: 12px; padding: 6px 0; margin-bottom: 12px; }
        .inv-table td {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            text-align: right; border-bottom: 1px solid #F3EDE3; padding: 9px 14px;
        }
        .inv-table tr:last-child, .inv-table td:last-child { border-bottom: none; margin-bottom: 0; }
        .inv-table td::before {
            content: attr(data-label); font: 800 10.5px 'Nunito Sans'; text-transform: uppercase;
            letter-spacing: .05em; color: #98897A; text-align: left; flex-shrink: 0;
        }
        .inv-table td[data-label="Patient"] { flex-direction: column; align-items: flex-start; text-align: left; }
        .inv-table td[data-label="Patient"]::before { margin-bottom: 4px; }
        .inv-table td:not([data-label]) { display: block; text-align: center; border-bottom: none; }
        .inv-actions { min-width: 0; width: 100%; }
    }
    .status-sel { border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 8px; font: 800 11px 'Nunito Sans'; background: #fff; }
    .age-chip { border-radius: 7px; padding: 3px 9px; font: 800 10.5px 'Nunito Sans'; white-space: nowrap; }

    .bar-row { margin-bottom: 12px; }
    .bar-top { display: flex; justify-content: space-between; font: 700 12px 'Nunito Sans'; color: #2B3A4C; margin-bottom: 4px; }
    .bar-track { background: #F3EDE3; border-radius: 999px; height: 7px; overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 999px; }
    .bar-sub { font: 700 10.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
    .payer-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; margin-right: 6px; }

    .alert-box { border-radius: 10px; padding: 12px 14px; font: 700 12.5px 'Nunito Sans'; margin-top: 6px; }
    .alert-box.rejected { background: #FBE1E1; color: #8A2020; }

    /* ---- Modals ---- */
    .cmodal-overlay { display: none; position: fixed; inset: 0; background: rgba(22,67,110,0.25); z-index: 60; align-items: center; justify-content: center; padding: 20px; }
    .cmodal-overlay.open { display: flex; }
    .cmodal { background: #FFFDFA; border-radius: 16px; padding: 24px; width: 100%; max-width: 440px; box-shadow: 0 24px 60px rgba(22,67,110,.25); display: flex; flex-direction: column; gap: 14px; max-height: 92vh; overflow: auto; }
    .cmodal.wide { max-width: 760px; }
    .cmodal.xwide { max-width: 980px; }
    .cmodal-title { font: 700 20px 'Baloo 2'; color: #16436E; }
    .cmodal-sub { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: -8px; }
    .cx { background: #fff; border: 1px solid #E2DACE; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; font: 700 13px 'Nunito Sans'; color: #5A6B7E; flex-shrink: 0; }
    .f-label { display: block; font: 800 11px 'Nunito Sans'; letter-spacing: .04em; color: #8A7D6C; text-transform: uppercase; margin-bottom: 6px; }
    .f-input, .f-select, .f-textarea { width: 100%; padding: 11px 10px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFDFA; font: 700 13.5px 'Nunito Sans'; color: #16436E; outline: none; box-sizing: border-box; }
    .f-textarea { font-weight: 600; resize: vertical; }
    .f-error { display: none; background: #F9E7EC; color: #C8355F; font: 700 12px 'Nunito Sans'; padding: 8px 10px; border-radius: 8px; }
    .f-row { display: flex; gap: 10px; }
    .f-row > div { flex: 1; }
    .btn-save { background: #C8355F; border: none; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #fff; cursor: pointer; width: 100%; }
    .btn-save:disabled { opacity: .5; cursor: not-allowed; }
    .btn-cancel { background: #fff; border: 1px solid #E2DACE; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #5A6B7E; cursor: pointer; width: 100%; }
    .btn-dark { background: #16436E; }
    .warn-box { background: #FDF6E9; border: 1px solid #EBDCC2; border-radius: 9px; padding: 10px 12px; font: 700 12px 'Nunito Sans'; color: #8A5A10; }
    .danger-box { background: #FBE1E1; border: 1px solid #EFC7C2; border-radius: 9px; padding: 10px 12px; font: 700 12px 'Nunito Sans'; color: #8A2020; }
    .toast { position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%); background: #16436E; color: #fff; padding: 11px 18px; border-radius: 10px; font: 800 12.5px 'Nunito Sans'; z-index: 100; display: none; box-shadow: 0 10px 30px rgba(22,67,110,.3); max-width: 90vw; }

    /* New-invoice session picker */
    .picker-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin: 4px 0 10px; }
    .picker-count { font: 700 12.5px 'Nunito Sans'; color: #5A6B7E; }
    .sess-row { border: 1px solid #EBE4DA; border-radius: 10px; padding: 10px 12px; display: flex; gap: 10px; align-items: flex-start; margin-bottom: 8px; }
    .sess-row.invoiced { opacity: .55; }
    .sess-row input[type="checkbox"] { margin-top: 3px; accent-color: #C8355F; }
    .sess-main { flex: 1; }
    .sess-top { display: flex; justify-content: space-between; gap: 8px; font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; }
    .sess-meta { font: 700 11px 'Nunito Sans'; color: #8A7D6C; margin-top: 2px; }
    .sess-rule { font: 700 10.5px 'Nunito Sans'; margin-top: 3px; }
    .att-select { border: 1px solid #E2DACE; border-radius: 7px; padding: 4px 6px; font: 800 10.5px 'Nunito Sans'; background: #fff; }
    .sess-amount { text-align: right; font: 800 12.5px 'Nunito Sans'; color: #16436E; white-space: nowrap; }
    .sess-invoiced-tag { font: 800 10px 'Nunito Sans'; color: #98897A; }

    /* Bulk run rows */
    .bulk-row { border: 1px solid #EBE4DA; border-radius: 10px; padding: 12px 14px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 8px; }
    .bulk-row input { margin-top: 3px; accent-color: #C8355F; }
    .bulk-main { flex: 1; }
    .bulk-name { font: 800 13px 'Nunito Sans'; color: #16436E; }
    .bulk-meta { font: 700 11px 'Nunito Sans'; color: #8A7D6C; margin-top: 2px; }
    .bulk-nums { display: flex; gap: 18px; align-items: baseline; }
    .bulk-nums .n { text-align: right; }
    .bulk-nums .n .l { font: 700 9.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; }
    .bulk-nums .n .v { font: 800 13px 'Nunito Sans'; color: #2B3A4C; }
    .bulk-footer { position: sticky; bottom: -24px; background: #FFFDFA; padding: 14px 0 2px; margin-top: 10px; display: flex; gap: 10px; align-items: center; }
</style>

<div class="bl-page" id="bl-root"
     data-invoices='@json($invoicesForJs)'
     data-claims='@json($claimsForJs)'
     data-preauths='@json($preAuthsForJs)'
     data-patients='@json($patientsForJs)'
     data-payers='@json($payers)'
     data-services='@json($services)'
     data-methods='@json($methods)'
     data-claim-statuses='@json($claimStatuses)'
     data-aging='@json($aging)'
     data-cancel-policy='@json($cancelPolicy)'
     data-bulk-defaults='@json($bulkDefaults)'
     data-can-invoice="{{ $canInvoice ? '1' : '0' }}"
     data-base-url="{{ url('billing') }}">

    <div class="role-strip">
        <span class="role-badge">{{ $capabilities['label'] }}</span>
        <span class="role-can">Can {{ implode(' · ', $capabilities['can']) }}</span>
        @if ($capabilities['locked'])
            <span class="role-locked">🔒 Locked: {{ implode(' · ', array_slice($capabilities['locked'], 0, 4)) }}@if (count($capabilities['locked']) > 4) · +{{ count($capabilities['locked']) - 4 }} more @endif</span>
        @endif
    </div>

    <div class="bl-head">
        <div>
            <div class="bl-title">Billing &amp; insurance</div>
            <div class="bl-sub">{{ $monthLabel }} · {{ $payerSummary }}</div>
        </div>
        @if ($canInvoice)
            <button type="button" class="bl-btn bl-btn-primary" id="btn-new-invoice">+ New invoice</button>
        @else
            <span class="bl-lock">🔒 Invoices are raised by Finance</span>
        @endif
    </div>

    <div class="bl-tabs" id="bl-tabs">
        <button type="button" class="bl-tab active" data-tab="invoices">Invoices &amp; claims</button>
        <button type="button" class="bl-tab" data-tab="aging">Aging &amp; statements</button>
        <button type="button" class="bl-tab" data-tab="bulk">Bulk run</button>
    </div>

    <!-- ============ TAB 1: Invoices & claims ============ -->
    <div class="bl-tab-panel" id="tab-invoices">
        <div class="bl-tiles">
            <div class="bl-tile"><div class="bl-tile-label">Invoiced · MTD</div><div class="bl-tile-value">AED {{ number_format($tiles['invoiced_mtd'], 0) }}</div></div>
            <div class="bl-tile"><div class="bl-tile-label">Collected</div><div class="bl-tile-value" style="color:#1E7A46;">AED {{ number_format($tiles['collected_mtd'], 0) }}</div></div>
            <div class="bl-tile"><div class="bl-tile-label">Outstanding claims</div><div class="bl-tile-value warn">AED {{ number_format($tiles['outstanding_claims'], 0) }}</div></div>
            <div class="bl-tile"><div class="bl-tile-label">Avg. claim cycle</div><div class="bl-tile-value">{{ $tiles['avg_claim_cycle'] }} days</div></div>
        </div>

        <div class="bl-grid">
            <div>
                <div class="bl-card">
                    <div class="bl-card-head">
                        <div><div class="bl-card-title">Invoices &amp; payments</div><div class="bl-card-sub">Record receipts and issue credit notes</div></div>
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="inv-table">
                            <thead><tr><th>Patient</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody id="invoice-list"></tbody>
                        </table>
                    </div>
                    <div id="invoice-pagination" class="inv-pagination"></div>
                </div>
            </div>

            <div>
                <div class="bl-card">
                    <div class="bl-card-title" style="margin-bottom:2px;">Outstanding claim aging</div>
                    <div class="bl-card-sub" style="margin-bottom:12px;">Open claims by days since submission</div>
                    <div id="claim-aging-bars"></div>
                </div>

                <div class="bl-card">
                    <div class="bl-card-head"><div><div class="bl-card-title">Insurance claims</div></div></div>
                    <div style="overflow-x:auto;">
                        <table class="claim-table">
                            <thead><tr><th>Patient</th><th>Insurer</th><th class="num">Amount</th><th class="num">Age</th><th>Status</th></tr></thead>
                            <tbody id="claims-body"></tbody>
                        </table>
                    </div>
                </div>

                <div class="bl-card">
                    <div class="bl-card-head">
                        <div><div class="bl-card-title">Pre-authorizations</div><div class="bl-card-sub">Requests, approvals and denials by payer</div></div>
                        @if ($canInvoice)<button type="button" class="bl-btn bl-btn-sm" id="btn-new-preauth">+ Request pre-auth</button>@endif
                    </div>
                    <div id="preauth-list"></div>
                </div>

                <div class="bl-card">
                    <div class="bl-card-title" style="margin-bottom:2px;">Revenue by payer · MTD</div>
                    <div id="revenue-by-payer" style="margin-top:12px;"></div>
                    @if ($rejectedAlert)
                        <div class="alert-box rejected">{{ $rejectedAlert['patient'] }}'s {{ $rejectedAlert['insurer'] }} claim ({{ $rejectedAlert['reference'] }}) was rejected — {{ $rejectedAlert['notes'] ?: 'resubmit with updated documentation' }}.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ============ TAB 2: Aging & statements ============ -->
    <div class="bl-tab-panel" id="tab-aging" style="display:none;">
        <div class="bl-tiles">
            <div class="bl-tile"><div class="bl-tile-label">Total outstanding</div><div class="bl-tile-value">AED {{ number_format($aging['total_outstanding'], 0) }}</div><div class="bar-sub">{{ $aging['open_count'] }} open invoices</div></div>
            <div class="bl-tile"><div class="bl-tile-label">Past due</div><div class="bl-tile-value danger">AED {{ number_format($aging['past_due'], 0) }}</div><div class="bar-sub">{{ $aging['past_due_count'] }} past the due date</div></div>
            <div class="bl-tile"><div class="bl-tile-label">Oldest item</div><div class="bl-tile-value">{{ $aging['oldest_days'] }} d</div><div class="bar-sub">{{ $aging['oldest_ref'] ?: '—' }}</div></div>
            <div class="bl-tile"><div class="bl-tile-label">Reminders sent</div><div class="bl-tile-value">{{ $aging['reminders_month'] }}</div><div class="bar-sub">this month</div></div>
        </div>

        <div class="bl-grid">
            <div>
                <div class="bl-card">
                    <div class="bl-card-head"><div><div class="bl-card-title">Who owes us money</div><div class="bl-card-sub">Open balances by age since the due date — as at {{ now()->format('d M Y') }}</div></div></div>
                    <div style="overflow-x:auto;">
                        <table class="aging-table">
                            <thead><tr><th>Invoice</th><th>Age</th><th class="num">Total</th><th class="num">Balance</th><th></th></tr></thead>
                            <tbody id="aging-body"></tbody>
                        </table>
                    </div>
                </div>
                <div class="bl-card">
                    <div class="bl-card-head"><div><div class="bl-card-title">Family statements</div><div class="bl-card-sub">Running balance across every invoice, credit note and payment</div></div></div>
                    <div style="overflow-x:auto;">
                        <table class="fam-table">
                            <thead><tr><th>Family</th><th class="num">Billed</th><th class="num">Balance</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($aging['families'] as $f)
                                    <tr>
                                        <td><strong>{{ $f['patient'] }}</strong><div style="color:#8A7D6C; font-weight:600;">{{ $f['parent'] }} · {{ $f['invoices'] }} invoice{{ $f['invoices'] === 1 ? '' : 's' }} · {{ $f['payer'] }}</div></td>
                                        <td class="num">AED {{ number_format($f['billed'], 2) }}</td>
                                        <td class="num" style="color: {{ $f['balance'] > 0 ? '#B3261E' : '#1E7A46' }};">AED {{ number_format($f['balance'], 2) }}</td>
                                        <td><a href="{{ $f['statement_url'] }}" target="_blank" class="bl-btn bl-btn-sm" style="text-decoration:none;">Open statement</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="bl-empty">No invoices raised yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div>
                <div class="bl-card">
                    <div class="bl-card-title" style="margin-bottom:2px;">Aging buckets</div>
                    <div class="bl-card-sub" style="margin-bottom:12px;">Balance by days past the due date</div>
                    @foreach ($aging['buckets'] as $b)
                        @php $colors = ['current'=>'#2E7D5B','d1_30'=>'#B97F24','d31_60'=>'#C8355F','d61_90'=>'#C8355F','d90'=>'#8A2020']; $max = max(1, collect($aging['buckets'])->max('amount')); @endphp
                        <div class="bar-row">
                            <div class="bar-top"><span>{{ $b['label'] }}</span><span>AED {{ number_format($b['amount'], 2) }}</span></div>
                            <div class="bar-track"><div class="bar-fill" style="width:{{ $b['amount'] > 0 ? max(3, $b['amount']/$max*100) : 0 }}%; background:{{ $colors[$b['key']] }};"></div></div>
                            <div class="bar-sub">{{ $b['count'] }} claim{{ $b['count'] === 1 ? '' : 's' }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="bl-card">
                    <div class="bl-card-title" style="margin-bottom:2px;">Outstanding by payer</div>
                    <div class="bl-card-sub" style="margin-bottom:12px;">Chase the insurer or chase the family</div>
                    @php $payerMax = max(1, $aging['by_payer']->max('amount') ?? 1); @endphp
                    @forelse ($aging['by_payer'] as $p)
                        <div class="bar-row">
                            <div class="bar-top"><span>{{ $p['payer'] }}</span><span>AED {{ number_format($p['amount'], 2) }}</span></div>
                            <div class="bar-track"><div class="bar-fill" style="width:{{ max(3, $p['amount']/$payerMax*100) }}%; background:#16436E;"></div></div>
                        </div>
                    @empty
                        <div class="bl-empty">Nothing outstanding — every issued invoice is settled.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- ============ TAB 3: Bulk run ============ -->
    <div class="bl-tab-panel" id="tab-bulk" style="display:none;">
        <div class="bl-card">
            <div class="bl-card-title">Bulk invoice run</div>
            <div class="bl-card-sub" style="margin-bottom:14px;">Every delivered session in the period that has not been invoiced yet, grouped by family. One invoice is raised per family.</div>
            <div class="f-row" style="align-items:flex-end; gap:12px; margin-bottom:14px; flex-wrap:wrap;">
                <div style="max-width:160px;"><label class="f-label" for="bulk-from">Period from</label><input type="date" id="bulk-from" class="f-input" value="{{ $bulkDefaults['from'] }}"></div>
                <div style="max-width:160px;"><label class="f-label" for="bulk-to">Period to</label><input type="date" id="bulk-to" class="f-input" value="{{ $bulkDefaults['to'] }}"></div>
                <div style="max-width:200px;"><label class="f-label" for="bulk-payer">Payer</label><select id="bulk-payer" class="f-select"><option value="all">All payers</option>@foreach ($payers as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach</select></div>
                <div style="margin-left:auto; display:flex; gap:8px;">
                    <button type="button" class="bl-btn" id="bulk-select-all">Select all</button>
                    @if ($canInvoice)<button type="button" class="bl-btn bl-btn-primary" id="bulk-issue" disabled>Issue invoices</button>@endif
                </div>
            </div>
            <div id="bulk-list"></div>
            <div class="bulk-footer" id="bulk-footer" style="display:none;"></div>
        </div>
    </div>
</div>

<!-- ============ New invoice: session picker ============ -->
<div id="picker-modal" class="cmodal-overlay">
    <div class="cmodal xwide">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
                <div class="cmodal-title" id="picker-title">New invoice</div>
                <div class="cmodal-sub" id="picker-sub">Pick the client, then the sessions to bill</div>
            </div>
            <button type="button" class="cx" data-close="picker-modal">✕</button>
        </div>
        <div class="f-error" id="picker-error"></div>

        <div id="picker-step-select">
            <label class="f-label" for="picker-patient">Client</label>
            <select id="picker-patient" class="f-select"><option value="">Select a client…</option></select>
            <div id="picker-prepaid" class="warn-box" style="margin-top:10px; display:none;"></div>

            <div class="picker-toolbar">
                <div class="picker-count" id="picker-count">No sessions selected</div>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="bl-btn bl-btn-sm" id="picker-select-unpaid">Select unpaid</button>
                    <button type="button" class="bl-btn bl-btn-sm" id="picker-select-all">Select all</button>
                    <button type="button" class="bl-btn bl-btn-sm" id="picker-clear">Clear</button>
                </div>
            </div>
            <div id="picker-sessions" style="max-height:46vh; overflow:auto;"></div>

            <div class="f-row" style="margin-top:14px;">
                <button type="button" class="btn-save" id="picker-preview" disabled>Preview invoice</button>
                <button type="button" class="btn-cancel" data-close="picker-modal">Cancel</button>
            </div>
        </div>

        <div id="picker-step-warn" style="display:none;">
            <div class="warn-box" id="warn-text"></div>
            <div class="f-row" style="margin-top:10px;">
                <button type="button" class="btn-save" id="warn-drop">Drop already-billed &amp; continue</button>
                <button type="button" class="btn-cancel" id="warn-back">Back</button>
            </div>
        </div>

        <div id="picker-step-preview" style="display:none;">
            <div id="preview-body"></div>
            <div class="f-row" style="margin-top:14px;">
                <button type="button" class="btn-save" id="preview-save">Save invoice</button>
                <button type="button" class="btn-save btn-dark" id="preview-send">Send invoice</button>
                <button type="button" class="btn-cancel" id="preview-back">Back</button>
            </div>
        </div>
    </div>
</div>

<!-- Record payment -->
<div id="payment-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Record payment</div><button type="button" class="cx" data-close="payment-modal">✕</button></div>
        <div class="cmodal-sub" id="pay-sub"></div>
        <div class="f-error" id="pay-error"></div>
        <div class="warn-box" id="pay-balance"></div>
        <div class="f-row">
            <div><label class="f-label" for="pay-amount">Amount (AED)</label><input type="number" step="0.01" min="0.01" id="pay-amount" class="f-input"></div>
            <div><label class="f-label" for="pay-method">Method</label><select id="pay-method" class="f-select"></select></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="pay-date">Date received</label><input type="date" id="pay-date" class="f-input"></div>
            <div><label class="f-label" for="pay-ref">Reference</label><input type="text" id="pay-ref" class="f-input" placeholder="e.g. FAB-77213"></div>
        </div>
        <div class="bar-sub">A numbered receipt is generated and attached to the invoice on save.</div>
        <div class="f-row"><button type="button" class="btn-save" id="pay-save">Save payment</button><button type="button" class="btn-cancel" data-close="payment-modal">Cancel</button></div>
    </div>
</div>

<!-- Credit note -->
<div id="credit-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Credit note</div><button type="button" class="cx" data-close="credit-modal">✕</button></div>
        <div class="cmodal-sub" id="credit-sub"></div>
        <div class="f-error" id="credit-error"></div>
        <div><label class="f-label" for="credit-amount">Amount (AED)</label><input type="number" step="0.01" min="0.01" id="credit-amount" class="f-input"></div>
        <div><label class="f-label" for="credit-reason">Reason *</label><input type="text" id="credit-reason" class="f-input" placeholder="e.g. wrong payer on lines 4-12"></div>
        <div class="f-row"><button type="button" class="btn-save" id="credit-save">Issue credit note</button><button type="button" class="btn-cancel" data-close="credit-modal">Cancel</button></div>
    </div>
</div>

<!-- Void / reissue -->
<div id="void-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Void invoice</div><button type="button" class="cx" data-close="void-modal">✕</button></div>
        <div class="cmodal-sub" id="void-sub"></div>
        <div class="danger-box">An issued tax invoice is never deleted. Voiding raises a credit note for the open balance of <strong id="void-open-amt"></strong>, marks this document Voided and leaves it in the ledger for audit.</div>
        <div class="f-error" id="void-error"></div>
        <div><label class="f-label" for="void-reason">Reason *</label><input type="text" id="void-reason" class="f-input" placeholder="e.g. wrong payer on lines 4-12"></div>
        <label style="display:flex; align-items:center; gap:8px; font:700 12.5px 'Nunito Sans'; color:#2B3A4C; cursor:pointer;">
            <input type="checkbox" id="void-reissue" checked style="accent-color:#C8355F;">
            <span>Reissue a corrected invoice<br><span style="font-weight:600; color:#8A7D6C;">Copies every hour line onto a fresh document number, linked back to this one. Payments and receipts stay on the original.</span></span>
        </label>
        <div class="f-row"><button type="button" class="btn-save" id="void-save" style="background:#B3261E;">Void and reissue</button><button type="button" class="btn-cancel" data-close="void-modal">Cancel</button></div>
    </div>
</div>

<!-- Send email (invoice or reminder) -->
<div id="email-modal" class="cmodal-overlay">
    <div class="cmodal wide">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title" id="email-title">Send invoice by email</div><button type="button" class="cx" data-close="email-modal">✕</button></div>
        <div class="cmodal-sub" id="email-sub"></div>
        <div class="f-error" id="email-error"></div>
        <div class="f-row">
            <div><label class="f-label" for="email-to">To *</label><input type="email" id="email-to" class="f-input"></div>
            <div><label class="f-label" for="email-cc">CC</label><input type="email" id="email-cc" class="f-input"></div>
        </div>
        <div><label class="f-label" for="email-subject">Subject</label><input type="text" id="email-subject" class="f-input"></div>
        <div><label class="f-label" for="email-message">Message</label><textarea id="email-message" rows="7" class="f-textarea"></textarea></div>
        <div class="bar-sub" id="email-attach"></div>
        <div class="f-row"><button type="button" class="btn-save" id="email-send">Send email</button><button type="button" class="btn-cancel" data-close="email-modal">Cancel</button></div>
    </div>
</div>

<!-- Request pre-auth -->
<div id="preauth-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title" id="preauth-title">Request pre-authorization</div><button type="button" class="cx" data-close="preauth-modal">✕</button></div>
        <div class="cmodal-sub">Submitted to the payer for approval before sessions are billed</div>
        <div class="f-error" id="preauth-error"></div>
        <div class="f-row">
            <div><label class="f-label" for="pa-patient">Client</label><select id="pa-patient" class="f-select"></select></div>
            <div><label class="f-label" for="pa-payer">Payer</label><select id="pa-payer" class="f-select"></select></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="pa-service">Service</label><select id="pa-service" class="f-select"></select></div>
            <div><label class="f-label" for="pa-hours">Hours</label><input type="number" min="1" id="pa-hours" class="f-input"></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="pa-from">Valid from</label><input type="date" id="pa-from" class="f-input"></div>
            <div><label class="f-label" for="pa-to">Valid to</label><input type="date" id="pa-to" class="f-input"></div>
        </div>
        <div><label class="f-label" for="pa-just">Clinical justification</label><input type="text" id="pa-just" class="f-input" placeholder="e.g. VB-MAPP level 2, September progress review attached"></div>
        <div class="f-row"><button type="button" class="btn-save" id="preauth-save">Submit request</button><button type="button" class="btn-cancel" data-close="preauth-modal">Cancel</button></div>
    </div>
</div>

<!-- Top up prepaid -->
<div id="topup-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Top up prepaid hours</div><button type="button" class="cx" data-close="topup-modal">✕</button></div>
        <div class="cmodal-sub" id="topup-sub"></div>
        <div class="f-error" id="topup-error"></div>
        <div><label class="f-label" for="topup-hours">Hours to add</label><input type="number" min="1" id="topup-hours" class="f-input" value="10"></div>
        <div class="f-row"><button type="button" class="btn-save" id="topup-save">Add hours</button><button type="button" class="btn-cancel" data-close="topup-modal">Cancel</button></div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
    const root = document.getElementById('bl-root');
    let INVOICES = JSON.parse(root.dataset.invoices);
    let CLAIMS = JSON.parse(root.dataset.claims);
    let PREAUTHS = JSON.parse(root.dataset.preauths);
    const PATIENTS = JSON.parse(root.dataset.patients);
    const PAYERS = JSON.parse(root.dataset.payers);
    const SERVICES = JSON.parse(root.dataset.services);
    const METHODS = JSON.parse(root.dataset.methods);
    const CLAIM_STATUSES = JSON.parse(root.dataset.claimStatuses);
    const AGING_ROWS = JSON.parse(root.dataset.aging).rows;
    const POLICY = JSON.parse(root.dataset.cancelPolicy);
    const BULK_DEFAULTS = JSON.parse(root.dataset.bulkDefaults);
    const CAN_INVOICE = root.dataset.canInvoice === '1';
    const BASE = root.dataset.baseUrl;
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    const STATUS_COLORS = { paid: ['#E3F1E9', '#1E7A46'], partly_paid: ['#F7EEDD', '#B97F24'], outstanding: ['#F9E4E2', '#B3261E'], voided: ['#EEEEEE', '#6B6B6B'] };
    const CLAIM_COLORS = { draft: ['#F1EDE5', '#8A7D6C'], submitted: ['#E7EFF7', '#24619C'], pending_info: ['#F7EEDD', '#B97F24'], rejected: ['#F9E4E2', '#B3261E'], settled: ['#E3F1E9', '#1E7A46'] };
    const PA_COLORS = { requested: ['#E7EFF7', '#24619C'], approved: ['#E3F1E9', '#1E7A46'], denied: ['#F9E4E2', '#B3261E'] };
    const PAYER_PALETTE = ['#C8355F', '#16436E', '#B97F24', '#6E4FA8', '#1F8FA8', '#2E7D5B'];

    function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function money(n) { return (Number(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    let toastTimer;
    function toast(msg) { const el = document.getElementById('toast'); el.textContent = msg; el.style.display = 'block'; clearTimeout(toastTimer); toastTimer = setTimeout(() => el.style.display = 'none', 3800); }
    async function api(url, options = {}) {
        const res = await fetch(url, { ...options, headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}) } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok && res.status !== 409) { const e = new Error(data.message || 'Request failed'); e.errors = data.errors || {}; e.status = res.status; throw e; }
        return { ...data, __status: res.status };
    }
    function showError(id, e) { const box = document.getElementById(id); box.textContent = Object.values(e.errors || {})[0]?.[0] || e.message; box.style.display = 'block'; }
    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => closeModal(b.dataset.close)));
    document.querySelectorAll('.cmodal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }));

    // ---- Tabs ----
    document.querySelectorAll('.bl-tab').forEach(btn => btn.addEventListener('click', () => {
        document.querySelectorAll('.bl-tab').forEach(b => b.classList.toggle('active', b === btn));
        document.querySelectorAll('.bl-tab-panel').forEach(p => p.style.display = 'none');
        document.getElementById('tab-' + btn.dataset.tab).style.display = '';
        if (btn.dataset.tab === 'bulk') loadBulk();
    }));

    // ================= Invoice list =================
    const INVOICES_PER_PAGE = 10;
    let invoicePage = 1;

    function renderInvoices() {
        const list = document.getElementById('invoice-list');
        const pager = document.getElementById('invoice-pagination');
        if (!INVOICES.length) { list.innerHTML = '<tr><td colspan="6" class="bl-empty">No invoices raised yet.</td></tr>'; pager.innerHTML = ''; return; }

        const pageCount = Math.max(1, Math.ceil(INVOICES.length / INVOICES_PER_PAGE));
        if (invoicePage > pageCount) invoicePage = pageCount;
        const start = (invoicePage - 1) * INVOICES_PER_PAGE;
        const pageItems = INVOICES.slice(start, start + INVOICES_PER_PAGE);

        list.innerHTML = pageItems.map(i => {
            const [bg, fg] = STATUS_COLORS[i.status] || STATUS_COLORS.outstanding;
            const receiptLines = i.receipts.map(r => `<div class="inv-meta receipt">${esc(r.number)} → AED ${money(r.amount)} · ${esc(r.method)} · ${esc(r.date)}${r.reference ? ' · ' + esc(r.reference) : ''}</div>`).join('');
            let metaLine = '';
            if (i.voided) metaLine = `<div class="inv-meta voided">Voided ${esc(i.voided_on)} — ${esc(i.void_reason)}${i.replaced_by ? ' · reissued as ' + esc(i.replaced_by) : ''}</div>`;
            else if (i.credit > 0) metaLine = `<div class="inv-meta voided">Credit note AED ${money(i.credit)} — ${esc((i.credit_reason || '').split('\n').pop())}</div>`;
            const replacesLine = i.replaces ? `<div class="inv-meta link">Replaces voided invoice ${esc(i.replaces)}</div>` : '';
            const actions = i.voided ? `<a href="${i.print_url}" target="_blank" class="act-btn">Open PDF</a>` : `
                <select class="act-dropdown" data-id="${i.id}">
                    <option value="">Actions…</option>
                    ${CAN_INVOICE && i.balance > 0.01 ? `<option value="pay">Record payment</option>` : ''}
                    ${CAN_INVOICE ? `<option value="credit">Credit note</option>` : ''}
                    <option value="pdf">Open PDF</option>
                    <option value="email">Send by email</option>
                    ${CAN_INVOICE ? `<option value="void">Void / reissue</option>` : ''}
                </select>`;

            return `<tr data-id="${i.id}">
                <td data-label="Patient" style="text-align:left;">
                    <div class="inv-name">${esc(i.patient)}</div>
                    <div class="inv-meta">${esc(i.number)} · ${esc(i.payer)} · due ${esc(i.due_label)}</div>
                    ${receiptLines}
                    ${metaLine}${replacesLine}
                </td>
                <td data-label="Total">${money(i.total)}</td>
                <td data-label="Paid" style="color:#1E7A46;">${money(i.paid)}</td>
                <td data-label="Balance" style="color:${i.balance > 0.01 ? '#B3261E' : '#1E7A46'};">${money(i.balance)}</td>
                <td data-label="Status"><span class="inv-status" style="background:${bg}; color:${fg};">${esc(i.status_label)}</span></td>
                <td data-label="Actions"><div class="inv-actions">${actions}</div></td>
            </tr>`;
        }).join('');

        list.querySelectorAll('.act-dropdown').forEach(sel => sel.addEventListener('change', () => {
            const act = sel.value;
            sel.value = '';
            if (!act) return;
            const inv = INVOICES.find(i => i.id === Number(sel.dataset.id));
            ({
                pay: openPayment,
                credit: openCredit,
                void: openVoid,
                email: () => openEmail(inv, 'invoice'),
                pdf: () => window.open(inv.print_url, '_blank'),
            })[act](inv);
        }));

        pager.innerHTML = `
            <div class="inv-pagination-info">Showing ${start + 1}–${Math.min(start + INVOICES_PER_PAGE, INVOICES.length)} of ${INVOICES.length}</div>
            <div class="inv-pagination-nav">
                <button type="button" class="bl-btn bl-btn-sm" id="inv-page-prev" ${invoicePage <= 1 ? 'disabled' : ''}>‹ Prev</button>
                <div class="inv-pagination-info" style="align-self:center;">Page ${invoicePage} of ${pageCount}</div>
                <button type="button" class="bl-btn bl-btn-sm" id="inv-page-next" ${invoicePage >= pageCount ? 'disabled' : ''}>Next ›</button>
            </div>`;
        document.getElementById('inv-page-prev').addEventListener('click', () => { invoicePage--; renderInvoices(); });
        document.getElementById('inv-page-next').addEventListener('click', () => { invoicePage++; renderInvoices(); });
    }

    function replaceInvoice(row) {
        const i = INVOICES.findIndex(x => x.id === row.id);
        if (i >= 0) INVOICES[i] = row; else INVOICES.unshift(row);
        renderInvoices();
    }
    function addInvoices(rows) { INVOICES = [...rows, ...INVOICES]; invoicePage = 1; renderInvoices(); }

    // ---- Record payment ----
    let payTarget = null;
    function openPayment(inv) {
        payTarget = inv;
        document.getElementById('pay-error').style.display = 'none';
        document.getElementById('pay-sub').textContent = `${inv.number} · ${inv.patient} · ${inv.payer}`;
        document.getElementById('pay-balance').textContent = `Balance outstanding — AED ${money(inv.balance)}`;
        document.getElementById('pay-amount').value = inv.balance.toFixed(2);
        document.getElementById('pay-method').innerHTML = METHODS.map(m => `<option>${esc(m)}</option>`).join('');
        document.getElementById('pay-date').value = new Date().toISOString().slice(0, 10);
        document.getElementById('pay-ref').value = '';
        openModal('payment-modal');
    }
    document.getElementById('pay-save').addEventListener('click', async () => {
        const err = document.getElementById('pay-error'); err.style.display = 'none';
        try {
            const r = await api(`${BASE}/invoices/${payTarget.id}/payments`, { method: 'POST', body: JSON.stringify({
                amount: document.getElementById('pay-amount').value, method: document.getElementById('pay-method').value,
                received_on: document.getElementById('pay-date').value, reference: document.getElementById('pay-ref').value || null,
            }) });
            replaceInvoice(r.invoice); closeModal('payment-modal'); toast(r.message);
        } catch (e) { showError('pay-error', e); }
    });

    // ---- Credit note ----
    let creditTarget = null;
    function openCredit(inv) {
        creditTarget = inv;
        document.getElementById('credit-error').style.display = 'none';
        document.getElementById('credit-sub').textContent = `${inv.number} · balance AED ${money(inv.balance)}`;
        document.getElementById('credit-amount').value = '';
        document.getElementById('credit-reason').value = '';
        openModal('credit-modal');
    }
    document.getElementById('credit-save').addEventListener('click', async () => {
        const err = document.getElementById('credit-error'); err.style.display = 'none';
        try {
            const r = await api(`${BASE}/invoices/${creditTarget.id}/credit`, { method: 'POST', body: JSON.stringify({ amount: document.getElementById('credit-amount').value, reason: document.getElementById('credit-reason').value }) });
            replaceInvoice(r.invoice); closeModal('credit-modal'); toast(r.message);
        } catch (e) { showError('credit-error', e); }
    });

    // ---- Void / reissue ----
    let voidTarget = null;
    function openVoid(inv) {
        voidTarget = inv;
        document.getElementById('void-error').style.display = 'none';
        document.getElementById('void-sub').textContent = `${inv.number} · ${inv.patient} · ${inv.period}`;
        document.getElementById('void-open-amt').textContent = `AED ${money(inv.balance)}`;
        document.getElementById('void-reason').value = '';
        document.getElementById('void-reissue').checked = true;
        openModal('void-modal');
    }
    document.getElementById('void-save').addEventListener('click', async () => {
        const err = document.getElementById('void-error'); err.style.display = 'none';
        try {
            const r = await api(`${BASE}/invoices/${voidTarget.id}/void`, { method: 'POST', body: JSON.stringify({ reason: document.getElementById('void-reason').value, reissue: document.getElementById('void-reissue').checked }) });
            replaceInvoice(r.invoice);
            if (r.reissued) replaceInvoice(r.reissued);
            closeModal('void-modal'); toast(r.message);
        } catch (e) { showError('void-error', e); }
    });

    // ---- Send email (invoice or reminder) ----
    let emailTarget = null, emailKind = 'invoice';
    function openEmail(inv, kind) {
        emailTarget = inv; emailKind = kind;
        document.getElementById('email-error').style.display = 'none';
        document.getElementById('email-title').textContent = kind === 'reminder' ? 'Send invoice by email' : 'Send invoice by email';
        document.getElementById('email-sub').innerHTML = `${esc(inv.number)} · the PDF is attached automatically`;
        document.getElementById('email-to').value = inv.parent_email || '';
        document.getElementById('email-cc').value = '{{ $clinic["billing_email"] }}';
        if (kind === 'reminder') {
            document.getElementById('email-subject').value = `Payment reminder — tax invoice ${inv.number} (${inv.patient})`;
            document.getElementById('email-message').value = `Dear ${inv.parent},\n\nOur records show tax invoice ${inv.number} for ${inv.patient} remains open with a balance of AED ${money(inv.balance)}. It fell due on ${inv.due_label} — ${Math.max(0, inv.days_past_due)} days ago.\n\nIf payment has already been sent, please share the transfer slip so we can close the invoice. Bank details are on the invoice.\n\nWith thanks,\n{{ $clinic["name"] }}`;
        } else {
            document.getElementById('email-subject').value = `Tax invoice ${inv.number} — ${inv.patient} (${inv.period})`;
            document.getElementById('email-message').value = `Dear ${inv.parent},\n\nPlease find attached tax invoice ${inv.number} for ${inv.patient}'s sessions in ${inv.period}.\n\nAmount due: AED ${money(inv.balance)}, payable by ${inv.due_label}.\n\nWith thanks,\n{{ $clinic["name"] }}`;
        }
        document.getElementById('email-attach').textContent = `PDF · ${inv.number}.pdf · tax invoice`;
        openModal('email-modal');
    }
    document.getElementById('email-send').addEventListener('click', async () => {
        const err = document.getElementById('email-error'); err.style.display = 'none';
        const btn = document.getElementById('email-send');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Sending…';
        try {
            const r = await api(`${BASE}/invoices/${emailTarget.id}/send`, { method: 'POST', body: JSON.stringify({
                to: document.getElementById('email-to').value, cc: document.getElementById('email-cc').value || null,
                subject: document.getElementById('email-subject').value, message: document.getElementById('email-message').value, kind: emailKind,
            }) });
            replaceInvoice(r.invoice); closeModal('email-modal'); toast(r.message);
        } catch (e) {
            showError('email-error', e);
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });

    // ================= Claims =================
    function renderClaims() {
        const body = document.getElementById('claims-body');
        if (!CLAIMS.length) { body.innerHTML = '<tr><td colspan="5" class="bl-empty">No claims submitted yet.</td></tr>'; return; }
        body.innerHTML = CLAIMS.map(c => {
            const [bg, fg] = CLAIM_COLORS[c.status];
            return `<tr data-id="${c.id}">
                <td><strong>${esc(c.patient)}</strong><div style="color:#8A7D6C; font-weight:600;">${esc(c.reference)}${c.period ? ' · ' + esc(c.period) : ''}</div></td>
                <td>${esc(c.insurer)}</td>
                <td class="num">AED ${money(c.amount)}</td>
                <td class="num" style="color:${c.age > 30 && c.open ? '#B3261E' : '#2B3A4C'};">${c.open ? c.age + 'd' : 'closed'}</td>
                <td><select class="status-sel" data-id="${c.id}" style="background:${bg}; color:${fg};">${Object.entries(CLAIM_STATUSES).map(([k, l]) => `<option value="${k}" ${k === c.status ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select></td>
            </tr>`;
        }).join('');
        body.querySelectorAll('.status-sel').forEach(sel => sel.addEventListener('change', async () => {
            const c = CLAIMS.find(x => x.id === Number(sel.dataset.id));
            try {
                const r = await api(`${BASE}/claims/${c.id}`, { method: 'PATCH', body: JSON.stringify({ status: sel.value }) });
                const idx = CLAIMS.findIndex(x => x.id === c.id); CLAIMS[idx] = r.claim; renderClaims();
                if (r.invoice) replaceInvoice(r.invoice);
                toast(r.message);
            } catch (e) { toast(e.message); renderClaims(); }
        }));
    }

    function renderClaimAging() {
        const box = document.getElementById('claim-aging-bars');
        const buckets = [{ label: '0–14 days', min: 0, max: 14 }, { label: '15–30 days', min: 15, max: 30 }, { label: '31–60 days', min: 31, max: 60 }, { label: '60+ days', min: 61, max: null }];
        const open = CLAIMS.filter(c => c.open);
        const rows = buckets.map(b => { const in_ = open.filter(c => c.age >= b.min && (b.max === null || c.age <= b.max)); return { ...b, count: in_.length, amount: in_.reduce((s, c) => s + c.amount, 0) }; });
        const max = Math.max(1, ...rows.map(r => r.amount));
        const colors = ['#2E7D5B', '#B97F24', '#C8355F', '#8A2020'];
        box.innerHTML = rows.map((r, i) => `<div class="bar-row"><div class="bar-top"><span>${r.label}</span><span>AED ${money(r.amount)}</span></div><div class="bar-track"><div class="bar-fill" style="width:${r.amount > 0 ? Math.max(3, r.amount / max * 100) : 0}%; background:${colors[i]};"></div></div><div class="bar-sub">${r.count} claim${r.count === 1 ? '' : 's'}</div></div>`).join('');
    }

    function renderRevenueByPayer() {
        const box = document.getElementById('revenue-by-payer');
        const rows = @json($revenueByPayer);
        box.innerHTML = rows.map((r, i) => `<div class="bar-row"><div class="bar-top"><span><span class="payer-dot" style="background:${PAYER_PALETTE[i % PAYER_PALETTE.length]};"></span>${esc(r.payer)}</span><span>${r.pct}%</span></div><div class="bar-track"><div class="bar-fill" style="width:${r.pct}%; background:${PAYER_PALETTE[i % PAYER_PALETTE.length]};"></div></div></div>`).join('') || '<div class="bl-empty">No revenue recorded this month yet.</div>';
    }

    // ================= Pre-authorizations =================
    function renderPreauths() {
        const box = document.getElementById('preauth-list');
        if (!PREAUTHS.length) { box.innerHTML = '<div class="bl-empty">No pre-authorization requests on file.</div>'; return; }
        box.innerHTML = PREAUTHS.map(p => {
            const [bg, fg] = PA_COLORS[p.status];
            return `<div class="inv-row" style="grid-template-columns:1fr auto;" data-id="${p.id}">
                <div>
                    <div class="inv-name">${esc(p.patient)} — ${esc(p.service)}</div>
                    <div class="inv-meta">${esc(p.payer)} · ${p.hours} h · ${esc(p.from)} → ${esc(p.to)} · submitted ${esc(p.submitted)}</div>
                    <div class="inv-meta">Ref ${esc(p.reference)}${p.payer_reference ? ' · payer ref ' + esc(p.payer_reference) : ''}</div>
                    ${p.status === 'denied' && p.denial_reason ? `<div class="inv-meta voided">${esc(p.denial_reason)}</div>` : ''}
                </div>
                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                    <span class="inv-status" style="background:${bg}; color:${fg};">${esc(p.status_label)}</span>
                    ${CAN_INVOICE && p.status === 'denied' ? `<button type="button" class="act-btn" data-resubmit="${p.id}">Resubmit</button>` : ''}
                </div>
            </div>`;
        }).join('');
        box.querySelectorAll('[data-resubmit]').forEach(btn => btn.addEventListener('click', () => {
            const p = PREAUTHS.find(x => x.id === Number(btn.dataset.resubmit));
            openPreauth(p);
        }));
    }

    function openPreauth(from = null) {
        document.getElementById('preauth-error').style.display = 'none';
        document.getElementById('preauth-title').textContent = from ? 'Resubmit pre-authorization' : 'Request pre-authorization';
        document.getElementById('pa-patient').innerHTML = PATIENTS.map(p => `<option value="${p.id}">${esc(p.name)}</option>`).join('');
        document.getElementById('pa-payer').innerHTML = PAYERS.map(p => `<option>${esc(p)}</option>`).join('');
        document.getElementById('pa-service').innerHTML = SERVICES.map(s => `<option>${esc(s)}</option>`).join('');
        if (from) {
            document.getElementById('pa-patient').value = from.patient_id;
            document.getElementById('pa-payer').value = from.payer;
            document.getElementById('pa-service').value = from.service;
            document.getElementById('pa-hours').value = from.hours;
            document.getElementById('pa-from').value = from.from_iso;
            document.getElementById('pa-to').value = from.to_iso;
            document.getElementById('pa-just').value = from.justification || '';
        } else {
            document.getElementById('pa-hours').value = 40;
            document.getElementById('pa-from').value = new Date().toISOString().slice(0, 10);
            document.getElementById('pa-to').value = new Date(Date.now() + 180 * 86400000).toISOString().slice(0, 10);
            document.getElementById('pa-just').value = '';
        }
        preauthResubmitFrom = from?.id ?? null;
        openModal('preauth-modal');
    }
    let preauthResubmitFrom = null;
    document.getElementById('btn-new-preauth')?.addEventListener('click', () => openPreauth());
    document.getElementById('preauth-save').addEventListener('click', async () => {
        const err = document.getElementById('preauth-error'); err.style.display = 'none';
        try {
            const r = await api(`${BASE}/pre-authorizations`, { method: 'POST', body: JSON.stringify({
                patient_id: document.getElementById('pa-patient').value, payer: document.getElementById('pa-payer').value,
                service: document.getElementById('pa-service').value, hours: document.getElementById('pa-hours').value,
                valid_from: document.getElementById('pa-from').value, valid_to: document.getElementById('pa-to').value,
                justification: document.getElementById('pa-just').value || null, resubmitted_from_id: preauthResubmitFrom,
            }) });
            PREAUTHS.unshift(r.preauth); renderPreauths(); closeModal('preauth-modal'); toast(r.message);
        } catch (e) { showError('preauth-error', e); }
    });

    // ================= Aging & statements tab =================
    function renderAgingRows() {
        const body = document.getElementById('aging-body');
        if (!AGING_ROWS.length) { body.innerHTML = '<tr><td colspan="5" class="bl-empty">Nothing outstanding — every issued invoice is settled.</td></tr>'; return; }
        body.innerHTML = AGING_ROWS.map(i => {
            const overdue = i.days_past_due > 0;
            return `<tr data-id="${i.id}">
                <td><strong>${esc(i.patient)}</strong><div style="color:#8A7D6C; font-weight:600;">${esc(i.number)} · ${esc(i.payer)} · due ${esc(i.due_label)}</div>${i.reminder_sent_at ? `<div style="color:#24619C; font-weight:700;">Reminder sent ${esc(i.reminder_sent_at)}</div>` : ''}</td>
                <td><span class="age-chip" style="background:${overdue ? '#F9E4E2' : '#E3F1E9'}; color:${overdue ? '#B3261E' : '#1E7A46'};">${esc(i.age_label)}</span></td>
                <td class="num">${money(i.total)}</td>
                <td class="num" style="color:#B3261E;">${money(i.balance)}</td>
                <td><button type="button" class="bl-btn bl-btn-sm bl-btn-dark" data-remind="${i.id}">Send reminder</button></td>
            </tr>`;
        }).join('');
        body.querySelectorAll('[data-remind]').forEach(btn => btn.addEventListener('click', () => {
            const inv = INVOICES.find(x => x.id === Number(btn.dataset.remind)) || AGING_ROWS.find(x => x.id === Number(btn.dataset.remind));
            openEmail(inv, 'reminder');
        }));
    }

    // ================= New invoice: session picker =================
    const pickerPatientSel = document.getElementById('picker-patient');
    let currentLedger = null, currentPatient = null, pendingSettledIds = [];

    document.getElementById('btn-new-invoice')?.addEventListener('click', () => {
        document.getElementById('picker-error').style.display = 'none';
        document.getElementById('picker-title').textContent = 'New invoice';
        document.getElementById('picker-sub').textContent = 'Pick the client, then the sessions to bill';
        pickerPatientSel.innerHTML = '<option value="">Select a client…</option>' + PATIENTS.map(p => `<option value="${p.id}">${esc(p.name)}</option>`).join('');
        document.getElementById('picker-sessions').innerHTML = '';
        document.getElementById('picker-prepaid').style.display = 'none';
        setPickerCount();
        document.getElementById('picker-preview').disabled = true;
        showPickerStep('select');
        openModal('picker-modal');
    });
    pickerPatientSel.addEventListener('change', () => loadLedger(pickerPatientSel.value));

    async function loadLedger(patientId) {
        const box = document.getElementById('picker-sessions');
        if (!patientId) { box.innerHTML = ''; return; }
        box.innerHTML = '<div class="bl-empty">Loading sessions…</div>';
        try {
            const data = await api(`${BASE}/patients/${patientId}/ledger`);
            currentLedger = data.rows; currentPatient = data.patient;
            renderSessionRows();
            const prepaidBox = document.getElementById('picker-prepaid');
            if (data.patient.prepaid) {
                prepaidBox.style.display = 'block';
                prepaidBox.innerHTML = `Prepaid package — ${data.prepaid_left} h left of ${data.patient.prepaid.total} (${data.prepaid_used} h drawn by completed sessions). These sessions draw on the prepaid balance. <button type="button" class="bl-btn bl-btn-sm" style="margin-left:8px;" id="btn-topup">+ Top up prepaid hours</button>`;
                document.getElementById('btn-topup').addEventListener('click', () => openTopup(data.patient));
            } else prepaidBox.style.display = 'none';
        } catch (e) { box.innerHTML = `<div class="bl-empty">${esc(e.message)}</div>`; }
    }

    const ATT_OPTIONS = [['completed', 'Completed'], ['no_show', 'No-show'], ['cancelled_late', 'Cancelled — late'], ['cancelled_notice', 'Cancelled — with notice'], ['cancelled_clinic', 'Cancelled — clinic']];
    function renderSessionRows() {
        const box = document.getElementById('picker-sessions');
        if (!currentLedger.length) { box.innerHTML = '<div class="bl-empty">No delivered sessions on file for this client yet.</div>'; setPickerCount(); return; }
        box.innerHTML = currentLedger.map(r => `
            <div class="sess-row ${r.invoiced ? 'invoiced' : ''}" data-id="${r.id}">
                <input type="checkbox" data-check="${r.id}" ${r.invoiced ? '' : ''}>
                <div class="sess-main">
                    <div class="sess-top"><span>${esc(r.service_label)} — 1:1 session</span><span>${esc(r.date_label)} · ${esc(r.start_time)}–${esc(r.end_time)}</span></div>
                    <div class="sess-meta">${esc(r.setting)} by ${esc(r.therapist_name)}${r.trainee_note ? ' (' + esc(r.trainee_note) + ')' : ''} · ${esc(r.payer)} ${r.coverage_pct}%${r.invoiced ? ' · ' + esc(r.invoice_number) : ''}${r.insufficient_authorization ? ' · <strong style="color:#8A5A10;">' + esc(r.insufficient_message) + '</strong>' : ''}</div>
                    <select class="att-select" data-att="${r.id}">${ATT_OPTIONS.map(([k, l]) => `<option value="${k}" ${k === r.attendance ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select>
                    <span class="sess-rule" style="color:${r.bill_hours > 0 ? '#5A6B7E' : '#98897A'};">${esc(r.charge_rule)}</span>
                </div>
                <div class="sess-amount">${money(r.gross)}<div class="sess-invoiced-tag">${esc(r.billing_status)}</div></div>
            </div>`).join('');

        box.querySelectorAll('[data-check]').forEach(cb => cb.addEventListener('change', setPickerCount));
        box.querySelectorAll('[data-att]').forEach(sel => sel.addEventListener('change', () => {
            const row = currentLedger.find(r => r.id === Number(sel.dataset.att));
            const rule = { completed: 'Attended — 100% charged', no_show: `No-show — ${POLICY.no_show_pct}% charged`, cancelled_late: `Cancelled late — ${POLICY.late_pct}% charged`, cancelled_notice: 'Cancelled with notice — not charged', cancelled_clinic: 'Cancelled by clinic — not charged' }[sel.value];
            row.attendance = sel.value;
            sel.closest('.sess-main').querySelector('.sess-rule').textContent = rule;
        }));
    }
    function setPickerCount() {
        const ids = [...document.querySelectorAll('[data-check]:checked')].map(c => Number(c.dataset.check));
        const rows = (currentLedger || []).filter(r => ids.includes(r.id));
        const hours = rows.reduce((s, r) => s + r.bill_hours, 0);
        document.getElementById('picker-count').textContent = ids.length ? `${ids.length} sessions · ${hours} h billable selected` : 'No sessions selected';
        document.getElementById('picker-preview').disabled = ids.length === 0;
    }
    document.getElementById('picker-select-unpaid')?.addEventListener('click', () => { document.querySelectorAll('[data-check]').forEach(c => { const r = currentLedger.find(x => x.id === Number(c.dataset.check)); c.checked = !r.invoiced; }); setPickerCount(); });
    document.getElementById('picker-select-all')?.addEventListener('click', () => { document.querySelectorAll('[data-check]').forEach(c => c.checked = true); setPickerCount(); });
    document.getElementById('picker-clear')?.addEventListener('click', () => { document.querySelectorAll('[data-check]').forEach(c => c.checked = false); setPickerCount(); });

    function showPickerStep(step) {
        ['select', 'warn', 'preview'].forEach(s => document.getElementById('picker-step-' + s).style.display = s === step ? '' : 'none');
    }
    function selectedIds() { return [...document.querySelectorAll('[data-check]:checked')].map(c => Number(c.dataset.check)); }
    function attendancePayload() {
        const out = {};
        currentLedger.forEach(r => { out[r.id] = { state: r.attendance }; });
        return out;
    }

    let lastPreview = null;
    async function doPreview(acknowledgeSettled) {
        const err = document.getElementById('picker-error'); err.style.display = 'none';
        try {
            const body = { patient_id: pickerPatientSel.value, session_ids: selectedIds(), attendance: attendancePayload() };
            const data = await api(`${BASE}/invoices/preview`, { method: 'POST', body: JSON.stringify(body) });
            lastPreview = data;
            renderPreview(data);
            showPickerStep('preview');
        } catch (e) { showError('picker-error', e); }
    }
    document.getElementById('picker-preview').addEventListener('click', () => doPreview(false));
    document.getElementById('warn-back').addEventListener('click', () => showPickerStep('select'));
    document.getElementById('warn-drop').addEventListener('click', () => {
        pendingSettledIds.forEach(id => { const cb = document.querySelector(`[data-check="${id}"]`); if (cb) cb.checked = false; });
        setPickerCount();
        doPreview(true);
    });
    document.getElementById('preview-back').addEventListener('click', () => showPickerStep('select'));

    function renderPreview(p) {
        const box = document.getElementById('preview-body');
        const lines = p.lines.map(l => `<tr><td>${l.line_no}</td><td>${esc(l.description)}<div class="bar-sub">${esc(l.note)}${l.exceeds_authorization ? ' · <strong style="color:#8A5A10;">Exceeds authorization — billed to family</strong>' : ''}</div></td><td>${esc(l.from_label)}</td><td>${esc(l.to_label)}</td><td class="num">1.00</td><td class="num">${money(l.rate)}</td><td class="num">${money(l.amount)}</td><td class="num">${money(l.vat_amount)}</td><td class="num">${money(l.total)}</td></tr>`).join('');
        const splits = p.splits.map(s => `<div class="inv-meta">${esc(s.payer)} — ${s.pct}% · AED ${money(s.amount)}</div>`).join('');
        const authWarnings = (p.warnings || []).length
            ? `<div class="warn-box" style="margin-bottom:12px;">Authorization insufficient — ${p.warnings.map(esc).join('; ')}. The excess is billed to the family on this invoice, not to insurance.</div>`
            : '';
        box.innerHTML = `
            <div class="cmodal-sub" style="margin:0 0 10px;">${esc(p.invoice_number)} · issue ${esc(p.issue_date)} · due ${esc(p.due_date)}${p.settled_count ? ` · <span style="color:#B3261E;">${p.settled_count} already billed</span>` : ''}</div>
            ${authWarnings}
            <div style="overflow-x:auto; max-height:32vh;"><table class="claim-table"><thead><tr><th>#</th><th>Item &amp; description</th><th>From</th><th>To</th><th class="num">Qty</th><th class="num">Rate</th><th class="num">Amount</th><th class="num">Tax</th><th class="num">Total</th></tr></thead><tbody>${lines}</tbody></table></div>
            <div class="f-row" style="margin-top:12px;">
                <div class="bl-card" style="margin:0; flex:1;">
                    <div class="bar-row"><div class="bar-top"><span>Subtotal — VAT excluded</span><span>AED ${money(p.net)}</span></div></div>
                    <div class="bar-row"><div class="bar-top"><span>VAT 5%</span><span>AED ${money(p.vat)}</span></div></div>
                    <div class="bar-row"><div class="bar-top"><strong>Invoice total</strong><strong>AED ${money(p.total)}</strong></div></div>
                    ${p.insurer_share > 0 ? `<div class="bar-row"><div class="bar-top" style="color:#1E7A46;"><span>Insurance coverage</span><span>– AED ${money(p.insurer_share)}</span></div>${splits}</div>` : ''}
                    <div class="warn-box" style="margin-top:6px;"><strong>Amount due — AED ${money(p.amount_due)}</strong></div>
                </div>
            </div>`;
    }

    document.getElementById('preview-save').addEventListener('click', () => submitInvoice(false));
    document.getElementById('preview-send').addEventListener('click', () => submitInvoice(true));
    async function submitInvoice(thenSend) {
        const err = document.getElementById('picker-error'); err.style.display = 'none';
        try {
            const body = { patient_id: pickerPatientSel.value, session_ids: selectedIds(), attendance: attendancePayload(), acknowledge_settled: true };
            const r = await api(`${BASE}/invoices`, { method: 'POST', body: JSON.stringify(body) });
            addInvoices([r.invoice]);
            closeModal('picker-modal'); toast(r.message);
            if (thenSend) openEmail(r.invoice, 'invoice');
        } catch (e) {
            if (e.status === 409) { pendingSettledIds = e.settled_ids || []; document.getElementById('warn-text').textContent = e.message + ' Drop them and bill only the rest, or go back and adjust your selection.'; showPickerStep('warn'); }
            else showError('picker-error', e);
        }
    }

    // ---- Top up prepaid ----
    let topupPatient = null;
    function openTopup(patient) {
        topupPatient = patient;
        document.getElementById('topup-error').style.display = 'none';
        document.getElementById('topup-sub').textContent = patient.name;
        document.getElementById('topup-hours').value = 10;
        openModal('topup-modal');
    }
    document.getElementById('topup-save').addEventListener('click', async () => {
        const err = document.getElementById('topup-error'); err.style.display = 'none';
        try {
            const r = await api(`${BASE}/patients/${topupPatient.id}/top-up`, { method: 'POST', body: JSON.stringify({ hours: document.getElementById('topup-hours').value }) });
            closeModal('topup-modal'); toast(r.message);
            loadLedger(topupPatient.id);
        } catch (e) { showError('topup-error', e); }
    });

    // ================= Bulk run =================
    let bulkGroups = [];
    async function loadBulk() {
        const box = document.getElementById('bulk-list');
        box.innerHTML = '<div class="bl-empty">Loading…</div>';
        const params = new URLSearchParams({ from: document.getElementById('bulk-from').value, to: document.getElementById('bulk-to').value, payer: document.getElementById('bulk-payer').value });
        try {
            const data = await api(`${BASE}/bulk-run?${params}`);
            bulkGroups = data.groups;
            renderBulk();
        } catch (e) { box.innerHTML = `<div class="bl-empty">${esc(e.message)}</div>`; }
    }
    function renderBulk() {
        const box = document.getElementById('bulk-list');
        if (!bulkGroups.length) { box.innerHTML = '<div class="bl-empty">Nothing to invoice — every session in this period has already been billed.</div>'; updateBulkFooter(); return; }
        box.innerHTML = bulkGroups.map(g => `
            <div class="bulk-row" data-pid="${g.patient_id}">
                <input type="checkbox" data-bulk-check="${g.patient_id}">
                <div class="bulk-main">
                    <div class="bulk-name">${esc(g.patient)}</div>
                    <div class="bulk-meta">${esc(g.parent)} · ${esc(g.payer)} · insurer share AED ${money(g.insurer_share)} · family AED ${money(g.family_share)}${g.adjusted ? ` · <span style="color:#B97F24;">${g.adjusted} session(s) adjusted by cancellation policy</span>` : ''}</div>
                </div>
                <div class="bulk-nums">
                    <div class="n"><div class="l">Sessions</div><div class="v">${g.sessions}</div></div>
                    <div class="n"><div class="l">Hours</div><div class="v">${g.hours}</div></div>
                    <div class="n"><div class="l">Net</div><div class="v">${money(g.net)}</div></div>
                    <div class="n"><div class="l">VAT</div><div class="v">${money(g.vat)}</div></div>
                    <div class="n"><div class="l">Invoice total</div><div class="v" style="color:#C8355F;">${money(g.total)}</div></div>
                </div>
            </div>`).join('');
        box.querySelectorAll('[data-bulk-check]').forEach(cb => cb.addEventListener('change', updateBulkFooter));
        updateBulkFooter();
    }
    function updateBulkFooter() {
        const ids = [...document.querySelectorAll('[data-bulk-check]:checked')].map(c => Number(c.dataset.bulkCheck));
        const sel = bulkGroups.filter(g => ids.includes(g.patient_id));
        const footer = document.getElementById('bulk-footer');
        const issueBtn = document.getElementById('bulk-issue');
        if (issueBtn) issueBtn.disabled = sel.length === 0;
        if (!sel.length) { footer.style.display = 'none'; return; }
        footer.style.display = 'flex';
        const sessions = sel.reduce((s, g) => s + g.sessions, 0), hours = sel.reduce((s, g) => s + g.hours, 0), total = sel.reduce((s, g) => s + g.total, 0);
        footer.innerHTML = `<strong>${sel.length} famil${sel.length === 1 ? 'y' : 'ies'} selected — ${sessions} sessions, ${hours} billable hours, AED ${money(total)} total.</strong>`;
    }
    document.getElementById('bulk-select-all')?.addEventListener('click', () => { const boxes = document.querySelectorAll('[data-bulk-check]'); const all = [...boxes].every(b => b.checked); boxes.forEach(b => b.checked = !all); updateBulkFooter(); });
    ['bulk-from', 'bulk-to', 'bulk-payer'].forEach(id => document.getElementById(id)?.addEventListener('change', loadBulk));
    document.getElementById('bulk-issue')?.addEventListener('click', async () => {
        const ids = [...document.querySelectorAll('[data-bulk-check]:checked')].map(c => Number(c.dataset.bulkCheck));
        if (!ids.length) return;
        try {
            const r = await api(`${BASE}/bulk-run`, { method: 'POST', body: JSON.stringify({ from: document.getElementById('bulk-from').value, to: document.getElementById('bulk-to').value, payer: document.getElementById('bulk-payer').value, patient_ids: ids }) });
            addInvoices(r.invoices);
            toast(r.message);
            loadBulk();
        } catch (e) { toast(e.message); }
    });

    renderInvoices(); renderClaims(); renderClaimAging(); renderRevenueByPayer(); renderPreauths(); renderAgingRows();
})();
</script>
@endsection
