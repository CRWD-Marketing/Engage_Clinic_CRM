@extends('layouts.admin-sidebar')

@section('title', 'Therapists · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')
    <style>
        .main-content-inner:has(.tp-wrap) { max-width: none; flex: 1 1 auto; min-height: 0; }
        .tp-wrap { flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px; }
        .tp-topbar {
            display: flex; align-items: center; gap: 16px; padding: 16px 28px;
            border-bottom: 1px solid #EBE4DA; background: #FFFDFA; flex-shrink: 0; flex-wrap: wrap;
        }
        .tp-title { font: 600 21px/1.2 'Baloo 2'; color: #16436E; }
        .tp-subtitle { font: 600 12.5px 'Nunito Sans'; color: #98897A; }
        .tp-add-btn, .tp-cal-link {
            background: #C8355F; color: #fff; border: none; border-radius: 10px; text-decoration: none;
            padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 6px; transition: background-color .15s ease;
        }
        .tp-add-btn:hover, .tp-cal-link:hover { background: #A82348; }

        .tp-week-nav { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .tp-week-btn {
            width: 34px; height: 34px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF;
            color: #16436E; display: flex; align-items: center; justify-content: center; font: 700 16px 'Nunito Sans';
            text-decoration: none; cursor: pointer; transition: background-color .15s ease;
        }
        .tp-week-btn:hover { background: #F5EFE7; }
        .tp-week-today {
            background: #FFFFFF; color: #16436E; border: 1px solid #E2DACE; border-radius: 9px; padding: 0 14px; height: 34px;
            display: inline-flex; align-items: center; font: 800 12.5px 'Nunito Sans'; text-decoration: none; cursor: pointer;
            transition: background-color .15s ease; white-space: nowrap;
        }
        .tp-week-today:hover { background: #F5EFE7; }
        .tp-week-date {
            height: 34px; padding: 0 10px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFFFF;
            font: 700 12.5px 'Nunito Sans'; color: #16436E; outline: none; cursor: pointer;
        }

        .tp-body { flex: 1; display: flex; min-height: 0; overflow: hidden; }

        .tp-sidebar {
            width: 280px; min-width: 200px; border-right: 1px solid #EBE4DA; background: #FFFDFA;
            display: flex; flex-direction: column; flex-shrink: 0; overflow-y: auto;
        }
        .tp-sidebar-head { padding: 16px 18px; border-bottom: 1px solid #EBE4DA; flex-shrink: 0; }
        .tp-sidebar-title { font: 600 16px 'Baloo 2'; color: #16436E; }
        .tp-sidebar-sub { font: 600 11px 'Nunito Sans'; color: #98897A; }
        .tp-therapist-row {
            display: flex; gap: 11px; align-items: center; padding: 12px 18px; text-decoration: none;
            border-bottom: 1px solid #F3EDE3; background: transparent; transition: background-color .15s ease;
        }
        .tp-therapist-row:hover { background: #F9F6F1; }
        .tp-therapist-row.is-active { background: #FBEFF3; }
        .tp-avatar {
            width: 36px; height: 36px; border-radius: 50%; color: #fff; display: flex; align-items: center;
            justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;
        }
        .tp-therapist-name {
            font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .tp-therapist-sub { font: 600 11px 'Nunito Sans'; color: #98897A; }
        .tp-count-badge {
            background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 4px 10px;
            font: 800 11px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0;
        }

        .tp-grid { flex: 1; background: #FFFDFA; min-width: 0; overflow-y: auto; }
        .tp-grid-row { overflow-x: auto; overflow-y: hidden; padding: 20px 24px; display: flex; gap: 14px; align-items: flex-start; }
        .tp-grid-row::-webkit-scrollbar { height: 7px; }
        .tp-grid-row::-webkit-scrollbar-track { background: transparent; }
        .tp-grid-row::-webkit-scrollbar-thumb { background: #D8CDBC; border-radius: 999px; }

        .tp-day-col { width: 210px; min-width: 160px; flex-shrink: 0; }
        .tp-day-head {
            background: #F0EBE5; border-radius: 10px; padding: 8px 12px; margin-bottom: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .tp-day-name { font: 600 13.5px 'Baloo 2'; color: #16436E; flex: 1; }
        .tp-day-count { font: 800 11.5px 'Nunito Sans'; color: #8A7D6C; }
        .tp-day-body { display: flex; flex-direction: column; gap: 8px; }
        .tp-empty-day { text-align: center; color: #B0A493; font: 600 11.5px 'Nunito Sans'; padding: 16px 4px; }

        .tp-session-card { border-radius: 11px; padding: 10px 12px; display: flex; flex-direction: column; gap: 4px; }
        .tp-session-card.is-closed { background: #F0EDE7 !important; }
        .tp-session-time { font: 600 13px 'Baloo 2'; }
        .tp-session-duration { font: 600 10.5px 'Nunito Sans'; color: #98897A; }
        .tp-session-patient { font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tp-session-card.is-closed .tp-session-patient { text-decoration: line-through; color: #98897A; }
        .tp-session-therapist { font: 700 10.5px 'Nunito Sans'; color: #8A7D6C; }
        .tp-session-tags { display: flex; gap: 6px; flex-wrap: wrap; }
        .tp-session-tag { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; display: inline-block; width: fit-content; }
        .tp-session-card.is-closed .tp-session-tag { text-decoration: line-through; background: #E4DFD6 !important; color: #98897A !important; }
        .tp-closed-badge { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; background: #F6DEDE; color: #B4463F; }
        .tp-session-room { font: 600 11px 'Nunito Sans'; color: #98897A; }
        .tp-card-actions { display: flex; gap: 6px; margin-top: 2px; }
        .tp-card-btn {
            flex: 1; background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px;
            padding: 5px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer; transition: opacity .15s ease;
        }
        .tp-card-btn.tp-close-btn { color: #C8355F; }
        .tp-card-btn.tp-close-btn.is-reopen { color: #2E7D5B; }
        .tp-card-btn:disabled { opacity: .5; cursor: default; }

        @media (max-width: 900px) {
            .tp-sidebar { width: 220px; }
        }
        @media (max-width: 640px) {
            /* Content is short once everything stacks, so stop forcing the
               page to fill the full viewport height — avoids a large empty
               band of background color trailing below the schedule. */
            .main-content-inner:has(.tp-wrap) { flex: 0 0 auto; }
            .tp-wrap { flex: 0 0 auto; min-height: auto; }

            .tp-topbar { padding: 12px 16px; flex-direction: column; align-items: stretch; gap: 10px; }
            .tp-title { font-size: 17px; }
            .tp-subtitle { font-size: 12px; }

            .tp-week-nav { width: 100%; flex-wrap: nowrap; }
            .tp-week-date { flex: 1 1 auto; min-width: 0; }
            .tp-week-btn { flex-shrink: 0; }
            .tp-week-today { flex-shrink: 0; padding: 0 10px; }

            .tp-add-btn, .tp-cal-link { width: 100%; justify-content: center; }

            .tp-body { flex-direction: column; overflow: visible; }
            .tp-sidebar { width: 100%; border-right: none; border-bottom: 1px solid #EBE4DA; max-height: 240px; overflow-y: auto; }
            .tp-grid { flex: 0 0 auto; overflow-y: visible; }
            .tp-grid-row { padding: 12px 14px; }
            .tp-day-col { width: 78vw; max-width: 240px; }

            #tp-panel-inner {
                width: 100% !important;
                border-left: none !important;
                border-top: 1px solid #EBE4DA;
                padding: 18px 16px 24px !important;
            }
        }
    </style>

    @php
        $avatarPalette = ['#C8355F', '#24619C', '#6E4FA8', '#B97F24', '#2E7D5B'];
        $activityColors = [
            'ABA'              => ['bg' => '#F9E7EC', 'fg' => '#C8355F'],
            'Speech'           => ['bg' => '#E7EFF7', 'fg' => '#24619C'],
            'OT'               => ['bg' => '#F7EEDD', 'fg' => '#B97F24'],
            'Assessment'       => ['bg' => '#EDE7F5', 'fg' => '#6E4FA8'],
            'Supervision'      => ['bg' => '#F9E7EC', 'fg' => '#C8355F'],
            'Parent training'  => ['bg' => '#E3F1E9', 'fg' => '#2E7D5B'],
        ];
        $defaultActivityColor = ['bg' => '#EDEFF1', 'fg' => '#5A6B7E'];
    @endphp

    <!-- Therapists & Schedules -->
    <div class="tp-wrap">

        <!-- Top Bar -->
        <div class="tp-topbar">
            <div style="flex: 1; min-width: 0;">
                <div class="tp-title">
                    @if ($canViewAll)
                        Therapists &amp; schedules
                    @else
                        My schedule
                    @endif
                </div>
                <div class="tp-subtitle">
                    Week of {{ $days->first()->format('M j') }}–{{ $days->last()->format('j, Y') }} (Mon–Fri)
                    @if ($canViewAll)
                        · click a therapist to filter · add, modify or close sessions
                    @endif
                </div>
            </div>
            <form method="GET" action="{{ route('therapist.index') }}" class="tp-week-nav">
                @if ($canViewAll && $selectedTherapistId)
                    <input type="hidden" name="therapist_id" value="{{ $selectedTherapistId }}">
                @endif
                <a href="{{ route('therapist.index', array_filter(['week' => $prevWeek, 'therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="tp-week-btn" title="Previous week" aria-label="Previous week">‹</a>
                <input type="date" name="week" value="{{ $days->first()->toDateString() }}" onchange="this.form.submit()" class="tp-week-date" title="Jump to week containing this date" aria-label="Jump to a week">
                @unless ($isCurrentWeek)
                    <a href="{{ route('therapist.index', array_filter(['therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="tp-week-today">Today</a>
                @endunless
                <a href="{{ route('therapist.index', array_filter(['week' => $nextWeek, 'therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="tp-week-btn" title="Next week" aria-label="Next week">›</a>
            </form>
            @if ($canViewAll)
                <button type="button" id="tp-add-btn" class="tp-add-btn">+ Add session</button>
            @else
                <a href="{{ route('calendar.index') }}" class="tp-cal-link">Open Calendar →</a>
            @endif
        </div>

        <!-- Main Content -->
        <div class="tp-body">

            @if ($canViewAll)
                <!-- Left Sidebar - Therapist List -->
                <div class="tp-sidebar">
                    <div class="tp-sidebar-head">
                        <div class="tp-sidebar-title">Therapists</div>
                        <div class="tp-sidebar-sub">{{ $therapists->count() }} active</div>
                    </div>

                    @foreach ($therapists as $i => $therapist)
                        @php
                            $fullName = trim("{$therapist->first_name} {$therapist->last_name}");
                            $initials = mb_strtoupper(mb_substr($therapist->first_name ?? '', 0, 1) . mb_substr($therapist->last_name ?? '', 0, 1));
                            $avatarColor = $avatarPalette[$i % count($avatarPalette)];
                            $isActive = $selectedTherapistId === $therapist->id;
                            $hours = round(($weeklyMinutes[$therapist->id] ?? 0) / 60, 1);
                            $therapistSessionCount = $allWeekSessions->where('therapist_id', $therapist->id)->count();
                        @endphp
                        <a href="{{ route('therapist.index', array_filter(['therapist_id' => $therapist->id, 'week' => $isCurrentWeek ? null : $days->first()->toDateString()])) }}" class="tp-therapist-row {{ $isActive ? 'is-active' : '' }}">
                            <div class="tp-avatar" style="background:{{ $avatarColor }};">{{ $initials ?: '?' }}</div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="tp-therapist-name">{{ $fullName ?: 'Unnamed' }}</div>
                                <div class="tp-therapist-sub">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $therapist->department ?? '')) }} · {{ rtrim(rtrim(number_format($hours, 1), '0'), '.') }}h/wk</div>
                            </div>
                            <span class="tp-count-badge">{{ $therapistSessionCount }} sessions</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Right Side - Weekly Schedule -->
            <div class="tp-grid">
              <div class="tp-grid-row">
                @foreach ($days as $day)
                    @php $daySessions = $sessions->filter(fn ($session) => $session->session_date->toDateString() === $day->toDateString()); @endphp
                    <div class="tp-day-col">
                        <div class="tp-day-head">
                            <div class="tp-day-name">{{ $day->format('D') }} · {{ $day->format('M j') }}</div>
                            <span class="tp-day-count">{{ $daySessions->count() }}</span>
                        </div>
                        <div class="tp-day-body">
                            @forelse ($daySessions as $session)
                                @php
                                    $colors = $activityColors[$session->activity_type] ?? $defaultActivityColor;
                                    $isClosed = $session->status === 'cancelled';
                                    $sessionJson = [
                                        'id' => $session->id,
                                        'therapist_id' => $session->therapist_id,
                                        'patient_id' => $session->patient_id,
                                        'activity_type' => $session->activity_type,
                                        'session_date' => $session->session_date->toDateString(),
                                        'start_time' => substr($session->start_time, 0, 5),
                                        'duration_minutes' => $session->duration_minutes,
                                        'room' => $session->room,
                                        'notes' => $session->notes,
                                        'status' => $session->status,
                                    ];
                                @endphp
                                <div class="tp-session-card {{ $isClosed ? 'is-closed' : '' }}" style="background: {{ $colors['bg'] }};" data-session='@json($sessionJson)'>
                                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                        <div class="tp-session-time" style="color: {{ $colors['fg'] }};">{{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}</div>
                                        <div class="tp-session-duration">{{ $session->duration_minutes }} min</div>
                                    </div>
                                    <div class="tp-session-patient">{{ $session->child->child_name ?? 'Unassigned' }}</div>
                                    @if ($canViewAll && is_null($selectedTherapistId))
                                        <div class="tp-session-therapist">{{ trim(($session->therapist->first_name ?? '') . ' ' . ($session->therapist->last_name ?? '')) ?: 'Unassigned' }}</div>
                                    @endif
                                    <div class="tp-session-tags">
                                        <span class="tp-session-tag" style="background: #FFFDFA; color: {{ $colors['fg'] }};">{{ $session->activity_type }}</span>
                                        @if ($isClosed)
                                            <span class="tp-closed-badge">Closed</span>
                                        @endif
                                    </div>
                                    @if ($session->room)
                                        <div class="tp-session-room">{{ $session->room }}</div>
                                    @endif
                                    @if ($canViewAll)
                                        <div class="tp-card-actions">
                                            <button type="button" class="tp-card-btn tp-edit-btn">Edit</button>
                                            <button type="button" class="tp-card-btn tp-close-btn {{ $isClosed ? 'is-reopen' : '' }}">{{ $isClosed ? 'Reopen' : 'Close' }}</button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="tp-empty-day">No sessions</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
              </div>
            </div>

            @if ($canViewAll)
                <!-- Add / Edit session panel -->
                <div id="tp-panel-inner" style="display:none; width:300px; flex-shrink:0; border-left:1px solid #EBE4DA; background:#FFFDFA; padding:20px; overflow-y:auto; flex-direction:column; gap:12px;">

                <div style="display:flex; align-items:center; gap:10px;">
                    <div id="tp-modal-title" style="font:600 17px 'Baloo 2'; color:#16436E;">Add session</div>
                </div>
                <div id="tp-modal-error" style="display:none; background:#F9E7EC; color:#C8355F; font:700 12px 'Nunito Sans'; padding:8px 10px; border-radius:8px;"></div>

                <form id="tp-session-form" style="display:flex; flex-direction:column; gap:12px;">
                    <input type="hidden" id="tf-id" value="">
                    <input type="hidden" id="tf-status" value="scheduled">

                    <div>
                        <label for="tf-therapist" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Therapist</label>
                        <select id="tf-therapist" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;"></select>
                    </div>

                    <div>
                        <label for="tf-day" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Date</label>
                        <input type="date" id="tf-day" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;">
                    </div>

                    <div style="display:flex; gap:10px;">
                        <div style="flex:1;">
                            <label for="tf-start" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Time</label>
                            <input type="time" id="tf-start" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; font:700 13.5px 'Nunito Sans'; color:#16436E;">
                        </div>
                        <div style="flex:1;">
                            <label for="tf-duration" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Duration</label>
                            <select id="tf-duration" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;">
                                <option value="30">30 min</option>
                                <option value="45">45 min</option>
                                <option value="60" selected>60 min</option>
                                <option value="90">90 min</option>
                                <option value="120">120 min</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="tf-patient" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Patient</label>
                        <select id="tf-patient" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;"></select>
                    </div>

                    <div>
                        <label for="tf-activity" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Type</label>
                        <select id="tf-activity" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;">
                            <option value="ABA">ABA</option>
                            <option value="Speech">Speech</option>
                            <option value="OT">OT</option>
                            <option value="Assessment">Assessment</option>
                            <option value="Supervision">Supervision</option>
                            <option value="Parent training">Parent training</option>
                        </select>
                    </div>

                    <div>
                        <label for="tf-room" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Room (optional)</label>
                        <input type="text" id="tf-room" placeholder="e.g. Room 1, Sensory gym" style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; font:600 13.5px 'Nunito Sans'; color:#16436E;">
                    </div>

                    <div>
                        <label for="tf-notes" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Notes (optional)</label>
                        <textarea id="tf-notes" rows="3" style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; font:600 13.5px 'Nunito Sans'; color:#16436E; resize:vertical;"></textarea>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:10px; margin-top:4px;">
                        <button type="submit" id="tp-save-btn" style="background:#C8355F; border:none; border-radius:10px; padding:13px 0; font:800 13.5px 'Nunito Sans'; color:#FFFFFF; cursor:pointer;">Save</button>
                        <button type="button" id="tp-modal-cancel" style="background:#FFFFFF; border:1px solid #E2DACE; border-radius:10px; padding:13px 0; font:800 13.5px 'Nunito Sans'; color:#5A6B7E; cursor:pointer;">Cancel</button>
                    </div>
                </form>
                </div>
            @endif

        </div>
    </div>

    @if ($canViewAll)
        <script>
        (function () {
            const THERAPISTS = @json($therapistsForJs);
            const LEADS = @json($leadsForJs);
            const PRESELECTED_THERAPIST = {{ $selectedTherapistId ?? 'null' }};
            const DEFAULT_DATE = '{{ $days->first()->toDateString() }}';
            const STORE_URL = "{{ route('calendar.store') }}";
            const UPDATE_BASE = "{{ url('calendar') }}";
            const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
                || '{{ csrf_token() }}';

            async function api(url, options = {}) {
                const res = await fetch(url, {
                    ...options,
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                        ...options.headers,
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const err = new Error(data.message || 'Request failed');
                    err.errors = data.errors || {};
                    throw err;
                }
                return data;
            }

            function fillOptions(select, items, valueKey, labelKey) {
                select.innerHTML = items.map(i =>
                    `<option value="${i[valueKey]}">${i[labelKey]}</option>`
                ).join('');
            }

            const modal = document.getElementById('tp-panel-inner');
            const form = document.getElementById('tp-session-form');

            function openModal(session = null) {
                fillOptions(document.getElementById('tf-therapist'), THERAPISTS, 'id', 'name');
                fillOptions(document.getElementById('tf-patient'), LEADS, 'id', 'name');
                document.getElementById('tp-modal-error').style.display = 'none';

                if (session) {
                    document.getElementById('tp-modal-title').textContent = 'Edit session';
                    document.getElementById('tf-id').value = session.id;
                    document.getElementById('tf-status').value = session.status;
                    document.getElementById('tf-therapist').value = session.therapist_id;
                    document.getElementById('tf-patient').value = session.patient_id;
                    document.getElementById('tf-activity').value = session.activity_type;
                    document.getElementById('tf-day').value = session.session_date;
                    document.getElementById('tf-start').value = session.start_time;
                    document.getElementById('tf-duration').value = session.duration_minutes;
                    document.getElementById('tf-room').value = session.room || '';
                    document.getElementById('tf-notes').value = session.notes || '';
                } else {
                    document.getElementById('tp-modal-title').textContent = 'Add session';
                    form.reset();
                    document.getElementById('tf-id').value = '';
                    document.getElementById('tf-status').value = 'scheduled';
                    document.getElementById('tf-therapist').value = PRESELECTED_THERAPIST || (THERAPISTS[0]?.id ?? '');
                    document.getElementById('tf-day').value = DEFAULT_DATE;
                }

                modal.style.display = 'flex';
            }

            function closeModal() {
                modal.style.display = 'none';
            }

            document.getElementById('tp-add-btn')?.addEventListener('click', () => openModal());
            document.getElementById('tp-modal-cancel').addEventListener('click', closeModal);

            document.querySelectorAll('.tp-edit-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const session = JSON.parse(btn.closest('.tp-session-card').dataset.session);
                    openModal(session);
                });
            });

            document.querySelectorAll('.tp-close-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const card = btn.closest('.tp-session-card');
                    const session = JSON.parse(card.dataset.session);
                    const nextStatus = session.status === 'cancelled' ? 'scheduled' : 'cancelled';
                    const originalLabel = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = '…';
                    try {
                        await api(`${UPDATE_BASE}/${session.id}`, {
                            method: 'PUT',
                            body: JSON.stringify({ ...session, status: nextStatus }),
                        });
                        window.location.reload();
                    } catch (e) {
                        alert(e.message);
                        btn.disabled = false;
                        btn.textContent = originalLabel;
                    }
                });
            });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const id = document.getElementById('tf-id').value;
                const payload = {
                    therapist_id: document.getElementById('tf-therapist').value,
                    patient_id: document.getElementById('tf-patient').value,
                    activity_type: document.getElementById('tf-activity').value,
                    session_date: document.getElementById('tf-day').value,
                    start_time: document.getElementById('tf-start').value,
                    duration_minutes: document.getElementById('tf-duration').value,
                    room: document.getElementById('tf-room').value,
                    notes: document.getElementById('tf-notes').value,
                    status: document.getElementById('tf-status').value,
                };

                const errBox = document.getElementById('tp-modal-error');
                errBox.style.display = 'none';

                try {
                    if (id) {
                        await api(`${UPDATE_BASE}/${id}`, { method: 'PUT', body: JSON.stringify(payload) });
                    } else {
                        await api(STORE_URL, { method: 'POST', body: JSON.stringify(payload) });
                    }
                    window.location.reload();
                } catch (err) {
                    const firstError = Object.values(err.errors || {})[0]?.[0] || err.message;
                    errBox.textContent = firstError;
                    errBox.style.display = 'block';
                }
            });
        })();
        </script>
    @endif
@endsection
