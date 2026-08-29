@extends('layouts.admin-sidebar')

@section('title', 'Dashboard · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')
<link rel="icon" type="image/png" href="/uploads/engage.png">
@section('content')
    @php
        $activityColors = [
            'ABA' => ['bg' => '#F9E7EC', 'color' => '#C8355F'],
            'Speech' => ['bg' => '#E7EFF7', 'color' => '#24619C'],
            'OT' => ['bg' => '#F7EEDD', 'color' => '#B97F24'],
            'Assessment' => ['bg' => '#EEE9F7', 'color' => '#6E4FA8'],
            'Supervision' => ['bg' => '#E3F1E9', 'color' => '#2E7D5B'],
            'Parent training' => ['bg' => '#FBF3E4', 'color' => '#8A5A10'],
        ];
        $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
        $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];
    @endphp

    <style>
        .db-topbar { display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; margin: -22px -28px 18px -28px; }
        .db-stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 18px; }
        .db-stat-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px; min-width: 0; }
        .db-main-grid { display: grid; grid-template-columns: 1.55fr 1fr; gap: 18px; align-items: start; }
        .db-schedule-row { display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3; flex-wrap: wrap; }
        .db-card-head { flex-wrap: wrap; row-gap: 4px; }

        @media (max-width: 900px) {
            .db-stats-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .db-main-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .db-topbar { padding: 14px 16px; flex-direction: column; align-items: stretch; gap: 10px; margin: -22px -28px 14px -28px; }
            .db-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; margin-bottom: 14px; }
            .db-stat-card { padding: 10px 12px; }
            .db-stat-card > div:nth-child(2) { font-size: 20px !important; }
            .db-stat-card > div:nth-child(1) { font-size: 9.5px !important; }
            .db-stat-card > div:nth-child(3) { font-size: 10.5px !important; }
            .db-main-grid { gap: 12px; }
            .db-schedule-row { padding: 9px 14px; gap: 8px; }
            .db-card-title { font-size: 14px !important; }
        }
        @media (max-width: 380px) {
            .db-stats-grid { grid-template-columns: 1fr; }
        }
    </style>

    <!-- Top Bar -->
    <div class="db-topbar">
        <div style="flex: 1; min-width: 0;">
            <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Good morning, {{ $user->full_name }}</div>
            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">{{ date('l, j F Y') }} · Khalifa City, Abu Dhabi</div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="db-stats-grid">
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Sessions today</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $sessionsTodayCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #8A7D6C;">{{ $roomsInUseCount }} {{ Str::plural('room', $roomsInUseCount) }} · {{ $activeTherapistsCount }} {{ Str::plural('therapist', $activeTherapistsCount) }}</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Attendance · 30d</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $attendanceRate !== null ? $attendanceRate . '%' : '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: {{ $attendanceDelta > 0 ? '#2E7D5B' : ($attendanceDelta < 0 ? '#B3261E' : '#8A7D6C') }};">
                @if ($attendanceDelta === null) Not enough data yet @else {{ $attendanceDelta > 0 ? '▲' : ($attendanceDelta < 0 ? '▼' : '–') }} {{ abs($attendanceDelta) }} pts @endif
            </div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Notes pending review</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $pendingNotesCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $overdueNotesCount > 0 ? $overdueNotesCount . ' overdue 48h+' : 'None overdue' }}</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Active treatment plans</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $activeTreatmentPlansCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $plansDueForReviewCount > 0 ? $plansDueForReviewCount . ' due for review' : 'None due soon' }}</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Therapist caseload</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $avgCaseloadPerTherapist ?? '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #8A7D6C;">avg. patients / therapist</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Waitlist</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $waitlistCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $waitlistCount > 0 ? 'avg. wait ' . $avgWaitWeeks . ' wks' : 'No one waiting' }}</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="db-main-grid">
        <!-- Left Column -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <!-- Today's Schedule -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                <div class="db-card-head" style="display: flex; align-items: baseline; gap: 10px; padding: 16px 20px 10px;">
                    <div class="db-card-title" style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Today's schedule</div>
                    <a href="{{ route('calendar.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">Open calendar →</a>
                </div>
                @forelse ($todaySessionsList as $session)
                    @php
                        $colors = $activityColors[$session->activity_type] ?? ['bg' => '#EEF0F2', 'color' => '#6B7A8C'];
                        $nowTime = now()->format('H:i:s');
                        if ($session->status === 'completed') {
                            $statusLabel = 'Completed'; $statusColors = ['bg' => '#F3EDE3', 'color' => '#98897A'];
                        } elseif ($session->status === 'cancelled') {
                            $statusLabel = 'Cancelled'; $statusColors = ['bg' => '#F9E4E2', 'color' => '#B3261E'];
                        } elseif ($session->status === 'no_show') {
                            $statusLabel = 'No-show'; $statusColors = ['bg' => '#F9E4E2', 'color' => '#B3261E'];
                        } elseif ($session->start_time <= $nowTime && $session->end_time >= $nowTime) {
                            $statusLabel = 'In Session'; $statusColors = ['bg' => '#F9E7EC', 'color' => '#C8355F'];
                        } elseif ($session->end_time < $nowTime) {
                            $statusLabel = 'Awaiting update'; $statusColors = ['bg' => '#F7EEDD', 'color' => '#B97F24'];
                        } else {
                            $statusLabel = 'Upcoming'; $statusColors = ['bg' => '#EEF0F2', 'color' => '#6B7A8C'];
                        }
                    @endphp
                    <div class="db-schedule-row">
                        <div style="width: 48px; font: 600 14px 'Baloo 2'; color: #16436E;">{{ \Illuminate\Support\Carbon::parse($session->start_time)->format('G:i') }}</div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">{{ $session->child->child_name ?? $session->patient_name ?? 'Unknown' }}</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">{{ $session->therapist ? $session->therapist->first_name . ' ' . $session->therapist->last_name : 'Unassigned' }}{{ $session->room ? ' · ' . $session->room : '' }}</div>
                        </div>
                        <span style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }}; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">{{ $session->activity_type }}</span>
                        <span style="background: {{ $statusColors['bg'] }}; color: {{ $statusColors['color'] }}; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">{{ $statusLabel }}</span>
                    </div>
                @empty
                    <div style="padding: 24px 20px; text-align: center; font: 700 12.5px 'Nunito Sans'; color: #98897A;">No sessions scheduled today.</div>
                @endforelse
            </div>

            <!-- Session Notes Awaiting Sign-off -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 20px;">
                <div class="db-card-head" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                    <div class="db-card-title" style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Session notes awaiting sign-off</div>
                    <a href="{{ route('patient.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">Review all →</a>
                </div>
                @if ($notesAwaitingSignoff->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A; padding-top: 8px; border-top: 1px solid #F3EDE3;">Nothing awaiting sign-off.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 9px;">
                        @foreach ($notesAwaitingSignoff as $note)
                            @php
                                $hoursOld = $note->created_at->diffInHours(now());
                                $isOverdue = $hoursOld >= 48;
                            @endphp
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-top: 1px solid #F3EDE3;">
                                <div>
                                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">{{ $note->patient->lead->child_name ?? 'Unknown patient' }}</div>
                                    <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">{{ $note->author_name }} · {{ $note->created_at->format('d M, g:ia') }}</div>
                                </div>
                                <span style="background: {{ $isOverdue ? '#F9E3EA' : '#F7EEDD' }}; color: {{ $isOverdue ? '#C8355F' : '#B97F24' }}; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">{{ $isOverdue ? 'Overdue ' . floor($hoursOld / 24) . 'd' : 'Pending ' . $hoursOld . 'h' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <!-- Flagged Therapist Notes -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                <div style="display: flex; align-items: center; gap: 8px; padding: 16px 20px 10px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #C8355F;"></span>
                    <div class="db-card-title" style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Flagged for supervisor review</div>
                    <a href="{{ route('patient.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">Open →</a>
                </div>
                @forelse ($flaggedNotes as $note)
                    <div style="display: flex; gap: 11px; align-items: center; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ $avatarColor($note->user_id) }}; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">{{ strtoupper(substr($note->author_name, 0, 1)) }}</div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">{{ $note->author_name }}</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Flagged note on {{ $note->patient->lead->child_name ?? 'patient' }}{{ $note->flag_reason ? ' · ' . $note->flag_reason : '' }}</div>
                        </div>
                        <span style="background: #C8355F; color: white; border-radius: 9px; padding: 1px 7px; font: 800 10.5px 'Nunito Sans'; flex-shrink: 0;">!</span>
                    </div>
                @empty
                    <div style="padding: 24px 20px; text-align: center; font: 700 12.5px 'Nunito Sans'; color: #98897A;">Nothing flagged right now.</div>
                @endforelse
            </div>

            <!-- Treatment Plans Due for Review -->
            <div style="background: #FBF3E4; border: 1px solid #EBDCBB; border-radius: 14px; padding: 16px 20px;">
                <div style="font: 600 15px 'Baloo 2'; color: #8A5A10; margin-bottom: 8px;">Treatment plans due for review</div>
                @if ($treatmentPlansDueList->isEmpty())
                    <div style="font: 600 12px 'Nunito Sans'; color: #8A5A10;">Nothing due in the next 7 days.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($treatmentPlansDueList as $patient)
                            @php $daysDue = today()->diffInDays($patient->treatment_plan_review_due_at, false); @endphp
                            <div style="display: flex; justify-content: space-between; gap: 10px;">
                                <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">{{ $patient->lead->child_name ?? 'Unknown' }}</div>
                                <div style="font: 600 12px 'Nunito Sans'; color: #8A5A10;">{{ $daysDue >= 0 ? 'Review due ' . $patient->treatment_plan_review_due_at->format('d M') : 'Review overdue ' . abs($daysDue) . 'd' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Waitlist -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 20px;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 10px;">Waitlist — next up</div>
                @if ($waitlistNextUp->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No one on the waitlist.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach ($waitlistNextUp as $i => $entry)
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="font: 600 13px 'Baloo 2'; color: #C8355F; width: 20px;">{{ $i + 1 }}</div>
                                <div style="flex: 1;">
                                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">{{ $entry->child_name }}{{ $entry->child_age ? ' · ' . $entry->child_age : '' }}</div>
                                    <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">{{ $entry->programme ?? 'Programme TBD' }}{{ $entry->hours_per_week ? ' ' . $entry->hours_per_week . 'h/wk' : '' }} · waiting {{ $entry->waitingWeeks() }} {{ Str::plural('wk', $entry->waitingWeeks()) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
