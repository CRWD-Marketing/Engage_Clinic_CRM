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

        /* View toggle + month grid - styled to match the full Calendar page */
        .cal-view-toggle { display: flex; gap: 6px; flex-shrink: 0; }
        .cal-view-btn {
            border: 1px solid #E2DACE; background: #fff; padding: 9px 16px;
            border-radius: 9px; font: 800 12.5px 'Nunito Sans'; color: #16436E; cursor: pointer; text-decoration: none;
        }
        .cal-view-btn.active { background: #C8355F; border-color: #C8355F; color: #fff; }

        .month-grid { flex: 1; overflow: auto; padding: 20px 24px; display: grid; grid-template-columns: repeat(7, minmax(130px, 1fr)); gap: 10px; align-content: start; }
        .month-dow { font: 800 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: .5px; padding: 0 4px 4px; }
        .month-cell {
            background: #fff; border: 1px solid #EBE4DA; border-radius: 12px; padding: 8px;
            min-height: 100px; display: flex; flex-direction: column; gap: 3px;
            color: inherit; text-decoration: none; transition: border-color .15s ease, background-color .15s ease;
        }
        a.month-cell { cursor: pointer; }
        a.month-cell:hover { border-color: #C8355F; background: #FFFDFA; }
        .month-cell.is-today { border: 2px solid #C8355F; }
        .month-cell.is-empty { background: transparent; border-color: transparent; }
        .month-cell.is-weekend { background: #F6F3EE; }
        .month-cell-top { display: flex; justify-content: space-between; align-items: baseline; }
        .month-cell-date { font: 800 12.5px 'Nunito Sans'; color: #2B3A4C; }
        .month-cell-count { font: 800 11px 'Nunito Sans'; color: #C8355F; }
        .month-chip { border-radius: 6px; padding: 2px 6px; font: 700 10.5px 'Nunito Sans'; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .month-chip.is-cancelled { text-decoration: line-through; }
        .month-more { font: 700 10px 'Nunito Sans'; color: #98897A; }

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
        /* Only ever on screen in the one-pane phone layout, where the list is
           off-screen while a therapist's week is open. */
        .tp-back-bar {
            display: none; align-items: center; gap: 10px;
            padding: 10px 14px; background: #FFFDFA; border-bottom: 1px solid #EBE4DA;
        }
        .tp-back-arrow {
            display: flex; align-items: center; justify-content: center;
            width: 30px; height: 30px; flex-shrink: 0; margin-left: -6px;
            border-radius: 9px; text-decoration: none;
        }
        .tp-back-name { flex: 1; min-width: 0; font: 800 14.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        #tp-panel-backdrop {
            display: none; position: fixed; inset: 0; z-index: 1290;
            background: rgba(22, 42, 60, .38);
        }
        .tp-back-sub { font: 700 11.5px 'Nunito Sans'; color: #98897A; flex-shrink: 0; }
        .tp-count-badge {
            background: #F3EDE3; color: #8A7D6C; border-radius: 8px; padding: 4px 10px;
            font: 800 11px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0;
        }

        .tp-grid { flex: 1; background: #F6F3EE; min-width: 0; overflow-y: auto; }
        .tp-grid-row { overflow-x: auto; overflow-y: hidden; padding: 20px 24px; display: flex; gap: 14px; align-items: flex-start; }
        .tp-grid-row::-webkit-scrollbar { height: 7px; }
        .tp-grid-row::-webkit-scrollbar-track { background: transparent; }
        .tp-grid-row::-webkit-scrollbar-thumb { background: #D8CDBC; border-radius: 999px; }

        .tp-day-col { flex: 1 1 0; min-width: 200px; }
        .tp-day-head {
            background: #F0EBE5; border-radius: 10px; padding: 8px 12px; margin-bottom: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        /* Saturday and Sunday are part of the week but are not the working
           days, so they sit back a shade rather than reading as equals. */
        .tp-day-col.is-weekend .tp-day-head { background: #EFE7DA; }
        .tp-day-col.is-weekend .tp-day-name { color: #8A7D6C; }
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

            /* Title on its own line, then the view toggle and the date pager
               share the next one - compact enough to fit side by side, and it
               saves a whole row above the schedule. */
            .tp-topbar { padding: 12px 14px; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 9px; }
            /* !important because the title block carries an inline flex:1, which
               would otherwise let the toggle ride up beside it and squeeze the
               subtitle into three lines. */
            .tp-topbar > div:first-child { flex: 1 1 100% !important; }
            .cal-view-toggle { flex: 0 0 auto; gap: 5px; }
            /* Trimmed just enough that the toggle and the pager share a row
               rather than missing it by a few pixels. */
            .cal-view-btn { padding: 8px 12px; font-size: 12px; }
            .tp-title { font-size: 17px; }
            .tp-subtitle { font-size: 12px; }

            /* The date field was taking every pixel the row could give it,
               leaving a mostly empty box between the arrows. It sizes to its
               own content now and the row reads as one pager. */
            .tp-week-nav { flex: 1 1 auto; width: auto; flex-wrap: nowrap; justify-content: flex-end; gap: 6px; }
            .tp-week-date { flex: 0 1 auto; min-width: 0; padding: 0 8px; font-size: 12px; }
            .tp-week-btn { flex-shrink: 0; width: 32px; height: 32px; }
            .tp-week-today { flex-shrink: 0; padding: 0 12px; height: 32px; }

            .tp-add-btn, .tp-cal-link { flex: 1 1 100%; width: 100%; justify-content: center; }

            /* The month has to stay seven columns wide, so the cells shrink
               until the whole month fits the screen and reads as an overview:
               the day and how many sessions are on it. Session names are
               unreadable in a 48px cell, and tapping a day opens its week. */
            .month-grid { padding: 12px 10px; gap: 4px; grid-template-columns: repeat(7, minmax(0, 1fr)); }
            .month-dow { font-size: 9px; padding: 0 0 3px; text-align: center; letter-spacing: 0; }
            .month-cell { min-height: 58px; padding: 5px 3px; border-radius: 8px; gap: 1px; }
            .month-cell.is-today { border-width: 1.5px; }
            .month-cell-top { justify-content: center; gap: 3px; }
            .month-cell-date { font-size: 11.5px; }
            .month-cell-count {
                background: #C8355F; color: #fff; border-radius: 999px;
                min-width: 15px; padding: 0 4px; text-align: center;
                font-size: 9.5px; line-height: 15px;
            }
            /* The patient's name stays on the chip - it is the one thing the
               month is read for - and truncates with an ellipsis rather than
               being dropped. The time goes instead: in a cell this narrow it
               would eat the whole line and leave nothing for the name. */
            .month-chip {
                display: block; padding: 1px 3px; border-radius: 4px;
                font: 700 8.5px 'Nunito Sans'; line-height: 1.5;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .month-chip-time { display: none; }
            .month-more { display: block; font-size: 8px; }

            .tp-body { flex-direction: column; overflow: visible; }
            .tp-sidebar { width: 100%; border-right: none; border-bottom: 1px solid #EBE4DA; }

            /* The therapist list takes the shape of the WhatsApp inbox rows: a
               large round avatar, the name with its count out on the right, and
               one grey line underneath. It was a 240px scroll box holding three
               names at a time; now the whole team reads in one pass. */
            .tp-sidebar-head { padding: 14px 16px 10px; border-bottom: none; }
            .tp-sidebar-title { font-size: 22px; letter-spacing: -0.3px; }
            .tp-sidebar-sub { font-size: 12px; }
            .tp-therapist-row {
                display: grid;
                grid-template-columns: auto 1fr auto;
                grid-template-rows: auto auto;
                column-gap: 11px; row-gap: 1px; align-items: center;
                padding: 9px 14px; border-bottom: none;
            }
            .tp-avatar { grid-row: 1 / 3; width: 52px; height: 52px; font-size: 18px; }
            /* The name and its sub line share a wrapper in the markup; this
               lifts them out so each can take a cell of its own. */
            .tp-therapist-row > div:not(.tp-avatar) { display: contents; }
            .tp-therapist-name { grid-column: 2; grid-row: 1; min-width: 0; font-size: 15px; }
            .tp-count-badge {
                grid-column: 3; grid-row: 1;
                background: none; padding: 0; color: #B0A493; font: 700 11.5px 'Nunito Sans';
            }
            .tp-therapist-sub {
                grid-column: 2 / 4; grid-row: 2; min-width: 0;
                font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .tp-grid { flex: 0 0 auto; overflow-y: visible; }
            /* The week stacks instead of scrolling sideways - five days of
               240px columns meant swiping past four of them to reach Friday,
               and the whole week is short enough to just read down. */
            .tp-grid-row {
                padding: 12px 14px;
                flex-direction: column; align-items: stretch;
                overflow-x: hidden; gap: 10px;
            }
            .tp-day-col { flex: 0 0 auto; width: auto; max-width: none; min-width: 0; }
            .tp-day-head { margin-bottom: 8px; }

            /* One pane at a time, the way the WhatsApp inbox works: the list of
               therapists, then a therapist's week behind a back arrow. The
               controller preselects the first therapist so the desktop layout is
               never empty, so which pane shows is decided by whether one was
               actually asked for in the URL. */
            .tp-wrap.tp-has-list:not(.tp-has-selection) .tp-grid { display: none; }
            .tp-wrap.tp-has-selection .tp-sidebar { display: none; }
            .tp-wrap.tp-has-selection .tp-back-bar { display: flex; }
            /* Nothing is 'selected' while the list is the whole screen. */
            .tp-wrap:not(.tp-has-selection) .tp-therapist-row.is-active { background: transparent; }

            /* Edit opens as a sheet over the schedule. As a column appended
               below it, the form landed off the bottom of the screen and the
               tap looked like it had done nothing. */
            #tp-panel-inner {
                position: fixed !important;
                left: 0 !important; right: 0 !important; bottom: 0 !important; top: auto !important;
                width: auto !important; max-height: 88vh;
                z-index: 1300;
                border-left: none !important;
                border-top: 1px solid #EBE4DA;
                border-radius: 18px 18px 0 0;
                padding: 12px 16px 24px !important;
                box-shadow: 0 -18px 46px rgba(22, 42, 60, .22);
            }
            /* A grab bar, so it reads as a sheet rather than a panel that has
               slipped out of place. */
            #tp-panel-inner::before {
                content: ''; align-self: center; flex: none;
                width: 38px; height: 4px; border-radius: 999px;
                background: #DFD6C8; margin-bottom: 6px;
            }
            #tp-panel-backdrop.is-open { display: block; }
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
    @php
        // Keyed off the request, not off $selectedTherapistId: the controller
        // falls back to the first therapist so the desktop schedule is never
        // blank, and on a phone that pane is not on screen yet.
        $tpHasSelection = $canViewAll && request()->filled('therapist_id');
        $tpSelected = $therapists->firstWhere('id', $selectedTherapistId);
        $tpBackLink = route('therapist.index', array_filter([
            'view' => $view === 'month' ? 'month' : null,
            'month' => $view === 'month' ? $monthAnchor->format('Y-m') : null,
            'week' => $view === 'month' || $isCurrentWeek ? null : $days->first()->toDateString(),
        ]));
    @endphp

    <div class="tp-wrap {{ $canViewAll ? 'tp-has-list' : '' }} {{ $tpHasSelection ? 'tp-has-selection' : '' }}">

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
                    @if ($view === 'month')
                        {{ $monthAnchor->format('F Y') }}
                    @else
                        Week of {{ $days->first()->format('M j') }}–{{ $days->last()->format('j, Y') }}
                    @endif
                    @if ($canViewAll)
                        · click a therapist to filter · add, modify or close sessions
                    @endif
                </div>
            </div>

            <div class="cal-view-toggle">
                <a href="{{ route('therapist.index', array_filter(['therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="cal-view-btn {{ $view === 'week' ? 'active' : '' }}">Week</a>
                <a href="{{ route('therapist.index', array_filter(['view' => 'month', 'month' => $monthAnchor->format('Y-m'), 'therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="cal-view-btn {{ $view === 'month' ? 'active' : '' }}">Month</a>
            </div>

            @if ($view === 'month')
                <form method="GET" action="{{ route('therapist.index') }}" class="tp-week-nav">
                    <input type="hidden" name="view" value="month">
                    @if ($canViewAll && $selectedTherapistId)
                        <input type="hidden" name="therapist_id" value="{{ $selectedTherapistId }}">
                    @endif
                    <a href="{{ route('therapist.index', array_filter(['view' => 'month', 'month' => $prevMonth, 'therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="tp-week-btn" title="Previous month" aria-label="Previous month">‹</a>
                    <input type="month" name="month" value="{{ $monthAnchor->format('Y-m') }}" onchange="this.form.submit()" class="tp-week-date" title="Jump to a month" aria-label="Jump to a month">
                    @unless ($isCurrentMonth)
                        <a href="{{ route('therapist.index', array_filter(['view' => 'month', 'therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="tp-week-today">This month</a>
                    @endunless
                    <a href="{{ route('therapist.index', array_filter(['view' => 'month', 'month' => $nextMonth, 'therapist_id' => $canViewAll ? $selectedTherapistId : null])) }}" class="tp-week-btn" title="Next month" aria-label="Next month">›</a>
                </form>
            @else
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
            @endif
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

            <!-- Right Side - Weekly / Monthly Schedule -->
            <div class="tp-grid">
              @if ($canViewAll && $tpSelected)
                <div class="tp-back-bar">
                    <a href="{{ $tpBackLink }}" class="tp-back-arrow" aria-label="Back to therapists">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M15 18L9 12L15 6" stroke="#16436E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <div class="tp-back-name">{{ trim("{$tpSelected->first_name} {$tpSelected->last_name}") ?: 'Unnamed' }}</div>
                    <span class="tp-back-sub">{{ rtrim(rtrim(number_format(round(($weeklyMinutes[$tpSelected->id] ?? 0) / 60, 1), 1), '0'), '.') }}h/wk</span>
                </div>
              @endif
              @if ($view === 'month')
                <div class="month-grid">
                    @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
                        <div class="month-dow">{{ $dow }}</div>
                    @endforeach
                    @foreach ($monthDays as $day)
                        @php
                            $inMonth = $day->isSameMonth($monthAnchor);
                            $daySessions = $monthSessions->filter(fn ($session) => $session->session_date->toDateString() === $day->toDateString());
                            $preview = $daySessions->take(3);
                        @endphp
                        @php
                            // A day in the month is a way into that week: the week
                            // view is keyed on any date it contains, so the cell
                            // just hands it this one. Days spilling in from the
                            // neighbouring months stay inert, as they always were.
                            $dayWeekLink = $inMonth
                                ? route('therapist.index', array_filter([
                                    'week' => $day->toDateString(),
                                    'therapist_id' => $canViewAll ? $selectedTherapistId : null,
                                ]))
                                : null;
                        @endphp
                        <{{ $dayWeekLink ? 'a' : 'div' }}
                            @if ($dayWeekLink) href="{{ $dayWeekLink }}" title="Open the week of {{ $day->format('M j') }}" @endif
                            class="month-cell {{ !$inMonth ? 'is-empty' : '' }} {{ $day->isToday() ? 'is-today' : '' }} {{ $day->isWeekend() ? 'is-weekend' : '' }}">
                            @if ($inMonth)
                                <div class="month-cell-top">
                                    <span class="month-cell-date">{{ $day->format('j') }}</span>
                                    @if ($daySessions->count())
                                        <span class="month-cell-count">{{ $daySessions->count() }}</span>
                                    @endif
                                </div>
                                @foreach ($preview as $session)
                                    @php $colors = $activityColors[$session->activity_type] ?? $defaultActivityColor; @endphp
                                    <div class="month-chip {{ $session->status === 'cancelled' ? 'is-cancelled' : '' }}" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">
                                        <span class="month-chip-time">{{ substr($session->start_time, 0, 5) }}</span> {{ $session->child->child_name ?? 'Unassigned' }}
                                    </div>
                                @endforeach
                                @if ($daySessions->count() > 3)
                                    <div class="month-more">+{{ $daySessions->count() - 3 }} more</div>
                                @endif
                            @endif
                        </{{ $dayWeekLink ? 'a' : 'div' }}>
                    @endforeach
                </div>
              @else
              <div class="tp-grid-row">
                @foreach ($days as $day)
                    @php $daySessions = $sessions->filter(fn ($session) => $session->session_date->toDateString() === $day->toDateString()); @endphp
                    <div class="tp-day-col {{ $day->isWeekend() ? 'is-weekend' : '' }}">
                        <div class="tp-day-head">
                            <div class="tp-day-name">{{ $day->format('D') }} · {{ $day->format('M j') }}</div>
                            <span class="tp-day-count">{{ $daySessions->count() }}</span>
                        </div>
                        <div class="tp-day-body">
                            @forelse ($daySessions as $session)
                                @php
                                    $colors = $activityColors[$session->activity_type] ?? $defaultActivityColor;
                                    $isClosed = $session->status === 'closed';
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
              @endif
            </div>

            @if ($canViewAll)
                <!-- Add / Edit session panel -->
                <div id="tp-panel-backdrop"></div>
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
            const backdrop = document.getElementById('tp-panel-backdrop');
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
                backdrop?.classList.add('is-open');
                modal.scrollTop = 0;
            }

            function closeModal() {
                modal.style.display = 'none';
                backdrop?.classList.remove('is-open');
            }

            document.getElementById('tp-add-btn')?.addEventListener('click', () => openModal());
            document.getElementById('tp-modal-cancel').addEventListener('click', closeModal);
            backdrop?.addEventListener('click', closeModal);
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

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
                    // Close = discontinue the slot (it disappears from the Calendar and
                    // frees the time/hours); Reopen puts it back as scheduled.
                    const nextStatus = session.status === 'closed' ? 'scheduled' : 'closed';
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
