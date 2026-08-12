@extends('layouts.admin-sidebar')

@section('title', 'Leads Pipeline · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

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
            overflow-y: hidden;
            padding: 20px 28px 28px 28px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: #D8CDBC transparent;
        }
        /* Slim custom scrollbar instead of the bulky OS-default one */
        .kanban-board::-webkit-scrollbar { height: 7px; }
        .kanban-board::-webkit-scrollbar-track { background: transparent; }
        .kanban-board::-webkit-scrollbar-thumb { background: #D8CDBC; border-radius: 999px; }
        .kanban-board::-webkit-scrollbar-thumb:hover { background: #C2B4A0; }
        .kanban-board::-webkit-scrollbar-button { display: none; width: 0; height: 0; }
        .kanban-column {
            width: 262px;
            min-width: 220px;
            flex-shrink: 0;
            background: #F0EBE1;
            border-radius: 14px;
            padding: 12px;
        }
        .kanban-column-head {
            display: flex; align-items: center; gap: 8px; padding: 2px 6px 10px;
        }
        .kanban-column-title { font: 600 14px 'Baloo 2'; color: #16436E; flex: 1; }
        .kanban-column-count {
            background: #FFFFFF; border-radius: 8px;
            padding: 2px 8px; font: 800 11.5px 'Nunito Sans'; color: #8A7D6C;
        }
        .kanban-column-body { display: flex; flex-direction: column; gap: 10px; min-height: 40px; }
        .kanban-empty { text-align: center; color: #B0A493; font: 600 12px 'Nunito Sans'; padding: 20px 0; }

        /* ===========================
           Lead card
        =========================== */
        .lead-card {
            background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 12px; padding: 12px 14px;
            display: flex; flex-direction: column; gap: 7px;
        }
        .lead-card:hover { border-color: #D8CDBC; }
        .lead-card-top { display: flex; align-items: center; gap: 8px; }
        .lead-card-name { font: 800 14px 'Nunito Sans'; color: #2B3A4C; flex: 1; }
        .lead-card-source-badge { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
        .lead-card-parent { font: 600 12px 'Nunito Sans'; color: #98897A; }
        .lead-card-notes {
            font: 600 12px/1.45 'Nunito Sans'; color: #5A6B7E; display: -webkit-box;
            -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .lead-card-bottom {
            display: flex; align-items: center; gap: 8px; border-top: 1px solid #F3EDE3; padding-top: 8px;
        }
        .lead-card-value { font: 800 12px 'Nunito Sans'; color: #16436E; flex: 1; }
        .lead-card-time { font: 600 11px 'Nunito Sans'; color: #B0A493; }
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
           Tablet
        =========================== */
        @media (max-width: 900px) {
            .kanban-column { width: 210px; }
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
            .topbar-buttons { display: flex; gap: 8px; }
            .btn-filter, .btn-new-lead { flex: 1 1 auto; text-align: center; padding: 10px 12px; }

            .kanban-board { padding: 12px 16px 16px 16px; gap: 10px; }
            .kanban-column { width: 84vw; min-width: 220px; max-width: 340px; }

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
            .kanban-column { width: 84vw; }
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

        $statusLabels = [
            'new' => 'New',
            'contacted' => 'Contacted',
            'assessment_booked' => 'Assessment Booked',
            'assessment_done' => 'Assessment Done',
            'enrolled' => 'Enrolled',
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
                        <span id="leadCount">{{ $leads->count() }}</span> active enquiries ·
                        <span id="totalValue">AED {{ number_format($totalValue ?? 0, 0) }}</span> est. monthly value ·
                        click → to advance a lead
                    </div>
                </div>
                <div class="topbar-buttons">
                    <button class="btn-filter">Filter: All sources</button>
                    <button class="btn-new-lead" onclick="openLeadModal()">+ New Lead</button>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="kanban-board">
                @foreach ($statusLabels as $statusKey => $statusLabel)
                    <div class="kanban-column">
                        <div class="kanban-column-head">
                            <div class="kanban-column-title">{{ $statusLabel }}</div>
                            <span class="kanban-column-count" id="{{ $statusKey }}Count">{{ $leads->where('status', $statusKey)->count() }}</span>
                        </div>
                        <div class="kanban-column-body" id="{{ $statusKey }}Leads">
                            @forelse ($leads->where('status', $statusKey) as $lead)
                                @php $badge = $sourceBadgeColors[$lead->source] ?? $defaultBadgeColor; @endphp
                                <div class="lead-card" data-id="{{ $lead->id }}" data-status="{{ $lead->status }}">
                                    <div class="lead-card-top">
                                        <div class="lead-card-name js-name">{{ $lead->child_name ?? 'N/A' }} · {{ $lead->child_age ?? 'N/A' }}</div>
                                        <span class="lead-card-source-badge js-source" style="background: {{ $badge['bg'] }}; color: {{ $badge['color'] }};">{{ $lead->source ?? 'N/A' }}</span>
                                    </div>
                                    <div class="lead-card-parent js-parent">{{ $lead->parent_guardian_name ?? 'N/A' }}</div>
                                    <div class="lead-card-notes js-notes">{{ $lead->notes ?? 'No notes' }}</div>
                                    <div class="lead-card-bottom">
                                        <div class="lead-card-value js-value">AED {{ number_format($lead->estimated_value_numeric ?? 0, 0) }}/mo</div>
                                        <div class="lead-card-time js-time">{{ $lead->created_at->diffForHumans() }}</div>
                                        <div class="lead-card-actions">
                                            <button class="btn-view" onclick="viewLead({{ $lead->id }})" title="View details">👁</button>
                                            <button class="btn-advance" onclick="advanceLead({{ $lead->id }}, '{{ $lead->status }}')" title="Move to next stage" {{ $statusKey === 'enrolled' ? 'disabled' : '' }}>→</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="kanban-empty">No leads in this stage</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
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
        'enrolled': 'Enrolled'
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
        'enrolled': null
    };

    const updateStatusUrl = '{{ route("leads.update-status", ["lead" => "__LEAD_ID__"]) }}';
    const storeLeadUrl = '{{ route("leads.store") }}';
    const viewLeadUrl = '{{ route("leads.show", ["lead" => "__LEAD_ID__"]) }}';
    const updateLeadUrl = '{{ route("leads.update", ["lead" => "__LEAD_ID__"]) }}';

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
                            advanceBtn.setAttribute('onclick', `advanceLead(${leadId}, '${lead.status}')`);
                            advanceBtn.disabled = !nextStatusMap[lead.status];
                        }

                        card.dataset.status = lead.status;
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
            if (button) {
                button.textContent = '→';
                button.disabled = !nextStatusMap[nextStatus];
            }
        });
    }

    // ===== Move card between columns =====
    function moveLeadCard(leadId, fromStatus, toStatus) {
        const card = document.querySelector(`.lead-card[data-id="${leadId}"]`);
        if (!card) return;

        const targetContainer = document.getElementById(toStatus + 'Leads');
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
            newButton.setAttribute('onclick', `advanceLead(${leadId}, '${toStatus}')`);
            newButton.disabled = !nextStatusMap[toStatus];
        }

        const viewButton = cardClone.querySelector('.btn-view');
        if (viewButton) viewButton.setAttribute('onclick', `viewLead(${leadId})`);

        if (card.parentNode) card.parentNode.removeChild(card);
        targetContainer.appendChild(cardClone);
    }

    // ===== Update column counts =====
    function updateCounts(fromStatus, toStatus) {
        const fromCountElement = document.getElementById(fromStatus + 'Count');
        if (fromCountElement) {
            const count = parseInt(fromCountElement.textContent) || 0;
            fromCountElement.textContent = Math.max(0, count - 1);
        }

        const toCountElement = document.getElementById(toStatus + 'Count');
        if (toCountElement) {
            const count = parseInt(toCountElement.textContent) || 0;
            toCountElement.textContent = count + 1;
        }
    }

    function closeViewLeadModal() {
        document.getElementById('viewLeadModal').style.display = 'none';
        document.body.style.overflow = '';
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

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewLeadModal();
                closeLeadModal();
            }
        });
    });
</script>
@endpush
