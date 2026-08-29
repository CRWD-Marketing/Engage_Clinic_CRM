@extends('layouts.admin-sidebar')

@section('title', 'Calendar · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    <style>
        /* ---- Calendar: responsive layout ---- */
        .cal-topbar {
            display: flex;
            align-items: center;
            gap: 12px 16px;
            flex-wrap: wrap;
            padding: 16px 28px;
            border-bottom: 1px solid #EBE4DA;
            background: #FFFDFA;
        }
        .cal-topbar-title { flex: 1 1 220px; min-width: 200px; order: 0; }
        .cal-daylabel { font: 600 21px/1.2 'Baloo 2'; color: #16436E; }
        .cal-subtitle { font: 600 12.5px 'Nunito Sans'; color: #98897A; }
        .cal-day-select, .cal-therapist-select {
            padding: 9px 12px; border: 1px solid #E2DACE; border-radius: 9px;
            background: #FFFFFF; font: 700 12.5px 'Nunito Sans'; color: #16436E;
            outline: none; cursor: pointer;
        }
        .cal-day-select { order: 1; cursor: text; }
        .cal-therapist-select { order: 1; max-width: 170px; }
        .cal-book-btn {
            background: #C8355F; color: #fff; border: none; border-radius: 10px;
            padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer;
            order: 2; white-space: nowrap; transition: background-color .15s ease;
        }
        .cal-book-btn:hover { background: #A82348; }
        .cal-legend {
            display: flex; gap: 8px; align-items: center; flex-wrap: wrap;
            flex: 1 1 100%; order: 3;
        }
        .cal-legend-item {
            display: flex; gap: 6px; align-items: center; white-space: nowrap;
            font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;
            background: #FFFFFF; border: 1px solid #E2DACE; border-radius: 999px;
            padding: 5px 11px 5px 8px; cursor: pointer; transition: all .15s ease;
        }
        .cal-legend-item:hover { border-color: #D8CDBC; }
        .cal-legend-item.is-off { opacity: .45; }
        .cal-legend-dot { width: 9px; height: 9px; border-radius: 3px; flex-shrink: 0; }
        .cal-legend-reset {
            font: 800 11px 'Nunito Sans'; color: #98897A; background: none; border: none;
            cursor: pointer; text-decoration: underline; padding: 5px 4px;
        }
        .cal-legend-reset:hover { color: #C8355F; }

        .cal-grid-wrap { flex: 1; display: flex; min-height: 0; }
        .cal-grid {
            flex: 1; overflow: auto; padding: 20px 24px;
            display: flex; gap: 14px; align-items: flex-start;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
        }
        .cal-grid::-webkit-scrollbar { height: 7px; }
        .cal-grid::-webkit-scrollbar-track { background: transparent; }
        .cal-grid::-webkit-scrollbar-thumb { background: #D8CDBC; border-radius: 999px; }
        .cal-grid::-webkit-scrollbar-thumb:hover { background: #C2B4A0; }

        .therapist-col {
            width: 224px; flex-shrink: 0; background: #F6F3EE;
            border-radius: 14px; padding: 8px; scroll-snap-align: start;
            transition: box-shadow .15s ease;
        }
        .therapist-col.drag-over { box-shadow: inset 0 0 0 2px rgba(200,53,95,.35); }
        .therapist-col.is-hidden { display: none; }
        .therapist-col-header { background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; }
        .therapist-col-name { font: 600 14px 'Baloo 2'; color: #FFFFFF; }
        .therapist-col-sub { font: 600 11px 'Nunito Sans'; color: #9FB6CC; }

        .session-card { transition: transform .15s ease, box-shadow .15s ease; }
        .session-card:hover { transform: translateY(-2px); box-shadow: 0 8px 18px -10px rgba(22,42,60,.3); }
        .session-card.is-hidden { display: none; }

        .col-empty {
            text-align: center; color: #B0A493; font: 600 11.5px 'Nunito Sans';
            padding: 16px 4px;
        }

        /* ---- View toggle (Day / Week / Month) ---- */
        .cal-view-toggle {
            display: flex; gap: 4px; background: #F3EDE3; padding: 4px;
            border-radius: 10px; order: 1;
        }
        .cal-view-btn {
            border: none; background: transparent; padding: 7px 14px;
            border-radius: 8px; font: 800 12.5px 'Nunito Sans'; color: #5A6B7E;
            cursor: pointer; transition: background-color .15s ease, color .15s ease;
        }
        .cal-view-btn.active { background: #C8355F; color: #FFFFFF; }
        .cal-view-btn:not(.active):hover { color: #16436E; }

        .cal-nav-arrow {
            background: #FFFFFF; border: 1px solid #E2DACE; border-radius: 8px;
            width: 30px; height: 30px; font: 700 15px 'Nunito Sans'; color: #16436E;
            cursor: pointer; line-height: 1;
        }
        .cal-nav-arrow:hover { border-color: #C8355F; color: #C8355F; }
        .cal-nav-label {
            font: 800 12.5px 'Nunito Sans'; color: #16436E; min-width: 110px;
            text-align: center; display: inline-block;
        }
        .cal-nav-today {
            background: #FFFFFF; border: 1px solid #E2DACE; border-radius: 8px;
            padding: 6px 11px; font: 800 11.5px 'Nunito Sans'; color: #16436E; cursor: pointer;
        }
        .cal-nav-today:hover { border-color: #C8355F; color: #C8355F; }
        .cal-month-nav, .cal-week-nav { display: flex; align-items: center; gap: 8px; order: 1; }

        /* ---- Week view ---- */
        .week-grid {
            flex: 1; overflow: auto; padding: 20px 24px;
            display: flex; gap: 14px; align-items: flex-start;
        }
        .week-col { flex: 1; min-width: 190px; background: #F6F3EE; border-radius: 14px; padding: 8px; }
        .week-col-header { background: #16436E; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; }
        .week-col-day { font: 700 14px 'Baloo 2'; color: #FFFFFF; }
        .week-col-sub { font: 600 11px 'Nunito Sans'; color: #9FB6CC; }
        .week-session {
            border-radius: 10px; padding: 9px 11px; margin-bottom: 8px;
            cursor: pointer; transition: transform .15s ease, box-shadow .15s ease;
        }
        .week-session:hover { transform: translateY(-2px); box-shadow: 0 8px 18px -10px rgba(22,42,60,.3); }
        .week-session-time { font: 700 11.5px 'Nunito Sans'; }
        .week-session-dur { font: 600 10px 'Nunito Sans'; color: #98897A; float: right; }
        .week-session-name { font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; margin-top: 3px; }
        .week-session-meta { font: 600 11px 'Nunito Sans'; color: #8A7D6C; margin-top: 1px; }

        /* ---- Month view ---- */
        .month-grid {
            flex: 1; overflow: auto; padding: 20px 24px;
            display: grid; grid-template-columns: repeat(7, minmax(120px, 1fr)); gap: 10px;
            align-content: start;
        }
        .month-dow {
            font: 800 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase;
            letter-spacing: .5px; padding: 0 4px 4px;
        }
        .month-cell {
            background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 12px;
            padding: 8px; min-height: 108px; cursor: pointer; transition: border-color .15s ease;
            display: flex; flex-direction: column; gap: 3px;
        }
        .month-cell:hover { border-color: #C8355F; }
        .month-cell.is-today { border: 2px solid #C8355F; }
        .month-cell.is-empty { background: transparent; border-color: transparent; cursor: default; }
        .month-cell-top { display: flex; justify-content: space-between; align-items: baseline; }
        .month-cell-date { font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .month-cell-count { font: 800 11px 'Nunito Sans'; color: #C8355F; }
        .month-chip {
            border-radius: 6px; padding: 2px 6px; font: 700 10.5px 'Nunito Sans';
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .month-more { font: 700 10px 'Nunito Sans'; color: #98897A; }
        .month-book-link {
            font: 800 10.5px 'Nunito Sans'; color: #C8355F; margin-top: auto;
            cursor: pointer; align-self: flex-start;
        }
        .month-book-link:hover { text-decoration: underline; }

        @media (max-width: 900px) {
            .cal-nav-label { min-width: 84px; font-size: 11.5px; }
            .cal-view-btn { padding: 6px 10px; font-size: 11.5px; }
        }

        @media (max-width: 640px) {
            .cal-topbar { padding: 12px 14px; gap: 10px; }
            .cal-daylabel { font-size: 17px; }
            .cal-subtitle { display: none; } /* helper hint takes too much vertical space on phones */
            .cal-book-btn { flex: 1 1 auto; text-align: center; }
            .cal-day-select, .cal-therapist-select { flex: 1 1 auto; }
            .cal-legend { gap: 6px; }
            .cal-legend-item { font-size: 10.5px; padding: 4px 9px 4px 7px; }
            .cal-grid { padding: 12px 14px; gap: 10px; }
            .therapist-col { width: 78vw; max-width: 240px; }

            #panel-inner {
                width: 100vw !important;
                max-width: 100vw !important;
                padding: 18px 16px 24px !important;
            }
        }
    </style>

    <!-- Calendar -->
    <div id="calendar-root"
         data-therapists='@json($therapistsForJs)'
         data-leads='@json($leadsForJs)'
         data-feed-url="{{ route('calendar.feed') }}"
         data-store-url="{{ route('calendar.store') }}"
         style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">

        <!-- Top Bar -->
        <div class="cal-topbar">
            <div class="cal-topbar-title">
                <div class="cal-daylabel">Calendar — <span id="day-label">Today</span></div>
                <div class="cal-subtitle" id="cal-subtitle">Drag a card to another therapist, or use the dropdown · click a legend tag to filter · click Edit to modify</div>
            </div>

            <div class="cal-view-toggle" id="cal-view-toggle">
                <button type="button" class="cal-view-btn active" data-view="day">Day</button>
                <button type="button" class="cal-view-btn" data-view="week">Week</button>
                <button type="button" class="cal-view-btn" data-view="month">Month</button>
            </div>

            <div class="cal-week-nav" id="cal-week-nav" style="display:none;">
                <button type="button" id="week-prev" class="cal-nav-arrow" aria-label="Previous week">‹</button>
                <button type="button" id="week-today" class="cal-nav-today">This week</button>
                <button type="button" id="week-next" class="cal-nav-arrow" aria-label="Next week">›</button>
            </div>

            <div class="cal-month-nav" id="cal-month-nav" style="display:none;">
                <button type="button" id="month-prev" class="cal-nav-arrow" aria-label="Previous month">‹</button>
                <span id="month-nav-label" class="cal-nav-label"></span>
                <button type="button" id="month-next" class="cal-nav-arrow" aria-label="Next month">›</button>
            </div>

            <select id="therapist-filter" class="cal-therapist-select">
                <option value="">All therapists</option>
                @foreach ($therapists as $therapist)
                    <option value="{{ $therapist->id }}">{{ trim("{$therapist->first_name} {$therapist->last_name}") }}</option>
                @endforeach
            </select>
            <input type="date" id="day-select" class="cal-day-select">
            <button id="btn-book" class="cal-book-btn">+ Book session</button>
            <div class="cal-legend" id="cal-legend">
                <button type="button" class="cal-legend-item" data-type="ABA"><span class="cal-legend-dot" style="background:#C8355F;"></span>ABA</button>
                <button type="button" class="cal-legend-item" data-type="Speech"><span class="cal-legend-dot" style="background:#24619C;"></span>Speech</button>
                <button type="button" class="cal-legend-item" data-type="OT"><span class="cal-legend-dot" style="background:#B97F24;"></span>OT</button>
                <button type="button" class="cal-legend-item" data-type="Assessment"><span class="cal-legend-dot" style="background:#6E4FA8;"></span>Assessment</button>
                <button type="button" class="cal-legend-item" data-type="Parent training"><span class="cal-legend-dot" style="background:#2E7D5B;"></span>Parent training</button>
                <button type="button" class="cal-legend-reset" id="legend-reset">Reset</button>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="cal-grid-wrap" id="day-view">
            <div id="calendar-grid" class="cal-grid">
                @foreach ($therapists as $therapist)
                    <div class="therapist-col" data-therapist-id="{{ $therapist->id }}">
                        <div class="therapist-col-header">
                            <div class="therapist-col-name">{{ trim("{$therapist->first_name} {$therapist->last_name}") }}</div>
                            <div class="therapist-col-sub">
                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $therapist->department)) }} ·
                                <span id="count-{{ $therapist->id }}">0</span> sessions
                            </div>
                        </div>
                        <div class="col-body" id="col-body-{{ $therapist->id }}" style="display: flex; flex-direction: column; gap: 8px; min-height: 40px;"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Week view -->
        <div class="cal-grid-wrap" id="week-view" style="display:none;">
            <div id="week-grid" class="week-grid"></div>
        </div>

        <!-- Month view -->
        <div class="cal-grid-wrap" id="month-view" style="display:none;">
            <div id="month-grid" class="month-grid"></div>
        </div>
    </div>

    <!-- Card template (cloned by JS) -->
    <template id="session-card-template">
        <div class="session-card" draggable="true" style="border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; cursor: grab;">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <div class="card-time" style="font: 600 13px 'Baloo 2';"></div>
                <div class="card-duration" style="font: 600 10.5px 'Nunito Sans'; color: #98897A;"></div>
            </div>
            <div class="card-patient" style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;"></div>
            <div class="card-meta" style="font: 600 11.5px 'Nunito Sans'; color: #8A7D6C;"></div>
            <select class="card-therapist-select" style="width: 100%; padding: 5px 6px; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; background: #FFFFFF; font: 700 11px 'Nunito Sans'; color: #16436E; outline: none;"></select>
            <div style="display: flex; gap: 6px;">
                <button class="card-edit-btn" style="flex:1; background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer;">Edit</button>
                <button class="card-cancel-btn" style="flex:1; background: #FFFFFF; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 4px 0; font: 800 11px 'Nunito Sans'; color: #C8355F; cursor: pointer;">Cancel</button>
            </div>
        </div>
    </template>

    <!-- Book / Edit slide-out panel -->
    <div id="session-modal" style="display:none; position:fixed; inset:0; background:rgba(22,67,110,0.15); z-index:50;">
        <div id="panel-inner" style="position:absolute; top:0; right:0; height:100vh; width:400px; max-width:92vw; background:#FFFDFA; box-shadow:-24px 0 60px rgba(22,67,110,0.15); padding:26px 26px 30px; overflow-y:auto; display:flex; flex-direction:column; gap:16px; border-left: 6px solid #C8355F; transition: border-left-color .15s ease;">

            <div style="display:flex; align-items:center; gap:10px;">
                <div id="modal-title" style="font:700 20px 'Baloo 2'; color:#16436E;">Book session</div>
                <span id="activity-color-dot" style="width:10px; height:10px; border-radius:3px; background:#C8355F; margin-left:auto;"></span>
            </div>
            <div id="modal-error" style="display:none; background:#F9E7EC; color:#C8355F; font:700 12px 'Nunito Sans'; padding:8px 10px; border-radius:8px;"></div>

            <form id="session-form" style="display:flex; flex-direction:column; gap:16px;">
                <input type="hidden" id="f-id" value="">

                <div>
                    <label for="f-therapist" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Therapist / Assign to</label>
                    <select id="f-therapist" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;"></select>
                </div>

                <div>
                    <label for="f-day" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Date</label>
                    <input type="date" id="f-day" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;">
                </div>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label for="f-start" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Time</label>
                        <input type="time" id="f-start" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; font:700 13.5px 'Nunito Sans'; color:#16436E;">
                    </div>
                    <div style="flex:1;">
                        <label for="f-duration" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Duration</label>
                        <select id="f-duration" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;">
                            <option value="30">30 min</option>
                            <option value="45">45 min</option>
                            <option value="60" selected>60 min</option>
                            <option value="90">90 min</option>
                            <option value="120">120 min</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="f-patient" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Patient / Activity</label>
                    <select id="f-patient" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;"></select>
                </div>

                <div>
                    <label for="f-activity" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Type</label>
                    <select id="f-activity" required style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;">
                        <option value="ABA">ABA</option>
                        <option value="Speech">Speech</option>
                        <option value="OT">OT</option>
                        <option value="Assessment">Assessment</option>
                        <option value="Supervision">Supervision</option>
                        <option value="Parent training">Parent training</option>
                    </select>
                </div>

                <div>
                    <label for="f-room" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Room (optional)</label>
                    <input type="text" id="f-room" placeholder="e.g. Room 1, Sensory gym" style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; font:600 13.5px 'Nunito Sans'; color:#16436E;">
                </div>

                <div id="f-status-wrap" style="display:none;">
                    <label for="f-status" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Status</label>
                    <select id="f-status" style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; background:#FFFFFF; font:700 13.5px 'Nunito Sans'; color:#16436E; outline:none;">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No show</option>
                    </select>
                </div>

                <div>
                    <label for="f-notes" style="display:block; font:800 11px 'Nunito Sans'; letter-spacing:.04em; color:#8A7D6C; text-transform:uppercase; margin-bottom:6px;">Notes (optional)</label>
                    <textarea id="f-notes" rows="3" style="width:100%; padding:11px 10px; border:1px solid #E2DACE; border-radius:9px; font:600 13.5px 'Nunito Sans'; color:#16436E; resize:vertical;"></textarea>
                </div>

                <div style="display:flex; flex-direction:column; gap:10px; margin-top:4px;">
                    <button type="submit" id="save-btn" style="background:#C8355F; border:none; border-radius:10px; padding:13px 0; font:800 13.5px 'Nunito Sans'; color:#FFFFFF; cursor:pointer; transition: background-color .15s ease;">Save</button>
                    <button type="button" id="modal-cancel" style="background:#FFFFFF; border:1px solid #E2DACE; border-radius:10px; padding:13px 0; font:800 13.5px 'Nunito Sans'; color:#5A6B7E; cursor:pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const root = document.getElementById('calendar-root');
        const THERAPISTS = JSON.parse(root.dataset.therapists);
        const LEADS = JSON.parse(root.dataset.leads);
        const FEED_URL = root.dataset.feedUrl;
        const STORE_URL = root.dataset.storeUrl;
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content
            || '{{ csrf_token() }}';

        const COLORS = {
            'ABA':             { bg: '#F9E7EC', fg: '#C8355F' },
            'Speech':          { bg: '#E7EFF7', fg: '#24619C' },
            'OT':              { bg: '#F7EEDD', fg: '#B97F24' },
            'Assessment':      { bg: '#EDE7F5', fg: '#6E4FA8' },
            'Supervision':     { bg: '#F9E7EC', fg: '#C8355F' },
            'Parent training': { bg: '#E3F1E9', fg: '#2E7D5B' },
        };

        let sessionsById = {};
        let activeTypeFilters = new Set(); // empty = show all types
        let currentView = 'day';
        let weekAnchor = null;  // any date within the currently displayed week
        let monthAnchor = null; // any date within the currently displayed month

        function todayIso() {
            return new Date().toISOString().slice(0, 10);
        }

        // Parsing "YYYY-MM-DD" directly with `new Date()` reads it as UTC
        // midnight, which can roll back a day in negative-UTC-offset zones —
        // appending a local time avoids that shift.
        function parseIsoDateLocal(dateStr) {
            return new Date(`${dateStr}T00:00:00`);
        }

        function formatDayLabel(dateStr) {
            return parseIsoDateLocal(dateStr).toLocaleDateString('en-US', {
                weekday: 'short', month: 'short', day: 'numeric',
            });
        }

        // Formats a Date back to "YYYY-MM-DD" using its *local* fields - toISOString()
        // converts to UTC first, which can roll the date back a day in negative-UTC
        // offset zones (same pitfall parseIsoDateLocal above works around on the way in).
        function toIsoLocal(date) {
            const y = date.getFullYear();
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        }

        function addDays(date, n) {
            const d = new Date(date);
            d.setDate(d.getDate() + n);
            return d;
        }

        function mondayOf(dateStr) {
            const d = parseIsoDateLocal(dateStr);
            const day = d.getDay(); // 0=Sun..6=Sat
            const diff = day === 0 ? -6 : 1 - day;
            return addDays(d, diff);
        }

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[c]));
        }

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

        function renderColumns() {
            THERAPISTS.forEach(t => {
                const body = document.getElementById(`col-body-${t.id}`);
                if (body) body.innerHTML = '';
            });
        }

        function renderSession(s) {
            sessionsById[s.id] = s;
            const body = document.getElementById(`col-body-${s.therapist_id}`);
            if (!body) return;

            const tmpl = document.getElementById('session-card-template').content.cloneNode(true);
            const card = tmpl.querySelector('.session-card');
            const colors = COLORS[s.activity_type] || { bg: '#F6F3EE', fg: '#5A6B7E' };
            card.style.background = colors.bg;
            card.dataset.sessionId = s.id;
            card.dataset.activityType = s.activity_type;

            card.querySelector('.card-time').textContent = `${s.start_time}–${s.end_time}`;
            card.querySelector('.card-time').style.color = colors.fg;
            card.querySelector('.card-duration').textContent = `${s.duration_minutes} min`;
            card.querySelector('.card-patient').textContent = s.patient_name;
            card.querySelector('.card-meta').textContent = `${s.activity_type}${s.room ? ' · ' + s.room : ''}`;

            const select = card.querySelector('.card-therapist-select');
            fillOptions(select, THERAPISTS, 'id', 'name');
            select.value = s.therapist_id;
            select.addEventListener('change', () => reassignTherapist(s.id, select.value));

            card.querySelector('.card-edit-btn').addEventListener('click', () => openModal(s));
            card.querySelector('.card-cancel-btn').addEventListener('click', () => cancelSession(s.id));

            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', String(s.id));
            });

            body.appendChild(card);
        }

        function refreshCounts() {
            THERAPISTS.forEach(t => {
                const body = document.getElementById(`col-body-${t.id}`);
                if (!body) return;
                const visibleCards = body.querySelectorAll('.session-card:not(.is-hidden)').length;
                document.getElementById(`count-${t.id}`).textContent = visibleCards;

                let emptyMsg = body.querySelector('.col-empty');
                if (visibleCards === 0) {
                    if (!emptyMsg) {
                        emptyMsg = document.createElement('div');
                        emptyMsg.className = 'col-empty';
                        body.appendChild(emptyMsg);
                    }
                    emptyMsg.textContent = body.querySelector('.session-card')
                        ? 'No sessions match filter'
                        : 'No sessions';
                } else if (emptyMsg) {
                    emptyMsg.remove();
                }
            });
        }

        // Applies both the therapist column filter and the activity-type card
        // filter to whatever is currently rendered, without re-fetching.
        function applyFilters() {
            const therapistId = document.getElementById('therapist-filter').value;

            document.querySelectorAll('.therapist-col').forEach(col => {
                const matches = !therapistId || col.dataset.therapistId === therapistId;
                col.classList.toggle('is-hidden', !matches);
            });

            document.querySelectorAll('.session-card').forEach(card => {
                const matches = activeTypeFilters.size === 0 || activeTypeFilters.has(card.dataset.activityType);
                card.classList.toggle('is-hidden', !matches);
            });

            refreshCounts();
        }

        async function loadFeed() {
            const date = document.getElementById('day-select').value || todayIso();
            document.getElementById('day-label').textContent = formatDayLabel(date);

            renderColumns();
            sessionsById = {};

            const sessions = await api(`${FEED_URL}?start=${date}&end=${date}`);
            sessions
                .sort((a, b) => a.start_time.localeCompare(b.start_time))
                .forEach(renderSession);

            applyFilters();
        }

        const WEEK_DAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

        function therapistName(therapistId) {
            return (THERAPISTS.find(t => t.id == therapistId) || {}).name || '';
        }

        async function loadWeekFeed() {
            const monday = mondayOf(weekAnchor || todayIso());
            const friday = addDays(monday, 4);
            const mondayIso = toIsoLocal(monday);
            const fridayIso = toIsoLocal(friday);

            document.getElementById('day-label').textContent =
                `week of ${monday.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' })}`;

            const sessions = await api(`${FEED_URL}?start=${mondayIso}&end=${fridayIso}`);
            const byDay = {};
            sessions.forEach(s => { (byDay[s.session_date] ||= []).push(s); });
            Object.values(byDay).forEach(list => list.sort((a, b) => a.start_time.localeCompare(b.start_time)));

            const grid = document.getElementById('week-grid');
            grid.innerHTML = '';

            WEEK_DAY_LABELS.forEach((label, i) => {
                const dateIso = toIsoLocal(addDays(monday, i));
                const daySessions = byDay[dateIso] || [];

                const col = document.createElement('div');
                col.className = 'week-col';

                const rows = daySessions.length === 0
                    ? '<div class="col-empty">No sessions</div>'
                    : daySessions.map(s => {
                        const colors = COLORS[s.activity_type] || { bg: '#F6F3EE', fg: '#5A6B7E' };
                        const meta = [therapistName(s.therapist_id), s.room].filter(Boolean).map(escapeHtml).join(' · ');
                        return `
                            <div class="week-session" style="background:${colors.bg};" data-session-id="${s.id}">
                                <div>
                                    <span class="week-session-time" style="color:${colors.fg};">${escapeHtml(s.start_time)}–${escapeHtml(s.end_time)}</span>
                                    <span class="week-session-dur">${escapeHtml(s.duration_minutes)} min</span>
                                </div>
                                <div class="week-session-name">${escapeHtml(s.patient_name)}</div>
                                <div class="week-session-meta">${meta}</div>
                            </div>
                        `;
                    }).join('');

                col.innerHTML = `
                    <div class="week-col-header">
                        <div class="week-col-day">${label}</div>
                        <div class="week-col-sub">${daySessions.length} session${daySessions.length === 1 ? '' : 's'}</div>
                    </div>
                    ${rows}
                `;

                col.querySelectorAll('.week-session').forEach(el => {
                    el.addEventListener('click', () => {
                        const session = daySessions.find(s => String(s.id) === el.dataset.sessionId);
                        if (session) openModal(session);
                    });
                });

                grid.appendChild(col);
            });
        }

        function monthRange(anchorIso) {
            const d = parseIsoDateLocal(anchorIso);
            const first = new Date(d.getFullYear(), d.getMonth(), 1);
            const last = new Date(d.getFullYear(), d.getMonth() + 1, 0);
            return { first, last };
        }

        async function loadMonthFeed() {
            const { first, last } = monthRange(monthAnchor || todayIso());
            const firstIso = toIsoLocal(first);
            const lastIso = toIsoLocal(last);
            const label = first.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

            document.getElementById('day-label').textContent = label;
            document.getElementById('month-nav-label').textContent = label;

            const sessions = await api(`${FEED_URL}?start=${firstIso}&end=${lastIso}`);
            const byDay = {};
            sessions.forEach(s => { (byDay[s.session_date] ||= []).push(s); });
            Object.values(byDay).forEach(list => list.sort((a, b) => a.start_time.localeCompare(b.start_time)));

            const grid = document.getElementById('month-grid');
            grid.innerHTML = '';

            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach(d => {
                const dow = document.createElement('div');
                dow.className = 'month-dow';
                dow.textContent = d;
                grid.appendChild(dow);
            });

            // Monday-first leading gap before day 1 (getDay() is Sunday-first: 0=Sun..6=Sat).
            const firstWeekday = first.getDay();
            const leadingBlanks = firstWeekday === 0 ? 6 : firstWeekday - 1;
            for (let i = 0; i < leadingBlanks; i++) {
                const blank = document.createElement('div');
                blank.className = 'month-cell is-empty';
                grid.appendChild(blank);
            }

            const todayIsoVal = todayIso();
            const daysInMonth = last.getDate();

            for (let day = 1; day <= daysInMonth; day++) {
                const cellDate = new Date(first.getFullYear(), first.getMonth(), day);
                const cellIso = toIsoLocal(cellDate);
                const daySessions = byDay[cellIso] || [];

                const cell = document.createElement('div');
                cell.className = 'month-cell' + (cellIso === todayIsoVal ? ' is-today' : '');

                const preview = daySessions.slice(0, 3).map(s => {
                    const colors = COLORS[s.activity_type] || { bg: '#F6F3EE', fg: '#5A6B7E' };
                    return `<div class="month-chip" style="background:${colors.bg}; color:${colors.fg};">${escapeHtml(s.start_time)} ${escapeHtml(s.patient_name)}</div>`;
                }).join('');

                const more = daySessions.length > 3
                    ? `<div class="month-more">+${daySessions.length - 3} more</div>`
                    : '';

                cell.innerHTML = `
                    <div class="month-cell-top">
                        <span class="month-cell-date">${day}</span>
                        ${daySessions.length ? `<span class="month-cell-count">${daySessions.length}</span>` : ''}
                    </div>
                    ${preview}
                    ${more}
                    <span class="month-book-link">+ Book</span>
                `;

                cell.querySelector('.month-book-link').addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.getElementById('day-select').value = cellIso;
                    openModal();
                });

                cell.addEventListener('click', () => {
                    document.getElementById('day-select').value = cellIso;
                    switchView('day');
                });

                grid.appendChild(cell);
            }
        }

        function switchView(view) {
            currentView = view;

            document.querySelectorAll('.cal-view-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });

            document.getElementById('day-view').style.display = view === 'day' ? 'flex' : 'none';
            document.getElementById('week-view').style.display = view === 'week' ? 'flex' : 'none';
            document.getElementById('month-view').style.display = view === 'month' ? 'flex' : 'none';

            document.getElementById('cal-legend').style.display = view === 'day' ? 'flex' : 'none';
            document.getElementById('therapist-filter').style.display = view === 'day' ? '' : 'none';
            document.getElementById('day-select').style.display = view === 'day' ? '' : 'none';
            document.getElementById('cal-week-nav').style.display = view === 'week' ? 'flex' : 'none';
            document.getElementById('cal-month-nav').style.display = view === 'month' ? 'flex' : 'none';

            const subtitle = document.getElementById('cal-subtitle');

            if (view === 'day') {
                subtitle.textContent = 'Drag a card to another therapist, or use the dropdown · click a legend tag to filter · click Edit to modify';
                loadFeed();
            } else if (view === 'week') {
                subtitle.textContent = 'Every therapist, Monday to Friday · click a session to edit it';
                weekAnchor = document.getElementById('day-select').value || todayIso();
                loadWeekFeed();
            } else {
                subtitle.textContent = 'Click a date to open that day';
                monthAnchor = document.getElementById('day-select').value || todayIso();
                loadMonthFeed();
            }
        }

        // Re-fetches whichever view is currently on screen - used after a session is
        // booked/edited/cancelled/reassigned so Week/Month don't silently reload Day's
        // data underneath a view the user isn't looking at.
        function refreshCurrentView() {
            if (currentView === 'week') loadWeekFeed();
            else if (currentView === 'month') loadMonthFeed();
            else loadFeed();
        }

        async function reassignTherapist(sessionId, newTherapistId) {
            const s = sessionsById[sessionId];
            if (!s) return;
            try {
                await api(`${FEED_URL.replace('/feed', '')}/${sessionId}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        therapist_id: newTherapistId,
                        patient_id: s.patient_id,
                        activity_type: s.activity_type,
                        session_date: s.session_date || document.getElementById('day-select').value,
                        start_time: s.start_time,
                        duration_minutes: s.duration_minutes,
                        room: s.room,
                        status: s.status || 'scheduled',
                        notes: s.notes || '',
                    }),
                });
                loadFeed();
            } catch (e) {
                alert(e.message + (e.errors?.start_time ? '\n' + e.errors.start_time[0] : ''));
                loadFeed();
            }
        }

        async function cancelSession(sessionId) {
            if (!confirm('Cancel this session?')) return;
            await api(`${FEED_URL.replace('/feed', '')}/${sessionId}`, { method: 'DELETE' });
            loadFeed();
        }

        // Drag & drop onto a column reassigns the therapist
        document.querySelectorAll('.therapist-col').forEach(col => {
            col.addEventListener('dragover', (e) => { e.preventDefault(); col.classList.add('drag-over'); });
            col.addEventListener('dragleave', () => col.classList.remove('drag-over'));
            col.addEventListener('drop', (e) => {
                e.preventDefault();
                col.classList.remove('drag-over');
                const sessionId = e.dataTransfer.getData('text/plain');
                reassignTherapist(sessionId, col.dataset.therapistId);
            });
        });

        // --- Therapist filter (column visibility) ---
        document.getElementById('therapist-filter').addEventListener('change', applyFilters);

        // --- Activity-type legend filter (card visibility) ---
        // Clicking a tag shows ONLY that type (and any others already toggled on);
        // clicking it again removes it from the active set. With nothing toggled,
        // everything shows — "Reset" clears back to that all-visible state.
        document.querySelectorAll('.cal-legend-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                if (activeTypeFilters.has(type)) {
                    activeTypeFilters.delete(type);
                    btn.classList.remove('is-off');
                } else {
                    activeTypeFilters.add(type);
                    btn.classList.add('is-off');
                }
                // If every tag ends up toggled "on" that's equivalent to "no filter" —
                // reset the set so newly-created sessions of other types still show.
                if (activeTypeFilters.size === document.querySelectorAll('.cal-legend-item').length) {
                    activeTypeFilters.clear();
                    document.querySelectorAll('.cal-legend-item').forEach(b => b.classList.remove('is-off'));
                }
                applyFilters();
            });
        });
        document.getElementById('legend-reset').addEventListener('click', () => {
            activeTypeFilters.clear();
            document.querySelectorAll('.cal-legend-item').forEach(b => b.classList.remove('is-off'));
            document.getElementById('therapist-filter').value = '';
            applyFilters();
        });

        // --- Slide-out panel (book / edit) ---
        const modal = document.getElementById('session-modal');
        const form = document.getElementById('session-form');
        const panelInner = document.getElementById('panel-inner');
        const activityDot = document.getElementById('activity-color-dot');
        const saveBtn = document.getElementById('save-btn');
        const activitySelect = document.getElementById('f-activity');

        // Recolors the panel's left accent border, the small dot next to the
        // title, and the Save button — all matched to the same palette used
        // by the legend swatches and the session cards.
        function applyActivityColor(type) {
            const c = COLORS[type] || { bg: '#F6F3EE', fg: '#5A6B7E' };
            panelInner.style.borderLeftColor = c.fg;
            activityDot.style.background = c.fg;
            saveBtn.style.background = c.fg;
        }

        activitySelect.addEventListener('change', () => applyActivityColor(activitySelect.value));

        function openModal(session = null) {
            fillOptions(document.getElementById('f-therapist'), THERAPISTS, 'id', 'name');
            fillOptions(document.getElementById('f-patient'), LEADS, 'id', 'name');
            document.getElementById('modal-error').style.display = 'none';

            if (session) {
                document.getElementById('modal-title').textContent = 'Edit session';
                document.getElementById('f-status-wrap').style.display = 'block';
                document.getElementById('f-id').value = session.id;
                document.getElementById('f-therapist').value = session.therapist_id;
                document.getElementById('f-patient').value = session.patient_id;
                document.getElementById('f-activity').value = session.activity_type;
                document.getElementById('f-day').value = session.session_date || document.getElementById('day-select').value;
                document.getElementById('f-start').value = session.start_time;
                document.getElementById('f-duration').value = session.duration_minutes;
                document.getElementById('f-room').value = session.room || '';
                document.getElementById('f-status').value = session.status || 'scheduled';
                document.getElementById('f-notes').value = session.notes || '';
            } else {
                document.getElementById('modal-title').textContent = 'Book session';
                document.getElementById('f-status-wrap').style.display = 'none';
                form.reset();
                document.getElementById('f-id').value = '';
                document.getElementById('f-day').value = document.getElementById('day-select').value;
            }

            applyActivityColor(document.getElementById('f-activity').value);
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        document.getElementById('btn-book').addEventListener('click', () => openModal());
        document.getElementById('modal-cancel').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('f-id').value;
            const payload = {
                therapist_id: document.getElementById('f-therapist').value,
                patient_id: document.getElementById('f-patient').value,
                activity_type: document.getElementById('f-activity').value,
                session_date: document.getElementById('f-day').value,
                start_time: document.getElementById('f-start').value,
                duration_minutes: document.getElementById('f-duration').value,
                room: document.getElementById('f-room').value,
                notes: document.getElementById('f-notes').value,
            };
            if (id) payload.status = document.getElementById('f-status').value;

            const errBox = document.getElementById('modal-error');
            errBox.style.display = 'none';

            try {
                if (id) {
                    await api(`${FEED_URL.replace('/feed', '')}/${id}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                } else {
                    await api(STORE_URL, {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                }
                closeModal();
                refreshCurrentView();
            } catch (err) {
                const firstError = Object.values(err.errors || {})[0]?.[0] || err.message;
                errBox.textContent = firstError;
                errBox.style.display = 'block';
            }
        });

        document.getElementById('day-select').addEventListener('change', loadFeed);

        // --- Day / Week / Month view toggle ---
        document.querySelectorAll('.cal-view-btn').forEach(btn => {
            btn.addEventListener('click', () => switchView(btn.dataset.view));
        });

        document.getElementById('week-prev').addEventListener('click', () => {
            weekAnchor = toIsoLocal(addDays(mondayOf(weekAnchor || todayIso()), -7));
            loadWeekFeed();
        });
        document.getElementById('week-next').addEventListener('click', () => {
            weekAnchor = toIsoLocal(addDays(mondayOf(weekAnchor || todayIso()), 7));
            loadWeekFeed();
        });
        document.getElementById('week-today').addEventListener('click', () => {
            weekAnchor = todayIso();
            loadWeekFeed();
        });

        document.getElementById('month-prev').addEventListener('click', () => {
            const { first } = monthRange(monthAnchor || todayIso());
            monthAnchor = toIsoLocal(new Date(first.getFullYear(), first.getMonth() - 1, 1));
            loadMonthFeed();
        });
        document.getElementById('month-next').addEventListener('click', () => {
            const { first } = monthRange(monthAnchor || todayIso());
            monthAnchor = toIsoLocal(new Date(first.getFullYear(), first.getMonth() + 1, 1));
            loadMonthFeed();
        });

        document.getElementById('day-select').value = todayIso();
        loadFeed();
    })();
    </script>
@endsection
