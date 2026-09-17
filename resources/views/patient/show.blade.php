@extends('layouts.admin-sidebar')

@section('title', ($patient->lead->child_name ?? 'Patient').' · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')

<style>
    .main-content-inner.pt-tight-padding { padding-left: 24px; padding-right: 24px; }
    .pt-page { background: transparent; }

    .pt-back-link { font: 700 12.5px 'Nunito Sans'; color: #C8355F; text-decoration: none; }
    .pt-back-link:hover { text-decoration: underline; }

    .pt-header { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-top: 12px; }
    .pt-header-avatar { width: 52px; height: 52px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font: 600 19px 'Baloo 2'; flex-shrink: 0; }
    .pt-header-name { font: 600 22px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .pt-header-sub { font: 600 13px 'Nunito Sans'; color: #98897A; }
    .pt-header-parent { font: 800 13px 'Nunito Sans'; color: #2B3A4C; text-align: right; }
    .pt-header-parent-phone { font: 600 12px 'Nunito Sans'; color: #98897A; text-align: right; }
    .pt-edit-btn {
        background: #fff; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px; padding: 11px 20px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; flex: none; white-space: nowrap; text-decoration: none;
        box-sizing: border-box; transition: background 0.15s ease, transform 0.05s ease;
    }
    .pt-edit-btn:hover { background: #F5EFE7; }
    .pt-edit-btn:active { transform: translateY(1px); }

    .pt-incomplete-banner {
        background: #FDF6E9; border: 1px solid #EBDCC2; border-radius: 12px; padding: 14px 16px; margin-top: 16px;
        display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    }
    .pt-incomplete-title { font: 600 15px 'Baloo 2'; color: #8A5A10; }
    .pt-incomplete-sub { font: 600 12px 'Nunito Sans'; color: #8A7D6C; margin-top: 2px; }
    .pt-incomplete-btn {
        background: #B97F24; color: #fff; border: none; border-radius: 10px; padding: 12px 22px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; flex-shrink: 0; box-sizing: border-box;
        box-shadow: 0 4px 14px rgba(185,127,36,0.28);
    }

    .pt-attention-banner {
        background: #FBEAE8; border: 1px solid #EFC7C2; border-radius: 12px; padding: 12px 16px; margin-top: 16px;
        font: 700 12.5px 'Nunito Sans'; color: #B3261E;
    }

    .pt-success-banner {
        background: #E4F6EB; border: 1px solid #BFE9CE; color: #1E8A4C; border-radius: 12px; padding: 12px 16px; margin-top: 16px;
        font: 700 12.5px 'Nunito Sans';
    }

    .pt-chips-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .pt-chip { border-radius: 7px; padding: 5px 12px; font: 800 12px 'Nunito Sans'; white-space: nowrap; }

    .pt-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 20px; margin-bottom: 18px; }
    .pt-tab-btn {
        background: #fff; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 9px; padding: 9px 16px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer;
    }
    .pt-tab-btn.active { background: #16436E; color: #fff; border-color: #16436E; }
    .pt-tab-panel { display: none; }
    .pt-tab-panel.active { display: block; }

    .pt-grid { display: grid; grid-template-columns: 1fr 400px; gap: 18px; align-items: start; }
    .pt-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; margin-bottom: 18px; }
    .pt-card-title { font: 600 16px 'Baloo 2'; color: #16436E; display: flex; align-items: center; justify-content: space-between; }
    .pt-card-empty { font: 600 12.5px/1.5 'Nunito Sans'; color: #98897A; }

    .pt-goal-row { display: flex; flex-direction: column; gap: 5px; }
    .pt-goal-top { display: flex; justify-content: space-between; }
    .pt-goal-name { font: 700 13px 'Nunito Sans'; color: #2B3A4C; }
    .pt-goal-pct { font: 800 12.5px 'Nunito Sans'; color: #16436E; }
    .pt-progress-track { height: 9px; background: #F3EDE3; border-radius: 5px; overflow: hidden; }
    .pt-progress-fill { height: 100%; background: #C8355F; border-radius: 5px; }

    .pt-notes-list { display: flex; flex-direction: column; gap: 8px; max-height: 260px; overflow-y: auto; }
    .pt-note-entry { border: 1px solid #EFE8DD; border-radius: 11px; padding: 11px 13px; }
    .pt-note-author-row { display: flex; align-items: baseline; gap: 8px; }
    .pt-note-author { flex: 1; font: 800 12px 'Nunito Sans'; color: #2B3A4C; }
    .pt-note-when { font: 600 10.5px 'Nunito Sans'; color: #A79C8E; }
    .pt-note-body { font: 600 12.5px/1.5 'Nunito Sans'; color: #5A6B7E; margin-top: 3px; text-wrap: pretty; }
    .pt-note-input {
        width: 100%; box-sizing: border-box; padding: 11px 13px; border: 1px solid #E2DACE; border-radius: 10px;
        background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none; resize: vertical; line-height: 1.5;
    }
    .pt-conversion-banner {
        box-sizing: border-box; padding: 11px 13px; border: 1px solid #E2DACE; border-radius: 10px;
        background: #F6F3EE; font: 600 12.5px/1.5 'Nunito Sans'; color: #5A6B7E;
    }
    .pt-conversion-banner b { color: #2B3A4C; font-weight: 800; }
    .pt-note-input:focus { border-color: #C8355F; }
    .pt-note-actions { display: flex; align-items: center; gap: 10px; }
    .pt-note-hint { flex: 1; font: 600 11px 'Nunito Sans'; color: #A79C8E; }
    .pt-add-note-btn {
        background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 11px 22px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; box-sizing: border-box;
        box-shadow: 0 4px 14px rgba(200,53,95,0.28);
    }
    .pt-add-note-btn:hover { background: #A82348; }
    .pt-add-note-btn:disabled { opacity: .6; cursor: default; box-shadow: none; }

    .pt-field-input {
        width: 100%; padding: 9px 12px; border: 1px solid #E2DACE; border-radius: 8px;
        background: #F6F3EE; font: 700 13.5px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;
    }
    .pt-field-input:focus { border-color: #C8355F; }
    .pt-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }

    .pt-goal-option { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 8px; background: #F6F3EE; }
    .pt-goal-option.hidden-by-search { display: none; }
    .pt-goal-usage { font: 600 10.5px 'Nunito Sans'; color: #98897A; margin-left: auto; text-align: right; }
    .pt-goal-option:has(.pt-goal-checkbox:checked) { background: #FBE7EC; }
    .pt-goal-option:has(.pt-goal-checkbox:checked) .pt-goal-name,
    .pt-goal-option:has(.pt-goal-checkbox:checked) .pt-goal-usage { color: #C8355F; }

    .pt-goal-checkbox {
        appearance: none; -webkit-appearance: none; -moz-appearance: none;
        width: 17px; height: 17px; border-radius: 5px; border: none; outline: none;
        background: #fff; flex-shrink: 0; cursor: pointer; position: relative; margin: 0;
    }
    .pt-goal-checkbox:checked { background: #C8355F; }
    .pt-goal-checkbox:checked::after {
        content: ''; position: absolute; left: 5px; top: 1px; width: 4px; height: 9px;
        border: solid #fff; border-width: 0 2px 2px 0; transform: rotate(45deg);
    }

    .pt-goal-chip {
        display: inline-flex; align-items: center; gap: 4px; background: #FBE7EC; color: #C8355F;
        border-radius: 999px; padding: 5px 5px 5px 12px; font: 800 12px 'Nunito Sans'; white-space: nowrap;
    }
    .pt-goal-chip button {
        background: none; border: none; color: #C8355F; font: 800 14px/1 'Nunito Sans'; cursor: pointer;
        width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0;
    }
    .pt-goal-chip button:hover { background: rgba(200,53,95,0.15); }

    .pt-auth-card { border: 1px solid #EBE4DA; border-radius: 12px; padding: 14px 16px; }
    .pt-auth-top { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .pt-auth-payer { font: 800 14px 'Nunito Sans'; color: #2B3A4C; }
    .pt-auth-coverage-badge { background: #E7EFF7; color: #24619C; border-radius: 999px; padding: 3px 10px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
    .pt-auth-covers { font: 600 12px 'Nunito Sans'; color: #98897A; margin: 4px 0 8px; }
    .pt-auth-value { font: 700 13px 'Nunito Sans'; color: #16436E; margin-bottom: 6px; }
    .pt-auth-meta { font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-top: 6px; display: flex; justify-content: space-between; }
    .pt-dashed-btn {
        background: #fff; border: 1px dashed #D9CDBD; border-radius: 9px; padding: 9px; text-align: center;
        font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer;
    }
    .pt-dashed-btn:hover { background: #FBF3E4; }

    .pt-team-entry { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; background: #F6F3EE; border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; gap: 9px; }
    .pt-avatar-sm { width: 26px; height: 26px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font: 600 10.5px 'Baloo 2'; flex-shrink: 0; }

    .pt-upcoming-row { display: flex; gap: 10px; align-items: center; }
    .pt-upcoming-day { width: 90px; font: 600 12.5px 'Baloo 2'; color: #16436E; flex-shrink: 0; }

    .pt-data-table { width: 100%; border-collapse: collapse; }
    .pt-data-table th { text-align: left; font: 800 10.5px 'Nunito Sans'; text-transform: uppercase; letter-spacing: 0.05em; color: #98897A; padding: 10px 14px; border-bottom: 1px solid #EBE4DA; white-space: nowrap; }
    .pt-data-table td { padding: 12px 14px; border-bottom: 1px solid #F3EDE3; font: 600 12.5px 'Nunito Sans'; color: #2B3A4C; }
    .pt-data-table tr:last-child td { border-bottom: none; }

    .pt-stat-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 18px; }
    .pt-stat-card { background: #fff; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 18px; }
    .pt-stat-label { font: 800 10.5px 'Nunito Sans'; text-transform: uppercase; letter-spacing: 0.06em; color: #98897A; }
    .pt-stat-value { font: 700 22px 'Baloo 2'; color: #16436E; margin-top: 6px; }
    .pt-stat-value.outstanding { color: #B97F24; }

    .pt-doc-row { display: flex; align-items: center; justify-content: space-between; padding: 11px 13px; border: 1px solid #EFE8DD; border-radius: 10px; gap: 10px; }
    .pt-doc-name { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; }
    .pt-doc-meta { font: 600 11px 'Nunito Sans'; color: #98897A; }
    .pt-doc-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
    .pt-doc-actions a, .pt-doc-actions button { background: none; border: none; padding: 0; font: 700 11.5px 'Nunito Sans'; cursor: pointer; }
    .pt-doc-actions a { color: #24619C; }
    .pt-doc-actions button { color: #B3261E; }
    .pt-doc-badge { border-radius: 999px; padding: 4px 11px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
    .pt-doc-badge-type { background: #E7EFF7; color: #24619C; }
    .pt-doc-badge-ok { background: #E4F6EB; color: #1E8A4C; }
    .pt-doc-badge-warn { background: #FDF6E9; color: #8A5A10; }
    .pt-doc-badge-neutral { background: #F6F3EE; color: #98897A; }

    .pt-profile-section-title { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; }
    .pt-profile-section-sub { font: 600 11.5px 'Nunito Sans'; color: #98897A; margin: -6px 0 12px; }
    .pt-profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
    .pt-profile-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.05em; }
    .pt-profile-field-value { font: 800 13px 'Nunito Sans'; color: #2B3A4C; margin-top: 3px; }

    /* Modals (shared look) */
    .pt-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45);
        align-items: center; justify-content: center; z-index: 9999; padding: 16px;
    }
    .pt-modal-box {
        width: 520px; max-width: 100%; max-height: 88vh; overflow-y: auto; background: #FFFDFA;
        border-radius: 18px; padding: 26px 28px; display: flex; flex-direction: column; gap: 16px;
        box-shadow: 0 20px 60px rgba(22,42,60,0.3);
    }
    .pt-modal-header { display: flex; align-items: flex-start; gap: 12px; }
    .pt-modal-header > div:first-child { flex: 1; }
    .pt-modal-title { font: 600 20px 'Baloo 2'; color: #16436E; }
    .pt-modal-subtitle { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 3px; }
    .pt-modal-close {
        width: 32px; height: 32px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF;
        color: #5A6B7E; font: 800 15px/1 'Nunito Sans'; cursor: pointer; display: flex;
        align-items: center; justify-content: center; flex-shrink: 0;
    }
    .pt-modal-close:hover { background: #F6F3EE; }
    .pt-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pt-modal-actions { display: flex; gap: 10px; border-top: 1px solid #F3EDE3; padding-top: 14px; }
    .pt-btn-save {
        flex: 1; background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 13px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer; box-sizing: border-box;
        box-shadow: 0 4px 14px rgba(200,53,95,0.28);
    }
    .pt-btn-save:hover { background: #A82348; }
    .pt-btn-cancel {
        background: #fff; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 13px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer; box-sizing: border-box;
    }
    .pt-btn-cancel:hover { background: #F6F3EE; }

    @media (max-width: 900px) {
        .pt-grid, .pt-stat-cards { grid-template-columns: 1fr; }
    }
</style>

@php
    $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];
    $lead = $patient->lead;
    $attendanceRate = $patient->attendanceRate();
    $primaryAuth = $patient->primaryAuthorization();
    $assessmentClinician = optional($lead)->assessmentClinician;
    $packageLocation = optional($lead)->packageLocation;
    $leadPackages = $lead ? $lead->packages()->get() : collect();

    // Package(s) agreed at intake - hours, rate and value are read straight off
    // the Package catalog rows the lead picked, summed when more than one was
    // selected, rather than re-entered anywhere.
    $packageHoursPerWeek = $leadPackages->sum(fn ($p) => (float) $p->hours_per_week);
    $packageRates = $leadPackages->pluck('rate')->filter()->unique();
    $packageRatePerHr = $packageRates->count() === 1 ? $packageRates->first() : ($packageRates->count() > 1 ? 'Mixed' : null);
    $packageValueExclVat = $leadPackages->sum(fn ($p) => (float) $p->total_excl_vat);
    $packageSettings = $leadPackages->pluck('delivery_mode')->filter()->unique();
    $packageSetting = $packageSettings->count() === 1 ? $packageSettings->first() : ($packageSettings->count() > 1 ? 'Mixed' : null);

    // "Funding" summary line - Insurance / Self pay / Mixed - derived from who
    // pays for each service line, falling back to the single funding_type
    // picked in the funding step if no per-service breakdown was captured.
    $fundingServiceRows = collect(optional($lead)->funding_services_needed ?? []);
    $fundingPayers = $fundingServiceRows->pluck('payer')->filter()->unique();
    $fundingSummary = $fundingPayers->count() > 1
        ? 'Mixed — insurance + self pay'
        : ($fundingPayers->first() ?? optional($lead)->funding_type);

    // "Insurance-funded hours" reads the approved-hours figure out of each
    // insurance-paid service line's free-text "cover" note (e.g. "96 h
    // approved"), rather than the weekly hours booked - those are two
    // different numbers and the intake form only ever captures the former
    // as prose.
    $fundingHoursNeeded = $fundingServiceRows
        ->filter(fn ($r) => ($r['payer'] ?? null) === 'Insurance')
        ->sum(fn ($r) => preg_match('/(\d+)/', $r['cover'] ?? '', $m) ? (int) $m[1] : (int) ($r['hours_per_week'] ?? 0));
    $fundingHoursNeeded = $fundingHoursNeeded > 0 ? $fundingHoursNeeded : null;

    $soonestRenewal = $patient->authorizations->filter(fn ($a) => $a->renews_at)->sortBy('renews_at')->first();
    $expiringAuthDoc = $patient->documents
        ->where('type', 'Authorization')
        ->filter(fn ($d) => $d->isDueForRenewal())
        ->sortBy('expires_at')
        ->first();
@endphp

<div class="pt-page">

    <a href="{{ route('patient.index') }}" class="pt-back-link">‹ All patients</a>

    <div class="pt-header">
        <div class="pt-header-avatar" style="background: {{ $avatarColor($patient->lead_id) }};">
            {{ strtoupper(substr($lead->child_name ?? '?', 0, 1)) }}
        </div>
        <div style="flex: 1; min-width: 200px;">
            <div class="pt-header-name">{{ $lead->child_name ?? 'Unnamed' }}</div>
            <div class="pt-header-sub">
                Age {{ $lead->child_age ?? '—' }}{{ $patient->diagnosis ? ' · '.$patient->diagnosis : '' }}
                · enrolled {{ $patient->enrolled_at->format('M Y') }}
            </div>
        </div>
        <div>
            <div class="pt-header-parent">{{ $lead->parent_guardian_name ?? '—' }}</div>
            <div class="pt-header-parent-phone">{{ $lead->phone ?? 'No phone on file' }}</div>
        </div>
        <button type="button" class="pt-edit-btn" onclick="openPatientEditModal()">Edit details</button>
    </div>

    @if (session('success'))
        <div class="pt-success-banner">{{ session('success') }}</div>
    @endif

    @if ($soonestRenewal && $soonestRenewal->renews_at->between(now(), now()->addDays(45)))
        <div class="pt-attention-banner">
            Attention — {{ $soonestRenewal->payer_name }} authorization renews {{ $soonestRenewal->renews_at->format('d M Y') }}
        </div>
    @elseif ($expiringAuthDoc)
        <div class="pt-attention-banner">
            Attention — {{ $expiringAuthDoc->name }} {{ $expiringAuthDoc->expires_at->isPast() ? 'expired' : 'expires' }} {{ $expiringAuthDoc->expires_at->format('d M Y') }} — sessions billed past this date may not be reimbursed
        </div>
    @endif

    @if ($patient->isProfileIncomplete())
        <div class="pt-incomplete-banner">
            <div style="flex: 1; min-width: 200px;">
                <div class="pt-incomplete-title">New client — profile incomplete</div>
                <div class="pt-incomplete-sub">Missing: {{ $patient->missingFieldsLabel() }}</div>
            </div>
            <button type="button" class="pt-incomplete-btn" onclick="openPatientEditModal()">Add client details</button>
        </div>
    @endif

    <div class="pt-chips-row">
        @if ($patient->programme)
            <span class="pt-chip" style="background: #F9E7EC; color: #C8355F;">{{ $patient->programme }}</span>
        @endif
        @if ($primaryAuth)
            <span class="pt-chip" style="background: #E7EFF7; color: #24619C;">{{ $primaryAuth->payer_name }}</span>
        @endif
        @if ($attendanceRate !== null)
            <span class="pt-chip" style="background: #F3EDE3; color: #5A6B7E;">Attendance {{ $attendanceRate }}%</span>
        @endif
    </div>

    <div class="pt-tabs">
        <button type="button" class="pt-tab-btn active" data-tab="overview" onclick="ptShowTab('overview')">Overview</button>
        <button type="button" class="pt-tab-btn" data-tab="history" onclick="ptShowTab('history')">Session history</button>
        <button type="button" class="pt-tab-btn" data-tab="payments" onclick="ptShowTab('payments')">Payments</button>
        <button type="button" class="pt-tab-btn" data-tab="documents" onclick="ptShowTab('documents')">Documents</button>
        <button type="button" class="pt-tab-btn" data-tab="profile" onclick="ptShowTab('profile')">Profile & intake</button>
    </div>

    <!-- ===== Overview ===== -->
    <div class="pt-tab-panel active" id="pt-tab-overview">
        <div class="pt-grid">
            <div>
                <div class="pt-card">
                    <div class="pt-card-title">
                        Session goals — worked on today
                        <span id="ptGoalSelectedCount" style="font: 800 12px 'Nunito Sans'; color: #C8355F; display:none;"></span>
                    </div>
                    @unless ($todaysSession)
                        <div class="pt-card-empty">No session scheduled today for this patient — saving goals below will log one automatically.</div>
                    @endunless
                    <div id="ptGoalChipsRow" style="display:none; flex-wrap:wrap; gap:6px;"></div>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="text" id="ptGoalSearch" class="pt-field-input" placeholder="Add a goal worked on this session…" oninput="ptFilterGoals()" onfocus="ptRevealGoalsList()" style="flex:1;">
                        <button type="button" class="pt-btn-save" id="ptGoalsDoneBtn" onclick="ptCollapseGoalsList()" style="display:none; flex:none; width:auto; padding:9px 20px; margin:0;">Done</button>
                    </div>
                    <div id="ptGoalExpanded" style="display:none;">
                        <div id="ptGoalsEmptyHint" class="pt-card-empty" style="{{ !empty($todaysGoalIds) ? 'display:none;' : '' }}">
                            No goals selected yet — pick what was targeted this session, or type a new one.
                        </div>
                        <div id="ptGoalNoMatch" style="display:none;">
                            <div class="pt-card-empty">No goal on record matches that — add it as a custom goal below.</div>
                            <button type="button" class="pt-dashed-btn" id="ptGoalAddCustomBtn" onclick="ptAddCustomGoal()" style="margin-top:8px; width: 100%;"></button>
                        </div>
                        <div class="pt-field-label" style="margin-top:8px;">Recommended from previous sessions</div>
                        <div id="ptGoalRecommendedList" style="display:flex; flex-direction:column; gap:6px;">
                            @foreach ($recommendedGoals as $g)
                                <label class="pt-goal-option" data-title="{{ strtolower($g['goal']->title) }}">
                                    <input type="checkbox" class="pt-goal-checkbox" value="{{ $g['goal']->id }}" onchange="ptToggleExistingGoal(this)" {{ in_array($g['goal']->id, $todaysGoalIds) ? 'checked' : '' }}>
                                    <span class="pt-goal-name" style="font-size: 12.5px;">{{ $g['goal']->title }}</span>
                                    <span class="pt-goal-usage">used in {{ $g['used'] }} of the last 10 sessions{{ $g['last_used_at'] ? ' · last '.$g['last_used_at']->format('j M') : '' }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="pt-card">
                    <div class="pt-card-title">Session notes</div>
                    @if ($patient->lead?->assessment_report_summary)
                        <div class="pt-conversion-banner">Converted from lead — {{ $patient->lead->assessment_report_summary }}</div>
                    @endif
                    <textarea id="ptNoteBody" class="pt-note-input" rows="3" placeholder="Write a session note — what was worked on, response, next step…"></textarea>
                    <div class="pt-note-actions">
                        <div class="pt-note-hint">Saved to the clinical record with your name and time.</div>
                        <button type="button" class="pt-add-note-btn" id="ptAddNoteBtn" onclick="addPatientNote()">Add note</button>
                    </div>
                    <div class="pt-notes-list" id="ptNotesList">
                        @forelse ($patient->notes as $note)
                            <div class="pt-note-entry">
                                <div class="pt-note-author-row">
                                    <div class="pt-note-author">{{ $note->author_name }}</div>
                                    <div class="pt-note-when">{{ $note->created_at->diffForHumans() }}</div>
                                </div>
                                <div class="pt-note-body">{{ $note->body }}</div>
                            </div>
                        @empty
                            <div class="pt-card-empty" id="ptNotesEmpty">No notes on record yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div>
                <div class="pt-card">
                    <div class="pt-card-title">Insurance & authorization</div>
                    @if ($patient->authorizations->count() > 1)
                        <div class="pt-card-empty" style="margin-top: -6px;">More than one payer on file — each covers different services.</div>
                    @endif
                    @forelse ($patient->authorizations as $auth)
                        @php
                            $hoursUsed = $auth->hoursUsed();
                            $hoursTotal = $auth->authorized_hours_total ?: 0;
                            $hoursLeft = max(0, $hoursTotal - $hoursUsed);
                            $pct = $hoursTotal ? min(100, round($hoursUsed / $hoursTotal * 100)) : 0;
                        @endphp
                        <div class="pt-auth-card">
                            <div class="pt-auth-top">
                                <div class="pt-auth-payer">{{ $auth->payer_name }}</div>
                                <span class="pt-auth-coverage-badge">{{ $auth->coverage_percent }}% covered</span>
                            </div>
                            <div class="pt-auth-covers">Covers {{ $auth->coversLabel() }}</div>
                            <div class="pt-auth-value">{{ $hoursLeft }} of {{ $hoursTotal }} hours left</div>
                            <div class="pt-progress-track"><div class="pt-progress-fill" style="width: {{ $pct }}%; background: {{ $hoursLeft <= 5 ? '#B3261E' : '#1E8A4C' }};"></div></div>
                            <div class="pt-auth-meta">
                                <span>Policy {{ $auth->policy_number ?: '—' }}</span>
                                <span>{{ $auth->renews_at ? 'renews '.$auth->renews_at->format('j M Y') : '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="pt-card-empty">No authorization on file — add one to track hours.</div>
                    @endforelse
                </div>

                <div class="pt-card">
                    <div class="pt-card-title">Care team</div>
                    @forelse ($patient->careTeam() as $therapist)
                        <div class="pt-team-entry">
                            <div class="pt-avatar-sm" style="background: {{ $avatarColor($therapist->id) }};">
                                {{ strtoupper(substr($therapist->first_name, 0, 1)) }}
                            </div>
                            {{ trim($therapist->first_name.' '.$therapist->last_name) }}{{ $therapist->job_title ? ' — '.$therapist->job_title : '' }}
                        </div>
                    @empty
                        <div class="pt-card-empty">No therapists assigned yet.</div>
                    @endforelse
                </div>

                <div class="pt-card">
                    <div class="pt-card-title">Upcoming sessions</div>
                    @php $activityColors = ['ABA' => ['#F9E7EC', '#C8355F'], 'Speech' => ['#E7EFF7', '#24619C']]; @endphp
                    @forelse ($upcomingSessions as $session)
                        @php
                            [$bg, $fg] = $activityColors[$session->activity_type] ?? ['#F3EDE3', '#5A6B7E'];
                            $dayLabel = $session->session_date->isToday() ? 'Today' : $session->session_date->format('D');
                        @endphp
                        <div class="pt-upcoming-row">
                            <div class="pt-upcoming-day">{{ $dayLabel }} {{ substr($session->start_time, 0, 5) }}</div>
                            <span class="pt-chip" style="background: {{ $bg }}; color: {{ $fg }};">{{ $session->activity_type }}</span>
                        </div>
                    @empty
                        <div class="pt-card-empty">No sessions booked yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Session History ===== -->
    <div class="pt-tab-panel" id="pt-tab-history">
        <div class="pt-card">
            <div class="pt-card-title">
                Session history
                <span style="font: 600 12px 'Nunito Sans'; color: #98897A;">{{ $sessions->where('status', 'completed')->count() }} of {{ $sessions->count() }} attended</span>
            </div>
            @if ($sessions->isEmpty())
                <div class="pt-card-empty">No sessions on record yet.</div>
            @else
                <div style="overflow-x: auto;">
                    <table class="pt-data-table">
                        <thead>
                            <tr><th>Date</th><th>Time</th><th>Type</th><th>Length</th><th>Therapist</th><th>Status</th><th>Note</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                <tr>
                                    <td>{{ $session->session_date->format('d M Y') }}</td>
                                    <td>{{ substr($session->start_time, 0, 5) }}</td>
                                    <td>{{ $session->activity_type }}</td>
                                    <td>{{ $session->duration_minutes }} min</td>
                                    <td>{{ $session->therapist ? trim($session->therapist->first_name.' '.$session->therapist->last_name) : '—' }}</td>
                                    <td>{{ $session->statusLabel() }}</td>
                                    <td>{{ $session->notes ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- ===== Payments ===== -->
    <div class="pt-tab-panel" id="pt-tab-payments">
        <div class="pt-stat-cards">
            <div class="pt-stat-card">
                <div class="pt-stat-label">Billed to date</div>
                <div class="pt-stat-value">AED {{ number_format($billedTotal, 0) }}</div>
            </div>
            <div class="pt-stat-card">
                <div class="pt-stat-label">Collected</div>
                <div class="pt-stat-value" style="color:#1E8A4C;">AED {{ number_format($collectedTotal, 0) }}</div>
            </div>
            <div class="pt-stat-card">
                <div class="pt-stat-label">Outstanding</div>
                <div class="pt-stat-value outstanding">AED {{ number_format($outstandingTotal, 0) }}</div>
            </div>
        </div>
        <div class="pt-card">
            <div class="pt-card-title">Payment history</div>
            @if ($patient->invoices->isEmpty())
                <div class="pt-card-empty">No invoices raised yet.</div>
            @else
                <div style="overflow-x: auto;">
                    <table class="pt-data-table">
                        <thead>
                            <tr><th>Invoice</th><th>Issued</th><th>Period</th><th>Amount</th><th>Paid</th><th>Method</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($patient->invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td>{{ $invoice->issue_date->format('d M Y') }}</td>
                                    <td>{{ $invoice->period->format('M Y') }}</td>
                                    <td>AED {{ number_format($invoice->subtotal, 0) }}</td>
                                    <td>AED {{ number_format($invoice->amount_paid, 0) }}</td>
                                    <td>{{ $invoice->payment_method ?: '—' }}</td>
                                    <td>{{ $invoice->paymentStatusLabel() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- ===== Documents ===== -->
    <div class="pt-tab-panel" id="pt-tab-documents">
        <div class="pt-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                <div>
                    <div class="pt-card-title" style="margin-bottom:2px;">Documents</div>
                    <div class="pt-card-empty" style="margin:0;">Reports, authorization letters, signed consent and identity records</div>
                </div>
                <button type="button" class="pt-add-note-btn" style="flex-shrink:0;" onclick="openDocModal()">+ Add document</button>
            </div>
            <div id="ptDocList" style="display:flex; flex-direction:column; gap:8px; margin-top: 8px;">
                @forelse ($patient->documents as $document)
                    @php $expiry = $document->expiryStatus(); @endphp
                    <div class="pt-doc-row" id="ptDocRow{{ $document->id }}">
                        <div>
                            <div class="pt-doc-name">{{ $document->name }}</div>
                            <div class="pt-doc-meta">Added by {{ $document->uploaderLabel() }} · {{ $document->created_at->format('d M Y') }}</div>
                        </div>
                        <div class="pt-doc-actions">
                            @if ($document->type)
                                <span class="pt-doc-badge pt-doc-badge-type">{{ $document->type }}</span>
                            @endif
                            <span class="pt-doc-badge pt-doc-badge-{{ $expiry['variant'] }}">{{ $expiry['label'] }}</span>
                            @if ($document->file_path)
                                <a href="{{ route('patient.documents.download', [$patient, $document]) }}">Download</a>
                            @endif
                            <button type="button" onclick="ptDeleteDocument({{ $document->id }}, '{{ route('patient.documents.destroy', [$patient, $document]) }}')">Delete</button>
                        </div>
                    </div>
                @empty
                    <div class="pt-card-empty" id="ptDocEmpty">No documents on file yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ===== Profile & Intake ===== -->
    <div class="pt-tab-panel" id="pt-tab-profile">
        <div class="pt-card">
            <div class="pt-profile-section-title">Package & funding</div>
            <div class="pt-profile-section-sub">Agreed at intake, before conversion</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Package(s)</div><div class="pt-profile-field-value">{{ $leadPackages->isNotEmpty() ? $leadPackages->pluck('name')->implode(', ') : '—' }}</div></div>
                <div><div class="pt-profile-field-label">Hours purchased</div><div class="pt-profile-field-value">{{ $packageHoursPerWeek > 0 ? rtrim(rtrim(number_format($packageHoursPerWeek, 1), '0'), '.').' h' : '—' }}</div></div>
                <div><div class="pt-profile-field-label">Hours / week</div><div class="pt-profile-field-value">{{ $packageHoursPerWeek > 0 ? rtrim(rtrim(number_format($packageHoursPerWeek, 1), '0'), '.').' h' : '—' }}</div></div>
                <div><div class="pt-profile-field-label">Rate / hr</div><div class="pt-profile-field-value">{{ $packageRatePerHr === 'Mixed' ? 'Mixed' : ($packageRatePerHr ? 'AED '.number_format($packageRatePerHr) : '—') }}</div></div>
                <div><div class="pt-profile-field-label">Package value excl. VAT</div><div class="pt-profile-field-value">{{ $packageValueExclVat > 0 ? 'AED '.number_format($packageValueExclVat) : '—' }}</div></div>
                <div><div class="pt-profile-field-label">Sessions / week</div><div class="pt-profile-field-value">{{ $lead->package_sessions_per_week ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Location</div><div class="pt-profile-field-value">{{ $packageLocation->name ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Setting</div><div class="pt-profile-field-value">{{ $packageSetting ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Funding</div><div class="pt-profile-field-value">{{ $fundingSummary ?: '—' }}</div></div>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Services & who pays</div>
            <div class="pt-profile-section-sub">Agreed at intake — a client can have several therapies with different payers</div>
            @if ($fundingServiceRows->isEmpty())
                <div class="pt-card-empty">No service/payer breakdown on file.</div>
            @else
                <div style="overflow-x: auto;">
                    <table class="pt-data-table">
                        <thead><tr><th>Service</th><th>Hours</th><th>Paid by</th><th>Cover</th><th>Approval ref</th></tr></thead>
                        <tbody>
                            @foreach ($fundingServiceRows as $row)
                                <tr>
                                    <td>{{ $row['service'] ?? '—' }}</td>
                                    <td>{{ isset($row['hours_per_week']) ? $row['hours_per_week'].' h / week' : '—' }}</td>
                                    <td>{{ $row['payer'] ?? '—' }}</td>
                                    <td>{{ $row['cover'] ?? '—' }}</td>
                                    <td>{{ $row['approval_ref'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Client</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Child</div><div class="pt-profile-field-value">{{ $lead->child_name ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Age</div><div class="pt-profile-field-value">{{ $lead->child_age ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Diagnosis</div><div class="pt-profile-field-value">{{ $patient->diagnosis ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Programme</div><div class="pt-profile-field-value">{{ $patient->programme ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Client since</div><div class="pt-profile-field-value">{{ $patient->enrolled_at->format('M Y') }}</div></div>
                <div><div class="pt-profile-field-label">Attendance</div><div class="pt-profile-field-value">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</div></div>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Parent & contact</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Parent / guardian</div><div class="pt-profile-field-value">{{ $lead->parent_guardian_name ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Mobile</div><div class="pt-profile-field-value">{{ $lead->phone ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Email</div><div class="pt-profile-field-value">{{ $lead->email ?? '—' }}</div></div>
            </div>
        </div>

        @if ($lead)
        <div class="pt-card">
            <div class="pt-profile-section-title">Child details</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Full name</div><div class="pt-profile-field-value">{{ $lead->child_name ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Emirates ID</div><div class="pt-profile-field-value">{{ $lead->child_emirates_id ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Emirates ID expiry</div><div class="pt-profile-field-value">{{ optional($lead->child_emirates_id_expiry)->format('d M Y') ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Date of birth</div><div class="pt-profile-field-value">{{ optional($lead->child_date_of_birth)->format('d M Y') ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Gender</div><div class="pt-profile-field-value">{{ $lead->child_gender ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Diagnosis</div><div class="pt-profile-field-value">{{ $lead->diagnosis_suspected ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Nursery / school</div><div class="pt-profile-field-value">{{ $lead->nursery_school ?: '—' }}</div></div>
                <div style="grid-column: 1 / -1;"><div class="pt-profile-field-label">Main concern</div><div class="pt-profile-field-value">{{ $lead->main_concern ?: '—' }}</div></div>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Intake form</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Received on</div><div class="pt-profile-field-value">{{ optional($lead->intake_form_received_on)->format('d M Y') ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Received via</div><div class="pt-profile-field-value">{{ $lead->intake_form_received_via ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Allergies</div><div class="pt-profile-field-value">{{ $lead->allergies ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Medical history</div><div class="pt-profile-field-value">{{ $lead->medical_history ?: '—' }}</div></div>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Consultation / assessment</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Date</div><div class="pt-profile-field-value">{{ optional($lead->assessment_date)->format('d M Y') ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Clinician</div><div class="pt-profile-field-value">{{ $assessmentClinician ? trim($assessmentClinician->first_name.' '.$assessmentClinician->last_name) : '—' }}</div></div>
                <div><div class="pt-profile-field-label">Tool</div><div class="pt-profile-field-value">{{ $lead->assessment_tool ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Report ref</div><div class="pt-profile-field-value">{{ $lead->assessment_report_reference ?: '—' }}</div></div>
                <div style="grid-column: 1 / -1;"><div class="pt-profile-field-label">Summary</div><div class="pt-profile-field-value">{{ $lead->assessment_report_summary ?: '—' }}</div></div>
            </div>
        </div>
        @endif

        <div class="pt-card">
            <div class="pt-profile-section-title">Billing authorizations</div>
            @if ($patient->authorizations->isEmpty())
                <div class="pt-card-empty">No funding on file.</div>
            @else
                <div style="overflow-x: auto;">
                    <table class="pt-data-table">
                        <thead><tr><th>Payer</th><th>Approved hours</th><th>Hours used</th><th>Renewal / valid until</th></tr></thead>
                        <tbody>
                            @foreach ($patient->authorizations as $auth)
                                <tr>
                                    <td>{{ $auth->payer_name }}</td>
                                    <td>{{ $auth->authorized_hours_total ?? '—' }}</td>
                                    <td>{{ $auth->hoursUsed() }}</td>
                                    <td>{{ optional($auth->renews_at)->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if ($lead)
        <div class="pt-card">
            <div class="pt-profile-section-title">Funding</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Funding type</div><div class="pt-profile-field-value">{{ $lead->funding_type ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Insurer / payer</div><div class="pt-profile-field-value">{{ $lead->funding_insurer ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Policy number</div><div class="pt-profile-field-value">{{ $lead->funding_policy_number ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Approval valid until</div><div class="pt-profile-field-value">{{ optional($lead->funding_approval_valid_until)->format('d M Y') ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Insurance-funded hours</div><div class="pt-profile-field-value">{{ $fundingHoursNeeded ?? '—' }}</div></div>
                <div style="grid-column: 1 / -1;"><div class="pt-profile-field-label">Funding notes</div><div class="pt-profile-field-value">{{ $lead->funding_notes ?: '—' }}</div></div>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Package agreed</div>
            <div class="pt-profile-grid">
                <div style="grid-column: 1 / -1;"><div class="pt-profile-field-label">Package(s)</div><div class="pt-profile-field-value">{{ $leadPackages->isNotEmpty() ? $leadPackages->pluck('name')->implode(', ') : '—' }}</div></div>
                <div><div class="pt-profile-field-label">Start date</div><div class="pt-profile-field-value">{{ optional($lead->package_start_date)->format('d M Y') ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Sessions / week</div><div class="pt-profile-field-value">{{ $lead->package_sessions_per_week ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Location</div><div class="pt-profile-field-value">{{ $packageLocation->name ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Agreed by</div><div class="pt-profile-field-value">{{ $lead->package_agreed_by ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Notes</div><div class="pt-profile-field-value">{{ $lead->package_scheduling_notes ?: '—' }}</div></div>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Consent & terms</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Agreement signed</div><div class="pt-profile-field-value">{{ optional($lead->consent_signed_date)->format('d M Y') ?? '—' }}</div></div>
                <div><div class="pt-profile-field-label">Signed by</div><div class="pt-profile-field-value">{{ $lead->consent_signed_by ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Data & photo consent</div><div class="pt-profile-field-value">{{ $lead->consent_data_photo ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Method</div><div class="pt-profile-field-value">{{ $lead->consent_signature_method ?: '—' }}</div></div>
                <div style="grid-column: 1 / -1;"><div class="pt-profile-field-label">Notes</div><div class="pt-profile-field-value">{{ $lead->consent_notes ?: '—' }}</div></div>
            </div>
        </div>

        <div class="pt-card">
            <div class="pt-profile-section-title">Lead origin</div>
            <div class="pt-profile-grid">
                <div><div class="pt-profile-field-label">Source</div><div class="pt-profile-field-value">{{ $lead->source ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">Campaign</div><div class="pt-profile-field-value">{{ $lead->campaign ?: '—' }}</div></div>
                <div><div class="pt-profile-field-label">First enquiry</div><div class="pt-profile-field-value">{{ $lead->created_at->format('d M Y, H:i') }}</div></div>
                <div><div class="pt-profile-field-label">Lead owner</div><div class="pt-profile-field-value">{{ $lead->assigned_to_name ?? '—' }}</div></div>
            </div>
        </div>
        @endif
    </div>

</div>

<!-- Edit Patient Details Modal -->
<div id="ptEditModal" class="pt-modal-overlay">
    <div class="pt-modal-box">
        <div class="pt-modal-header">
            <div>
                <div class="pt-modal-title">Client details</div>
                <div class="pt-modal-subtitle">Clinical and enrollment record</div>
            </div>
            <button type="button" class="pt-modal-close" onclick="closePatientEditModal()">✕</button>
        </div>

        <form id="ptEditForm" onsubmit="savePatientDetails(event)">
            @csrf
            @method('PUT')

            <div class="pt-grid-2col">
                <div>
                    <div class="pt-field-label">Child's name *</div>
                    <input id="ptChildName" name="child_name" type="text" class="pt-field-input" value="{{ $lead->child_name }}">
                </div>
                <div>
                    <div class="pt-field-label">Age</div>
                    <input id="ptChildAge" name="child_age" type="number" min="0" max="25" class="pt-field-input" value="{{ $lead->child_age }}">
                </div>
                <div>
                    <div class="pt-field-label">Diagnosis *</div>
                    <input id="ptDiagnosis" name="diagnosis" type="text" class="pt-field-input" value="{{ $patient->diagnosis }}">
                </div>
                <div>
                    <div class="pt-field-label">Programme *</div>
                    <input id="ptProgramme" name="programme" type="text" class="pt-field-input" value="{{ $patient->programme }}">
                </div>
                <div>
                    <div class="pt-field-label">Parent / guardian</div>
                    <input id="ptParentName" name="parent_guardian_name" type="text" class="pt-field-input" value="{{ $lead->parent_guardian_name }}">
                </div>
                <div>
                    <div class="pt-field-label">Phone *</div>
                    <input id="ptPhone" name="phone" type="text" class="pt-field-input" value="{{ $lead->phone ?? '' }}">
                </div>
                <div>
                    <div class="pt-field-label">Insurance</div>
                    @php $currentPayer = $primaryAuth->payer_name ?? null; @endphp
                    <select id="ptInsurance" name="insurance" class="pt-field-input">
                        <option value="">No insurance</option>
                        @if ($currentPayer && $currentPayer !== 'Self-pay' && ! $insurances->contains('name', $currentPayer))
                            <option value="{{ $currentPayer }}" selected>{{ $currentPayer }} (inactive)</option>
                        @endif
                        @foreach ($insurances as $insurance)
                            <option value="{{ $insurance->name }}" @selected($currentPayer === $insurance->name)>{{ $insurance->name }}</option>
                        @endforeach
                        <option value="Self-pay" @selected($currentPayer === 'Self-pay')>Self-pay</option>
                    </select>
                </div>
                <div>
                    <div class="pt-field-label">Auth. hrs</div>
                    <input id="ptAuthHours" name="authorized_hours_total" type="number" min="0" class="pt-field-input" value="{{ $primaryAuth->authorized_hours_total ?? '' }}">
                </div>
                <div>
                    <div class="pt-field-label">Renewal</div>
                    <input id="ptRenewsAt" name="renews_at" type="date" class="pt-field-input" value="{{ optional($primaryAuth?->renews_at)->format('Y-m-d') }}">
                </div>
                <div>
                    <div class="pt-field-label">Start</div>
                    <input id="ptEnrolledAt" name="enrolled_at" type="date" class="pt-field-input" value="{{ optional($patient->enrolled_at)->format('Y-m-d') }}">
                </div>
                <div style="grid-column: 1 / -1;">
                    <div class="pt-field-label">Clinical note</div>
                    <textarea id="ptClinicalNote" name="clinical_note" rows="2" class="pt-field-input" style="resize: vertical; font-family: 'Nunito Sans';" placeholder="Optional — adds a timestamped session note"></textarea>
                </div>
            </div>

            <div class="pt-modal-actions">
                <button type="submit" class="pt-btn-save">Save client</button>
                <button type="button" class="pt-btn-cancel" onclick="closePatientEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="ptDocModal" class="pt-modal-overlay">
    <div class="pt-modal-box" style="width:460px;">
        <div class="pt-modal-header">
            <div>
                <div class="pt-modal-title">Add document</div>
                <div class="pt-modal-subtitle">Filed against the client's clinical record</div>
            </div>
            <button type="button" class="pt-modal-close" onclick="closeDocModal()">✕</button>
        </div>

        <form id="ptDocForm" onsubmit="ptSubmitDocument(event)">
            <div>
                <div class="pt-field-label">File name *</div>
                <input id="ptDocName" name="name" type="text" class="pt-field-input" placeholder="e.g. September progress review.pdf" required>
            </div>
            <div class="pt-grid-2col" style="margin-top:12px;">
                <div>
                    <div class="pt-field-label">Type</div>
                    <select id="ptDocType" name="type" class="pt-field-input">
                        @foreach (\App\Models\PatientDocument::TYPES as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="pt-field-label">Expiry</div>
                    <input id="ptDocExpiry" name="expires_at" type="date" class="pt-field-input" placeholder="optional">
                </div>
            </div>

            <div class="pt-modal-actions">
                <button type="submit" class="pt-btn-save">Add to record</button>
                <button type="button" class="pt-btn-cancel" onclick="closeDocModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function ptCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;
    }

    // ---- Tabs ----
    function ptShowTab(tab) {
        const panel = document.getElementById('pt-tab-' + tab);
        const btn = document.querySelector('.pt-tab-btn[data-tab="' + tab + '"]');
        if (!panel || !btn) return;

        document.querySelectorAll('.pt-tab-panel').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.pt-tab-btn').forEach(el => el.classList.remove('active'));
        panel.classList.add('active');
        btn.classList.add('active');

        history.replaceState(null, '', '#' + tab);
    }

    // ---- Edit patient modal ----
    const ptUpdateUrl = @json(route('patient.update', $patient));
    const ptNotesUrl = @json(route('patient.notes.store', $patient));

    function openPatientEditModal() {
        document.getElementById('ptEditModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closePatientEditModal() {
        document.getElementById('ptEditModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function savePatientDetails(event) {
        event.preventDefault();
        const form = document.getElementById('ptEditForm');
        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        const btn = form.querySelector('.pt-btn-save');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving…';

        fetch(ptUpdateUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ptCsrf(), 'Accept': 'application/json' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Could not save: ' + (data.errors ? Object.values(data.errors).flat().join(', ') : 'unknown error'));
                btn.disabled = false;
                btn.textContent = originalText;
            }
        })
        .catch(() => {
            alert('Network error. Please try again.');
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    // ---- Session notes ----
    function addPatientNote() {
        const textarea = document.getElementById('ptNoteBody');
        const body = textarea.value.trim();
        if (!body) return;

        const btn = document.getElementById('ptAddNoteBtn');
        btn.disabled = true;

        fetch(ptNotesUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ptCsrf(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ body })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const list = document.getElementById('ptNotesList');
                const emptyPlaceholder = document.getElementById('ptNotesEmpty');
                if (emptyPlaceholder) emptyPlaceholder.remove();

                const entry = document.createElement('div');
                entry.className = 'pt-note-entry';
                entry.innerHTML = `
                    <div class="pt-note-author-row">
                        <div class="pt-note-author">${data.note.author_name}</div>
                        <div class="pt-note-when">just now</div>
                    </div>
                    <div class="pt-note-body"></div>
                `;
                entry.querySelector('.pt-note-body').textContent = data.note.body;
                list.insertBefore(entry, list.firstChild);

                textarea.value = '';
            } else {
                alert('Could not add note.');
            }
        })
        .catch(() => alert('Network error. Please try again.'))
        .finally(() => { btn.disabled = textarea.value.trim().length === 0; });
    }

    // ---- Session goals worked on today ----
    // Checking/unchecking a goal (or adding a custom one) saves immediately -
    // there is no separate "save" step. "Done" only collapses the picker;
    // the choices themselves stay intact and reappear on the next focus.
    const ptGoalsTodayUrl = @json(route('patient.goals.today', $patient));

    function ptRevealGoalsList() {
        const expanded = document.getElementById('ptGoalExpanded');
        if (expanded) expanded.style.display = '';

        const doneBtn = document.getElementById('ptGoalsDoneBtn');
        if (doneBtn) doneBtn.style.display = '';

        const search = document.getElementById('ptGoalSearch');
        if (search) search.placeholder = 'Search goals or type a new one…';
    }

    function ptCollapseGoalsList() {
        const expanded = document.getElementById('ptGoalExpanded');
        if (expanded) expanded.style.display = 'none';

        const doneBtn = document.getElementById('ptGoalsDoneBtn');
        if (doneBtn) doneBtn.style.display = 'none';

        const search = document.getElementById('ptGoalSearch');
        if (search) search.placeholder = 'Add a goal worked on this session…';
    }

    function ptFilterGoals() {
        ptRevealGoalsList();
        const term = document.getElementById('ptGoalSearch').value.trim().toLowerCase();
        const options = document.querySelectorAll('#ptGoalRecommendedList .pt-goal-option');
        let anyVisible = false;

        options.forEach(opt => {
            const matches = !term || opt.dataset.title.includes(term);
            opt.classList.toggle('hidden-by-search', !matches);
            if (matches) anyVisible = true;
        });

        const noMatch = document.getElementById('ptGoalNoMatch');
        const addBtn = document.getElementById('ptGoalAddCustomBtn');
        if (term && !anyVisible) {
            noMatch.style.display = 'block';
            addBtn.textContent = '+ Add "' + document.getElementById('ptGoalSearch').value.trim() + '" as a custom goal';
        } else {
            noMatch.style.display = 'none';
        }
    }

    function ptToggleExistingGoal(checkboxEl) {
        ptSyncGoalsEmptyHint();
        ptAutoSaveGoals();
    }

    function ptAddCustomGoal() {
        const input = document.getElementById('ptGoalSearch');
        const title = input.value.trim();
        if (!title) return;

        const addBtn = document.getElementById('ptGoalAddCustomBtn');
        addBtn.disabled = true;

        ptAutoSaveGoals({ new_goal_titles: [title] }, (data) => {
            addBtn.disabled = false;

            (data.created_goals || []).forEach(g => {
                if (document.querySelector('#ptGoalRecommendedList .pt-goal-checkbox[value="' + g.id + '"]')) return;

                const row = document.createElement('label');
                row.className = 'pt-goal-option';
                row.dataset.title = g.title.toLowerCase();
                row.innerHTML = `<input type="checkbox" class="pt-goal-checkbox" value="${g.id}" checked onchange="ptToggleExistingGoal(this)">`
                    + `<span class="pt-goal-name" style="font-size:12.5px;">${g.title}</span>`
                    + `<span class="pt-goal-usage">custom goal — added this session</span>`;
                document.getElementById('ptGoalRecommendedList').appendChild(row);
            });

            input.value = '';
            document.getElementById('ptGoalNoMatch').style.display = 'none';
            document.querySelectorAll('#ptGoalRecommendedList .pt-goal-option').forEach(opt => opt.classList.remove('hidden-by-search'));
            ptSyncGoalsEmptyHint();
        });
    }

    function ptGoalSelectedItems() {
        return Array.from(document.querySelectorAll('#ptGoalRecommendedList .pt-goal-checkbox:checked')).map(el => ({
            id: el.value,
            title: el.closest('.pt-goal-option').querySelector('.pt-goal-name').textContent,
        }));
    }

    function ptRemoveGoalSelection(item) {
        const cb = document.querySelector('#ptGoalRecommendedList .pt-goal-checkbox[value="' + item.id + '"]');
        if (cb) {
            cb.checked = false;
            ptSyncGoalsEmptyHint();
            ptAutoSaveGoals();
        }
    }

    function ptSyncGoalsEmptyHint() {
        const items = ptGoalSelectedItems();
        const anyChecked = items.length > 0;

        const hint = document.getElementById('ptGoalsEmptyHint');
        if (hint) hint.style.display = anyChecked ? 'none' : 'block';

        const countEl = document.getElementById('ptGoalSelectedCount');
        if (countEl) {
            countEl.style.display = anyChecked ? 'inline' : 'none';
            countEl.textContent = anyChecked ? (items.length + ' selected') : '';
        }

        const chipsRow = document.getElementById('ptGoalChipsRow');
        if (chipsRow) {
            chipsRow.innerHTML = '';
            chipsRow.style.display = anyChecked ? 'flex' : 'none';
            items.forEach(item => {
                const chip = document.createElement('span');
                chip.className = 'pt-goal-chip';
                const label = document.createElement('span');
                label.textContent = item.title;
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.setAttribute('aria-label', 'Remove');
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = () => ptRemoveGoalSelection(item);
                chip.appendChild(label);
                chip.appendChild(removeBtn);
                chipsRow.appendChild(chip);
            });
        }
    }

    function ptAutoSaveGoals(extra, onSuccess) {
        if (!ptGoalsTodayUrl) return;

        const goalIds = Array.from(document.querySelectorAll('#ptGoalRecommendedList .pt-goal-checkbox:checked')).map(el => el.value);

        fetch(ptGoalsTodayUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ptCsrf(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ goal_ids: goalIds, new_goal_titles: (extra && extra.new_goal_titles) || [] })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (onSuccess) onSuccess(data);
            } else {
                alert('Could not save goals.');
            }
        })
        .catch(() => alert('Network error. Please try again.'));
    }

    // ---- Documents ----
    const ptDocStoreUrl = @json(route('patient.documents.store', $patient));

    function openDocModal() {
        document.getElementById('ptDocModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeDocModal() {
        document.getElementById('ptDocModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function ptSubmitDocument(event) {
        event.preventDefault();

        const btn = document.querySelector('#ptDocForm .pt-btn-save');
        btn.disabled = true;
        btn.textContent = 'Adding…';

        fetch(ptDocStoreUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ptCsrf(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: document.getElementById('ptDocName').value,
                type: document.getElementById('ptDocType').value,
                expires_at: document.getElementById('ptDocExpiry').value || null,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                ptPrependDocumentRow(data.document);
                closeDocModal();
                document.getElementById('ptDocForm').reset();
            } else {
                alert('Could not add document: ' + (data.errors ? Object.values(data.errors).flat().join(', ') : 'unknown error'));
            }
            btn.disabled = false;
            btn.textContent = 'Add to record';
        })
        .catch(() => {
            alert('Network error. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Add to record';
        });
    }

    function ptPrependDocumentRow(doc) {
        const list = document.getElementById('ptDocList');
        const emptyState = document.getElementById('ptDocEmpty');
        if (emptyState) emptyState.remove();

        const row = document.createElement('div');
        row.className = 'pt-doc-row';
        row.id = 'ptDocRow' + doc.id;

        const typeBadge = doc.type ? `<span class="pt-doc-badge pt-doc-badge-type">${doc.type}</span>` : '';
        row.innerHTML = `
            <div>
                <div class="pt-doc-name"></div>
                <div class="pt-doc-meta">Added by ${doc.uploader_label} · ${doc.created_at_label}</div>
            </div>
            <div class="pt-doc-actions">
                ${typeBadge}
                <span class="pt-doc-badge pt-doc-badge-${doc.expiry_variant}">${doc.expiry_label}</span>
                <button type="button" onclick="ptDeleteDocument(${doc.id}, '${doc.delete_url}')">Delete</button>
            </div>
        `;
        row.querySelector('.pt-doc-name').textContent = doc.name;

        list.insertBefore(row, list.firstChild);
    }

    function ptDeleteDocument(id, url) {
        if (!confirm('Delete this document?')) return;

        fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': ptCsrf(), 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('ptDocRow' + id);
                if (row) row.remove();

                const list = document.getElementById('ptDocList');
                if (!list.querySelector('.pt-doc-row')) {
                    const empty = document.createElement('div');
                    empty.className = 'pt-card-empty';
                    empty.id = 'ptDocEmpty';
                    empty.textContent = 'No documents on file yet.';
                    list.appendChild(empty);
                }
            } else {
                alert('Could not delete document.');
            }
        })
        .catch(() => alert('Network error. Please try again.'));
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Restore whichever tab the page was on before a reload/refresh -
        // ptShowTab() keeps the URL hash in sync with the active tab.
        const tabFromHash = location.hash ? location.hash.slice(1) : null;
        if (tabFromHash && document.getElementById('pt-tab-' + tabFromHash)) {
            ptShowTab(tabFromHash);
        }

        const editModal = document.getElementById('ptEditModal');
        if (editModal) editModal.addEventListener('click', function (e) { if (e.target === this) this.style.display = 'none'; });

        const docModal = document.getElementById('ptDocModal');
        if (docModal) docModal.addEventListener('click', function (e) { if (e.target === this) closeDocModal(); });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closePatientEditModal();
                closeDocModal();
            }
        });

        const noteTextarea = document.getElementById('ptNoteBody');
        const noteBtn = document.getElementById('ptAddNoteBtn');
        if (noteTextarea && noteBtn) {
            const syncNoteBtnState = () => { noteBtn.disabled = noteTextarea.value.trim().length === 0; };
            syncNoteBtnState();
            noteTextarea.addEventListener('input', syncNoteBtnState);
        }

        // Reflect any already-selected goals (chips, count, hint) immediately on
        // load, since they're server-rendered as checked but the picker itself
        // starts collapsed - without this the chips row stays empty until the
        // next interaction even though goals are already saved.
        if (document.getElementById('ptGoalRecommendedList')) ptSyncGoalsEmptyHint();

        // Auto-hide the goal picker when clicking anywhere outside it - it
        // should only ever open via the text box, same as a search dropdown.
        document.addEventListener('click', function (e) {
            const search = document.getElementById('ptGoalSearch');
            const expanded = document.getElementById('ptGoalExpanded');
            if (!search || !expanded) return;
            if (expanded.style.display === 'none') return;
            if (search.contains(e.target) || expanded.contains(e.target)) return;
            ptCollapseGoalsList();
        });
    });
</script>
@endpush

@endsection
