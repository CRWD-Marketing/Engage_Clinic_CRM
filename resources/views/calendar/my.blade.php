@extends('layouts.admin-sidebar')

@section('title', 'My calendar · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@php
    $typeColors = [
        'ABA' => ['bg' => '#F9E7EC', 'fg' => '#C8355F'],
        'Speech' => ['bg' => '#E7EFF7', 'fg' => '#24619C'],
        'OT' => ['bg' => '#F7EEDD', 'fg' => '#B97F24'],
        'Assessment' => ['bg' => '#EDE7F5', 'fg' => '#6E4FA8'],
        'Parent training' => ['bg' => '#E3F1E9', 'fg' => '#2E7D5B'],
    ];
    $categoryColors = [
        'supervision' => ['bg' => '#E4E0F7', 'fg' => '#4B3F9E'],
        'observation' => ['bg' => '#F0E4F5', 'fg' => '#8A4FA8'],
        'admin' => ['bg' => '#F7EEDD', 'fg' => '#8A6A2B'],
    ];
    $neutral = ['bg' => '#F3EDE3', 'fg' => '#5A6B7E'];
@endphp

@section('content')
    <style>
        .mc-topbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; flex-wrap: wrap; padding: 16px 28px 12px; background: #FFFDFA; }
        .mc-title { font: 600 21px/1.2 'Baloo 2'; color: #16436E; }
        .mc-subtitle { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
        .mc-nav { display: flex; align-items: center; gap: 8px; }
        .mc-nav-arrow { background: #fff; border: 1px solid #E2DACE; border-radius: 8px; width: 30px; height: 34px; font: 700 15px 'Nunito Sans'; color: #16436E; cursor: pointer; text-align: center; line-height: 32px; text-decoration: none; display: inline-block; }
        .mc-nav-arrow:hover { border-color: #C8355F; color: #C8355F; }
        .mc-locked { background: #F7EEDD; color: #8A6A2B; border: 1px solid #EBDBB8; border-radius: 10px; padding: 9px 14px; font: 800 12px 'Nunito Sans'; white-space: nowrap; }

        .mc-grid-wrap { flex: 1; overflow: auto; padding: 18px 24px; }
        .mc-grid { display: grid; grid-template-columns: repeat(7, minmax(200px, 1fr)); gap: 14px; min-width: 1200px; }
        .mc-day-header { font: 800 10.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #98897A; padding-bottom: 8px; border-bottom: 2px solid #EBE4DA; margin-bottom: 10px; }
        .mc-day-header.is-today { color: #C8355F; border-bottom-color: #C8355F; }
        .mc-day-header small { display: block; font: 700 10px 'Nunito Sans'; color: inherit; text-transform: none; letter-spacing: 0; margin-top: 1px; opacity: .8; }
        .mc-day-body { display: flex; flex-direction: column; gap: 10px; }
        .mc-off { background: #F5E3C0; color: #8A6A2B; border-radius: 11px; text-align: center; padding: 14px 8px; font: 800 11.5px 'Nunito Sans'; letter-spacing: .04em; }
        .mc-leave { background: #FDF3B0; border-radius: 11px; padding: 10px 13px; }
        .mc-leave-title { font: 800 12.5px 'Nunito Sans'; color: #7A5C00; }
        .mc-leave-reason { font: 600 11px 'Nunito Sans'; color: #8A7D6C; margin-top: 2px; }

        .mc-card { border-radius: 11px; padding: 10px 13px; display: flex; flex-direction: column; gap: 6px; }
        .mc-card.is-cancelled .mc-card-name, .mc-card.is-cancelled .mc-card-time { text-decoration: line-through; opacity: .7; }
        .mc-card-top { display: flex; justify-content: space-between; align-items: baseline; }
        .mc-card-time { font: 600 13px 'Baloo 2'; color: inherit; }
        .mc-card-duration { font: 600 10.5px 'Nunito Sans'; color: #98897A; }
        .mc-card-name { font: 800 13px 'Nunito Sans'; color: inherit; }
        .mc-card-meta { font: 600 11.5px 'Nunito Sans'; color: #8A7D6C; }
        .mc-status { font: 800 9.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; }
        .mc-status-completed { color: #2E7D5B; }
        .mc-status-upcoming { color: #B97F24; }
        .mc-status-cancelled { color: #98897A; text-decoration: line-through; }
        .mc-status-no_show { color: #C8355F; }

        .mc-sup-badge { display: inline-block; background: #6E4FA8; color: #fff; font: 800 9.5px 'Nunito Sans'; letter-spacing: .04em; padding: 2px 7px; border-radius: 5px; width: fit-content; }
        .mc-sup-note { font: 600 10.5px 'Nunito Sans'; color: #5A6B7E; white-space: normal; line-height: 1.35; }

        .mc-note-display { font: 700 12px 'Nunito Sans'; color: #2B3A4C; background: #FFFDFA; border: 1px solid rgba(43,58,76,0.12); border-radius: 8px; padding: 7px 9px; white-space: pre-wrap; word-break: break-word; }
        .mc-note-btn { width: 100%; background: #fff; border: 1px solid rgba(43,58,76,0.15); border-radius: 6px; padding: 6px 0; font: 800 11px 'Nunito Sans'; color: #16436E; cursor: pointer; }
        .mc-note-btn:hover { border-color: #C8355F; color: #C8355F; }

        @media (max-width: 900px) {
            .mc-grid { grid-template-columns: repeat(7, 78vw); min-width: 0; }
        }

        /* ---- Session note modal (matches the admin "Supervision note" modal) ---- */
        .f-textarea { width: 100%; padding: 11px 10px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFDFA; font: 600 13.5px 'Nunito Sans'; color: #16436E; outline: none; box-sizing: border-box; resize: vertical; }
        .f-row { display: flex; gap: 10px; }
        .btn-save { background: #C8355F; border: none; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #fff; cursor: pointer; width: 100%; }
        .btn-cancel { background: #fff; border: 1px solid #E2DACE; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #5A6B7E; cursor: pointer; width: 100%; }
        .cmodal-overlay { display: none; position: fixed; inset: 0; background: rgba(22,67,110,0.25); z-index: 60; align-items: center; justify-content: center; padding: 20px; }
        .cmodal-overlay.open { display: flex; }
        .cmodal { background: #FFFDFA; border-radius: 16px; padding: 24px; width: 100%; max-width: 420px; box-shadow: 0 24px 60px rgba(22,67,110,.25); display: flex; flex-direction: column; gap: 14px; max-height: 92vh; overflow: auto; }
        .cmodal-title { font: 700 20px 'Baloo 2'; color: #16436E; }
        .cmodal-sub { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: -8px; }
    </style>

    <div class="role-strip">
        <span class="role-badge">{{ $capabilities['label'] }}</span>
        <span class="role-can">Can {{ implode(' · ', $capabilities['can']) }}</span>
        @if ($capabilities['locked'])
            <span class="role-locked">
                🔒 Locked: {{ implode(' · ', array_slice($capabilities['locked'], 0, 4)) }}@if (count($capabilities['locked']) > 4) · +{{ count($capabilities['locked']) - 4 }} more @endif
            </span>
        @endif
    </div>

    <div class="mc-topbar">
        <div>
            <div class="mc-title">My calendar — {{ $monday->format('d M') }} – {{ $sunday->format('d M Y') }}</div>
            <div class="mc-subtitle">{{ $therapistName }} · {{ $designation }} · {{ $therapyHours }} therapy hours this week</div>
        </div>
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <div class="mc-nav">
                <a class="mc-nav-arrow" href="{{ route('calendar.index', ['date' => $prevDate]) }}" aria-label="Previous week">‹</a>
                <a class="mc-nav-arrow" href="{{ route('calendar.index', ['date' => $nextDate]) }}" aria-label="Next week">›</a>
            </div>
            <div class="mc-locked">🔒 Your schedule is set by your supervisor</div>
        </div>
    </div>

    <div class="mc-grid-wrap">
        <div class="mc-grid">
            @foreach ($days as $day)
                <div>
                    <div class="mc-day-header {{ $day['is_today'] ? 'is-today' : '' }}">
                        {{ strtoupper($day['date']->format('D')) }}
                        <small>{{ $day['date']->format('j-M') }}@if($day['is_today']) · today @endif</small>
                    </div>
                    <div class="mc-day-body">
                        @if ($day['leave'])
                            <div class="mc-leave">
                                <div class="mc-leave-title">On leave · {{ $day['leave']->leave_type }}</div>
                                <div class="mc-leave-reason">{{ $day['leave']->reason }}</div>
                            </div>
                        @elseif ($day['is_weekend'] && $day['sessions']->isEmpty())
                            <div class="mc-off">OFF</div>
                        @elseif ($day['sessions']->isEmpty())
                            <div class="mc-card-meta" style="text-align:center; padding:16px 4px; color:#B0A493;">No sessions</div>
                        @endif

                        @foreach ($day['sessions'] as $s)
                            @php
                                $c = $typeColors[$s->activity_type] ?? $categoryColors[$s->category()] ?? $neutral;
                                $isCancelled = $s->status === 'cancelled';
                                $isCompleted = $s->status === 'completed';
                                $isNoShow = $s->status === 'no_show';
                                $isUpcoming = ! $isCancelled && ! $isCompleted && ! $isNoShow;
                                $meta = collect([$s->activity_type, $s->notes])->filter()->implode(' · ');
                            @endphp
                            <div class="mc-card {{ $isCancelled ? 'is-cancelled' : '' }}" style="background: {{ $c['bg'] }}; color: {{ $c['fg'] }};">
                                <div class="mc-card-top">
                                    <span class="mc-card-time">{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</span>
                                    <span class="mc-card-duration">{{ $s->duration_minutes }} min</span>
                                </div>
                                <div class="mc-card-name">{{ $s->displayName() }}</div>
                                @if ($meta)
                                    <div class="mc-card-meta">{{ $meta }}</div>
                                @endif

                                @if ($s->isSupervised())
                                    <span class="mc-sup-badge">★ Supervised</span>
                                    @if ($s->supervision_notes)
                                        <div class="mc-sup-note">{{ $s->supervision_notes }}</div>
                                    @endif
                                @endif

                                @if ($isCancelled)
                                    <span class="mc-status mc-status-cancelled">{{ $s->statusLabel() }}</span>
                                @elseif ($isNoShow)
                                    <span class="mc-status mc-status-no_show">{{ $s->statusLabel() }}</span>
                                @elseif ($isCompleted)
                                    <span class="mc-status mc-status-completed">Completed</span>
                                    <div class="mc-note-box"
                                         data-url="{{ route('calendar.therapist-note.store', $s) }}"
                                         data-line="{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }} · {{ $s->displayName() }} · {{ $s->activity_type }} ({{ $s->session_date->format('D j-M') }})">
                                        <div class="mc-note-view" @if(! $s->therapist_note) style="display:none;" @endif>
                                            <div class="mc-note-display">{{ $s->therapist_note }}</div>
                                            <button type="button" class="mc-note-btn mc-note-edit-btn">Edit note</button>
                                        </div>
                                        <button type="button" class="mc-note-btn mc-note-add-btn" @if($s->therapist_note) style="display:none;" @endif>+ Add session note</button>
                                    </div>
                                @else
                                    <span class="mc-status mc-status-upcoming">Upcoming</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Session note -->
    <div id="note-modal" class="cmodal-overlay">
        <div class="cmodal">
            <div class="cmodal-title">Session note</div>
            <div class="cmodal-sub" id="note-session-line"></div>
            <div class="cmodal-sub" id="note-status-line" style="margin-top:2px; color:#2E7D5B; font-weight:800;"></div>
            <textarea id="note-textarea" rows="4" class="f-textarea" placeholder="How did the session go?"></textarea>
            <div class="f-row">
                <button type="button" class="btn-save" id="note-save-btn">Save note</button>
                <button type="button" class="btn-cancel" id="note-cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
        const modal = document.getElementById('note-modal');
        const sessionLine = document.getElementById('note-session-line');
        const statusLine = document.getElementById('note-status-line');
        const textarea = document.getElementById('note-textarea');
        const saveBtn = document.getElementById('note-save-btn');
        const cancelBtn = document.getElementById('note-cancel-btn');
        let activeBox = null;

        function openModal(box) {
            activeBox = box;
            const display = box.querySelector('.mc-note-display');
            sessionLine.textContent = box.dataset.line;
            statusLine.textContent = 'Completed — '.concat(display.textContent.trim() ? 'note recorded by you' : 'add a note about how it went');
            textarea.value = display.textContent.trim();
            modal.classList.add('open');
            textarea.focus();
        }
        function closeModal() {
            modal.classList.remove('open');
            activeBox = null;
        }

        document.querySelectorAll('.mc-note-box').forEach(function (box) {
            box.querySelector('.mc-note-add-btn').addEventListener('click', function () { openModal(box); });
            box.querySelector('.mc-note-edit-btn').addEventListener('click', function () { openModal(box); });
        });

        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

        saveBtn.addEventListener('click', function () {
            if (!activeBox) return;
            const box = activeBox;
            const note = textarea.value.trim();
            saveBtn.disabled = true;
            fetch(box.dataset.url, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ note: note }),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    const hasNote = !!data.therapist_note;
                    box.querySelector('.mc-note-display').textContent = data.therapist_note || '';
                    box.querySelector('.mc-note-view').style.display = hasNote ? '' : 'none';
                    box.querySelector('.mc-note-add-btn').style.display = hasNote ? 'none' : '';
                    closeModal();
                })
                .catch(function () { alert('Could not save the note — try again.'); })
                .finally(function () { saveBtn.disabled = false; });
        });
    })();
    </script>
@endsection
