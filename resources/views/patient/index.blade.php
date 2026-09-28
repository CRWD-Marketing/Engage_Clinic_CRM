@extends('layouts.admin-sidebar')

@section('title', 'Patients · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')

<style>
    .main-content-inner.pt-tight-padding { padding-left: 24px; padding-right: 24px; }
    .pt-page { background: transparent; }

    .pt-header-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 12px; }
    .pt-header-title { font: 600 26px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .pt-header-sub { font: 600 13px 'Nunito Sans'; color: #98897A; margin-top: 4px; }

    .pt-add-btn {
        background: #C8355F; color: #fff; font: 800 13px 'Nunito Sans'; border-radius: 8px;
        padding: 10px 18px; display: inline-flex; align-items: center; gap: 8px;
        box-shadow: 0 4px 14px rgba(200,53,95,0.28); border: none; cursor: pointer;
    }
    .pt-add-btn:hover { background: #A82348; }

    .pt-filter-select {
        border: 1px solid #E2DACE; border-radius: 8px; background: #fff; color: #16436E;
        font: 800 12.5px 'Nunito Sans'; padding: 9px 32px 9px 14px; cursor: pointer; outline: none;
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6'><path d='M0 0l5 6 5-6z' fill='%2316436E'/></svg>");
        background-repeat: no-repeat; background-position: right 12px center;
    }
    .pt-filter-select:hover { border-color: #C8355F; }

    .pt-table-card { border: 1px solid #EBE4DA; border-radius: 16px; background: #fff; overflow: hidden; box-shadow: 0 2px 10px rgba(22,42,60,0.04); overflow-x: auto; }
    .pt-table { width: 100%; border-collapse: collapse; min-width: 900px; }
    .pt-table th {
        text-align: left; font: 800 11px 'Nunito Sans'; text-transform: uppercase; letter-spacing: 0.06em;
        color: #98897A; padding: 13px 20px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; white-space: nowrap;
    }
    .pt-table td { padding: 15px 20px; border-bottom: 1px solid #F3EDE3; font: 600 13px 'Nunito Sans'; color: #2B3A4C; vertical-align: middle; }
    .pt-table tr:last-child td { border-bottom: none; }
    .pt-table-row { cursor: pointer; text-decoration: none; color: inherit; display: table-row; }
    .pt-table-row:hover td { background: #FFFDFA; }

    .pt-name-cell { display: flex; align-items: center; gap: 11px; }
    .pt-avatar { width: 36px; height: 36px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0; }
    .pt-name { font: 800 13.5px 'Nunito Sans'; color: #2B3A4C; }
    .pt-name-sub { font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-top: 1px; }

    .pt-parent-name { font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; }
    .pt-parent-phone { font: 600 11.5px 'Nunito Sans'; color: #98897A; }

    .pt-badge { border-radius: 999px; padding: 4px 11px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
    .pt-badge.active { background: #E4F6EB; color: #1E8A4C; }
    .pt-badge.needs-details { background: #FDF6E9; color: #8A5A10; }

    .pt-empty { padding: 56px 20px; text-align: center; color: #98897A; font: 700 12.5px 'Nunito Sans'; }

    /* ── Phone: grouped card list ─────────────────────────────────────────
       The table needs 900px before it stops cramping, so below that it is
       replaced by a card per patient, under a heading per status. Everything
       stays on screen - this list is short and is read straight through, so
       there is nothing here worth putting behind a tap. The cards are their
       own list rather than the table restyled, so the desktop table keeps its
       own ordering (newest first) instead of being re-sorted by status. */
    .pt-groups { display: none; flex-direction: column; gap: 8px; }
    .pt-group { border: 1px solid #EBE4DA; border-radius: 14px; background: #fff; overflow: hidden; box-shadow: 0 2px 10px rgba(22,42,60,0.04); }
    .pt-group-head { display: flex; align-items: center; gap: 9px; padding: 10px 12px; }
    .pt-group-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .pt-group-title { flex: 1; font: 800 13.5px 'Nunito Sans'; color: #2B3A4C; }
    .pt-group-count { background: #F6F3EE; color: #5A6B7E; border-radius: 999px; padding: 2px 9px; font: 800 11.5px 'Nunito Sans'; }
    .pt-group-body { display: flex; flex-direction: column; }

    /* Built like the WhatsApp inbox rows: a large round avatar, the name and
       its badge on the first line, one grey line under it, and the running
       figure out on the right. Two lines per patient, no dividers - the rest
       of the record is a tap away. */
    .pt-mcard {
        display: flex; align-items: center; gap: 11px;
        padding: 9px 14px; text-decoration: none; color: inherit;
    }
    .pt-mcard .pt-avatar { width: 52px; height: 52px; font-size: 18px; }
    .pt-mcard-ident { flex: 1 1 0; min-width: 0; }
    .pt-mcard-top, .pt-mcard-bottom { display: flex; align-items: center; gap: 8px; }
    .pt-mcard-bottom { margin-top: 1px; }
    /* Everything truncates rather than wraps, so a long name or diagnosis can
       never push a row to double height. */
    .pt-mcard .pt-name, .pt-mcard-line { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pt-mcard .pt-name { font-size: 15px; }
    .pt-mcard .pt-badge { flex-shrink: 0; padding: 2px 8px; font-size: 10px; }
    .pt-mcard-line { font: 600 13px 'Nunito Sans'; color: #98897A; }
    .pt-mcard-meta { flex-shrink: 0; font: 700 11.5px 'Nunito Sans'; color: #B0A493; }

    @media (max-width: 860px) {
        .main-content-inner.pt-tight-padding { padding-left: 12px; padding-right: 12px; }
        .pt-header-bar { margin-bottom: 16px; gap: 10px; }
        .pt-header-title { font-size: 21px; }
        .pt-header-sub { font-size: 12px; }
        .pt-table-card-table { display: none; }
        .pt-groups { display: flex; }
    }

    .pt-success-alert {
        background: #E4F6EB; border: 1px solid #BFE9CE; color: #1E8A4C; border-radius: 10px;
        padding: 12px 16px; font: 700 13px 'Nunito Sans'; margin-bottom: 18px;
    }

    /* Add Patient modal */
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
</style>

@php
    $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];
@endphp

<div class="pt-page">

    <div class="pt-header-bar">
        <div>
            <div class="pt-header-title">Patients</div>
            <div class="pt-header-sub">{{ $patients->count() }} active · {{ $patients->count() }} shown</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <select class="pt-filter-select" id="ptPeriodFilter" onchange="ptFilterByPeriod(this.value)">
                <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All time</option>
                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This week</option>
                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This month</option>
                <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>Last month</option>
                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>This year</option>
            </select>
            @if (auth()->user()->role !== 'COORDINATOR' && auth()->user()->role !== 'THERAPIST')
                <button type="button" class="pt-add-btn" onclick="openPatientAddModal()">+ Add patient</button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="pt-success-alert">{{ session('success') }}</div>
    @endif

    <div class="pt-table-card {{ $patients->isEmpty() ? '' : 'pt-table-card-table' }}">
        @if ($patients->isEmpty())
            <div class="pt-empty">No patients yet. Convert an enrolled lead from the Leads pipeline to get started.</div>
        @else
            <table class="pt-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Parent · Phone</th>
                        <th>Programme</th>
                        <th>Insurance</th>
                        <th>Authorization</th>
                        <th>Attendance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($patients as $patient)
                        @php
                            $lead = $patient->lead;
                            $primaryAuth = $patient->primaryAuthorization();
                            $attendanceRate = $patient->attendanceRate();
                            $nameParts = preg_split('/\s+/', trim($lead->child_name ?? ''));
                            $initials = strtoupper(($nameParts[0][0] ?? '?').($nameParts[1][0] ?? ''));
                        @endphp
                        <tr onclick="window.location='{{ route('patient.show', $patient) }}'" class="pt-table-row">
                            <td>
                                <div class="pt-name-cell">
                                    <div class="pt-avatar" style="background: {{ $avatarColor($patient->lead_id) }};">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <div class="pt-name">{{ $lead->child_name ?? 'Unnamed' }}</div>
                                        <div class="pt-name-sub">Age {{ $lead->child_age ?? '—' }}{{ $patient->diagnosis ? ' · '.$patient->diagnosis : '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="pt-parent-name">{{ $lead->parent_guardian_name ?? '—' }}</div>
                                <div class="pt-parent-phone">{{ $lead->phone ?? '—' }}</div>
                            </td>
                            <td>{{ $patient->programme ?: '—' }}</td>
                            <td>{{ $primaryAuth->payer_name ?? '—' }}</td>
                            <td>
                                @if ($primaryAuth && $primaryAuth->authorized_hours_total)
                                    @php $hoursLeft = max(0, $primaryAuth->authorized_hours_total - $primaryAuth->hoursUsed()); @endphp
                                    <strong>{{ $hoursLeft }} / {{ $primaryAuth->authorized_hours_total }}</strong> h left
                                @else
                                    <span style="color:#98897A;">No auth</span>
                                @endif
                            </td>
                            <td>{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</td>
                            <td>
                                @if ($patient->isProfileIncomplete())
                                    <span class="pt-badge needs-details">Needs details</span>
                                @else
                                    <span class="pt-badge active">Active</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($patients->isNotEmpty())
        @php
            // Two states is all this list has, and the incomplete records are
            // what someone usually opens this page to deal with, so they lead.
            $ptGroups = [
                ['label' => 'Needs details', 'dot' => '#C8355F', 'items' => $patients->filter(fn ($p) => $p->isProfileIncomplete())->values()],
                ['label' => 'Active', 'dot' => '#1E8A4C', 'items' => $patients->reject(fn ($p) => $p->isProfileIncomplete())->values()],
            ];
        @endphp
        <div class="pt-groups">
            @foreach ($ptGroups as $group)
                @continue ($group['items']->isEmpty())
                <div class="pt-group">
                    <div class="pt-group-head">
                        <span class="pt-group-dot" style="background: {{ $group['dot'] }};"></span>
                        <span class="pt-group-title">{{ $group['label'] }}</span>
                        <span class="pt-group-count">{{ $group['items']->count() }}</span>
                    </div>
                    <div class="pt-group-body">
                        @foreach ($group['items'] as $patient)
                            @php
                                $lead = $patient->lead;
                                $primaryAuth = $patient->primaryAuthorization();
                                $attendanceRate = $patient->attendanceRate();
                                $nameParts = preg_split('/\s+/', trim($lead->child_name ?? ''));
                                $initials = strtoupper(($nameParts[0][0] ?? '?').($nameParts[1][0] ?? ''));
                                $hoursLeft = ($primaryAuth && $primaryAuth->authorized_hours_total)
                                    ? max(0, $primaryAuth->authorized_hours_total - $primaryAuth->hoursUsed())
                                    : null;
                            @endphp
                            <a href="{{ route('patient.show', $patient) }}" class="pt-mcard">
                                <div class="pt-avatar" style="background: {{ $avatarColor($patient->lead_id) }};">{{ $initials }}</div>
                                <div class="pt-mcard-ident">
                                    <div class="pt-mcard-top">
                                        <div class="pt-name">{{ $lead->child_name ?? 'Unnamed' }}{{ $lead->child_age ? ' · '.$lead->child_age : '' }}</div>
                                        @if ($patient->isProfileIncomplete())
                                            <span class="pt-badge needs-details">Needs details</span>
                                        @else
                                            <span class="pt-badge active">Active</span>
                                        @endif
                                    </div>
                                    <div class="pt-mcard-bottom">
                                        <div class="pt-mcard-line">{{ $lead->parent_guardian_name ?? 'No parent on file' }} · {{ $lead->phone ?? 'no phone' }}</div>
                                        <div class="pt-mcard-meta">
                                            @if ($hoursLeft !== null)
                                                {{ $hoursLeft }}/{{ $primaryAuth->authorized_hours_total }}h
                                            @else
                                                No auth
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<!-- Add Patient Modal -->
<div id="ptAddModal" class="pt-modal-overlay">
    <div class="pt-modal-box">
        <div class="pt-modal-header">
            <div>
                <div class="pt-modal-title">Client details</div>
                <div class="pt-modal-subtitle">Enrollment + optional insurance record</div>
            </div>
            <button type="button" class="pt-modal-close" onclick="closePatientAddModal()">✕</button>
        </div>

        <form id="ptAddForm" onsubmit="savePatientAdd(event)">
            @csrf

            <div class="pt-grid-2col">
                <div>
                    <div class="pt-field-label">Child's name *</div>
                    <input id="ptNewChildName" name="child_name" type="text" placeholder="e.g. Khalifa Al Mansoori" class="pt-field-input">
                </div>
                <div>
                    <div class="pt-field-label">Age</div>
                    <input id="ptNewChildAge" name="child_age" type="number" min="0" max="25" placeholder="6" class="pt-field-input">
                </div>
                <div>
                    <div class="pt-field-label">Diagnosis *</div>
                    <input id="ptNewDiagnosis" name="diagnosis" type="text" placeholder="e.g. ASD Level 2" class="pt-field-input">
                </div>
                <div>
                    <div class="pt-field-label">Programme *</div>
                    <select id="ptNewProgramme" name="programme" class="pt-field-input">
                        <option value="">Select a package…</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->name }}">{{ $package->name }} — {{ rtrim(rtrim(number_format($package->hours_per_week, 1), '0'), '.') }}h/wk @ AED {{ number_format($package->rate, 0) }}/hr</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="pt-field-label">Parent / guardian</div>
                    <input id="ptNewParentName" name="parent_guardian_name" type="text" placeholder="e.g. Mr. Saif Al Mansoori" class="pt-field-input">
                </div>
                <div>
                    <div class="pt-field-label">Phone *</div>
                    <input id="ptNewPhone" name="phone" type="text" placeholder="+971 5x xxx xxxx" class="pt-field-input">
                </div>
                <div>
                    <div class="pt-field-label">Insurance payer</div>
                    <select id="ptNewInsurance" name="payer_name" class="pt-field-input">
                        <option value="">No insurance (optional)</option>
                        @foreach ($insurances as $insurance)
                            <option value="{{ $insurance->name }}">{{ $insurance->name }}</option>
                        @endforeach
                        <option value="Self-pay">Self-pay</option>
                    </select>
                </div>
                <div>
                    <div class="pt-field-label">Auth. hrs</div>
                    <input id="ptNewAuthTotal" name="authorized_hours_total" type="number" min="0" placeholder="96" class="pt-field-input">
                </div>
                <div>
                    <div class="pt-field-label">Renewal</div>
                    <input id="ptNewAuthRenews" name="authorization_renews_at" type="date" class="pt-field-input">
                </div>
                <div>
                    <div class="pt-field-label">Start</div>
                    <input id="ptNewEnrolledAt" name="enrolled_at" type="date" class="pt-field-input">
                </div>
                <div style="grid-column: 1 / -1;">
                    <div class="pt-field-label">Clinical note</div>
                    <textarea id="ptNewClinicalNote" name="clinical_note" rows="2" placeholder="Optional — adds a timestamped session note" class="pt-field-input" style="resize: vertical; font-family: 'Nunito Sans';"></textarea>
                </div>
            </div>

            <div id="ptAddFormError" style="display:none; color:#B3261E; font: 700 12px 'Nunito Sans'; margin-top: 4px;"></div>

            <div class="pt-modal-actions">
                <button type="submit" class="pt-btn-save">Save client</button>
                <button type="button" class="pt-btn-cancel" onclick="closePatientAddModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function ptFilterByPeriod(period) {
        const url = new URL(window.location.href);
        if (period === 'all') url.searchParams.delete('period');
        else url.searchParams.set('period', period);
        window.location.href = url.toString();
    }

    function ptCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;
    }

    const ptStoreUrl = @json(route('patient.store'));

    function openPatientAddModal() {
        const modal = document.getElementById('ptAddModal');
        if (modal) { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }

    function closePatientAddModal() {
        const modal = document.getElementById('ptAddModal');
        if (modal) { modal.style.display = 'none'; document.body.style.overflow = ''; }
        const form = document.getElementById('ptAddForm');
        if (form) form.reset();
        const errorBox = document.getElementById('ptAddFormError');
        if (errorBox) errorBox.style.display = 'none';
    }

    function savePatientAdd(event) {
        event.preventDefault();

        const form = document.getElementById('ptAddForm');
        const formData = new FormData(form);
        const errorBox = document.getElementById('ptAddFormError');
        errorBox.style.display = 'none';

        const btn = form.querySelector('.pt-btn-save');
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Saving…';

        fetch(ptStoreUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ptCsrf(), 'Accept': 'application/json' },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                errorBox.textContent = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Could not save.');
                errorBox.style.display = 'block';
                btn.disabled = false;
                btn.textContent = originalText;
            }
        })
        .catch(() => {
            errorBox.textContent = 'Network error. Please try again.';
            errorBox.style.display = 'block';
            btn.disabled = false;
            btn.textContent = originalText;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const addModal = document.getElementById('ptAddModal');
        if (addModal) {
            addModal.addEventListener('click', function (e) {
                if (e.target === this) closePatientAddModal();
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closePatientAddModal();
        });
    });
</script>
@endpush

@endsection
