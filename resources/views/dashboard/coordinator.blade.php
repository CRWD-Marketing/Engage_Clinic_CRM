@extends('layouts.admin-sidebar')
@section('title', 'Dashboard · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

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
    <!-- Top Bar -->
    <div style="display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; margin: -22px -28px 18px -28px;">
        <div style="flex: 1; min-width: 0;">
            <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Good morning, {{ $user->full_name }}</div>
            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">{{ date('l, j F Y') }} · Khalifa City, Abu Dhabi</div>
        </div>
        <input
            type="text"
            placeholder="Search families, leads, appointments…"
            style="width: 260px; padding: 10px 14px; border: 1px solid #E2DACE; border-radius: 10px; background: #F6F3EE; font: 600 13px 'Nunito Sans'; color: #2B3A4C; outline: none;"
        >
        <a href="{{ route('leads.index') }}" style="background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; text-decoration: none; display: inline-block;">+ New Lead</a>
    </div>

    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 18px;">
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">New leads · week</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $newLeadsCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: {{ $newLeadsDelta > 0 ? '#2E7D5B' : ($newLeadsDelta < 0 ? '#B3261E' : '#8A7D6C') }};">{{ $newLeadsDelta > 0 ? '▲' : ($newLeadsDelta < 0 ? '▼' : '–') }} {{ abs($newLeadsDelta) }}% vs last week</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Intake calls pending</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $pendingIntakeCallsCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $overdueIntakeCallsCount > 0 ? $overdueIntakeCallsCount . ' overdue' : 'None overdue' }}</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">No-shows · week</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $noShowsCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $noShowFollowUpsNeeded > 0 ? $noShowFollowUpsNeeded . ' need follow-up' : 'All followed up' }}</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Waitlist</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $waitlistCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">{{ $openingsThisWeek }} openings this week</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Sessions today</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $sessionsTodayCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #8A7D6C;">{{ $roomsInUseCount }} {{ Str::plural('room', $roomsInUseCount) }} · {{ $activeTherapistsCount }} {{ Str::plural('therapist', $activeTherapistsCount) }}</div>
        </div>
        <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px;">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Attendance · 30d</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $attendanceRate !== null ? $attendanceRate . '%' : '—' }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: {{ $attendanceDelta > 0 ? '#2E7D5B' : ($attendanceDelta < 0 ? '#B3261E' : '#8A7D6C') }};">
                @if ($attendanceDelta === null) Not enough data yet @else {{ $attendanceDelta > 0 ? '▲' : ($attendanceDelta < 0 ? '▼' : '–') }} {{ abs($attendanceDelta) }} pts @endif
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div style="display: grid; grid-template-columns: 1.55fr 1fr; gap: 18px; align-items: start;">
        <!-- Left Column -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <!-- Today's Schedule -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                <div style="display: flex; align-items: baseline; gap: 10px; padding: 16px 20px 10px;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Today's schedule</div>
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
                    <div style="display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
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

            <!-- Intake Pipeline -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 20px;">
                <div style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Intake pipeline — awaiting scheduling</div>
                    <a href="{{ route('leads.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">View leads →</a>
                </div>
                @if ($intakePipeline->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A; padding-top: 8px; border-top: 1px solid #F3EDE3;">No leads waiting on an intake call.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 9px;">
                        @foreach ($intakePipeline as $lead)
                            @php
                                $isOverdue = $lead->follow_up_due_at ? $lead->follow_up_due_at->isPast() : $lead->created_at->lt(now()->subDays(2));
                            @endphp
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 8px 0; border-top: 1px solid #F3EDE3;">
                                <div>
                                    <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">{{ $lead->parent_guardian_name ?? $lead->child_name }}</div>
                                    <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">{{ $lead->interested_in ?? 'Interest not captured' }} · {{ $lead->source }}</div>
                                </div>
                                <span style="background: {{ $isOverdue ? '#F9E3EA' : '#F7EEDD' }}; color: {{ $isOverdue ? '#C8355F' : '#B97F24' }}; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">{{ $isOverdue ? 'Overdue' : 'Awaiting call' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <!-- WhatsApp Inbox -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                <div style="display: flex; align-items: center; gap: 8px; padding: 16px 20px 10px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #1FA855;"></span>
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">WhatsApp inbox</div>
                    <a href="{{ route('whatsapp.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">Open →</a>
                </div>
                @forelse ($whatsappInbox as $contact)
                    <a href="{{ route('whatsapp.index', ['contact' => $contact->id]) }}" style="display: flex; gap: 11px; align-items: center; padding: 11px 20px; border-top: 1px solid #F3EDE3; cursor: pointer; text-decoration: none;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ $avatarColor($contact->id) }}; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">{{ strtoupper(substr($contact->name ?? '?', 0, 1)) }}</div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">{{ $contact->name ?? 'Unknown contact' }}</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $contact->last_message_preview ?? 'No messages yet' }}</div>
                        </div>
                        @if ($contact->unread_count > 0)
                            <span style="background: #1FA855; color: white; border-radius: 9px; padding: 1px 7px; font: 800 10.5px 'Nunito Sans'; flex-shrink: 0;">{{ $contact->unread_count }}</span>
                        @endif
                    </a>
                @empty
                    <div style="padding: 24px 20px; text-align: center; font: 700 12.5px 'Nunito Sans'; color: #98897A;">No conversations yet.</div>
                @endforelse
            </div>

            <!-- No-show Follow-ups -->
            <div style="background: #FBF3E4; border: 1px solid #EBDCBB; border-radius: 14px; padding: 16px 20px;">
                <div style="font: 600 15px 'Baloo 2'; color: #8A5A10; margin-bottom: 8px;">No-shows needing follow-up</div>
                @if ($noShowFollowUpList->isEmpty())
                    <div style="font: 600 12px 'Nunito Sans'; color: #8A5A10;">No outstanding no-show follow-ups.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($noShowFollowUpList as $session)
                            <div style="display: flex; justify-content: space-between; gap: 10px;">
                                <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">{{ $session->child->child_name ?? $session->patient_name ?? 'Unknown' }}</div>
                                <div style="font: 600 12px 'Nunito Sans'; color: #8A5A10;">Missed {{ $session->activity_type }} · {{ $session->session_date->format('D') }} {{ \Illuminate\Support\Carbon::parse($session->start_time)->format('G:i') }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
