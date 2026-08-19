@extends('layouts.admin-sidebar')

@section('title', 'Patients · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-fill-height content-full-width')

@section('content')
<style>
    .pt-wrap { flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px; }

    .pt-sidebar { width: 330px; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0; }
    .pt-sidebar-head { padding: 16px 18px; border-bottom: 1px solid #EBE4DA; display: flex; flex-direction: column; gap: 12px; }
    .pt-sidebar-title { font: 600 18px 'Baloo 2'; color: #16436E; }
    .pt-sidebar-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
    .pt-search-form { position: relative; }
    .pt-search-input {
        width: 100%; box-sizing: border-box; padding: 10px 14px 10px 34px; border: 1px solid #E2DACE;
        border-radius: 10px; background: #F6F3EE; font: 600 12.5px 'Nunito Sans'; color: #2B3A4C; outline: none;
        transition: border-color 0.15s ease, background 0.15s ease;
    }
    .pt-search-input:focus { border-color: #C8355F; background: #FFFDFA; }
    .pt-search-icon {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #B0A493;
        font-size: 12.5px; pointer-events: none;
    }
    .pt-list { flex: 1; overflow-y: auto; }
    .pt-row {
        display: flex; gap: 11px; align-items: center; padding: 13px 18px; cursor: pointer;
        border-bottom: 1px solid #F3EDE3; background: transparent; text-decoration: none; transition: background-color .12s ease;
    }
    .pt-row:hover { background: #F5EFE7; }
    .pt-row.is-active { background: #F5EFE7; }
    .pt-avatar {
        width: 36px; height: 36px; border-radius: 50%; color: #fff; display: flex; align-items: center;
        justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;
    }
    .pt-row-name { font: 800 13.5px 'Nunito Sans'; color: #2B3A4C; }
    .pt-row-sub { font: 600 11.5px 'Nunito Sans'; color: #98897A; }
    .pt-chip { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0; }
    .pt-empty-list { padding: 48px 20px; text-align: center; color: #98897A; font: 700 12.5px 'Nunito Sans'; }

    .pt-detail { flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 18px; min-width: 420px; }
    .pt-detail-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: #98897A; font: 700 13px 'Nunito Sans'; }

    .pt-header { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
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
        background: #FDF6E9; border: 1px solid #EBDCC2; border-radius: 12px; padding: 14px 16px;
        display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
    }
    .pt-incomplete-title { font: 600 15px 'Baloo 2'; color: #8A5A10; }
    .pt-incomplete-sub { font: 600 12px 'Nunito Sans'; color: #8A7D6C; margin-top: 2px; }
    .pt-incomplete-btn {
        background: #B97F24; color: #fff; border: none; border-radius: 10px; padding: 12px 22px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; flex-shrink: 0; box-sizing: border-box;
        box-shadow: 0 4px 14px rgba(185,127,36,0.28); transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.05s ease;
    }
    .pt-incomplete-btn:hover { background: #9C6A1C; box-shadow: 0 4px 16px rgba(185,127,36,0.36); }
    .pt-incomplete-btn:active { transform: translateY(1px); }

    .pt-attention-banner {
        background: #FBEAE8; border: 1px solid #EFC7C2; border-radius: 12px; padding: 12px 16px;
        font: 700 12.5px 'Nunito Sans'; color: #B3261E;
    }

    .pt-chips-row { display: flex; gap: 8px; flex-wrap: wrap; }

    .pt-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; align-items: start; }
    .pt-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
    .pt-card-title { font: 600 16px 'Baloo 2'; color: #16436E; }
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
    .pt-note-input:focus { border-color: #C8355F; }
    .pt-note-actions { display: flex; align-items: center; gap: 10px; }
    .pt-note-hint { flex: 1; font: 600 11px 'Nunito Sans'; color: #A79C8E; }
    .pt-add-note-btn {
        background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 11px 22px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; box-sizing: border-box;
        box-shadow: 0 4px 14px rgba(200,53,95,0.28); transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.05s ease;
    }
    .pt-add-note-btn:hover { background: #A82348; box-shadow: 0 4px 16px rgba(200,53,95,0.36); }
    .pt-add-note-btn:active { transform: translateY(1px); }
    .pt-add-note-btn:disabled { opacity: .6; cursor: default; box-shadow: none; }

    .pt-auth-value { font: 600 28px 'Baloo 2'; color: #16436E; }
    .pt-auth-value span { font: 600 14px 'Baloo 2'; color: #98897A; }
    .pt-auth-meta { font: 600 12px 'Nunito Sans'; color: #98897A; }

    .pt-team-entry { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; background: #F6F3EE; border-radius: 8px; padding: 8px 12px; }

    .pt-upcoming-row { display: flex; gap: 10px; align-items: center; }
    .pt-upcoming-day { width: 78px; font: 600 12.5px 'Baloo 2'; color: #16436E; flex-shrink: 0; }

    /* ===== Edit modal (reuses the app's shared modal look) ===== */
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
    .pt-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
    .pt-field-input {
        width: 100%; padding: 9px 12px; border: 1px solid #E2DACE; border-radius: 8px;
        background: #F6F3EE; font: 700 13.5px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;
    }
    .pt-field-input:focus { border-color: #C8355F; }
    .pt-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pt-modal-actions { display: flex; gap: 10px; border-top: 1px solid #F3EDE3; padding-top: 14px; }
    .pt-btn-save {
        flex: 1; background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 13px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer; box-sizing: border-box;
        box-shadow: 0 4px 14px rgba(200,53,95,0.28); transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.05s ease;
    }
    .pt-btn-save:hover { background: #A82348; box-shadow: 0 4px 16px rgba(200,53,95,0.36); }
    .pt-btn-save:active { transform: translateY(1px); }
    .pt-btn-cancel {
        background: #fff; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 13px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer; box-sizing: border-box; transition: background 0.15s ease, transform 0.05s ease;
    }
    .pt-btn-cancel:hover { background: #F6F3EE; }
    .pt-btn-cancel:active { transform: translateY(1px); }

    @media (max-width: 1000px) {
        .pt-wrap { flex-direction: column; overflow-x: hidden; }
        .pt-sidebar { width: 100%; max-height: 240px; border-right: none; border-bottom: 1px solid #EBE4DA; }
        .pt-detail { min-width: 0; }
    }

    @media (max-width: 900px) {
        .pt-grid { grid-template-columns: 1fr; }
    }
</style>

@php
    $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];
@endphp

<div class="pt-wrap">

    <!-- Left Sidebar - Patient List -->
    <div class="pt-sidebar">
        <div class="pt-sidebar-head">
            <div>
                <div class="pt-sidebar-title">Patients</div>
                <div class="pt-sidebar-sub">{{ $patients->count() }} {{ Str::plural('patient', $patients->count()) }}</div>
            </div>
            <form class="pt-search-form" method="GET" action="{{ route('patient.index') }}">
                <span class="pt-search-icon">&#128269;</span>
                <input
                    type="text" name="search" class="pt-search-input"
                    placeholder="Search patients, parents, phone…"
                    value="{{ request('search') }}"
                >
            </form>
        </div>
        <div class="pt-list">
            @forelse ($patients as $patient)
                @php $isActive = $activePatient && $activePatient->id === $patient->id; @endphp
                <a href="{{ route('patient.index', ['patient' => $patient->id] + (request('search') ? ['search' => request('search')] : [])) }}" class="pt-row {{ $isActive ? 'is-active' : '' }}">
                    <div class="pt-avatar" style="background: {{ $avatarColor($patient->lead_id) }};">
                        {{ strtoupper(substr($patient->lead->child_name ?? '?', 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="pt-row-name">{{ $patient->lead->child_name ?? 'Unnamed' }}</div>
                        <div class="pt-row-sub">Age {{ $patient->lead->child_age ?? '—' }}{{ $patient->diagnosis ? ' · '.$patient->diagnosis : '' }}</div>
                    </div>
                    @if ($patient->isProfileIncomplete())
                        <span class="pt-chip" style="background: #FDF6E9; color: #8A5A10;">Needs details</span>
                    @elseif ($patient->programme)
                        <span class="pt-chip" style="background: #F9E7EC; color: #C8355F;">{{ Str::limit($patient->programme, 14) }}</span>
                    @endif
                </a>
            @empty
                <div class="pt-empty-list">No patients yet. Convert an enrolled lead from the Leads pipeline to get started.</div>
            @endforelse
        </div>
    </div>

    <!-- Right Side - Patient Detail -->
    <div class="pt-detail">
        @if ($activePatient)
            @php $lead = $activePatient->lead; @endphp
            <div class="pt-header">
                <div class="pt-header-avatar" style="background: {{ $avatarColor($activePatient->lead_id) }};">
                    {{ strtoupper(substr($lead->child_name ?? '?', 0, 1)) }}
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div class="pt-header-name">{{ $lead->child_name ?? 'Unnamed' }}</div>
                    <div class="pt-header-sub">
                        Age {{ $lead->child_age ?? '—' }}{{ $activePatient->diagnosis ? ' · '.$activePatient->diagnosis : '' }}
                        · enrolled {{ $activePatient->enrolled_at->format('M Y') }}
                    </div>
                </div>
                <div>
                    <div class="pt-header-parent">{{ $lead->parent_guardian_name ?? '—' }}</div>
                    <div class="pt-header-parent-phone">{{ $lead->phone ?? 'No phone on file' }}</div>
                </div>
                <button type="button" class="pt-edit-btn" onclick="openPatientEditModal()">Edit details</button>
            </div>

            @if ($activePatient->authorization_renews_at && $activePatient->authorization_renews_at->between(now(), now()->addDays(45)))
                <div class="pt-attention-banner">
                    Attention — authorization renews {{ $activePatient->authorization_renews_at->format('d M Y') }}
                </div>
            @endif

            @if ($activePatient->isProfileIncomplete())
                <div class="pt-incomplete-banner">
                    <div style="flex: 1; min-width: 200px;">
                        <div class="pt-incomplete-title">New client — profile incomplete</div>
                        <div class="pt-incomplete-sub">Missing: {{ $activePatient->missingFieldsLabel() }}</div>
                    </div>
                    <button type="button" class="pt-incomplete-btn" onclick="openPatientEditModal()">Add client details</button>
                </div>
            @endif

            @if ($activePatient->programme || $activePatient->insurance_provider)
                <div class="pt-chips-row">
                    @if ($activePatient->programme)
                        <span class="pt-chip" style="background: #F9E7EC; color: #C8355F; padding: 5px 12px; font-size: 12px;">{{ $activePatient->programme }}</span>
                    @endif
                    @if ($activePatient->insurance_provider)
                        <span class="pt-chip" style="background: #E7EFF7; color: #24619C; padding: 5px 12px; font-size: 12px;">{{ $activePatient->insurance_provider }}</span>
                    @endif
                </div>
            @endif

            <div class="pt-grid">
                <!-- Left column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div class="pt-card">
                        <div class="pt-card-title">Therapy goals — current plan</div>
                        @forelse ($activePatient->goals as $goal)
                            <div class="pt-goal-row">
                                <div class="pt-goal-top">
                                    <div class="pt-goal-name">{{ $goal->title }}</div>
                                    <div class="pt-goal-pct">{{ $goal->progress_percent }}%</div>
                                </div>
                                <div class="pt-progress-track"><div class="pt-progress-fill" style="width: {{ $goal->progress_percent }}%;"></div></div>
                            </div>
                        @empty
                            <div class="pt-card-empty">No goals set yet — add them after the first programme review.</div>
                        @endforelse
                    </div>

                    <div class="pt-card">
                        <div class="pt-card-title">Session notes</div>
                        <div class="pt-notes-list" id="ptNotesList">
                            @forelse ($activePatient->notes as $note)
                                <div class="pt-note-entry">
                                    <div class="pt-note-author-row">
                                        <div class="pt-note-author">{{ $note->author_name }}</div>
                                        <div class="pt-note-when">{{ $note->created_at->diffForHumans() }}</div>
                                    </div>
                                    <div class="pt-note-body">{{ $note->body }}</div>
                                </div>
                            @empty
                                <div class="pt-card-empty" id="ptNotesEmpty">No notes added yet.</div>
                            @endforelse
                        </div>
                        <textarea id="ptNoteBody" class="pt-note-input" rows="3" placeholder="Write a session note — what was worked on, response, next step…"></textarea>
                        <div class="pt-note-actions">
                            <div class="pt-note-hint">Saved to the clinical record with your name and time.</div>
                            <button type="button" class="pt-add-note-btn" id="ptAddNoteBtn" onclick="addPatientNote()">Add note</button>
                        </div>
                    </div>
                </div>

                <!-- Right column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div class="pt-card">
                        <div class="pt-card-title">Insurance authorization</div>
                        @if ($activePatient->authorized_sessions_total)
                            @php
                                $hoursUsed = $activePatient->hoursUsed();
                                $pct = min(100, round(($hoursUsed / max(1, $activePatient->authorized_sessions_total)) * 100));
                                $remaining = max(0, $activePatient->authorized_sessions_total - $hoursUsed);
                            @endphp
                            <div class="pt-auth-value">
                                <span style="{{ $remaining <= 5 ? 'color: #B3261E;' : '' }}">{{ $remaining }}</span>
                                <span>hours left of {{ $activePatient->authorized_sessions_total }}</span>
                            </div>
                            <div class="pt-progress-track"><div class="pt-progress-fill" style="width: {{ $pct }}%; background: {{ $remaining <= 5 ? '#B3261E' : '#B97F24' }};"></div></div>
                            <div class="pt-auth-meta">
                                {{ $activePatient->insurance_provider ?? 'Insurance' }}
                                @if ($activePatient->authorization_renews_at) · renews {{ $activePatient->authorization_renews_at->format('d M Y') }} @endif
                            </div>
                        @else
                            <div class="pt-card-empty">No authorization on file — add insurance details to track hours.</div>
                        @endif
                    </div>

                    <div class="pt-card">
                        <div class="pt-card-title">Care team</div>
                        @forelse ($activePatient->careTeam() as $therapist)
                            <div class="pt-team-entry" style="display: flex; align-items: center; gap: 9px;">
                                <div class="pt-avatar" style="width: 26px; height: 26px; font-size: 10.5px; background: {{ $avatarColor($therapist->id) }};">
                                    {{ strtoupper(substr($therapist->first_name, 0, 1)) }}
                                </div>
                                {{ trim($therapist->first_name.' '.$therapist->last_name) }}
                            </div>
                        @empty
                            <div class="pt-card-empty">No therapists assigned yet.</div>
                        @endforelse
                    </div>

                    <div class="pt-card">
                        <div class="pt-card-title">Upcoming sessions</div>
                        @php $activityColors = ['ABA' => ['#F9E7EC', '#C8355F'], 'Speech' => ['#E7EFF7', '#24619C']]; @endphp
                        @forelse ($upcomingSessions as $session)
                            @php [$bg, $fg] = $activityColors[$session->activity_type] ?? ['#F3EDE3', '#5A6B7E']; @endphp
                            <div class="pt-upcoming-row">
                                <div class="pt-upcoming-day">{{ $session->session_date->format('D j M') }} {{ substr($session->start_time, 0, 5) }}</div>
                                <span class="pt-chip" style="background: {{ $bg }}; color: {{ $fg }};">{{ $session->activity_type }}</span>
                            </div>
                        @empty
                            <div class="pt-card-empty">No sessions booked yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div class="pt-detail-empty">Select a patient to see their details.</div>
        @endif
    </div>
</div>

@if ($activePatient)
    <!-- Edit Patient Details Modal -->
    <div id="ptEditModal" class="pt-modal-overlay">
        <div class="pt-modal-box">
            <div class="pt-modal-header">
                <div>
                    <div class="pt-modal-title">Client details</div>
                    <div class="pt-modal-subtitle">Complete the clinical and insurance record</div>
                </div>
                <button type="button" class="pt-modal-close" onclick="closePatientEditModal()">✕</button>
            </div>

            <form id="ptEditForm" onsubmit="savePatientDetails(event)">
                @csrf
                @method('PUT')

                <div class="pt-grid-2col">
                    <div>
                        <div class="pt-field-label">Child's name *</div>
                        <input id="ptChildName" name="child_name" type="text" placeholder="e.g. Khalifa Al Mansoori" class="pt-field-input" value="{{ $lead->child_name }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Age</div>
                        <input id="ptChildAge" name="child_age" type="number" min="0" max="25" placeholder="6" class="pt-field-input" value="{{ $lead->child_age }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Diagnosis *</div>
                        <input id="ptDiagnosis" name="diagnosis" type="text" placeholder="e.g. ASD Level 2" class="pt-field-input" value="{{ $activePatient->diagnosis }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Programme *</div>
                        <input id="ptProgramme" name="programme" type="text" placeholder="e.g. ABA 20h/wk + Speech 2h" class="pt-field-input" value="{{ $activePatient->programme }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Parent / guardian</div>
                        <input id="ptParentName" name="parent_guardian_name" type="text" placeholder="e.g. Mr. Saif Al Mansoori" class="pt-field-input" value="{{ $lead->parent_guardian_name }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Phone *</div>
                        <input id="ptPhone" name="phone" type="text" placeholder="+971 5x xxx xxxx" class="pt-field-input" value="{{ $lead->phone ?? '' }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Insurance *</div>
                        <input id="ptInsurance" name="insurance_provider" type="text" placeholder="e.g. Daman Enhanced" class="pt-field-input" value="{{ $activePatient->insurance_provider }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Auth. hrs *</div>
                        <input id="ptAuthTotal" name="authorized_sessions_total" type="number" min="0" placeholder="96" class="pt-field-input" value="{{ $activePatient->authorized_sessions_total }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Renewal</div>
                        <input id="ptAuthRenews" name="authorization_renews_at" type="date" class="pt-field-input" value="{{ optional($activePatient->authorization_renews_at)->format('Y-m-d') }}">
                    </div>
                    <div>
                        <div class="pt-field-label">Start</div>
                        <input id="ptEnrolledAt" name="enrolled_at" type="date" class="pt-field-input" value="{{ optional($activePatient->enrolled_at)->format('Y-m-d') }}">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <div class="pt-field-label">Clinical note</div>
                        <textarea id="ptClinicalNote" name="clinical_note" rows="2" placeholder="Optional — adds a timestamped session note" class="pt-field-input" style="resize: vertical; font-family: 'Nunito Sans';"></textarea>
                    </div>
                </div>

                <div class="pt-modal-actions">
                    <button type="submit" class="pt-btn-save">Save client</button>
                    <button type="button" class="pt-btn-cancel" onclick="closePatientEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    const ptPatientId = {{ $activePatient->id ?? 'null' }};
    const ptUpdateUrl = @json($activePatient ? route('patient.update', $activePatient->id) : null);
    const ptNotesUrl = @json($activePatient ? route('patient.notes.store', $activePatient->id) : null);

    function ptCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;
    }

    function openPatientEditModal() {
        const modal = document.getElementById('ptEditModal');
        if (modal) { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }

    function closePatientEditModal() {
        const modal = document.getElementById('ptEditModal');
        if (modal) { modal.style.display = 'none'; document.body.style.overflow = ''; }
    }

    function savePatientDetails(event) {
        event.preventDefault();
        if (!ptUpdateUrl) return;

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

    function addPatientNote() {
        if (!ptNotesUrl) return;

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
                const empty = document.getElementById('ptNotesEmpty');
                if (empty) empty.remove();

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
        .finally(() => { btn.disabled = false; });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('ptEditModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closePatientEditModal();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closePatientEditModal();
        });
    });
</script>
@endpush
