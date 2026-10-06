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
    th.num, td.num { text-align: right; }
    th.center, td.center { text-align: center; }

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
        .inv-table td[data-label="Status"] > div { text-align: right; }
        .inv-table td[data-label="Status"] .wf-track { justify-content: flex-end; }
    }

    /* ── Phone ───────────────────────────────────────────────────────────
       The page sat inside two 28px gutters - the layout's and its own - and
       the cards still overflowed the screen, because a 1fr grid track takes
       its minimum from its content and the wide tables inside stretched it.
       minmax(0, 1fr) lets the track shrink, and the tables scroll inside
       their own card rather than dragging it past the edge. */
    @media (max-width: 820px) {
        .main-content-inner { padding-left: 0 !important; padding-right: 0 !important; }
        .bl-page { padding: 0 8px 28px; }
        .role-strip { margin: 0 -8px 10px; padding-left: 8px; padding-right: 8px; }
        .role-can, .role-locked { display: none; }

        .bl-head { margin: 12px 0 10px; gap: 10px; }
        .bl-title { font-size: 19px; }
        .bl-sub { font-size: 11.5px; }

        /* One scrolling strip rather than three tabs wrapping onto two rows. */
        .bl-tabs { gap: 14px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; }
        .bl-tabs::-webkit-scrollbar { display: none; }
        .bl-tabs > * { flex-shrink: 0; white-space: nowrap; }

        .bl-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
        .bl-tile { padding: 11px 12px; }
        .bl-tile-value { font-size: 17px !important; }

        .bl-grid { grid-template-columns: minmax(0, 1fr); gap: 10px; }
        .bl-card { padding: 14px 12px; margin-bottom: 10px; min-width: 0; }
        .bl-card-head { flex-wrap: wrap; }

        .claim-table, .aging-table, .fam-table, .pa-table, .util-tbl {
            display: block; overflow-x: auto; white-space: nowrap;
        }
    }
    .status-sel { border: 1px solid #E2DACE; border-radius: 7px; padding: 5px 8px; font: 800 11px 'Nunito Sans'; background: #fff; }

    /* Invoice list: money columns line up on the decimal, the Finance
       workflow reads as a five-step track, and the one next step for the
       signed-in role is a button instead of being buried in the menu. */
    .inv-table th.num, .inv-table td.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .inv-table tbody tr:hover td { background: #FFFBF5; }
    .inv-filters { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
    .inv-chip { border: 1px solid #E2DACE; background: #fff; border-radius: 999px; padding: 5px 12px; font: 800 11px 'Nunito Sans'; color: #5A6B7E; cursor: pointer; white-space: nowrap; }
    .inv-chip:hover { border-color: #16436E; }
    .inv-chip.active { background: #16436E; color: #fff; border-color: #16436E; }
    .inv-chip .n { opacity: .65; margin-left: 5px; }
    .inv-chip.attn:not(.active) { border-color: #E9CF9C; background: #FDF6E9; color: #8A5A10; }
    .wf { margin-top: 9px; }
    .wf-track { display: flex; align-items: center; justify-content: center; }
    .wf-dot { width: 8px; height: 8px; border-radius: 50%; background: #E6DFD3; flex-shrink: 0; }
    .wf-bar { width: 12px; height: 2px; background: #E6DFD3; }
    .wf-dot.done, .wf-bar.done { background: #1E7A46; }
    .wf-dot.now { width: 10px; height: 10px; background: var(--wf); box-shadow: 0 0 0 3px color-mix(in srgb, var(--wf) 22%, transparent); }
    .wf-label { font: 800 10.5px 'Nunito Sans'; color: var(--wf); margin-top: 5px; white-space: nowrap; }
    .act-btn.next { background: #16436E; color: #fff; border-color: #16436E; padding: 7px 10px; font-size: 11.5px; border-radius: 9px; }
    .act-btn.next:hover { background: #0F3256; }
    .act-btn.next.go { background: #1E7A46; border-color: #1E7A46; }
    .act-btn.next.go:hover { background: #17633A; }
    .act-btn.next.quiet { background: #fff; color: #16436E; border-color: #B9C9DA; }
    .act-btn.next.quiet:hover { background: #EEF3F8; }
    .act-wait { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-align: center; }
    .inv-actions { min-width: 140px; }

    /* Claims sit in the narrow column - a card per claim rather than a
       six-column table squeezed until every word wraps. */
    .claim-card { border: 1px solid #F0EAE0; border-radius: 12px; padding: 12px 14px; margin-bottom: 8px; }
    .claim-card:last-child { margin-bottom: 0; }
    .claim-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
    .claim-amt { font: 800 13.5px 'Nunito Sans'; color: #16436E; white-space: nowrap; font-variant-numeric: tabular-nums; text-align: right; }
    .claim-age { font: 700 11px 'Nunito Sans'; color: #8A7D6C; text-align: right; margin-top: 2px; }
    .claim-age.late { color: #B3261E; }
    .claim-insurer { display: inline-block; background: #EEF3F8; color: #24619C; border-radius: 6px; padding: 2px 8px; font: 800 10.5px 'Nunito Sans'; margin-top: 6px; }
    .claim-foot { display: flex; gap: 8px; align-items: center; margin-top: 10px; }
    .claim-foot .status-sel { flex: 1; min-width: 0; padding: 7px 8px; }

    /* Quotations */
    .quote-card { border: 1px solid #F0EAE0; border-radius: 12px; padding: 14px 16px; margin-bottom: 8px; display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: 18px; align-items: center; }
    .quote-money { text-align: right; }
    .quote-total { font: 800 15px 'Nunito Sans'; color: #16436E; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .quote-acts { display: flex; flex-direction: column; gap: 6px; min-width: 170px; }
    .quote-links { display: flex; gap: 10px; justify-content: center; }
    .quote-links button, .quote-links a { background: none; border: none; padding: 0; font: 800 10.5px 'Nunito Sans'; color: #24619C; cursor: pointer; text-decoration: none; }
    .quote-links .danger { color: #B3261E; }
    .q-check { display: flex; align-items: flex-start; gap: 8px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; cursor: pointer; }
    .q-check input { accent-color: #C8355F; margin-top: 2px; }
    @media (max-width: 820px) { .quote-card { grid-template-columns: minmax(0, 1fr); gap: 10px; } .quote-money { text-align: left; } }
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
    .att-line { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 3px; }
    .notice-input { width: 56px; border: 1px solid #E2DACE; border-radius: 7px; padding: 4px 6px; font: 800 10.5px 'Nunito Sans'; background: #fff; color: #2B3A4C; }
    .notice-input[hidden] { display: none; }
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
     data-preauth-channels='@json($preauthChannels)'
     data-quotations='@json($quotationsForJs)'
     data-quotation-options='@json($quotationOptions)'
     data-aging='@json($aging)'
     data-cancel-policy='@json($cancelPolicy)'
     data-bulk-defaults='@json($bulkDefaults)'
     data-can-invoice="{{ $canInvoice ? '1' : '0' }}"
     data-can-approve="{{ $canApprove ? '1' : '0' }}"
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
        <button type="button" class="bl-tab" data-tab="quotations">Quotations</button>
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
            <div class="bl-tile"><div class="bl-tile-label">Awaiting Finance</div><div class="bl-tile-value" id="tile-awaiting">0</div></div>
        </div>

        <div class="bl-grid">
            <div>
                <div class="bl-card">
                    <div class="bl-card-head">
                        <div><div class="bl-card-title">Invoices &amp; payments</div><div class="bl-card-sub">Draft → Finance verification → Finalized → Dispatched</div></div>
                    </div>
                    <div class="inv-filters" id="invoice-filters"></div>
                    <div style="overflow-x:auto;">
                        <table class="inv-table">
                            <thead><tr><th style="text-align:left;">Patient</th><th class="num">Total</th><th class="num">Paid</th><th class="num">Balance</th><th>Status</th><th>Next step</th></tr></thead>
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
                    <div id="claims-body"></div>
                    <div id="claims-pagination" class="inv-pagination"></div>
                </div>

                <div class="bl-card">
                    <div class="bl-card-head">
                        <div><div class="bl-card-title">Pre-authorizations</div><div class="bl-card-sub">Requests, approvals and denials by payer</div></div>
                        @if ($canInvoice)<button type="button" class="bl-btn bl-btn-sm" id="btn-new-preauth">+ Request pre-auth</button>@endif
                    </div>
                    <div id="preauth-list"></div>
                    <div id="preauth-pagination" class="inv-pagination"></div>
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

    <!-- ============ TAB: Quotations ============ -->
    <div class="bl-tab-panel" id="tab-quotations" style="display:none;">
        <div class="bl-card">
            <div class="bl-card-head">
                <div>
                    <div class="bl-card-title">Quotations</div>
                    <div class="bl-card-sub">Quotation → customer confirmation → payment confirmation → sessions can be scheduled</div>
                </div>
                @if ($canInvoice)<button type="button" class="bl-btn bl-btn-primary bl-btn-sm" id="btn-new-quote">+ New quotation</button>@endif
            </div>
            <div id="quote-list"></div>
            <div id="quote-pagination" class="inv-pagination"></div>
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
                            <thead><tr><th>Invoice</th><th class="center">Age</th><th class="center">Total</th><th class="center">Balance</th><th class="center"></th></tr></thead>
                            <tbody id="aging-body"></tbody>
                        </table>
                    </div>
                </div>
                <div class="bl-card">
                    <div class="bl-card-head"><div><div class="bl-card-title">Family statements</div><div class="bl-card-sub">Running balance across every invoice, credit note and payment</div></div></div>
                    <div style="overflow-x:auto;">
                        <table class="fam-table">
                            <thead><tr><th>Family</th><th class="center">Billed</th><th class="center">Balance</th><th class="center"></th></tr></thead>
                            <tbody>
                                @forelse ($aging['families'] as $f)
                                    <tr>
                                        <td><strong>{{ $f['patient'] }}</strong><div style="color:#8A7D6C; font-weight:600;">{{ $f['parent'] }} · {{ $f['invoices'] }} invoice{{ $f['invoices'] === 1 ? '' : 's' }} · {{ $f['payer'] }}</div></td>
                                        <td class="center">AED {{ number_format($f['billed'], 2) }}</td>
                                        <td class="center" style="color: {{ $f['balance'] > 0 ? '#B3261E' : '#1E7A46' }};">AED {{ number_format($f['balance'], 2) }}</td>
                                        <td class="center"><a href="{{ $f['statement_url'] }}" target="_blank" class="bl-btn bl-btn-sm" style="text-decoration:none;">Open statement</a></td>
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
                    @if ($canInvoice)<button type="button" class="bl-btn bl-btn-primary" id="bulk-issue" disabled>Raise draft invoices</button>@endif
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
                <button type="button" class="btn-save" id="preview-save">Save draft</button>
                <button type="button" class="btn-save btn-dark" id="preview-send">Save &amp; submit to Finance</button>
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

<!-- New quotation -->
<div id="quote-modal" class="cmodal-overlay">
    <div class="cmodal wide">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">New quotation</div><button type="button" class="cx" data-close="quote-modal">✕</button></div>
        <div class="cmodal-sub">Location, payment mode and service come first — they decide the price</div>
        <div class="f-error" id="quote-error"></div>
        <div class="f-row">
            <div><label class="f-label" for="q-patient">Client *</label><select id="q-patient" class="f-select"></select></div>
            <div><label class="f-label" for="q-location">Customer location *</label><select id="q-location" class="f-select"></select></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="q-mode">Payment mode *</label><select id="q-mode" class="f-select"></select></div>
            <div id="q-payer-wrap"><label class="f-label" for="q-payer">Insurer *</label><select id="q-payer" class="f-select"></select></div>
            <div><label class="f-label" for="q-service">Service type *</label><select id="q-service" class="f-select"></select></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="q-basis">Pricing basis *</label><select id="q-basis" class="f-select"></select></div>
            <div><label class="f-label" for="q-unit">Billed per *</label><select id="q-unit" class="f-select"><option value="hour">Hour</option><option value="session">Session</option></select></div>
            <div><label class="f-label" for="q-qty">Quantity *</label><input type="number" min="0.5" step="0.5" id="q-qty" class="f-input"></div>
            <div><label class="f-label" for="q-rate">Rate (AED) *</label><input type="number" min="0.01" step="0.01" id="q-rate" class="f-input"></div>
        </div>
        <div class="warn-box" id="q-total"></div>
        <div class="f-row">
            <div style="flex:2;"><label class="f-label" for="q-terms">Payment terms *</label><input type="text" id="q-terms" class="f-input"></div>
            <div><label class="f-label" for="q-valid">Valid until *</label><input type="date" id="q-valid" class="f-input"></div>
        </div>
        <div><label class="f-label" for="q-notes">Notes</label><input type="text" id="q-notes" class="f-input" placeholder="optional"></div>
        <div class="f-row"><button type="button" class="btn-save" id="quote-save">Save quotation</button><button type="button" class="btn-cancel" data-close="quote-modal">Cancel</button></div>
    </div>
</div>

<!-- Quotation step: sent / customer confirmed / cancelled -->
<div id="qstep-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title" id="qstep-title"></div><button type="button" class="cx" data-close="qstep-modal">✕</button></div>
        <div class="cmodal-sub" id="qstep-sub"></div>
        <div class="f-error" id="qstep-error"></div>
        <div id="qstep-send"><label class="f-label" for="qstep-via">Shared with the customer by *</label><select id="qstep-via" class="f-select"></select></div>
        <div id="qstep-confirm" class="f-row">
            <div><label class="f-label" for="qstep-method">Confirmation *</label><select id="qstep-method" class="f-select"></select></div>
            <div><label class="f-label" for="qstep-date">Confirmed on *</label><input type="date" id="qstep-date" class="f-input"></div>
        </div>
        <div id="qstep-cancel"><label class="f-label" for="qstep-reason">Reason *</label><input type="text" id="qstep-reason" class="f-input" placeholder="e.g. customer declined"></div>
        <div class="f-row"><button type="button" class="btn-save" id="qstep-save">Save</button><button type="button" class="btn-cancel" data-close="qstep-modal">Close</button></div>
    </div>
</div>

<!-- Quotation: payment confirmation / insurance activation -->
<div id="qclear-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title" id="qclear-title"></div><button type="button" class="cx" data-close="qclear-modal">✕</button></div>
        <div class="cmodal-sub" id="qclear-sub"></div>
        <div class="warn-box">Saving this clears the client for session scheduling.</div>
        <div class="f-error" id="qclear-error"></div>
        <div id="qclear-self" style="display:flex; flex-direction:column; gap:14px;">
            <div class="f-row">
                <div><label class="f-label" for="qclear-amount">Amount received (AED) *</label><input type="number" step="0.01" min="0.01" id="qclear-amount" class="f-input"></div>
                <div><label class="f-label" for="qclear-method">Method *</label><select id="qclear-method" class="f-select"></select></div>
            </div>
            <div class="f-row">
                <div><label class="f-label" for="qclear-date">Received on *</label><input type="date" id="qclear-date" class="f-input"></div>
                <div><label class="f-label" for="qclear-ref">Reference</label><input type="text" id="qclear-ref" class="f-input" placeholder="transfer / slip no."></div>
            </div>
            <label class="q-check"><input type="checkbox" id="qclear-policy"><span>Signed prepayment policy received and passed to Finance</span></label>
            <label class="q-check" id="qclear-pos-wrap"><input type="checkbox" id="qclear-pos"><span>Signed card POS fee agreement received <span id="qclear-pos-fee" style="font-weight:600; color:#8A7D6C;"></span></span></label>
        </div>
        <div id="qclear-ins" style="display:flex; flex-direction:column; gap:12px;">
            <label class="q-check"><input type="checkbox" id="qclear-noc"><span>NOC received from the customer</span></label>
            <label class="q-check"><input type="checkbox" id="qclear-liab"><span>Customer acknowledged 100% personal liability for amounts the insurer rejects</span></label>
        </div>
        <div class="f-row"><button type="button" class="btn-save" id="qclear-save">Confirm</button><button type="button" class="btn-cancel" data-close="qclear-modal">Cancel</button></div>
    </div>
</div>

<!-- Record the payer's pre-authorization decision -->
<div id="padec-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title" id="padec-title">Record payer decision</div><button type="button" class="cx" data-close="padec-modal">✕</button></div>
        <div class="cmodal-sub" id="padec-sub"></div>
        <div class="warn-box" id="padec-hint"></div>
        <div class="f-error" id="padec-error"></div>
        <div class="f-row">
            <div><label class="f-label" for="padec-date">Decision received on *</label><input type="date" id="padec-date" class="f-input"></div>
            <div><label class="f-label" for="padec-channel">Received via *</label><select id="padec-channel" class="f-select"></select></div>
        </div>
        <div id="padec-approve-fields" style="display:flex; flex-direction:column; gap:14px;">
            <div><label class="f-label" for="padec-ref">Payer approval reference *</label><input type="text" id="padec-ref" class="f-input" placeholder="As given by the payer"></div>
            <div class="f-row">
                <div><label class="f-label" for="padec-hours">Hours approved *</label><input type="number" min="1" id="padec-hours" class="f-input"></div>
                <div><label class="f-label" for="padec-pct">Coverage % *</label><input type="number" min="1" max="100" id="padec-pct" class="f-input" placeholder="e.g. 80"></div>
            </div>
            <div><label class="f-label" for="padec-to">Valid until</label><input type="date" id="padec-to" class="f-input"></div>
        </div>
        <div id="padec-deny-fields"><label class="f-label" for="padec-reason">Reason given by the payer *</label><textarea id="padec-reason" class="f-textarea" rows="3" placeholder="e.g. clinical justification insufficient — progress report requested"></textarea></div>
        <div class="f-row"><button type="button" class="btn-save" id="padec-save">Save</button><button type="button" class="btn-cancel" data-close="padec-modal">Cancel</button></div>
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

<!-- Return for correction -->
<div id="return-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Return for correction</div><button type="button" class="cx" data-close="return-modal">✕</button></div>
        <div class="cmodal-sub" id="return-sub"></div>
        <div class="f-error" id="return-error"></div>
        <div><label class="f-label" for="return-reason">What needs correcting *</label><textarea id="return-reason" class="f-textarea" rows="3" placeholder="e.g. 12 Sep session billed 2 h, attendance sheet shows 1 h"></textarea></div>
        <div class="f-row"><button type="button" class="btn-save" id="return-save">Return to Operations</button><button type="button" class="btn-cancel" data-close="return-modal">Cancel</button></div>
    </div>
</div>

<!-- Log a WhatsApp dispatch -->
<div id="wa-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Log WhatsApp dispatch</div><button type="button" class="cx" data-close="wa-modal">✕</button></div>
        <div class="cmodal-sub" id="wa-sub"></div>
        <div class="f-error" id="wa-error"></div>
        <div><label class="f-label" for="wa-to">WhatsApp number *</label><input type="text" id="wa-to" class="f-input" placeholder="+971 …"></div>
        <div><label class="f-label" for="wa-at">Sent at</label><input type="datetime-local" id="wa-at" class="f-input"></div>
        <div><label class="f-label" for="wa-note">Note</label><input type="text" id="wa-note" class="f-input" placeholder="optional"></div>
        <div class="f-row"><button type="button" class="btn-save" id="wa-save">Log dispatch</button><button type="button" class="btn-cancel" data-close="wa-modal">Cancel</button></div>
    </div>
</div>

<!-- Invoice history -->
<div id="history-modal" class="cmodal-overlay">
    <div class="cmodal wide">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Invoice history</div><button type="button" class="cx" data-close="history-modal">✕</button></div>
        <div class="cmodal-sub" id="history-sub"></div>
        <div id="history-body"></div>
    </div>
</div>

<!-- Insurance submission -->
<div id="claim-modal" class="cmodal-overlay">
    <div class="cmodal">
        <div style="display:flex; justify-content:space-between;"><div class="cmodal-title">Insurance submission</div><button type="button" class="cx" data-close="claim-modal">✕</button></div>
        <div class="cmodal-sub" id="claim-sub"></div>
        <div class="f-error" id="claim-error"></div>
        <div><label class="f-label" for="claim-status">Status</label><select id="claim-status" class="f-select"></select></div>
        <div><label class="f-label" for="claim-portal">Insurance portal</label><input type="text" id="claim-portal" class="f-input" placeholder="e.g. Daman"></div>
        <div><label class="f-label" for="claim-payer-ref">Insurer claim reference</label><input type="text" id="claim-payer-ref" class="f-input" placeholder="CL-ON-000-0000XXX"></div>
        <div><label class="f-label" for="claim-medical">Medical report *</label><select id="claim-medical" class="f-select"></select></div>
        <div><label class="f-label" for="claim-assessment">Engage assessment report</label><select id="claim-assessment" class="f-select"></select></div>
        <div><label class="f-label" for="claim-notes">Notes</label><input type="text" id="claim-notes" class="f-input"></div>
        <div class="f-row"><button type="button" class="btn-save" id="claim-save">Save</button><button type="button" class="btn-cancel" data-close="claim-modal">Cancel</button></div>
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
    const PREAUTH_CHANNELS = JSON.parse(root.dataset.preauthChannels);
    let QUOTES = JSON.parse(root.dataset.quotations);
    const QOPT = JSON.parse(root.dataset.quotationOptions);
    const QUOTE_COLORS = { draft: ['#F1EDE5', '#8A7D6C'], sent: ['#E7EFF7', '#24619C'], customer_confirmed: ['#F7EEDD', '#B97F24'], cleared: ['#E3F1E9', '#1E7A46'], cancelled: ['#EEEEEE', '#6B6B6B'], expired: ['#F9E4E2', '#B3261E'] };
    const AGING_ROWS = JSON.parse(root.dataset.aging).rows;
    const POLICY = JSON.parse(root.dataset.cancelPolicy);
    const BULK_DEFAULTS = JSON.parse(root.dataset.bulkDefaults);
    const CAN_INVOICE = root.dataset.canInvoice === '1';
    const CAN_APPROVE = root.dataset.canApprove === '1';
    const BASE = root.dataset.baseUrl;
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    const STATUS_COLORS = { paid: ['#E3F1E9', '#1E7A46'], partly_paid: ['#F7EEDD', '#B97F24'], outstanding: ['#F9E4E2', '#B3261E'], voided: ['#EEEEEE', '#6B6B6B'] };
    const WORKFLOW_COLORS = { draft: ['#F1EDE5', '#8A7D6C'], pending_verification: ['#F7EEDD', '#B97F24'], correction_required: ['#F9E4E2', '#B3261E'], approved: ['#E7EFF7', '#24619C'], finalized: ['#E3F1E9', '#1E7A46'], dispatched: ['#E3F1E9', '#1E7A46'] };
    const CLAIM_COLORS = { draft: ['#F1EDE5', '#8A7D6C'], documents_ready: ['#E7EFF7', '#24619C'], submitted: ['#E7EFF7', '#24619C'], pending_info: ['#F7EEDD', '#B97F24'], rejected: ['#F9E4E2', '#B3261E'], settled: ['#E3F1E9', '#1E7A46'] };
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

    // Where each invoice sits in the Finance workflow, as filter chips.
    const INVOICE_FILTERS = [
        { key: 'all', label: 'All', test: () => true },
        { key: 'draft', label: 'Drafts', test: i => !i.voided && (i.workflow === 'draft' || i.workflow === 'correction_required') },
        { key: 'pending', label: 'Awaiting Finance', attn: true, test: i => !i.voided && i.workflow === 'pending_verification' },
        { key: 'approved', label: 'To finalize', attn: true, test: i => !i.voided && i.workflow === 'approved' },
        { key: 'final', label: 'Finalized', test: i => !i.voided && i.is_final },
        { key: 'voided', label: 'Voided', test: i => i.voided },
    ];
    let invoiceFilter = 'all';
    const WF_STEPS = ['draft', 'pending_verification', 'approved', 'finalized', 'dispatched'];

    function workflowTrack(i) {
        const at = i.workflow === 'correction_required' ? 1 : Math.max(0, WF_STEPS.indexOf(i.workflow));
        const complete = i.workflow === 'dispatched';
        const color = (WORKFLOW_COLORS[i.workflow] || WORKFLOW_COLORS.draft)[1];
        const track = WF_STEPS.map((_, n) => {
            const cls = complete || n < at ? 'done' : (n === at ? 'now' : '');
            return (n ? `<span class="wf-bar ${complete || n <= at ? 'done' : ''}"></span>` : '') + `<span class="wf-dot ${cls}"></span>`;
        }).join('');
        const stamps = i.stamps.length ? ' · ' + i.stamps.map(st => st === 'paid' ? 'PAID stamp' : 'Company Stamp').join(' + ') : '';
        return `<div class="wf" style="--wf:${color};" title="${esc(i.workflow_label + stamps)}"><div class="wf-track">${track}</div><div class="wf-label">${esc(i.workflow_label)}</div></div>`;
    }

    // The single step this invoice is waiting on, if the signed-in role can take it.
    function nextStep(i) {
        const wf = i.workflow;
        if (CAN_INVOICE && (wf === 'draft' || wf === 'correction_required')) return { act: 'submit', label: wf === 'draft' ? 'Submit to Finance' : 'Resubmit to Finance' };
        if (wf === 'pending_verification') return CAN_APPROVE ? { act: 'approve', label: 'Approve', go: true } : { wait: 'With Finance for verification' };
        if (wf === 'approved') return CAN_INVOICE ? { act: 'finalize', label: 'Finalize & stamp', go: true } : { wait: 'Approved — to be finalized' };
        if (wf === 'finalized') return { act: 'email', label: 'Send to customer', quiet: true };
        if (CAN_INVOICE && i.dispatches.some(d => d.kind === 'invoice' && !d.confirmed_at)) return { act: 'confirm', label: 'Confirm receipt' };
        if (CAN_INVOICE && i.balance > 0.01) return { act: 'pay', label: 'Record payment' };
        return null;
    }

    function runInvoiceAction(act, inv) {
        ({
            pay: openPayment,
            credit: openCredit,
            void: openVoid,
            email: () => openEmail(inv, 'invoice'),
            pdf: () => window.open(inv.print_url, '_blank'),
            submit: () => workflowStep(inv, 'submit'),
            approve: () => workflowStep(inv, 'approve'),
            finalize: () => workflowStep(inv, 'finalize'),
            return: openReturn,
            whatsapp: openWhatsapp,
            confirm: confirmReceipt,
            history: openHistory,
        })[act](inv);
    }

    function renderInvoiceFilters() {
        const box = document.getElementById('invoice-filters');
        box.innerHTML = INVOICE_FILTERS.map(f => {
            const n = INVOICES.filter(f.test).length;
            return `<button type="button" class="inv-chip ${f.key === invoiceFilter ? 'active' : ''} ${f.attn && n ? 'attn' : ''}" data-filter="${f.key}">${f.label}<span class="n">${n}</span></button>`;
        }).join('');
        box.querySelectorAll('[data-filter]').forEach(b => b.addEventListener('click', () => { invoiceFilter = b.dataset.filter; invoicePage = 1; renderInvoices(); }));
        const awaiting = INVOICES.filter(INVOICE_FILTERS[2].test).length;
        const tile = document.getElementById('tile-awaiting');
        tile.textContent = awaiting + (awaiting === 1 ? ' invoice' : ' invoices');
        tile.classList.toggle('warn', awaiting > 0);
    }

    function renderInvoices() {
        const list = document.getElementById('invoice-list');
        const pager = document.getElementById('invoice-pagination');
        renderInvoiceFilters();
        if (!INVOICES.length) { list.innerHTML = '<tr><td colspan="6" class="bl-empty">No invoices raised yet.</td></tr>'; pager.innerHTML = ''; return; }
        const shown = INVOICES.filter(INVOICE_FILTERS.find(f => f.key === invoiceFilter).test);
        if (!shown.length) { list.innerHTML = '<tr><td colspan="6" class="bl-empty">No invoices at this stage.</td></tr>'; pager.innerHTML = ''; return; }

        const pageCount = Math.max(1, Math.ceil(shown.length / INVOICES_PER_PAGE));
        if (invoicePage > pageCount) invoicePage = pageCount;
        const start = (invoicePage - 1) * INVOICES_PER_PAGE;
        const pageItems = shown.slice(start, start + INVOICES_PER_PAGE);

        list.innerHTML = pageItems.map(i => {
            const [bg, fg] = STATUS_COLORS[i.status] || STATUS_COLORS.outstanding;
            const receiptLines = i.receipts.map(r => `<div class="inv-meta receipt">${esc(r.number)} → AED ${money(r.amount)} · ${esc(r.method)} · ${esc(r.date)}${r.reference ? ' · ' + esc(r.reference) : ''}</div>`).join('');
            let metaLine = '';
            if (i.voided) metaLine = `<div class="inv-meta voided">Voided ${esc(i.voided_on)} — ${esc(i.void_reason)}${i.replaced_by ? ' · reissued as ' + esc(i.replaced_by) : ''}</div>`;
            else if (i.credit > 0) metaLine = `<div class="inv-meta voided">Credit note AED ${money(i.credit)} — ${esc((i.credit_reason || '').split('\n').pop())}</div>`;
            const replacesLine = i.replaces ? `<div class="inv-meta link">Replaces voided invoice ${esc(i.replaces)}</div>` : '';
            const correctionLine = i.workflow === 'correction_required' && i.correction_note ? `<div class="inv-meta voided">Finance: ${esc(i.correction_note)}</div>` : '';
            const dispatchLines = i.dispatches.filter(d => d.kind === 'invoice').map(d => `<div class="inv-meta link">Sent by ${esc(d.channel_label)} to ${esc(d.sent_to)} · ${esc(d.sent_at)} · ${d.confirmed_at ? 'receipt confirmed ' + esc(d.confirmed_at) : 'receipt not yet confirmed'}</div>`).join('');
            const unconfirmed = i.dispatches.find(d => d.kind === 'invoice' && !d.confirmed_at);
            const wf = i.workflow;
            const next = i.voided ? null : nextStep(i);
            const nextHtml = !next ? '' : (next.wait ? `<div class="act-wait">${esc(next.wait)}</div>` : `<button type="button" class="act-btn next ${next.go ? 'go' : ''} ${next.quiet ? 'quiet' : ''}" data-next="${next.act}" data-id="${i.id}">${esc(next.label)}</button>`);
            const actions = i.voided ? `<a href="${i.print_url}" target="_blank" class="act-btn">Open PDF</a>` : `${nextHtml}
                <select class="act-dropdown" data-id="${i.id}">
                    <option value="">More actions…</option>
                    ${CAN_INVOICE && (wf === 'draft' || wf === 'correction_required') ? `<option value="submit">${wf === 'draft' ? 'Submit to Finance' : 'Resubmit to Finance'}</option>` : ''}
                    ${CAN_APPROVE && wf === 'pending_verification' ? `<option value="approve">Approve (Finance)</option><option value="return">Return for correction</option>` : ''}
                    ${CAN_INVOICE && wf === 'approved' ? `<option value="finalize">Finalize &amp; apply stamp</option>` : ''}
                    ${CAN_INVOICE && i.balance > 0.01 ? `<option value="pay">Record payment</option>` : ''}
                    ${CAN_INVOICE ? `<option value="credit">Credit note</option>` : ''}
                    <option value="pdf">${i.is_final ? 'Open PDF' : 'Open draft'}</option>
                    ${i.is_final ? `<option value="email">Send by email</option>` : ''}
                    ${CAN_INVOICE && i.is_final ? `<option value="whatsapp">Log WhatsApp dispatch</option>` : ''}
                    ${CAN_INVOICE && unconfirmed ? `<option value="confirm">Confirm customer receipt</option>` : ''}
                    <option value="history">History</option>
                    ${CAN_INVOICE ? `<option value="void">Void / reissue</option>` : ''}
                </select>`;


            return `<tr data-id="${i.id}">
                <td data-label="Patient" style="text-align:left;">
                    <div class="inv-name">${esc(i.patient)}</div>
                    <div class="inv-meta">${esc(i.number)} · ${esc(i.payer)} · due ${esc(i.due_label)}</div>
                    ${receiptLines}
                    ${metaLine}${replacesLine}${correctionLine}${dispatchLines}
                </td>
                <td data-label="Total" class="num">${money(i.total)}</td>
                <td data-label="Paid" class="num" style="color:${i.paid > 0 ? '#1E7A46' : '#B0A493'};">${money(i.paid)}</td>
                <td data-label="Balance" class="num" style="color:${i.balance > 0.01 ? '#B3261E' : '#B0A493'}; ${i.balance > 0.01 ? 'font-weight:800;' : ''}">${money(i.balance)}</td>
                <td data-label="Status"><div><span class="inv-status" style="background:${bg}; color:${fg};">${esc(i.status_label)}</span>${i.voided ? '' : workflowTrack(i)}</div></td>
                <td data-label="Next step"><div class="inv-actions">${actions}</div></td>
            </tr>`;
        }).join('');

        list.querySelectorAll('.act-dropdown').forEach(sel => sel.addEventListener('change', () => {
            const act = sel.value;
            sel.value = '';
            if (!act) return;
            runInvoiceAction(act, INVOICES.find(i => i.id === Number(sel.dataset.id)));
        }));
        list.querySelectorAll('[data-next]').forEach(btn => btn.addEventListener('click', () => {
            runInvoiceAction(btn.dataset.next, INVOICES.find(i => i.id === Number(btn.dataset.id)));
        }));

        pager.innerHTML = `
            <div class="inv-pagination-info">Showing ${start + 1}–${Math.min(start + INVOICES_PER_PAGE, shown.length)} of ${shown.length}</div>
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

    // ---- Finance verification workflow ----
    async function workflowStep(inv, step, body = null) {
        try {
            const r = await api(`${BASE}/invoices/${inv.id}/${step}`, { method: 'POST', ...(body ? { body: JSON.stringify(body) } : {}) });
            replaceInvoice(r.invoice); toast(r.message);
            return r;
        } catch (e) { toast(e.message); return null; }
    }

    let returnTarget = null;
    function openReturn(inv) {
        returnTarget = inv;
        document.getElementById('return-error').style.display = 'none';
        document.getElementById('return-sub').textContent = `${inv.number} · ${inv.patient} · AED ${money(inv.total)}`;
        document.getElementById('return-reason').value = '';
        openModal('return-modal');
    }
    document.getElementById('return-save').addEventListener('click', async () => {
        document.getElementById('return-error').style.display = 'none';
        try {
            const r = await api(`${BASE}/invoices/${returnTarget.id}/return`, { method: 'POST', body: JSON.stringify({ reason: document.getElementById('return-reason').value }) });
            replaceInvoice(r.invoice); closeModal('return-modal'); toast(r.message);
        } catch (e) { showError('return-error', e); }
    });

    // ---- Dispatch log (WhatsApp is sent by hand, then recorded here) ----
    let waTarget = null;
    function openWhatsapp(inv) {
        waTarget = inv;
        document.getElementById('wa-error').style.display = 'none';
        document.getElementById('wa-sub').textContent = `${inv.number} · send the finalized PDF from WhatsApp, then record it here`;
        document.getElementById('wa-to').value = inv.phone || '';
        const now = new Date(); now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('wa-at').value = now.toISOString().slice(0, 16);
        document.getElementById('wa-note').value = '';
        openModal('wa-modal');
    }
    document.getElementById('wa-save').addEventListener('click', async () => {
        document.getElementById('wa-error').style.display = 'none';
        try {
            const r = await api(`${BASE}/invoices/${waTarget.id}/dispatches`, { method: 'POST', body: JSON.stringify({
                channel: 'whatsapp', sent_to: document.getElementById('wa-to').value,
                sent_at: document.getElementById('wa-at').value || null, note: document.getElementById('wa-note').value || null,
            }) });
            replaceInvoice(r.invoice); closeModal('wa-modal'); toast(r.message);
        } catch (e) { showError('wa-error', e); }
    });
    function confirmReceipt(inv) {
        const d = inv.dispatches.find(x => x.kind === 'invoice' && !x.confirmed_at);
        if (d) workflowStep(inv, `dispatches/${d.id}/confirm`);
    }

    async function openHistory(inv) {
        document.getElementById('history-sub').textContent = `${inv.number} · ${inv.patient}${inv.retain_until ? ' · keep on record until ' + inv.retain_until : ''}`;
        const body = document.getElementById('history-body');
        body.innerHTML = '<div class="bl-empty">Loading…</div>';
        openModal('history-modal');
        try {
            const r = await api(`${BASE}/invoices/${inv.id}/history`);
            body.innerHTML = r.events.length
                ? r.events.map(ev => `<div style="padding:9px 0; border-bottom:1px solid #EFE8DC;"><div class="inv-name">${esc(ev.label)}</div><div class="inv-meta">${esc(ev.at)} · ${esc(ev.by)}</div>${ev.note ? `<div class="inv-meta" style="color:#2B3A4C;">${esc(ev.note)}</div>` : ''}</div>`).join('')
                : '<div class="bl-empty">No history recorded — this invoice predates the audit trail.</div>';
        } catch (e) { body.innerHTML = `<div class="bl-empty">${esc(e.message)}</div>`; }
    }

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

    // ---- Side-column lists: five to a page, latest first ----
    const SIDE_PER_PAGE = 5;
    const sidePage = { claims: 1, preauths: 1, quotes: 1 };
    function pageSlice(key, items) {
        const pageCount = Math.max(1, Math.ceil(items.length / SIDE_PER_PAGE));
        sidePage[key] = Math.min(Math.max(1, sidePage[key]), pageCount);
        const start = (sidePage[key] - 1) * SIDE_PER_PAGE;
        return { items: items.slice(start, start + SIDE_PER_PAGE), start, pageCount };
    }
    function renderSidePager(key, elId, total, start, pageCount, rerender) {
        const pager = document.getElementById(elId);
        if (total <= SIDE_PER_PAGE) { pager.innerHTML = ''; pager.style.display = 'none'; return; }
        pager.style.display = '';
        pager.innerHTML = `
            <div class="inv-pagination-info">${start + 1}–${Math.min(start + SIDE_PER_PAGE, total)} of ${total}</div>
            <div class="inv-pagination-nav">
                <button type="button" class="bl-btn bl-btn-sm" data-dir="-1" ${sidePage[key] <= 1 ? 'disabled' : ''}>‹ Prev</button>
                <div class="inv-pagination-info" style="align-self:center;">${sidePage[key]} / ${pageCount}</div>
                <button type="button" class="bl-btn bl-btn-sm" data-dir="1" ${sidePage[key] >= pageCount ? 'disabled' : ''}>Next ›</button>
            </div>`;
        pager.querySelectorAll('[data-dir]').forEach(b => b.addEventListener('click', () => { sidePage[key] += Number(b.dataset.dir); rerender(); }));
    }

    // ================= Quotations =================
    function opts(list, sel) { return list.map(v => `<option ${v === sel ? 'selected' : ''}>${esc(v)}</option>`).join(''); }
    function replaceQuote(row) { const i = QUOTES.findIndex(x => x.id === row.id); if (i >= 0) QUOTES[i] = row; else { QUOTES.unshift(row); sidePage.quotes = 1; } renderQuotes(); }

    function renderQuotes() {
        const box = document.getElementById('quote-list');
        if (!QUOTES.length) { box.innerHTML = '<div class="bl-empty">No quotations yet. A client needs a payment-confirmed quotation before sessions are scheduled.</div>'; renderSidePager('quotes', 'quote-pagination', 0, 0, 1, renderQuotes); return; }
        const page = pageSlice('quotes', QUOTES);
        renderSidePager('quotes', 'quote-pagination', QUOTES.length, page.start, page.pageCount, renderQuotes);
        box.innerHTML = page.items.map(q => {
            const [bg, fg] = QUOTE_COLORS[q.status] || QUOTE_COLORS.draft;
            const next = !CAN_INVOICE || q.expired ? null : ({
                draft: { act: 'send', label: 'Mark as sent' },
                sent: { act: 'confirm', label: 'Customer confirmed' },
                customer_confirmed: { act: 'clear', label: q.insurance ? 'Activate insurance billing' : 'Confirm payment', go: true },
            })[q.status];
            const open = ['draft', 'sent', 'customer_confirmed'].includes(q.status) || q.expired;
            return `<div class="quote-card">
                <div>
                    <div class="inv-name">${esc(q.patient)} — ${esc(q.service)}</div>
                    <div class="inv-meta">${esc(q.number)} · ${esc(q.location)} · ${esc(q.insurance ? 'Insurance · ' + (q.payer || '') : 'Self-pay')} · ${esc(q.pricing_basis)}</div>
                    <div class="inv-meta">${q.quantity} ${esc(q.unit)}${q.quantity === 1 ? '' : 's'} × AED ${money(q.rate)} · ${esc(q.terms || '')} · valid until ${esc(q.valid_until)}</div>
                    ${q.sent ? `<div class="inv-meta link">Sent ${esc(q.sent)}${q.confirmed ? ' · customer confirmed ' + esc(q.confirmed) : ''}</div>` : ''}
                    ${q.status === 'cleared' && !q.insurance ? `<div class="inv-meta receipt">AED ${money(q.amount_received)} received ${esc(q.cleared_on)} · ${esc(q.payment_method)}${q.payment_reference ? ' · ref ' + esc(q.payment_reference) : ''} · AED ${money(q.prepaid_balance)} not yet invoiced${q.pos_fee > 0 ? ' · POS fee AED ' + money(q.pos_fee) : ''}</div>` : ''}
                    ${q.status === 'cleared' && q.insurance ? `<div class="inv-meta receipt">NOC and liability acknowledgement on file · activated ${esc(q.cleared_on)}</div>` : ''}
                </div>
                <div class="quote-money">
                    <div class="quote-total">AED ${money(q.total)}</div>
                    <div class="inv-meta">incl. VAT AED ${money(q.vat)}</div>
                    <div style="margin-top:6px;"><span class="inv-status" style="background:${bg}; color:${fg};">${esc(q.status_label)}</span></div>
                </div>
                <div class="quote-acts">
                    ${next ? `<button type="button" class="act-btn next ${next.go ? 'go' : ''}" data-qact="${next.act}" data-id="${q.id}">${esc(next.label)}</button>` : (q.status === 'cleared' ? '<div class="act-wait">Cleared for scheduling</div>' : '')}
                    <div class="quote-links">
                        <a href="${q.print_url}" target="_blank">Open</a>
                        <button type="button" data-qact="history" data-id="${q.id}">History</button>
                        ${CAN_INVOICE && open ? `<button type="button" class="danger" data-qact="cancel" data-id="${q.id}">Cancel</button>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
        box.querySelectorAll('[data-qact]').forEach(b => b.addEventListener('click', () => {
            const q = QUOTES.find(x => x.id === Number(b.dataset.id));
            ({ send: () => openQuoteStep(q, 'send'), confirm: () => openQuoteStep(q, 'confirm'), cancel: () => openQuoteStep(q, 'cancel'), clear: () => openQuoteClear(q), history: () => openQuoteHistory(q) })[b.dataset.qact]();
        }));
    }

    // ---- New quotation ----
    function quoteTotals() {
        const sub = (Number(document.getElementById('q-qty').value) || 0) * (Number(document.getElementById('q-rate').value) || 0);
        const vat = sub * QOPT.vat_rate / 100;
        const pos = sub * QOPT.pos_fee_pct / 100 * (1 + QOPT.vat_rate / 100);
        document.getElementById('q-total').innerHTML = `Service value AED ${money(sub)} + VAT ${QOPT.vat_rate}% AED ${money(vat)} = <strong>AED ${money(sub + vat)}</strong>` +
            (document.getElementById('q-mode').value === 'Insurance' ? '' : ` · if paid by card, POS fee ${QOPT.pos_fee_pct}% + VAT = AED ${money(pos)}, charged separately`);
    }
    function quoteModeChanged() {
        const ins = document.getElementById('q-mode').value === 'Insurance';
        document.getElementById('q-payer-wrap').style.display = ins ? '' : 'none';
        if (ins) document.getElementById('q-basis').value = 'Insurance approved rate';
        else if (document.getElementById('q-basis').value === 'Insurance approved rate') document.getElementById('q-basis').value = 'Per hour';
        quoteTotals();
    }
    document.getElementById('btn-new-quote')?.addEventListener('click', () => {
        document.getElementById('quote-error').style.display = 'none';
        document.getElementById('q-patient').innerHTML = PATIENTS.map(p => `<option value="${p.id}">${esc(p.name)}</option>`).join('');
        document.getElementById('q-location').innerHTML = opts(QOPT.locations);
        document.getElementById('q-mode').innerHTML = opts(QOPT.payment_modes);
        document.getElementById('q-payer').innerHTML = opts(PAYERS.filter(p => p !== 'Self-pay'));
        document.getElementById('q-service').innerHTML = opts(SERVICES);
        document.getElementById('q-basis').innerHTML = opts(QOPT.pricing_bases);
        document.getElementById('q-unit').value = 'hour';
        document.getElementById('q-qty').value = 20;
        const patient = PATIENTS[0];
        document.getElementById('q-rate').value = patient ? patient.rate : '';
        document.getElementById('q-terms').value = QOPT.default_terms;
        document.getElementById('q-valid').value = new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10);
        document.getElementById('q-notes').value = '';
        quoteModeChanged();
        openModal('quote-modal');
    });
    document.getElementById('q-mode').addEventListener('change', quoteModeChanged);
    document.getElementById('q-patient').addEventListener('change', e => {
        const p = PATIENTS.find(x => x.id === Number(e.target.value));
        if (p) { document.getElementById('q-rate').value = p.rate; quoteTotals(); }
    });
    ['q-qty', 'q-rate'].forEach(id => document.getElementById(id).addEventListener('input', quoteTotals));
    document.getElementById('quote-save').addEventListener('click', async () => {
        document.getElementById('quote-error').style.display = 'none';
        const btn = document.getElementById('quote-save');
        if (btn.disabled) return;
        btn.disabled = true; btn.textContent = 'Saving…';
        const ins = document.getElementById('q-mode').value === 'Insurance';
        try {
            const r = await api(`${BASE}/quotations`, { method: 'POST', body: JSON.stringify({
                patient_id: document.getElementById('q-patient').value, location: document.getElementById('q-location').value,
                payment_mode: document.getElementById('q-mode').value, payer: ins ? document.getElementById('q-payer').value : null,
                service: document.getElementById('q-service').value, pricing_basis: document.getElementById('q-basis').value,
                billing_unit: document.getElementById('q-unit').value, quantity: document.getElementById('q-qty').value,
                rate: document.getElementById('q-rate').value, payment_terms: document.getElementById('q-terms').value,
                valid_until: document.getElementById('q-valid').value, notes: document.getElementById('q-notes').value || null,
            }) });
            replaceQuote(r.quotation); closeModal('quote-modal'); toast(r.message);
        } catch (e) { showError('quote-error', e); }
        finally { btn.disabled = false; btn.textContent = 'Save quotation'; }
    });

    // ---- Sent / customer confirmed / cancelled ----
    let qstepTarget = null, qstepKind = 'send';
    function openQuoteStep(q, kind) {
        qstepTarget = q; qstepKind = kind;
        document.getElementById('qstep-error').style.display = 'none';
        document.getElementById('qstep-title').textContent = { send: 'Quotation sent', confirm: 'Customer confirmation', cancel: 'Cancel quotation' }[kind];
        document.getElementById('qstep-sub').textContent = `${q.number} · ${q.patient} · AED ${money(q.total)}`;
        ['send', 'confirm', 'cancel'].forEach(k => document.getElementById('qstep-' + k).style.display = k === kind ? (k === 'confirm' ? 'flex' : '') : 'none');
        document.getElementById('qstep-via').innerHTML = opts(QOPT.send_channels);
        document.getElementById('qstep-method').innerHTML = opts(QOPT.confirmation_methods);
        document.getElementById('qstep-date').value = new Date().toISOString().slice(0, 10);
        document.getElementById('qstep-reason').value = '';
        document.getElementById('qstep-save').textContent = { send: 'Mark as sent', confirm: 'Record confirmation', cancel: 'Cancel quotation' }[kind];
        openModal('qstep-modal');
    }
    document.getElementById('qstep-save').addEventListener('click', async () => {
        document.getElementById('qstep-error').style.display = 'none';
        const body = { send: { sent_via: document.getElementById('qstep-via').value }, confirm: { confirmation_method: document.getElementById('qstep-method').value, confirmed_on: document.getElementById('qstep-date').value }, cancel: { reason: document.getElementById('qstep-reason').value } }[qstepKind];
        try {
            const r = await api(`${BASE}/quotations/${qstepTarget.id}/${qstepKind}`, { method: 'POST', body: JSON.stringify(body) });
            replaceQuote(r.quotation); closeModal('qstep-modal'); toast(r.message);
        } catch (e) { showError('qstep-error', e); }
    });

    // ---- Payment confirmation / insurance activation ----
    let qclearTarget = null;
    function openQuoteClear(q) {
        qclearTarget = q;
        document.getElementById('qclear-error').style.display = 'none';
        document.getElementById('qclear-title').textContent = q.insurance ? 'Activate insurance billing' : 'Confirm payment';
        document.getElementById('qclear-sub').textContent = `${q.number} · ${q.patient} · AED ${money(q.total)}${q.insurance ? ' · ' + (q.payer || '') : ''}`;
        document.getElementById('qclear-self').style.display = q.insurance ? 'none' : 'flex';
        document.getElementById('qclear-ins').style.display = q.insurance ? 'flex' : 'none';
        document.getElementById('qclear-amount').value = q.total.toFixed(2);
        document.getElementById('qclear-method').innerHTML = opts(QOPT.pay_methods);
        document.getElementById('qclear-date').value = new Date().toISOString().slice(0, 10);
        document.getElementById('qclear-ref').value = '';
        ['qclear-policy', 'qclear-pos', 'qclear-noc', 'qclear-liab'].forEach(id => document.getElementById(id).checked = false);
        document.getElementById('qclear-pos-fee').textContent = `— fee AED ${money(q.pos_fee_if_card)} (${QOPT.pos_fee_pct}% + VAT)`;
        qclearMethodChanged();
        openModal('qclear-modal');
    }
    function qclearMethodChanged() { document.getElementById('qclear-pos-wrap').style.display = document.getElementById('qclear-method').value === 'Card' ? 'flex' : 'none'; }
    document.getElementById('qclear-method').addEventListener('change', qclearMethodChanged);
    document.getElementById('qclear-save').addEventListener('click', async () => {
        document.getElementById('qclear-error').style.display = 'none';
        const q = qclearTarget;
        const body = q.insurance
            ? { noc_received: document.getElementById('qclear-noc').checked, liability_acknowledged: document.getElementById('qclear-liab').checked }
            : { amount_received: document.getElementById('qclear-amount').value, payment_method: document.getElementById('qclear-method').value,
                payment_received_on: document.getElementById('qclear-date').value, payment_reference: document.getElementById('qclear-ref').value || null,
                prepayment_policy_received: document.getElementById('qclear-policy').checked, pos_agreement_received: document.getElementById('qclear-pos').checked };
        try {
            const r = await api(`${BASE}/quotations/${q.id}/clear`, { method: 'POST', body: JSON.stringify(body) });
            replaceQuote(r.quotation); closeModal('qclear-modal'); toast(r.message);
        } catch (e) { showError('qclear-error', e); }
    });

    async function openQuoteHistory(q) {
        document.getElementById('history-sub').textContent = `${q.number} · ${q.patient} · keep on record until ${q.retain_until}`;
        const body = document.getElementById('history-body');
        body.innerHTML = '<div class="bl-empty">Loading…</div>';
        openModal('history-modal');
        try {
            const r = await api(`${BASE}/quotations/${q.id}/history`);
            body.innerHTML = r.events.map(ev => `<div style="padding:9px 0; border-bottom:1px solid #EFE8DC;"><div class="inv-name">${esc(ev.label)}</div><div class="inv-meta">${esc(ev.at)} · ${esc(ev.by)}</div>${ev.note ? `<div class="inv-meta" style="color:#2B3A4C;">${esc(ev.note)}</div>` : ''}</div>`).join('') || '<div class="bl-empty">No history recorded.</div>';
        } catch (e) { body.innerHTML = `<div class="bl-empty">${esc(e.message)}</div>`; }
    }

    // ================= Claims =================
    function renderClaims() {
        const body = document.getElementById('claims-body');
        if (!CLAIMS.length) { body.innerHTML = '<div class="bl-empty">No claims raised yet.</div>'; renderSidePager('claims', 'claims-pagination', 0, 0, 1, renderClaims); return; }
        const page = pageSlice('claims', CLAIMS);
        renderSidePager('claims', 'claims-pagination', CLAIMS.length, page.start, page.pageCount, renderClaims);
        body.innerHTML = page.items.map(c => {
            const [bg, fg] = CLAIM_COLORS[c.status];
            const late = c.open && c.age > 30 && !['draft', 'documents_ready'].includes(c.status);
            return `<div class="claim-card" data-id="${c.id}">
                <div class="claim-top">
                    <div style="min-width:0;">
                        <div class="inv-name">${esc(c.patient)}</div>
                        <div class="inv-meta">${esc(c.reference)}${c.period ? ' · ' + esc(c.period) : ''}${c.invoice ? ' · ' + esc(c.invoice) : ''}</div>
                        ${c.payer_reference ? `<div class="inv-meta link">Insurer ref ${esc(c.payer_reference)}${c.portal ? ' · ' + esc(c.portal) : ''}</div>` : ''}
                        <span class="claim-insurer">${esc(c.insurer)}</span>
                    </div>
                    <div>
                        <div class="claim-amt">AED ${money(c.amount)}</div>
                        <div class="claim-age ${late ? 'late' : ''}">${c.open ? (c.submitted_on && !['draft', 'documents_ready'].includes(c.status) ? c.age + ' d since submission' : 'not yet submitted') : 'closed'}</div>
                    </div>
                </div>
                <div class="claim-foot">
                    <select class="status-sel" data-id="${c.id}" style="background:${bg}; color:${fg};" ${CAN_INVOICE ? '' : 'disabled'}>${Object.entries(CLAIM_STATUSES).map(([k, l]) => `<option value="${k}" ${k === c.status ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select>
                    ${CAN_INVOICE ? `<button type="button" class="bl-btn bl-btn-sm" data-claim-open="${c.id}">Submission details</button>` : ''}
                </div>
            </div>`;
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
        body.querySelectorAll('[data-claim-open]').forEach(b => b.addEventListener('click', () => openClaim(CLAIMS.find(x => x.id === Number(b.dataset.claimOpen)))));
    }

    // ---- Insurance submission record: portal, insurer reference, documents ----
    let claimTarget = null;
    async function openClaim(c) {
        claimTarget = c;
        document.getElementById('claim-error').style.display = 'none';
        document.getElementById('claim-sub').textContent = `${c.reference} · ${c.patient} · ${c.insurer} · AED ${money(c.amount)} incl. VAT${c.service_date ? ' · service date ' + c.service_date : ''}`;
        document.getElementById('claim-status').innerHTML = Object.entries(CLAIM_STATUSES).map(([k, l]) => `<option value="${k}" ${k === c.status ? 'selected' : ''}>${esc(l)}</option>`).join('');
        document.getElementById('claim-portal').value = c.portal || '';
        document.getElementById('claim-payer-ref').value = c.payer_reference || '';
        document.getElementById('claim-notes').value = c.notes || '';
        ['claim-medical', 'claim-assessment'].forEach(id => document.getElementById(id).innerHTML = '<option value="">Loading…</option>');
        openModal('claim-modal');
        try {
            const r = await api(`${BASE}/claims/${c.id}`);
            const opts = sel => '<option value="">— none attached —</option>' + r.documents.map(d => `<option value="${d.id}" ${d.id === sel ? 'selected' : ''}>${esc(d.name)}${d.type ? ' (' + esc(d.type) + ')' : ''}</option>`).join('');
            document.getElementById('claim-medical').innerHTML = opts(r.claim.medical_report_document_id);
            document.getElementById('claim-assessment').innerHTML = opts(r.claim.assessment_report_document_id);
        } catch (e) { showError('claim-error', e); }
    }
    document.getElementById('claim-save').addEventListener('click', async () => {
        document.getElementById('claim-error').style.display = 'none';
        try {
            const r = await api(`${BASE}/claims/${claimTarget.id}`, { method: 'PATCH', body: JSON.stringify({
                status: document.getElementById('claim-status').value,
                portal: document.getElementById('claim-portal').value || null,
                payer_reference: document.getElementById('claim-payer-ref').value || null,
                medical_report_document_id: document.getElementById('claim-medical').value || null,
                assessment_report_document_id: document.getElementById('claim-assessment').value || null,
                notes: document.getElementById('claim-notes').value || null,
            }) });
            const idx = CLAIMS.findIndex(x => x.id === claimTarget.id); CLAIMS[idx] = r.claim; renderClaims();
            if (r.invoice) replaceInvoice(r.invoice);
            closeModal('claim-modal'); toast(r.message);
        } catch (e) { showError('claim-error', e); }
    });

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
        if (!PREAUTHS.length) { box.innerHTML = '<div class="bl-empty">No pre-authorization requests on file.</div>'; renderSidePager('preauths', 'preauth-pagination', 0, 0, 1, renderPreauths); return; }
        const page = pageSlice('preauths', PREAUTHS);
        renderSidePager('preauths', 'preauth-pagination', PREAUTHS.length, page.start, page.pageCount, renderPreauths);
        box.innerHTML = page.items.map(p => {
            const [bg, fg] = PA_COLORS[p.status];
            return `<div class="inv-row" style="grid-template-columns:1fr auto;" data-id="${p.id}">
                <div>
                    <div class="inv-name">${esc(p.patient)} — ${esc(p.service)}</div>
                    <div class="inv-meta">${esc(p.payer)} · ${p.hours} h · ${esc(p.from)} → ${esc(p.to)} · submitted ${esc(p.submitted)}</div>
                    <div class="inv-meta">Ref ${esc(p.reference)}${p.payer_reference ? ' · payer ref ' + esc(p.payer_reference) : ''}</div>
                    ${p.status === 'approved' && p.approved_hours ? `<div class="inv-meta receipt">Approved ${p.approved_hours} h at ${p.coverage_percent}%${p.on_client_file ? ' · added to client file' : ''}</div>` : ''}
                    ${p.status === 'denied' && p.denial_reason ? `<div class="inv-meta voided">${esc(p.denial_reason)}</div>` : ''}
                    ${p.decided_on ? `<div class="inv-meta">Payer answered ${esc(p.decided_on)}${p.decision_channel ? ' via ' + esc(p.decision_channel) : ''}${p.decided_by ? ' · recorded by ' + esc(p.decided_by) : ''}</div>` : ''}
                    ${p.status === 'requested' ? `<div class="inv-meta">Awaiting the payer’s answer</div>` : ''}
                </div>
                <div style="display:flex; flex-direction:column; align-items:stretch; gap:6px; min-width:96px;">
                    <span class="inv-status" style="background:${bg}; color:${fg}; text-align:center;">${esc(p.status_label)}</span>
                    ${CAN_INVOICE && p.status === 'requested' ? `<button type="button" class="act-btn next go" data-decide="approved" data-id="${p.id}">Approved</button><button type="button" class="act-btn danger" data-decide="denied" data-id="${p.id}">Denied</button>` : ''}
                    ${CAN_INVOICE && p.status === 'denied' ? `<button type="button" class="act-btn" data-resubmit="${p.id}">Resubmit</button>` : ''}
                </div>
            </div>`;
        }).join('');
        box.querySelectorAll('[data-decide]').forEach(btn => btn.addEventListener('click', () => {
            openDecision(PREAUTHS.find(x => x.id === Number(btn.dataset.id)), btn.dataset.decide);
        }));
        box.querySelectorAll('[data-resubmit]').forEach(btn => btn.addEventListener('click', () => {
            const p = PREAUTHS.find(x => x.id === Number(btn.dataset.resubmit));
            openPreauth(p);
        }));
    }

    // ---- Payer decision: the payer answers outside the CRM, staff record it ----
    let decisionTarget = null, decisionStatus = 'approved';
    function openDecision(p, status) {
        decisionTarget = p; decisionStatus = status;
        const approving = status === 'approved';
        document.getElementById('padec-error').style.display = 'none';
        document.getElementById('padec-title').textContent = approving ? 'Payer approved the request' : 'Payer denied the request';
        document.getElementById('padec-sub').textContent = `${p.reference} · ${p.patient} · ${p.payer} · ${p.service} · ${p.hours} h requested`;
        document.getElementById('padec-hint').textContent = approving
            ? 'Record what the payer confirmed. Saving adds these hours to the client’s file, so sessions start billing to this payer. This can’t be undone here.'
            : 'Record the payer’s reason. A denied request stays on file and can be resubmitted as a new one.';
        document.getElementById('padec-date').value = new Date().toISOString().slice(0, 10);
        document.getElementById('padec-channel').innerHTML = PREAUTH_CHANNELS.map(c => `<option>${esc(c)}</option>`).join('');
        document.getElementById('padec-approve-fields').style.display = approving ? 'flex' : 'none';
        document.getElementById('padec-deny-fields').style.display = approving ? 'none' : '';
        document.getElementById('padec-ref').value = '';
        document.getElementById('padec-hours').value = p.hours;
        document.getElementById('padec-pct').value = '';
        document.getElementById('padec-to').value = p.to_iso;
        document.getElementById('padec-reason').value = '';
        const save = document.getElementById('padec-save');
        save.textContent = approving ? 'Record approval' : 'Record denial';
        openModal('padec-modal');
    }
    document.getElementById('padec-save').addEventListener('click', async () => {
        document.getElementById('padec-error').style.display = 'none';
        const approving = decisionStatus === 'approved';
        try {
            const r = await api(`${BASE}/pre-authorizations/${decisionTarget.id}`, { method: 'PATCH', body: JSON.stringify({
                status: decisionStatus,
                decided_on: document.getElementById('padec-date').value,
                decision_channel: document.getElementById('padec-channel').value,
                payer_reference: approving ? document.getElementById('padec-ref').value : null,
                approved_hours: approving ? document.getElementById('padec-hours').value : null,
                coverage_percent: approving ? document.getElementById('padec-pct').value : null,
                valid_to: approving ? document.getElementById('padec-to').value || null : null,
                denial_reason: approving ? null : document.getElementById('padec-reason').value,
            }) });
            const idx = PREAUTHS.findIndex(x => x.id === decisionTarget.id); PREAUTHS[idx] = r.preauth; renderPreauths();
            closeModal('padec-modal'); toast(r.message);
        } catch (e) { showError('padec-error', e); }
    });

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
        // One click, one request: the button locks until the server answers,
        // and the form closes as soon as the request is filed.
        const btn = document.getElementById('preauth-save');
        if (btn.disabled) return;
        btn.disabled = true;
        btn.textContent = 'Submitting…';
        try {
            const r = await api(`${BASE}/pre-authorizations`, { method: 'POST', body: JSON.stringify({
                patient_id: document.getElementById('pa-patient').value, payer: document.getElementById('pa-payer').value,
                service: document.getElementById('pa-service').value, hours: document.getElementById('pa-hours').value,
                valid_from: document.getElementById('pa-from').value, valid_to: document.getElementById('pa-to').value,
                justification: document.getElementById('pa-just').value || null, resubmitted_from_id: preauthResubmitFrom,
            }) });
            PREAUTHS.unshift(r.preauth); sidePage.preauths = 1; renderPreauths(); closeModal('preauth-modal'); toast(r.message);
        } catch (e) {
            showError('preauth-error', e);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Submit request';
        }
    });

    // ================= Aging & statements tab =================
    function renderAgingRows() {
        const body = document.getElementById('aging-body');
        if (!AGING_ROWS.length) { body.innerHTML = '<tr><td colspan="5" class="bl-empty">Nothing outstanding — every issued invoice is settled.</td></tr>'; return; }
        body.innerHTML = AGING_ROWS.map(i => {
            const overdue = i.days_past_due > 0;
            return `<tr data-id="${i.id}">
                <td><strong>${esc(i.patient)}</strong><div style="color:#8A7D6C; font-weight:600;">${esc(i.number)} · ${esc(i.payer)} · due ${esc(i.due_label)}</div>${i.reminder_sent_at ? `<div style="color:#24619C; font-weight:700;">Reminder sent ${esc(i.reminder_sent_at)}</div>` : ''}</td>
                <td class="center"><span class="age-chip" style="background:${overdue ? '#F9E4E2' : '#E3F1E9'}; color:${overdue ? '#B3261E' : '#1E7A46'};">${esc(i.age_label)}</span></td>
                <td class="center">${money(i.total)}</td>
                <td class="center" style="color:#B3261E;">${money(i.balance)}</td>
                <td class="center"><button type="button" class="bl-btn bl-btn-sm bl-btn-dark" data-remind="${i.id}">Send reminder</button></td>
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
    // Family cancellations are priced off the notice given, so those two
    // states carry an hours box; the ledger decides free vs late from it.
    const NOTICE_STATES = ['cancelled_late', 'cancelled_notice'];
    function defaultNotice(state) { return state === 'cancelled_notice' ? POLICY.notice_hours : Math.max(0, POLICY.notice_hours - 1); }
    function noticeValue(r) {
        if (!NOTICE_STATES.includes(r.attendance)) return '';
        return r.notice_hours === null || r.notice_hours === undefined ? defaultNotice(r.attendance) : Number(r.notice_hours);
    }
    function stateForNotice(n) { return n >= POLICY.notice_hours ? 'cancelled_notice' : 'cancelled_late'; }
    function chargeFactor(state, notice) {
        if (NOTICE_STATES.includes(state)) {
            const h = notice === '' || notice === null || isNaN(notice) ? defaultNotice(state) : Number(notice);
            return h >= POLICY.notice_hours ? 0 : POLICY.late_pct / 100;
        }
        return { completed: 1, no_show: POLICY.no_show_pct / 100, cancelled_clinic: 0 }[state] ?? 0;
    }
    /**
     * Re-price one picker row the way the ledger would, so the amount on the
     * right follows the attendance and the notice hours without a round trip.
     * The invoice itself is still composed server-side on preview.
     */
    function repriceRow(row, notice) {
        const vatRate = Number(currentPatient?.vat_rate ?? 0.05);
        row.factor = chargeFactor(row.attendance, notice);
        row.bill_hours = row.factor === 0 ? 0 : row.hours;
        row.net = row.bill_hours * row.rate * row.factor;
        row.vat = row.net * vatRate;
        row.gross = row.net + row.vat;
        row.charge_rule = chargeRule(row.attendance, notice);
        const el = document.querySelector(`.sess-row[data-id="${row.id}"]`);
        if (!el) return;
        el.querySelector('.sess-amount').innerHTML = `${money(row.gross)}<div class="sess-invoiced-tag">${esc(row.billing_status)}</div>`;
        const rule = el.querySelector('.sess-rule');
        rule.textContent = row.charge_rule;
        rule.style.color = row.bill_hours > 0 ? '#5A6B7E' : '#98897A';
        setPickerCount();
    }
    function chargeRule(state, notice) {
        if (NOTICE_STATES.includes(state) && notice !== '' && notice !== null && !isNaN(notice)) {
            const h = Number(notice);
            return h >= POLICY.notice_hours
                ? `Cancelled ${h} h ahead — not charged`
                : `Cancelled ${h} h ahead — ${POLICY.late_pct}% charged`;
        }
        return {
            completed: 'Attended — 100% charged',
            no_show: `No-show — ${POLICY.no_show_pct}% charged`,
            cancelled_late: `Cancelled late — ${POLICY.late_pct}% charged`,
            cancelled_notice: 'Cancelled with notice — not charged',
            cancelled_clinic: 'Cancelled by clinic — not charged',
        }[state];
    }
    function renderSessionRows() {
        const box = document.getElementById('picker-sessions');
        if (!currentLedger.length) { box.innerHTML = '<div class="bl-empty">No delivered sessions on file for this client yet.</div>'; setPickerCount(); return; }
        box.innerHTML = currentLedger.map(r => `
            <div class="sess-row ${r.invoiced ? 'invoiced' : ''}" data-id="${r.id}">
                <input type="checkbox" data-check="${r.id}" ${r.invoiced ? '' : ''}>
                <div class="sess-main">
                    <div class="sess-top"><span>${esc(r.service_label)} — 1:1 session</span><span>${esc(r.date_label)} · ${esc(r.start_time)}–${esc(r.end_time)}</span></div>
                    <div class="sess-meta">${esc(r.setting)} by ${esc(r.therapist_name)}${r.trainee_note ? ' (' + esc(r.trainee_note) + ')' : ''} · ${esc(r.payer)} ${r.coverage_pct}%${r.invoiced ? ' · ' + esc(r.invoice_number) : ''}${r.insufficient_authorization ? ' · <strong style="color:#8A5A10;">' + esc(r.insufficient_message) + '</strong>' : ''}</div>
                    <div class="att-line">
                        <select class="att-select" data-att="${r.id}">${ATT_OPTIONS.map(([k, l]) => `<option value="${k}" ${k === r.attendance ? 'selected' : ''}>${esc(l)}</option>`).join('')}</select>
                        <input type="number" class="notice-input" data-notice="${r.id}" min="0" max="720" step="any" value="${noticeValue(r)}" placeholder="h" title="Hours of notice the family gave" ${NOTICE_STATES.includes(r.attendance) ? '' : 'hidden'}>
                    </div>
                    <span class="sess-rule" style="color:${r.bill_hours > 0 ? '#5A6B7E' : '#98897A'};">${esc(r.charge_rule)}</span>
                </div>
                <div class="sess-amount">${money(r.gross)}<div class="sess-invoiced-tag">${esc(r.billing_status)}</div></div>
            </div>`).join('');

        box.querySelectorAll('[data-check]').forEach(cb => cb.addEventListener('change', setPickerCount));
        box.querySelectorAll('[data-att]').forEach(sel => sel.addEventListener('change', () => {
            const row = currentLedger.find(r => r.id === Number(sel.dataset.att));
            const notice = sel.closest('.sess-main').querySelector('[data-notice]');
            if (NOTICE_STATES.includes(sel.value)) {
                // Keep the box in step with the state picked: 24 h means free,
                // 23 h means the late fee, so switching states moves the hours.
                notice.value = defaultNotice(sel.value);
                notice.hidden = false;
            } else {
                notice.value = '';
                notice.hidden = true;
            }
            row.attendance = sel.value;
            row.notice_hours = notice.value === '' ? null : Number(notice.value);
            repriceRow(row, notice.value);
        }));
        box.querySelectorAll('[data-notice]').forEach(inp => inp.addEventListener('input', () => {
            const row = currentLedger.find(r => r.id === Number(inp.dataset.notice));
            const main = inp.closest('.sess-main');
            if (inp.value !== '' && Number(inp.value) >= 0) {
                // The hours are what actually price the cancellation, so let
                // them drive the dropdown rather than contradict it.
                row.attendance = stateForNotice(Number(inp.value));
                row.notice_hours = Number(inp.value);
                main.querySelector('[data-att]').value = row.attendance;
            } else {
                row.notice_hours = null;
            }
            repriceRow(row, inp.value);
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
        currentLedger.forEach(r => {
            out[r.id] = { state: r.attendance };
            if (NOTICE_STATES.includes(r.attendance) && r.notice_hours !== null && r.notice_hours !== undefined) {
                out[r.id].notice_hours = r.notice_hours;
            }
        });
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
            ${p.quotation_notice ? `<div class="warn-box" style="margin-bottom:12px;">${esc(p.quotation_notice)}</div>` : ''}
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
    async function submitInvoice(thenSubmit) {
        const err = document.getElementById('picker-error'); err.style.display = 'none';
        try {
            const body = { patient_id: pickerPatientSel.value, session_ids: selectedIds(), attendance: attendancePayload(), acknowledge_settled: true };
            const r = await api(`${BASE}/invoices`, { method: 'POST', body: JSON.stringify(body) });
            addInvoices([r.invoice]);
            closeModal('picker-modal'); toast(r.message);
            if (thenSubmit) workflowStep(r.invoice, 'submit');
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

    renderQuotes();
    renderInvoices(); renderClaims(); renderClaimAging(); renderRevenueByPayer(); renderPreauths(); renderAgingRows();
})();
</script>
@endsection
