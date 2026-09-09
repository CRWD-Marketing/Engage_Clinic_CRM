@extends('layouts.admin-sidebar')

@section('title', 'Voice Calls · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-fill-height content-full-width')

@section('content')
    @php
        $avatarColor = fn ($seed) => \App\Models\WhatsappContact::avatarColor($seed);
        $statusColors = [
            'in_progress' => ['#1FA855', '#E9F7EF'],
            'completed' => ['#24619C', '#EAF1F8'],
            'escalated' => ['#C8355F', '#FBEAF0'],
            'failed' => ['#98897A', '#F0ECE5'],
            'no_answer' => ['#98897A', '#F0ECE5'],
        ];
        $sentimentColors = [
            'positive' => '#1FA855',
            'neutral' => '#8A7D6C',
            'negative' => '#B97F24',
            'distressed' => '#C8355F',
        ];
    @endphp

    <style>
        .vc-row { transition: background 0.12s ease; cursor: pointer; }
        .vc-row:hover { background: #FAF5EC; }
        .vc-row.is-active { background: #F5EFE7; }
        .vc-messages::-webkit-scrollbar, .vc-list::-webkit-scrollbar { width: 6px; }
        .vc-messages::-webkit-scrollbar-thumb, .vc-list::-webkit-scrollbar-thumb { background: #E2DACE; border-radius: 3px; }
        .vc-messages {
            background-image: radial-gradient(rgba(43, 58, 76, 0.05) 1px, transparent 1px);
            background-size: 22px 22px;
        }
        .vc-status-pill { font: 700 10px 'Nunito Sans'; letter-spacing: 0.4px; text-transform: uppercase; padding: 3px 9px; border-radius: 20px; }
    </style>

    <div style="flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px;">

        <!-- Left - call list -->
        <div class="vc-list" style="width: 320px; min-height: 0; overflow-y: auto; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0;">
            <div style="padding: 16px 18px; border-bottom: 1px solid #EBE4DA;">
                <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Voice Calls</div>
                <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-top: 1px;">Live-answered by the AI Employee</div>
            </div>

            @forelse ($sessions as $session)
                @php [$fg, $bg] = $statusColors[$session->status] ?? ['#98897A', '#F0ECE5']; @endphp
                <a href="{{ route('voice_calls.index', ['session' => $session->id]) }}"
                   class="vc-row {{ $activeSession && $activeSession->id === $session->id ? 'is-active' : '' }}"
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 18px; border-bottom: 1px solid #F3EEE5; text-decoration: none;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: {{ $avatarColor($session->from_number) }}; display: flex; align-items: center; justify-content: center; color: #FFFDFA; font: 700 14px 'Nunito Sans'; flex-shrink: 0;">
                        <i class="fas fa-phone" style="font-size: 13px;"></i>
                    </div>
                    <div style="min-width: 0; flex: 1;">
                        <div style="font: 700 13.5px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $session->from_number }}</div>
                        <div style="font: 500 11.5px 'Nunito Sans'; color: #98897A; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $session->started_at->format('M j, g:i A') }}</div>
                    </div>
                    <span class="vc-status-pill" style="color: {{ $fg }}; background: {{ $bg }};">{{ str_replace('_', ' ', $session->status) }}</span>
                </a>
            @empty
                <div style="padding: 24px 18px; font: 500 12.5px 'Nunito Sans'; color: #98897A;">No calls yet.</div>
            @endforelse
        </div>

        <!-- Right - transcript + summary -->
        <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; background: #FBF8F2;">
            @if ($activeSession)
                <div style="padding: 16px 24px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <div style="font: 700 15px 'Nunito Sans'; color: #16436E;">{{ $activeSession->from_number }} → {{ $activeSession->to_number }}</div>
                        <div style="font: 500 11.5px 'Nunito Sans'; color: #98897A; margin-top: 2px;">
                            {{ $activeSession->started_at->format('M j, Y g:i A') }}
                            @if ($activeSession->duration_seconds)
                                · {{ gmdate('i:s', $activeSession->duration_seconds) }}
                            @endif
                            @if ($activeSession->escalated)
                                · <span style="color: #C8355F;">escalated{{ $activeSession->escalation_reason ? ' — '.$activeSession->escalation_reason : '' }}</span>
                            @endif
                        </div>
                    </div>
                    @if ($activeSession->contact && $activeSession->contact->needs_human_attention)
                        <span class="vc-status-pill" style="color: #C8355F; background: #FBEAF0;">Needs attention</span>
                    @endif
                </div>

                @if ($activeSession->summary)
                    <div style="padding: 14px 24px; background: #FFFDFA; border-bottom: 1px solid #EBE4DA; display: flex; flex-wrap: wrap; gap: 18px; font: 500 12.5px 'Nunito Sans'; color: #2B3A4C;">
                        <div><strong>Intent:</strong> {{ $activeSession->summary['intent'] ?? '—' }}</div>
                        <div><strong>Outcome:</strong> {{ $activeSession->summary['outcome'] ?? '—' }}</div>
                        <div><strong>Sentiment:</strong> <span style="color: {{ $sentimentColors[$activeSession->summary['sentiment'] ?? ''] ?? '#2B3A4C' }};">{{ $activeSession->summary['sentiment'] ?? '—' }}</span></div>
                        <div><strong>Follow-up:</strong> {{ ($activeSession->summary['follow_up_required'] ?? false) ? 'Yes' : 'No' }}</div>
                        @if (!empty($activeSession->summary['notes']))
                            <div style="width: 100%; color: #5C5142;">{{ $activeSession->summary['notes'] }}</div>
                        @endif
                    </div>
                @elseif ($activeSession->summary_status === 'pending' || $activeSession->summary_status === 'processing')
                    <div style="padding: 10px 24px; background: #FFFDFA; border-bottom: 1px solid #EBE4DA; font: 500 12px 'Nunito Sans'; color: #98897A;">Summary generating…</div>
                @endif

                <div class="vc-messages" style="flex: 1; min-height: 0; overflow-y: auto; padding: 20px 24px; display: flex; flex-direction: column; gap: 10px;">
                    @forelse ($transcript as $message)
                        @php $isInbound = $message->direction === 'inbound'; @endphp
                        <div style="display: flex; justify-content: {{ $isInbound ? 'flex-start' : 'flex-end' }};">
                            <div style="max-width: 60%; padding: 9px 13px; border-radius: 14px; background: {{ $isInbound ? '#FFFDFA' : '#E9F7EF' }}; box-shadow: 0 1px 3px rgba(43,58,76,0.06);">
                                <div style="font: 500 13.5px/1.4 'Nunito Sans'; color: #2B3A4C;">{{ $message->body }}</div>
                                <div style="font: 600 10px 'Nunito Sans'; color: #98897A; margin-top: 4px;">{{ $isInbound ? 'Caller' : 'AI Employee' }} · {{ $message->sent_at->format('g:i:s A') }}</div>
                            </div>
                        </div>
                    @empty
                        <div style="font: 500 12.5px 'Nunito Sans'; color: #98897A;">No transcript recorded for this call.</div>
                    @endforelse
                </div>
            @else
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; color: #98897A; font: 500 13px 'Nunito Sans';">Select a call to view its transcript.</div>
            @endif
        </div>
    </div>
@endsection
