@extends('layouts.admin-sidebar')

@section('title', 'Leads Pipeline · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    <style>
        /* ===========================
           Page-level scroll guard
           (safe now that .leads-page is JS-clamped to the
           visible width — this only blocks stray overflow,
           it doesn't clip any reachable content)
        =========================== */
        html, body {
            overflow-x: hidden;
        }

        /* ===========================
           Main container
        =========================== */
        .leads-page {
            display: flex;
            flex-direction: column;
            gap: 0;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            /* .main-content-inner (parent layout) adds 22px/28px padding —
               bleed out of it so the topbar sits flush against the sidebar
               and the top edge instead of floating with a gap. */
            margin: -22px -28px 0;
        }

        .leads-container {
            background: transparent;
            border: none;
            border-radius: 0;
            overflow: visible;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        /* ===========================
           Topbar
        =========================== */
        .kanban-topbar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 28px;
            border-bottom: 1px solid #EBE4DA;
            background: #FFFDFA;
            flex-wrap: wrap;
        }
        .kanban-topbar .topbar-info { flex: 1; min-width: 180px; }
        .kanban-topbar .topbar-title { font: 600 21px/1.2 'Baloo 2'; color: #16436E; }
        .kanban-topbar .topbar-sub { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .topbar-buttons { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .btn-filter {
            background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px;
            padding: 10px 16px; font: 800 13px 'Nunito Sans'; cursor: pointer; white-space: nowrap;
        }
        .btn-filter:hover { background: #F5EFE7; }
        .btn-filter.is-active { background: #16436E; color: #fff; border-color: #16436E; }
        .btn-filter.is-active:hover { background: #0F3255; }
        .btn-new-lead {
            background: #C8355F; color: white; border: none; border-radius: 10px;
            padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; white-space: nowrap;
        }
        .btn-new-lead:hover { background: #A82348; }

        /* ===========================
           Kanban board
        =========================== */
        .kanban-board {
            overflow-x: auto;
            padding: 20px 28px 28px 28px;
            display: grid;
            grid-template-columns: repeat(4, minmax(250px, 1fr));
            gap: 14px;
            align-items: start;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: #D8CDBC transparent;
        }
        .kanban-board.show-terminated {
            grid-template-columns: repeat(5, minmax(250px, 1fr));
        }
        /* Slim custom scrollbar instead of the bulky OS-default one */
        .kanban-board::-webkit-scrollbar { height: 7px; }
        .kanban-board::-webkit-scrollbar-track { background: transparent; }
        .kanban-board::-webkit-scrollbar-thumb { background: #D8CDBC; border-radius: 999px; }
        .kanban-board::-webkit-scrollbar-thumb:hover { background: #C2B4A0; }
        .kanban-board::-webkit-scrollbar-button { display: none; width: 0; height: 0; }
        .kanban-column {
            min-width: 0;
            height: 100%;
            max-height: calc(100vh - 220px);
            background: #F0EBE1;
            border-radius: 14px;
            padding: 12px;
            display: flex;
            flex-direction: column;
        }
        .kanban-column-head {
            display: flex; align-items: center; gap: 8px; padding: 2px 6px 10px; flex: none;
        }
        .kanban-column-title { font: 600 14px 'Baloo 2'; color: #16436E; flex: 1; }
        .kanban-column-count {
            background: #FFFFFF; border-radius: 8px;
            padding: 2px 8px; font: 800 11.5px 'Nunito Sans'; color: #8A7D6C;
        }
        .kanban-column-body {
            flex: 1; min-height: 40px; overflow-y: auto;
            display: flex; flex-direction: column; gap: 10px; padding-right: 2px;
        }
        .kanban-empty { text-align: center; color: #B0A493; font: 600 12px 'Nunito Sans'; padding: 20px 0; }

        /* ===========================
           Lead card
        =========================== */
        .lead-card {
            background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 12px; padding: 12px 14px;
            display: flex; flex-direction: column; gap: 7px; cursor: pointer;
        }
        .lead-card:hover { border-color: #D8CDBC; }
        .lead-card-top { display: flex; align-items: center; gap: 8px; }
        .lead-card-name { font: 800 14px 'Nunito Sans'; color: #2B3A4C; flex: 1; }
        .lead-card-source-badge { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
        .lead-card-tags { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .lead-card-tag { border-radius: 6px; padding: 2px 8px; font: 800 10.5px 'Nunito Sans'; white-space: nowrap; }
        .lead-card-parent { font: 600 12px 'Nunito Sans'; color: #98897A; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .lead-card-notes {
            font: 600 12px/1.45 'Nunito Sans'; color: #5A6B7E; display: -webkit-box;
            -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .lead-card-bottom {
            display: flex; align-items: center; gap: 8px; border-top: 1px solid #F3EDE3; padding-top: 8px;
        }
        .lead-card-value { font: 800 12px 'Nunito Sans'; color: #16436E; flex: 1; }
        .lead-card-time { font: 600 11px 'Nunito Sans'; color: #B0A493; }
        .lead-card-owner { font: 600 11px 'Nunito Sans'; color: #98897A; }
        .lead-card-owner.is-unassigned { font-weight: 800; color: #B97F24; }
        .lead-card-actions { display: flex; gap: 4px; }
        .lead-card-actions button {
            width: 24px; height: 24px; border-radius: 8px; border: 1px solid #E2DACE; background: #FFFDFA;
            font: 800 13px/1 'Nunito Sans'; cursor: pointer;
        }
        .lead-card-actions .btn-view { color: #24619C; }
        .lead-card-actions .btn-view:hover { background: #E7EFF7; }
        .lead-card-actions .btn-advance { color: #C8355F; font: 800 13px/1 'Nunito Sans'; }
        .lead-card-actions .btn-advance:hover { background: #C8355F; color: #fff; }
        .lead-card-actions button:disabled { opacity: 0.5; cursor: default; }

        /* ===========================
           Modals
        =========================== */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45);
            align-items: center; justify-content: center; z-index: 9999; padding: 16px;
        }
        .modal-box {
            width: 600px; max-width: 100%; max-height: 88vh; overflow-y: auto; background: #FFFDFA;
            border-radius: 18px; padding: 26px 28px; display: flex; flex-direction: column; gap: 16px;
            box-shadow: 0 20px 60px rgba(22,42,60,0.3);
        }
        .modal-box.modal-box-narrow { width: 520px; }
        .modal-header { display: flex; align-items: center; gap: 12px; }
        .modal-header-title { font: 600 20px 'Baloo 2'; color: #16436E; }
        .modal-header-sub { font: 600 12px 'Nunito Sans'; color: #98897A; }
        .modal-close-btn {
            width: 32px; height: 32px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF;
            color: #5A6B7E; font: 800 15px/1 'Nunito Sans'; cursor: pointer; display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
        }
        .modal-close-btn:hover { background: #F6F3EE; }
        .field-label {
            font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase;
            letter-spacing: 0.6px; margin-bottom: 4px;
        }
        .field-input, .field-select, .field-textarea {
            width: 100%; padding: 8px 12px; border: 1px solid #E2DACE; border-radius: 8px;
            background: #F6F3EE; font: 700 14px 'Nunito Sans'; color: #2B3A4C; outline: none;
            box-sizing: border-box;
        }
        .field-input:focus, .field-select:focus, .field-textarea:focus {
            border-color: #C8355F;
        }
        .form-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-grid-name-age { display: grid; grid-template-columns: 1fr 110px; gap: 12px; }
        .modal-actions { display: flex; gap: 10px; border-top: 1px solid #F3EDE3; padding-top: 12px; }
        .btn-save {
            flex: 1; background: #C8355F; color: white; border: none; border-radius: 8px;
            padding: 10px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer;
        }
        .btn-save:hover { background: #B02B52; }
        .btn-save:disabled { opacity: 0.6; cursor: default; }
        .btn-cancel {
            flex: 0.5; background: #FFFFFF; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 8px;
            padding: 10px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer;
        }
        .btn-cancel:hover { background: #F6F3EE; }
        .modal-timestamps {
            display: flex; justify-content: space-between; font: 600 11px 'Nunito Sans'; color: #B0A493;
            border-top: 1px solid #F3EDE3; padding-top: 12px;
        }

        /* ===========================
           Toast
        =========================== */
        .toast-stack {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 340px;
        }
        .toast {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #FFFDFA;
            border: 1px solid #EBE4DA;
            border-left: 4px solid #16436E;
            border-radius: 10px;
            padding: 12px 14px;
            font: 700 12.5px/1.4 'Nunito Sans';
            color: #2B3A4C;
            box-shadow: 0 8px 24px rgba(22,42,60,0.12);
        }
        .toast-success { border-left-color: #178A45; }
        .toast-error { border-left-color: #C8355F; }
        .toast-info { border-left-color: #24619C; }
        .toast-icon { font-size: 14px; line-height: 1.4; }
        .toast-text { flex: 1; }
        .toast-close {
            border: none; background: transparent; color: #B0A493; cursor: pointer;
            font: 800 12px 'Nunito Sans'; padding: 0; line-height: 1.4;
        }
        .toast-close:hover { color: #5A6B7E; }

        /* ===========================
           Action panel (side drawer)
        =========================== */
        .ap-overlay {
            display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.35);
            z-index: 9998; justify-content: flex-end;
        }
        .ap-panel {
            width: 420px; max-width: 100%; height: 100%; background: #FFFDFA;
            box-shadow: -12px 0 40px rgba(22,42,60,0.18); display: flex; flex-direction: column;
            overflow: hidden;
        }
        .ap-head { padding: 20px 22px 14px; border-bottom: 1px solid #EBE4DA; flex: none; }
        .ap-head-top { display: flex; align-items: center; gap: 10px; }
        .ap-name { font: 600 22px 'Baloo 2'; color: #16436E; flex: 1; }
        .ap-close {
            width: 32px; height: 32px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF;
            color: #5A6B7E; font: 800 15px/1 'Nunito Sans'; cursor: pointer; display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
        }
        .ap-close:hover { background: #F6F3EE; }
        .ap-meta { display: flex; align-items: center; gap: 8px; margin-top: 8px; }
        .ap-updated { font: 600 11.5px 'Nunito Sans'; color: #98897A; }

        .ap-body { flex: 1; overflow-y: auto; padding: 18px 22px 22px; display: flex; flex-direction: column; gap: 18px; }
        .ap-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .ap-stat { background: #F6F3EE; border-radius: 10px; padding: 10px 12px; }

        .ap-section-label {
            font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase;
            letter-spacing: 0.6px; display: flex; align-items: baseline; justify-content: space-between;
        }
        .ap-section-link {
            font: 700 11px 'Nunito Sans'; color: #C8355F; text-decoration: none; cursor: pointer;
            text-transform: none; letter-spacing: normal;
        }
        .ap-section-link:hover { color: #A82348; }
        .ap-enquiry-notes { font: 600 13px/1.5 'Nunito Sans'; color: #2B3A4C; margin-top: 6px; }

        .ap-hint { font: 600 11px/1.5 'Nunito Sans'; color: #B97F24; margin-top: 6px; }
        .ap-hint.is-hidden { display: none; }

        .ap-assignment-log { display: none; flex-direction: column; gap: 6px; margin-top: 8px; }
        .ap-assignment-log.is-open { display: flex; }
        .ap-log-entry {
            font: 600 11.5px 'Nunito Sans'; color: #5A6B7E; background: #F6F3EE; border-radius: 8px; padding: 7px 10px;
        }
        .ap-log-entry .ap-log-when { color: #98897A; }
        .ap-log-empty { font: 600 11.5px 'Nunito Sans'; color: #B0A493; }

        .ap-notes-list { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; max-height: 220px; overflow-y: auto; }
        .ap-note-entry { border: 1px solid #EFE8DD; border-radius: 10px; padding: 9px 11px; }
        .ap-note-author-row { display: flex; align-items: baseline; gap: 8px; }
        .ap-note-author { flex: 1; font: 800 11.5px 'Nunito Sans'; color: #2B3A4C; }
        .ap-note-when { font: 600 10.5px 'Nunito Sans'; color: #A79C8E; }
        .ap-note-body { font: 600 12.5px/1.5 'Nunito Sans'; color: #5A6B7E; margin-top: 3px; }

        .ap-actions { border-top: 1px solid #EBE4DA; padding: 18px 22px 22px; display: flex; gap: 10px; flex: none; }
        .ap-actions button {
            border: none; border-radius: 10px; padding: 14px 18px; font: 800 13px 'Nunito Sans';
            letter-spacing: 0.2px; cursor: pointer; box-sizing: border-box; transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.05s ease;
        }
        .ap-actions button:active { transform: translateY(1px); }
        .ap-btn-success { flex: 1; background: #E3F1E9; color: #2E7D5B; }
        .ap-btn-success:hover { background: #D2E7DA; }
        .ap-btn-success:disabled { background: #EFE8DD; color: #B0A493; cursor: default; }
        .ap-btn-followup { flex: 1; background: #FBF0DC; color: #8A5A10; }
        .ap-btn-followup:hover { background: #F5E5C4; }
        .ap-btn-terminate {
            flex: 1; background: #FFFFFF; color: #B3261E; border: 1px solid #E8CFCC !important;
            white-space: nowrap;
        }
        .ap-btn-terminate:hover { background: #F9E4E2; }
        .ap-btn-convert {
            flex: 1; background: #16436E; color: #fff; padding: 16px 18px; font-size: 13.5px;
            box-shadow: 0 4px 14px rgba(22,67,110,0.28);
        }
        .ap-btn-convert:hover { background: #0F3255; box-shadow: 0 4px 16px rgba(22,67,110,0.36); }

        @media (max-width: 480px) {
            .ap-panel { width: 100%; }
            .ap-actions { flex-wrap: wrap; }
        }

        /* ===========================
           Tablet
        =========================== */
        @media (max-width: 900px) {
            .kanban-board { grid-template-columns: repeat(2, minmax(220px, 1fr)); }
            .kanban-board.show-terminated { grid-template-columns: repeat(2, minmax(220px, 1fr)); }
        }

        /* ===========================
           Mobile
        =========================== */
        @media (max-width: 768px) {
            /* Parent layout's 66px top padding clears the fixed hamburger button
               (top:14px, ~56px tall) — only bleed horizontally here, keep the top
               padding intact or the topbar renders underneath that floating button. */
            .leads-page { margin: 0 -28px 0; }

            .kanban-topbar {
                padding: 14px 16px;
                gap: 10px;
                flex-direction: column;
                align-items: stretch;
            }
            .kanban-topbar .topbar-info { min-width: 0; }
            .kanban-topbar .topbar-title { font-size: 16px; }
            .kanban-topbar .topbar-sub { font-size: 11.5px; line-height: 1.4; }
            .topbar-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
            .btn-filter, .btn-new-lead { flex: 1 1 auto; text-align: center; padding: 10px 12px; }

            .kanban-board { padding: 12px 16px 16px 16px; gap: 10px; grid-template-columns: 1fr; }
            .kanban-board.show-terminated { grid-template-columns: 1fr; }
            .kanban-column { max-height: none; }

            .modal-box, .modal-box.modal-box-narrow {
                width: 100%; max-width: 100%; max-height: 92vh; padding: 20px 16px; border-radius: 14px;
            }
            .modal-header-title { font-size: 18px; }
            .form-grid-2col, .form-grid-name-age { grid-template-columns: 1fr; }
            .modal-timestamps { flex-direction: column; gap: 4px; }

            .toast-stack { left: 16px; right: 16px; top: 16px; max-width: none; }
        }

        /* ===========================
           Small phones
        =========================== */
        @media (max-width: 420px) {
            .topbar-buttons { flex-direction: column; }
            .btn-filter, .btn-new-lead { flex: 1 1 100%; }
            .modal-overlay { padding: 8px; }
            .modal-box { padding: 16px 12px; }
        }
    </style>

    @php
        $sourceBadgeColors = [
            'WhatsApp'    => ['bg' => '#E3F4E9', 'color' => '#178A45'],
            'Website'     => ['bg' => '#E7EFF7', 'color' => '#24619C'],
            'Instagram'   => ['bg' => '#FAE7F2', 'color' => '#C13584'],
            'Referral'    => ['bg' => '#F7EEDD', 'color' => '#B97F24'],
            'Google'      => ['bg' => '#EEE9F7', 'color' => '#6E4FA8'],
            'Walk-in'     => ['bg' => '#EDEFF1', 'color' => '#5A6B7E'],
            'Phone call'  => ['bg' => '#E3F1E9', 'color' => '#2E7D5B'],
            'Event'       => ['bg' => '#F7EEDD', 'color' => '#8A5A10'],
        ];
        $defaultBadgeColor = ['bg' => '#EDEFF1', 'color' => '#5A6B7E'];

        // Visual columns the board renders. A column can absorb more than one real
        // status (Initial assessment = assessment_booked + assessment_done) so the
        // 5-status pipeline still fits the 4-column layout without losing data.
        $columnDefs = [
            'new' => ['label' => 'New', 'statuses' => ['new']],
            'contacted' => ['label' => 'Contacted / Follow-up', 'statuses' => ['contacted']],
            'assessment' => ['label' => 'Initial assessment', 'statuses' => ['assessment_booked', 'assessment_done']],
            'enrolled' => ['label' => 'Enrolled', 'statuses' => ['enrolled']],
        ];

        $statusTagColors = [
            'new' => ['bg' => '#E7EFF7', 'color' => '#24619C'],
            'contacted' => ['bg' => '#FBF0DC', 'color' => '#8A5A10'],
            'assessment_booked' => ['bg' => '#EDE7F5', 'color' => '#6E4FA8'],
            'assessment_done' => ['bg' => '#EDE7F5', 'color' => '#6E4FA8'],
            'enrolled' => ['bg' => '#E3F1E9', 'color' => '#2E7D5B'],
            'terminated' => ['bg' => '#F9E4E2', 'color' => '#B3261E'],
        ];
        $statusTagLabels = [
            'new' => 'New',
            'contacted' => 'Contacted',
            'assessment_booked' => 'Booked',
            'assessment_done' => 'Assessment done',
            'enrolled' => 'Enrolled',
            'terminated' => 'Terminated',
        ];
    @endphp

    <div class="leads-page">
        <!-- Main container -->
        <div class="leads-container">

            <!-- Top Bar -->
            <div class="kanban-topbar">
                <div class="topbar-info">
                    <div class="topbar-title">Leads pipeline</div>
                    <div class="topbar-sub">
                        <span id="leadCount">{{ $activeLeads->count() }}</span> active leads ·
                        <span id="unassignedCount">{{ $unassignedCount }}</span> unassigned ·
                        <span id="followUpCount">{{ $followUpCount }}</span> on follow-up ·
                        <span id="totalValue">AED {{ number_format($totalValue ?? 0, 0) }}</span> est. monthly value
                    </div>
                </div>
                <div class="topbar-buttons">
                    <button class="btn-filter">Filter: All sources</button>
                    <button class="btn-filter" id="terminatedToggleBtn" onclick="toggleTerminated()">Terminated · {{ $terminatedCount }}</button>
                    <button class="btn-new-lead" onclick="openLeadModal()">+ New Lead</button>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="kanban-board" id="kanbanBoard">
                @foreach ($columnDefs as $colKey => $col)
                    @php
                        // Once a lead is converted to a client, it's graduated out of the
                        // pipeline into the Patients module - stop showing it here even
                        // though the Lead row itself stays (Calendar still keys off it).
                        $colLeads = $leads->whereIn('status', $col['statuses'])->reject(fn ($lead) => $lead->patient);
                    @endphp
                    <div class="kanban-column">
                        <div class="kanban-column-head">
                            <div class="kanban-column-title">{{ $col['label'] }}</div>
                            <span class="kanban-column-count" id="{{ $colKey }}Count">{{ $colLeads->count() }}</span>
                        </div>
                        <div class="kanban-column-body" id="{{ $colKey }}Leads">
                            @forelse ($colLeads as $lead)
                                @include('lead._card', ['lead' => $lead])
                            @empty
                                <div class="kanban-empty">No leads in this stage</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                <!-- Terminated column - hidden until "Terminated" is toggled on -->
                <div class="kanban-column" id="terminatedColumn" style="display: none;">
                    <div class="kanban-column-head">
                        <div class="kanban-column-title">Terminated</div>
                        <span class="kanban-column-count" id="terminatedCount">{{ $terminatedCount }}</span>
                    </div>
                    <div class="kanban-column-body" id="terminatedLeads">
                        @forelse ($leads->where('status', 'terminated') as $lead)
                            @include('lead._card', ['lead' => $lead])
                        @empty
                            <div class="kanban-empty">No terminated leads</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast stack -->
    <div id="toastStack" class="toast-stack"></div>

    <!-- View Lead Modal -->
    <div id="viewLeadModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <div style="flex: 1;">
                    <div class="modal-header-title" id="viewLeadTitle">Lead Details</div>
                    <div class="modal-header-sub" id="viewLeadSubtitle">View and update lead information</div>
                </div>
                <button class="modal-close-btn" onclick="closeViewLeadModal()">✕</button>
            </div>

            <form id="editLeadForm" onsubmit="updateLeadDetails(event)">
                @csrf
                @method('PUT')
                <input type="hidden" id="editLeadId" name="lead_id" value="">

                <div class="form-grid-2col">
                    <div>
                        <div class="field-label">Child's Name</div>
                        <input id="editChildName" name="child_name" type="text" class="field-input">
                    </div>
                    <div>
                        <div class="field-label">Age</div>
                        <input id="editChildAge" name="child_age" type="text" class="field-input">
                    </div>
                    <div>
                        <div class="field-label">Parent / Guardian</div>
                        <input id="editParentName" name="parent_guardian_name" type="text" class="field-input">
                    </div>
                    <div>
                        <div class="field-label">Phone</div>
                        <input id="editPhone" name="phone" type="text" class="field-input">
                    </div>
                    <div>
                        <div class="field-label">Source</div>
                        <select id="editSource" name="source" class="field-select">
                            <option value="Walk-in">Walk-in</option>
                            <option value="Phone call">Phone call</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Website">Website</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Referral">Referral</option>
                            <option value="Google">Google</option>
                            <option value="Event">Event</option>
                        </select>
                    </div>
                    <div>
                        <div class="field-label">Interested In</div>
                        <select id="editInterest" name="interested_in" class="field-select">
                            <option value="ABA therapy">ABA therapy</option>
                            <option value="Speech therapy">Speech therapy</option>
                            <option value="Occupational therapy">Occupational therapy</option>
                            <option value="Diagnostic assessment">Diagnostic assessment</option>
                            <option value="Early intervention">Early intervention</option>
                            <option value="Combined program">Combined program</option>
                        </select>
                    </div>
                    <div>
                        <div class="field-label">Insurance</div>
                        <select id="editInsurance" name="insurance" class="field-select">
                            <option value="Not sure yet">Not sure yet</option>
                            <option value="Daman">Daman</option>
                            <option value="Daman Enhanced">Daman Enhanced</option>
                            <option value="Thiqa">Thiqa</option>
                            <option value="ADNIC">ADNIC</option>
                            <option value="AXA / GIG">AXA / GIG</option>
                            <option value="Self-pay">Self-pay</option>
                        </select>
                    </div>
                    <div>
                        <div class="field-label">Est. Monthly Value (AED)</div>
                        <input id="editValue" name="estimated_value" type="text" placeholder="12,800" class="field-input">
                    </div>
                    <div>
                        <div class="field-label">Assigned to</div>
                        <select id="editAssignedTo" name="assigned_to" class="field-select">
                            <option value="">Unassigned</option>
                            @foreach ($assignableUsers as $user)
                                <option value="{{ $user->id }}">{{ trim($user->first_name.' '.$user->last_name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="field-label">Follow-up due</div>
                        <input id="editFollowUpDue" name="follow_up_due_at" type="date" class="field-input">
                    </div>
                </div>

                <div>
                    <div class="field-label">Notes</div>
                    <textarea id="editNotes" name="notes" rows="2" class="field-textarea" style="resize: vertical;"></textarea>
                </div>

                <div>
                    <div class="field-label">Status</div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select id="editStatus" name="status" class="field-select" style="flex: 1;">
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="assessment_booked">Assessment Booked</option>
                            <option value="assessment_done">Assessment Done</option>
                            <option value="enrolled">Enrolled</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <button type="button" onclick="closeViewLeadModal()" class="btn-cancel">Cancel</button>
                </div>

                <div class="modal-timestamps">
                    <span>Created: <span id="viewCreatedAt">-</span></span>
                    <span>Last updated: <span id="viewUpdatedAt">-</span></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Action Panel (opens when a card's container is clicked - assign, schedule
         follow-up, log notes, or terminate. The eye icon opens the full edit modal above instead. -->
    <div id="actionPanel" class="ap-overlay">
        <div class="ap-panel">
            <div class="ap-head">
                <div class="ap-head-top">
                    <div class="ap-name" id="apName">—</div>
                    <span class="lead-card-source-badge" id="apSourceBadge"></span>
                    <button type="button" class="ap-close" onclick="closeActionPanel()">✕</button>
                </div>
                <div class="ap-meta">
                    <span class="lead-card-tag" id="apStatusTag"></span>
                    <span class="ap-updated" id="apUpdated"></span>
                </div>
            </div>

            <div class="ap-body">
                <div class="ap-stats">
                    <div class="ap-stat">
                        <div class="ap-section-label">Parent / Guardian</div>
                        <div class="ap-enquiry-notes" id="apParent">—</div>
                    </div>
                    <div class="ap-stat">
                        <div class="ap-section-label">Est. monthly value</div>
                        <div class="ap-enquiry-notes" id="apValue">—</div>
                    </div>
                </div>

                <div>
                    <div class="ap-section-label">Enquiry notes</div>
                    <div class="ap-enquiry-notes" id="apEnquiryNotes">—</div>
                </div>

                <div>
                    <div class="ap-section-label">
                        Assigned to
                        <span class="ap-section-link" id="apAssignmentLogToggle" onclick="toggleAssignmentLog()">Assignment log (0)</span>
                    </div>
                    <select id="apAssignedTo" class="field-select" style="margin-top: 6px;" onchange="onApAssignedToChange()">
                        <option value="">Unassigned</option>
                        @foreach ($assignableUsers as $user)
                            <option value="{{ $user->id }}">{{ trim($user->first_name.' '.$user->last_name) }}</option>
                        @endforeach
                    </select>
                    <div class="ap-hint is-hidden" id="apAssignHint">A lead must have an owner before it can move to Contacted.</div>
                    <div class="ap-assignment-log" id="apAssignmentLog"></div>
                </div>

                <div>
                    <div class="ap-section-label">Follow-up</div>
                    <select id="apFollowUpPreset" class="field-select" style="margin-top: 6px;" onchange="onApFollowUpPresetChange()">
                        <option value="">No follow-up scheduled</option>
                        <option value="1">Tomorrow</option>
                        <option value="2">In 2 days</option>
                        <option value="3">In 3 days</option>
                        <option value="7">In 1 week</option>
                        <option value="custom">Pick a date…</option>
                    </select>
                    <input type="date" id="apFollowUpDate" class="field-input" style="margin-top: 6px; display: none;">
                </div>

                <div>
                    <div class="ap-section-label">Notes</div>
                    <div class="ap-notes-list" id="apNotesList"></div>
                    <textarea id="apNoteBody" class="field-textarea" rows="2" placeholder="Add a note — call outcome, parent questions, next step…" style="margin-top: 8px; resize: vertical;"></textarea>
                    <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                        <div style="flex: 1; font: 600 11px 'Nunito Sans'; color: #A79C8E;">Saved against this lead with your name and time.</div>
                        <button type="button" class="btn-filter" id="apAddNoteBtn" onclick="addApNote()" style="padding: 8px 14px;">Add note</button>
                    </div>
                </div>
            </div>

            <div class="ap-actions" id="apActions">
                <button type="button" class="ap-btn-success" id="apSuccessBtn" onclick="saveApChanges()">Success</button>
                <button type="button" class="ap-btn-followup" id="apFollowUpBtn" onclick="followUpApLead()" style="display: none;">Follow-up</button>
                <button type="button" class="ap-btn-terminate" id="apTerminateBtn" onclick="terminateApLead()">Terminate</button>
                <button type="button" class="ap-btn-convert" id="apConvertBtn" onclick="convertApLead()" style="display: none;">Convert to client</button>
            </div>
        </div>
    </div>

    <!-- New Lead Modal -->
    <div id="leadModal" class="modal-overlay">
        <div class="modal-box modal-box-narrow">
            <div class="modal-header">
                <div style="flex: 1;">
                    <div class="modal-header-title">New lead — manual entry</div>
                    <div class="modal-header-sub">Walk-in, phone call, or event enquiry</div>
                </div>
                <button class="modal-close-btn" onclick="closeLeadModal()">✕</button>
            </div>

            <form id="leadForm" onsubmit="saveLead(event)">
                @csrf
                <div class="form-grid-name-age">
                    <div>
                        <div class="field-label">Child's name *</div>
                        <input id="childName" name="child_name" type="text" placeholder="e.g. Hamad" class="field-input" required>
                    </div>
                    <div>
                        <div class="field-label">Age</div>
                        <input id="childAge" name="child_age" type="text" placeholder="5" class="field-input">
                    </div>
                </div>

                <div class="form-grid-2col" style="margin-top: 12px;">
                    <div>
                        <div class="field-label">Parent / guardian *</div>
                        <input id="parentName" name="parent_guardian_name" type="text" placeholder="e.g. Mrs. Shamma Al Qubaisi" class="field-input" required>
                    </div>
                    <div>
                        <div class="field-label">Phone (WhatsApp)</div>
                        <input id="phoneNumber" name="phone" type="tel" placeholder="+971 5x xxx xxxx" class="field-input">
                    </div>
                </div>

                <div class="form-grid-2col" style="margin-top: 12px;">
                    <div>
                        <div class="field-label">Source</div>
                        <select id="leadSource" name="source" class="field-select">
                            <option value="Walk-in">Walk-in</option>
                            <option value="Phone call">Phone call</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Website">Website</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Referral">Referral</option>
                            <option value="Google">Google</option>
                            <option value="Event">Event</option>
                        </select>
                    </div>
                    <div>
                        <div class="field-label">Interested in</div>
                        <select id="leadInterest" name="interested_in" class="field-select">
                            <option value="ABA therapy">ABA therapy</option>
                            <option value="Speech therapy">Speech therapy</option>
                            <option value="Occupational therapy">Occupational therapy</option>
                            <option value="Diagnostic assessment">Diagnostic assessment</option>
                            <option value="Early intervention">Early intervention</option>
                            <option value="Combined program">Combined program</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2col" style="margin-top: 12px;">
                    <div>
                        <div class="field-label">Insurance</div>
                        <select id="leadInsurance" name="insurance" class="field-select">
                            <option value="Not sure yet">Not sure yet</option>
                            <option value="Daman">Daman</option>
                            <option value="Daman Enhanced">Daman Enhanced</option>
                            <option value="Thiqa">Thiqa</option>
                            <option value="ADNIC">ADNIC</option>
                            <option value="AXA / GIG">AXA / GIG</option>
                            <option value="Self-pay">Self-pay</option>
                        </select>
                    </div>
                    <div>
                        <div class="field-label">Est. monthly value (AED)</div>
                        <input id="leadValue" name="estimated_value" type="text" placeholder="12,800" class="field-input">
                    </div>
                </div>

                <div class="form-grid-2col" style="margin-top: 12px;">
                    <div>
                        <div class="field-label">Assigned to</div>
                        <select id="leadAssignedTo" name="assigned_to" class="field-select">
                            <option value="">Unassigned</option>
                            @foreach ($assignableUsers as $user)
                                <option value="{{ $user->id }}">{{ trim($user->first_name.' '.$user->last_name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="field-label">Follow-up due</div>
                        <input id="leadFollowUpDue" name="follow_up_due_at" type="date" class="field-input">
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <div class="field-label">Notes</div>
                    <input id="leadNote" name="notes" type="text" placeholder="e.g. asked about fees and Daman coverage" class="field-input">
                </div>

                <div style="display: flex; gap: 10px; margin-top: 16px;">
                    <button type="submit" class="btn-save" style="border-radius: 10px; padding: 12px 0;">Save lead</button>
                    <button type="button" onclick="closeLeadModal()" class="btn-cancel" style="width: 120px; flex: none; border-radius: 10px; padding: 12px 0;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Clamp the container to the actual visible width next to the sidebar.
    // This makes the kanban board scroll INSIDE itself, instead of the
    // whole page scrolling or content getting cut off at the edge.
    function containLeadsWidth() {
        const page = document.querySelector('.leads-page');
        if (!page) return;

        page.style.width = 'auto';
        page.style.maxWidth = 'none';

        const rect = page.getBoundingClientRect();
        const available = Math.floor(window.innerWidth - rect.left);

        if (available > 0) {
            page.style.width = available + 'px';
            page.style.maxWidth = available + 'px';
        }
    }
    containLeadsWidth();
    requestAnimationFrame(containLeadsWidth);
    window.addEventListener('load', containLeadsWidth);
    window.addEventListener('resize', containLeadsWidth);

    const statusMap = {
        'new': 'New',
        'contacted': 'Contacted',
        'assessment_booked': 'Assessment Booked',
        'assessment_done': 'Assessment Done',
        'enrolled': 'Enrolled',
        'terminated': 'Terminated'
    };

    const statusTagColors = {
        'new': { bg: '#E7EFF7', color: '#24619C' },
        'contacted': { bg: '#FBF0DC', color: '#8A5A10' },
        'assessment_booked': { bg: '#EDE7F5', color: '#6E4FA8' },
        'assessment_done': { bg: '#EDE7F5', color: '#6E4FA8' },
        'enrolled': { bg: '#E3F1E9', color: '#2E7D5B' },
        'terminated': { bg: '#F9E4E2', color: '#B3261E' }
    };

    const statusTagLabels = {
        'new': 'New',
        'contacted': 'Contacted',
        'assessment_booked': 'Booked',
        'assessment_done': 'Assessment done',
        'enrolled': 'Enrolled',
        'terminated': 'Terminated'
    };

    // Which visual column a given DB status renders in - "Initial assessment"
    // absorbs both assessment_booked and assessment_done into one column.
    const statusToColumn = {
        'new': 'new',
        'contacted': 'contacted',
        'assessment_booked': 'assessment',
        'assessment_done': 'assessment',
        'enrolled': 'enrolled',
        'terminated': 'terminated'
    };

    const sourceColorMap = {
        'WhatsApp': { bg: '#E3F4E9', color: '#178A45' },
        'Website': { bg: '#E7EFF7', color: '#24619C' },
        'Instagram': { bg: '#FAE7F2', color: '#C13584' },
        'Referral': { bg: '#F7EEDD', color: '#B97F24' },
        'Google': { bg: '#EEE9F7', color: '#6E4FA8' },
        'Walk-in': { bg: '#EDEFF1', color: '#5A6B7E' },
        'Phone call': { bg: '#E3F1E9', color: '#2E7D5B' },
        'Event': { bg: '#F7EEDD', color: '#8A5A10' }
    };

    const nextStatusMap = {
        'new': 'contacted',
        'contacted': 'assessment_booked',
        'assessment_booked': 'assessment_done',
        'assessment_done': 'enrolled',
        'enrolled': null,
        'terminated': null
    };

    const updateStatusUrl = '{{ route("leads.update-status", ["lead" => "__LEAD_ID__"]) }}';
    const storeLeadUrl = '{{ route("leads.store") }}';
    const viewLeadUrl = '{{ route("leads.show", ["lead" => "__LEAD_ID__"]) }}';
    const updateLeadUrl = '{{ route("leads.update", ["lead" => "__LEAD_ID__"]) }}';
    const addNoteUrl = '{{ route("leads.notes.store", ["lead" => "__LEAD_ID__"]) }}';
    const convertToPatientUrl = '{{ route("leads.convert-to-patient", ["lead" => "__LEAD_ID__"]) }}';

    function csrfToken() {
        return document.querySelector('input[name="_token"]').value || '{{ csrf_token() }}';
    }

    // ===== Toast =====
    const toastIcons = { success: '✅', error: '⚠️', info: 'ℹ️' };

    function showNotification(message, type = 'success') {
        const stack = document.getElementById('toastStack');

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = `
            <span class="toast-icon">${toastIcons[type] || 'ℹ️'}</span>
            <span class="toast-text"></span>
            <button class="toast-close" aria-label="Dismiss">✕</button>
        `;
        toast.querySelector('.toast-text').textContent = message;
        toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());

        stack.appendChild(toast);

        setTimeout(() => toast.remove(), 3500);
    }

    // ===== Terminated column toggle =====
    function toggleTerminated() {
        const board = document.getElementById('kanbanBoard');
        const column = document.getElementById('terminatedColumn');
        const btn = document.getElementById('terminatedToggleBtn');

        const showing = column.style.display !== 'none';

        column.style.display = showing ? 'none' : 'flex';
        board.classList.toggle('show-terminated', !showing);
        btn.classList.toggle('is-active', !showing);
    }

    // ===== View lead =====
    function viewLead(leadId) {
        const url = viewLeadUrl.replace('__LEAD_ID__', leadId);

        document.getElementById('viewLeadTitle').textContent = 'Loading...';
        document.getElementById('viewLeadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';

        fetch(url, {
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const lead = data.lead;

                document.getElementById('editLeadId').value = lead.id;
                document.getElementById('editChildName').value = lead.child_name || '';
                document.getElementById('editChildAge').value = lead.child_age || '';
                document.getElementById('editParentName').value = lead.parent_guardian_name || '';
                document.getElementById('editPhone').value = lead.phone || '';
                document.getElementById('editSource').value = lead.source || 'Walk-in';
                document.getElementById('editInterest').value = lead.interested_in || 'ABA therapy';
                document.getElementById('editInsurance').value = lead.insurance || 'Not sure yet';
                document.getElementById('editValue').value = lead.estimated_value ? Number(lead.estimated_value).toLocaleString() : '';
                document.getElementById('editNotes').value = lead.notes || '';
                document.getElementById('editStatus').value = lead.status || 'new';
                document.getElementById('editAssignedTo').value = lead.assigned_to || '';
                document.getElementById('editFollowUpDue').value = lead.follow_up_due_at ? lead.follow_up_due_at.substring(0, 10) : '';

                document.getElementById('viewLeadTitle').textContent = (lead.child_name || 'Lead') + ' · Lead Details';
                document.getElementById('viewLeadSubtitle').textContent = 'ID: #' + lead.id + ' · ' + (statusMap[lead.status] || lead.status);
                document.getElementById('viewCreatedAt').textContent = new Date(lead.created_at).toLocaleString();
                document.getElementById('viewUpdatedAt').textContent = new Date(lead.updated_at).toLocaleString();
            } else {
                showNotification('Error loading lead details.', 'error');
                closeViewLeadModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
            closeViewLeadModal();
        });
    }

    // ===== Format a "Due in Xd" / "Overdue Xd" / "Due today" badge =====
    function formatDueLabel(dueDateStr) {
        if (!dueDateStr) return null;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const due = new Date(dueDateStr);
        due.setHours(0, 0, 0, 0);

        const diffDays = Math.round((due - today) / 86400000);

        if (diffDays < 0) return { label: 'Overdue ' + Math.abs(diffDays) + 'd', overdue: true };
        if (diffDays === 0) return { label: 'Due today', overdue: false };
        return { label: 'Due in ' + diffDays + 'd', overdue: false };
    }

    // ===== Refresh a card's tag row + owner line after an update =====
    function refreshCardChrome(card, lead) {
        const tagsEl = card.querySelector('.js-tags');
        if (tagsEl) {
            const statusColors = statusTagColors[lead.status] || { bg: '#EDEFF1', color: '#5A6B7E' };
            const statusLabel = statusTagLabels[lead.status] || lead.status;
            let html = `<span class="lead-card-tag" style="background:${statusColors.bg}; color:${statusColors.color};">${statusLabel}</span>`;

            const due = formatDueLabel(lead.follow_up_due_at);
            if (due) {
                const dueColors = due.overdue ? { bg: '#F9E4E2', color: '#B3261E' } : { bg: '#FBF0DC', color: '#8A5A10' };
                html += `<span class="lead-card-tag" style="background:${dueColors.bg}; color:${dueColors.color};">${due.label}</span>`;
            }
            tagsEl.innerHTML = html;
        }

        const ownerEl = card.querySelector('.js-owner');
        if (ownerEl) {
            if (lead.assigned_to_name) {
                ownerEl.textContent = 'Assigned to ' + lead.assigned_to_name;
                ownerEl.classList.remove('is-unassigned');
            } else {
                ownerEl.textContent = 'Unassigned — open to assign';
                ownerEl.classList.add('is-unassigned');
            }
        }
    }

    // ===== Update lead =====
    function updateLeadDetails(event) {
        event.preventDefault();

        const leadId = document.getElementById('editLeadId').value;
        const url = updateLeadUrl.replace('__LEAD_ID__', leadId);

        const form = document.getElementById('editLeadForm');
        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const lead = data.lead;
                let card = document.querySelector(`.lead-card[data-id="${leadId}"]`);

                if (card) {
                    const oldStatus = card.dataset.status;
                    const newStatus = lead.status;

                    if (oldStatus !== newStatus) {
                        moveLeadCard(leadId, oldStatus, newStatus);
                        updateCounts(oldStatus, newStatus);
                        card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
                    }

                    if (card) {
                        const nameEl = card.querySelector('.js-name');
                        const sourceEl = card.querySelector('.js-source');
                        const parentEl = card.querySelector('.js-parent');
                        const notesEl = card.querySelector('.js-notes');
                        const valueEl = card.querySelector('.js-value');
                        const timeEl = card.querySelector('.js-time');

                        if (nameEl) nameEl.textContent = (lead.child_name || 'N/A') + ' · ' + (lead.child_age || 'N/A');

                        if (sourceEl) {
                            const colors = sourceColorMap[lead.source] || { bg: '#EDEFF1', color: '#5A6B7E' };
                            sourceEl.style.background = colors.bg;
                            sourceEl.style.color = colors.color;
                            sourceEl.textContent = lead.source || 'N/A';
                        }

                        if (parentEl) parentEl.textContent = lead.parent_guardian_name || 'N/A';
                        if (notesEl) notesEl.textContent = lead.notes || 'No notes';

                        if (valueEl) {
                            const numValue = parseFloat(lead.estimated_value) || 0;
                            valueEl.textContent = 'AED ' + numValue.toLocaleString() + '/mo';
                        }
                        if (timeEl) timeEl.textContent = 'just now';

                        const advanceBtn = card.querySelector('.btn-advance');
                        if (advanceBtn) {
                            advanceBtn.setAttribute('onclick', `event.stopPropagation(); advanceLead(${leadId}, '${lead.status}')`);
                            advanceBtn.disabled = !nextStatusMap[lead.status];
                        }

                        card.dataset.status = lead.status;
                        refreshCardChrome(card, lead);
                    }
                }

                showNotification('Lead updated successfully.', 'success');
                closeViewLeadModal();
            } else {
                let errorMsg = 'Could not update lead.';
                if (data.errors) errorMsg = Object.values(data.errors).flat().join(', ');
                showNotification(errorMsg, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    // ===== Advance lead =====
    function advanceLead(leadId, currentStatus) {
        const nextStatus = nextStatusMap[currentStatus];

        if (!nextStatus) {
            showNotification('This lead is already in the final stage.', 'info');
            return;
        }

        const url = updateStatusUrl.replace('__LEAD_ID__', leadId);
        const card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
        const button = card ? card.querySelector('.btn-advance') : null;
        if (button) {
            button.textContent = '⏳';
            button.disabled = true;
        }

        fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: nextStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                moveLeadCard(leadId, currentStatus, nextStatus);
                updateCounts(currentStatus, nextStatus);
                if (card) {
                    card.dataset.status = nextStatus;
                    refreshCardChrome(card, data.lead);
                }
                showNotification('Lead moved to ' + statusMap[nextStatus] + '.', 'success');
            } else {
                showNotification(data.message || 'Could not update lead status.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            const refreshedCard = document.querySelector(`.lead-card[data-id="${leadId}"]`);
            const refreshedButton = refreshedCard ? refreshedCard.querySelector('.btn-advance') : null;
            if (refreshedButton) {
                refreshedButton.textContent = '→';
                refreshedButton.disabled = !nextStatusMap[nextStatus];
            }
        });
    }

    // ===== Move card between columns =====
    // Moving between two statuses that share the same visual column (e.g.
    // assessment_booked -> assessment_done, both "Initial assessment") doesn't
    // relocate the card in the DOM - it stays put, only its tag badge changes.
    function moveLeadCard(leadId, fromStatus, toStatus) {
        const fromCol = statusToColumn[fromStatus];
        const toCol = statusToColumn[toStatus];

        if (fromCol === toCol) return;

        const card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
        if (!card) return;

        const targetContainer = document.getElementById(toCol + 'Leads');
        if (!targetContainer) return;

        const emptyMsg = targetContainer.querySelector('.kanban-empty');
        if (emptyMsg) emptyMsg.remove();

        const cardClone = card.cloneNode(true);
        cardClone.dataset.status = toStatus;

        const newButton = cardClone.querySelector('.btn-advance');
        if (newButton) {
            // The clone inherits whatever the button showed at clone time (e.g. the
            // "⏳" set while the move was in flight) — reset it explicitly so it
            // doesn't get stuck instead of going back to "→".
            newButton.textContent = '→';
            newButton.setAttribute('onclick', `event.stopPropagation(); advanceLead(${leadId}, '${toStatus}')`);
            newButton.disabled = !nextStatusMap[toStatus];
        }

        const viewButton = cardClone.querySelector('.btn-view');
        if (viewButton) viewButton.setAttribute('onclick', `event.stopPropagation(); viewLead(${leadId})`);

        cardClone.setAttribute('onclick', `openActionPanel(${leadId})`);

        if (card.parentNode) card.parentNode.removeChild(card);
        targetContainer.appendChild(cardClone);

        // Source column may now be empty.
        const sourceContainer = document.getElementById(fromCol + 'Leads');
        if (sourceContainer && sourceContainer.children.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'kanban-empty';
            empty.textContent = fromCol === 'terminated' ? 'No terminated leads' : 'No leads in this stage';
            sourceContainer.appendChild(empty);
        }
    }

    // ===== Update column counts =====
    // No-ops when the move stays within the same visual column, since the
    // column's total doesn't change (assessment_booked <-> assessment_done).
    function updateCounts(fromStatus, toStatus) {
        const fromCol = statusToColumn[fromStatus];
        const toCol = statusToColumn[toStatus];

        if (fromCol === toCol) return;

        const fromCountElement = document.getElementById(fromCol + 'Count');
        if (fromCountElement) {
            const count = parseInt(fromCountElement.textContent) || 0;
            fromCountElement.textContent = Math.max(0, count - 1);
        }

        const toCountElement = document.getElementById(toCol + 'Count');
        if (toCountElement) {
            const count = parseInt(toCountElement.textContent) || 0;
            toCountElement.textContent = count + 1;
        }
    }

    function closeViewLeadModal() {
        document.getElementById('viewLeadModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // ===================================================================
    // Action panel - opened by clicking a card's container (not the eye
    // icon). Focused on the routine actions: assign, schedule a follow-up,
    // log a note, or terminate. Full field editing stays in the eye-icon modal.
    // ===================================================================
    let apLead = null;

    function apUserName(user) {
        if (!user) return null;
        return (user.first_name + ' ' + user.last_name).trim();
    }

    function apTimeAgo(dateStr) {
        if (!dateStr) return '';
        const diffMs = Date.now() - new Date(dateStr).getTime();
        const mins = Math.round(diffMs / 60000);
        if (mins < 1) return 'just now';
        if (mins < 60) return mins + 'm ago';
        const hours = Math.round(mins / 60);
        if (hours < 24) return hours + 'h ago';
        return Math.round(hours / 24) + 'd ago';
    }

    function openActionPanel(leadId) {
        document.getElementById('apName').textContent = 'Loading…';
        document.getElementById('actionPanel').style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const url = viewLeadUrl.replace('__LEAD_ID__', leadId);

        fetch(url, {
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showNotification('Error loading lead details.', 'error');
                closeActionPanel();
                return;
            }

            apLead = data.lead;
            apLead.notes_log = data.notes_log || [];
            apLead.assignment_log = data.assignment_log || [];

            document.getElementById('apName').textContent = (apLead.child_name || 'N/A') + ' · ' + (apLead.child_age || 'N/A');

            const sourceColors = sourceColorMap[apLead.source] || { bg: '#EDEFF1', color: '#5A6B7E' };
            const sourceBadge = document.getElementById('apSourceBadge');
            sourceBadge.textContent = apLead.source || 'N/A';
            sourceBadge.style.background = sourceColors.bg;
            sourceBadge.style.color = sourceColors.color;

            const tagColors = statusTagColors[apLead.status] || { bg: '#EDEFF1', color: '#5A6B7E' };
            const statusTag = document.getElementById('apStatusTag');
            statusTag.textContent = statusTagLabels[apLead.status] || apLead.status;
            statusTag.style.background = tagColors.bg;
            statusTag.style.color = tagColors.color;

            document.getElementById('apUpdated').textContent = 'updated ' + apTimeAgo(apLead.updated_at);
            document.getElementById('apParent').textContent = apLead.parent_guardian_name || 'N/A';
            document.getElementById('apValue').textContent = 'AED ' + (parseFloat(apLead.estimated_value) || 0).toLocaleString() + '/mo';
            document.getElementById('apEnquiryNotes').textContent = apLead.notes || 'No notes';

            document.getElementById('apAssignedTo').value = apLead.assigned_to || '';
            document.getElementById('apAssignmentLogToggle').textContent = 'Assignment log (' + apLead.assignment_log.length + ')';
            renderApAssignmentLog(apLead.assignment_log);
            document.getElementById('apAssignmentLog').classList.remove('is-open');

            setApFollowUpFields(apLead.follow_up_due_at);
            renderApNotes(apLead.notes_log);
            document.getElementById('apNoteBody').value = '';

            refreshApActions();
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
            closeActionPanel();
        });
    }

    function closeActionPanel() {
        document.getElementById('actionPanel').style.display = 'none';
        document.body.style.overflow = '';
        apLead = null;
    }

    function toggleAssignmentLog() {
        document.getElementById('apAssignmentLog').classList.toggle('is-open');
    }

    function renderApAssignmentLog(entries) {
        const container = document.getElementById('apAssignmentLog');
        if (!entries.length) {
            container.innerHTML = '<div class="ap-log-empty">No assignment changes yet.</div>';
            return;
        }
        container.innerHTML = entries.map(e => `
            <div class="ap-log-entry">
                ${e.body} · <span class="ap-log-when">${apUserName(e.user) || 'System'} · ${apTimeAgo(e.created_at)}</span>
            </div>
        `).join('');
    }

    function renderApNotes(notes) {
        const container = document.getElementById('apNotesList');
        if (!notes.length) {
            container.innerHTML = '<div class="ap-log-empty">No notes yet.</div>';
            return;
        }
        container.innerHTML = notes.map(n => `
            <div class="ap-note-entry">
                <div class="ap-note-author-row">
                    <div class="ap-note-author">${apUserName(n.user) || 'System'}</div>
                    <div class="ap-note-when">${apTimeAgo(n.created_at)}</div>
                </div>
                <div class="ap-note-body">${n.body}</div>
            </div>
        `).join('');
    }

    function setApFollowUpFields(dueDateStr) {
        const preset = document.getElementById('apFollowUpPreset');
        const dateInput = document.getElementById('apFollowUpDate');

        if (!dueDateStr) {
            preset.value = '';
            dateInput.value = '';
            dateInput.style.display = 'none';
            return;
        }

        const dueDate = dueDateStr.substring(0, 10);
        dateInput.value = dueDate;

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const due = new Date(dueDate + 'T00:00:00');
        const diffDays = Math.round((due - today) / 86400000);

        if ([1, 2, 3, 7].includes(diffDays)) {
            preset.value = String(diffDays);
            dateInput.style.display = 'none';
        } else {
            preset.value = 'custom';
            dateInput.style.display = 'block';
        }
    }

    function onApFollowUpPresetChange() {
        const preset = document.getElementById('apFollowUpPreset');
        const dateInput = document.getElementById('apFollowUpDate');

        if (preset.value === '') {
            dateInput.value = '';
            dateInput.style.display = 'none';
        } else if (preset.value === 'custom') {
            dateInput.style.display = 'block';
        } else {
            const d = new Date();
            d.setDate(d.getDate() + parseInt(preset.value, 10));
            dateInput.value = d.toISOString().substring(0, 10);
            dateInput.style.display = 'none';
        }
    }

    function onApAssignedToChange() {
        refreshApActions();
    }

    function refreshApActions() {
        if (!apLead) return;

        const successBtn = document.getElementById('apSuccessBtn');
        const followUpBtn = document.getElementById('apFollowUpBtn');
        const terminateBtn = document.getElementById('apTerminateBtn');
        const convertBtn = document.getElementById('apConvertBtn');
        const hint = document.getElementById('apAssignHint');
        const assignedValue = document.getElementById('apAssignedTo').value;
        const currentStatus = apLead.status;

        // Terminal stages get their own dedicated action instead of the usual set.
        if (currentStatus === 'enrolled') {
            successBtn.style.display = 'none';
            followUpBtn.style.display = 'none';
            terminateBtn.style.display = 'none';
            convertBtn.style.display = 'flex';
            hint.classList.add('is-hidden');
            return;
        }

        if (currentStatus === 'terminated') {
            successBtn.style.display = 'none';
            followUpBtn.style.display = 'none';
            terminateBtn.style.display = 'none';
            convertBtn.style.display = 'none';
            hint.classList.add('is-hidden');
            return;
        }

        convertBtn.style.display = 'none';
        terminateBtn.style.display = 'flex';
        // The Follow-up quick-action (log it + clear the due badge) only makes
        // sense while the lead is actually in the Contacted/Follow-up stage.
        followUpBtn.style.display = currentStatus === 'contacted' ? 'flex' : 'none';

        const needsOwnerFirst = currentStatus === 'new' && !assignedValue;
        hint.classList.toggle('is-hidden', !needsOwnerFirst);

        successBtn.style.display = 'flex';

        if (needsOwnerFirst) {
            successBtn.textContent = 'Assign first';
            successBtn.disabled = true;
            successBtn.dataset.mode = 'blocked';
            delete successBtn.dataset.nextStatus;
            return;
        }

        // Once the lead has an owner, Success saves any assignment/follow-up
        // edits made in this panel AND advances to the next pipeline stage in
        // one action - there's always a next step until Enrolled/Terminated.
        const nextStatus = nextStatusMap[currentStatus];

        successBtn.disabled = false;
        successBtn.textContent = 'Success';
        if (nextStatus) {
            successBtn.dataset.mode = 'advance';
            successBtn.dataset.nextStatus = nextStatus;
        } else {
            successBtn.dataset.mode = 'save';
            delete successBtn.dataset.nextStatus;
        }
    }

    function addApNote() {
        if (!apLead) return;

        const body = document.getElementById('apNoteBody').value.trim();
        if (!body) return;

        const url = addNoteUrl.replace('__LEAD_ID__', apLead.id);
        const btn = document.getElementById('apAddNoteBtn');
        btn.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ body })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                apLead.notes_log.unshift(data.note);
                renderApNotes(apLead.notes_log);
                document.getElementById('apNoteBody').value = '';
            } else {
                showNotification('Could not add note.', 'error');
            }
        })
        .catch(() => showNotification('Network error. Please try again.', 'error'))
        .finally(() => { btn.disabled = false; });
    }

    function saveApChanges() {
        if (!apLead) return;

        const btn = document.getElementById('apSuccessBtn');
        const mode = btn.dataset.mode;
        const nextStatus = btn.dataset.nextStatus;
        const assignedTo = document.getElementById('apAssignedTo').value;
        const followUpDue = document.getElementById('apFollowUpDate').value;

        const url = updateLeadUrl.replace('__LEAD_ID__', apLead.id);
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving…';

        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('assigned_to', assignedTo);
        formData.append('follow_up_due_at', followUpDue);
        // The update endpoint validates the full record - resend the fields it
        // already has so this partial save doesn't blank them out.
        ['child_name', 'child_age', 'parent_guardian_name', 'phone', 'source', 'interested_in', 'insurance', 'estimated_value', 'notes', 'status'].forEach(key => {
            if (apLead[key] !== null && apLead[key] !== undefined) formData.append(key, apLead[key]);
        });

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                let errorMsg = 'Could not update lead.';
                if (data.errors) errorMsg = Object.values(data.errors).flat().join(', ');
                showNotification(errorMsg, 'error');
                btn.disabled = false;
                btn.textContent = originalText;
                return;
            }

            const lead = data.lead;
            const card = document.querySelector(`.lead-card[data-id="${apLead.id}"]`);
            if (card) refreshCardChrome(card, lead);

            apLead.assigned_to = lead.assigned_to;
            apLead.assigned_to_name = lead.assigned_to_name;
            apLead.follow_up_due_at = lead.follow_up_due_at;

            // Assignment/follow-up saved - if this button was also the "move to
            // the next stage" action, advance the status now in a second request.
            if (mode === 'advance' && nextStatus) {
                const statusUrl = updateStatusUrl.replace('__LEAD_ID__', apLead.id);
                const fromStatus = apLead.status;

                return fetch(statusUrl, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ status: nextStatus })
                })
                .then(response => response.json())
                .then(statusData => {
                    if (!statusData.success) {
                        showNotification(statusData.message || 'Could not move lead to the next stage.', 'error');
                        return;
                    }

                    moveLeadCard(apLead.id, fromStatus, nextStatus);
                    updateCounts(fromStatus, nextStatus);
                    const refreshedCard = document.querySelector(`.lead-card[data-id="${apLead.id}"]`);
                    if (refreshedCard) {
                        refreshedCard.dataset.status = nextStatus;
                        refreshCardChrome(refreshedCard, statusData.lead);
                    }

                    showNotification('Lead moved to ' + statusMap[nextStatus] + '.', 'success');
                    closeActionPanel();
                });
            }

            showNotification('Lead updated successfully.', 'success');
            closeActionPanel();
        })
        .catch(() => showNotification('Network error. Please try again.', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    function terminateApLead() {
        if (!apLead) return;
        if (!confirm('Mark this lead as terminated? It will move out of the active pipeline.')) return;

        const leadId = apLead.id;
        const currentStatus = apLead.status;
        const url = updateStatusUrl.replace('__LEAD_ID__', leadId);

        fetch(url, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: JSON.stringify({ status: 'terminated' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                moveLeadCard(leadId, currentStatus, 'terminated');
                updateCounts(currentStatus, 'terminated');
                const card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
                if (card) {
                    card.dataset.status = 'terminated';
                    refreshCardChrome(card, data.lead);
                }
                showNotification('Lead terminated.', 'success');
                closeActionPanel();
            } else {
                showNotification(data.message || 'Could not terminate lead.', 'error');
            }
        })
        .catch(() => showNotification('Network error. Please try again.', 'error'));
    }

    // Quick action for the Contacted/Follow-up stage: log that the follow-up
    // happened and clear the due badge, without advancing the pipeline stage.
    function followUpApLead() {
        if (!apLead) return;

        const leadId = apLead.id;
        const btn = document.getElementById('apFollowUpBtn');
        btn.disabled = true;

        const noteUrl = addNoteUrl.replace('__LEAD_ID__', leadId);
        const updateUrl = updateLeadUrl.replace('__LEAD_ID__', leadId);

        fetch(noteUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ body: 'Followed up with the family.' })
        })
        .then(() => {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('assigned_to', apLead.assigned_to || '');
            formData.append('follow_up_due_at', '');
            ['child_name', 'child_age', 'parent_guardian_name', 'phone', 'source', 'interested_in', 'insurance', 'estimated_value', 'notes', 'status'].forEach(key => {
                if (apLead[key] !== null && apLead[key] !== undefined) formData.append(key, apLead[key]);
            });

            return fetch(updateUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: formData
            });
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
                if (card) refreshCardChrome(card, data.lead);
                apLead.follow_up_due_at = null;
                showNotification('Follow-up logged.', 'success');
                closeActionPanel();
            } else {
                showNotification('Could not log the follow-up.', 'error');
            }
        })
        .catch(() => showNotification('Network error. Please try again.', 'error'))
        .finally(() => { btn.disabled = false; });
    }

    // Terminal action for Enrolled leads - creates the real patient record.
    function convertApLead() {
        if (!apLead) return;

        const url = convertToPatientUrl.replace('__LEAD_ID__', apLead.id);
        const btn = document.getElementById('apConvertBtn');
        btn.disabled = true;
        btn.textContent = 'Converting…';

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                showNotification(data.message || 'Could not convert this lead.', 'error');
                btn.disabled = false;
                btn.textContent = 'Convert to client';
            }
        })
        .catch(() => {
            showNotification('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.textContent = 'Convert to client';
        });
    }

    function openLeadModal() {
        document.getElementById('leadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLeadModal() {
        document.getElementById('leadModal').style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('leadForm').reset();
    }

    function saveLead(event) {
        event.preventDefault();

        const form = document.getElementById('leadForm');
        const formData = new FormData(form);

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;

        fetch(storeLeadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeLeadModal();
                showNotification('Lead saved successfully. Refreshing...', 'success');
                setTimeout(() => location.reload(), 900);
            } else {
                let errorMsg = 'Could not save lead.';
                if (data.errors) errorMsg = Object.values(data.errors).flat().join(', ');
                showNotification(errorMsg, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Network error. Please try again.', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const viewModal = document.getElementById('viewLeadModal');
        if (viewModal) {
            viewModal.addEventListener('click', function(e) {
                if (e.target === this) closeViewLeadModal();
            });
        }

        const leadModal = document.getElementById('leadModal');
        if (leadModal) {
            leadModal.addEventListener('click', function(e) {
                if (e.target === this) closeLeadModal();
            });
        }

        const actionPanel = document.getElementById('actionPanel');
        if (actionPanel) {
            actionPanel.addEventListener('click', function(e) {
                if (e.target === this) closeActionPanel();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewLeadModal();
                closeLeadModal();
                closeActionPanel();
            }
        });
    });
</script>
@endpush
