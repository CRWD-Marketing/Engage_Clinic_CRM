@extends('layouts.admin-sidebar')

@section('title', 'Dashboard · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    @php
        $sourceColors = [
            'WhatsApp' => '#1FA855', 'Instagram' => '#C13584', 'Website' => '#24619C',
            'Referral' => '#B97F24', 'Google' => '#6E4FA8', 'Google Ads' => '#6E4FA8', 'Facebook' => '#1877F2',
        ];
        $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
        $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];
    @endphp

    <style>
        .db-topbar { display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; margin: -22px -28px 18px -28px; }
        .db-topbar-btn { background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; text-decoration: none; display: inline-block; white-space: nowrap; }
        .db-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 18px; }
        .db-stat-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px; min-width: 0; }
        .db-main-grid { display: grid; grid-template-columns: 1.55fr 1fr; gap: 18px; align-items: start; }
        .db-card-head { flex-wrap: wrap; row-gap: 4px; }

        @media (max-width: 900px) {
            .db-stats-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
            .db-main-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .db-topbar { padding: 14px 16px; flex-direction: column; align-items: stretch; gap: 10px; margin: -22px -28px 14px -28px; }
            .db-topbar-btn { text-align: center; }
            .db-stats-grid { grid-template-columns: repeat(1, 1fr); gap: 8px; margin-bottom: 14px; }
            .db-stat-card { padding: 10px 12px; }
            .db-stat-card > div:nth-child(2) { font-size: 20px !important; }
            .db-stat-card > div:nth-child(1) { font-size: 9.5px !important; }
            .db-stat-card > div:nth-child(3) { font-size: 10.5px !important; }
            .db-main-grid { gap: 12px; }
            .db-card-title { font-size: 14px !important; }
        }
    </style>

    <!-- Top Bar -->
    <div class="db-topbar">
        <div style="flex: 1; min-width: 0;">
            <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Good morning, {{ $user->full_name }}</div>
            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">{{ date('l, j F Y') }} · Khalifa City, Abu Dhabi</div>
        </div>
        <a href="{{ route('leads.index') }}" class="db-topbar-btn">+ New Lead</a>
    </div>

    <!-- Stats Cards -->
    <div class="db-stats-grid">
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">New leads · week</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $newLeadsCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: {{ $newLeadsDelta > 0 ? '#2E7D5B' : ($newLeadsDelta < 0 ? '#B3261E' : '#8A7D6C') }};">{{ $newLeadsDelta > 0 ? '▲' : ($newLeadsDelta < 0 ? '▼' : '–') }} {{ abs($newLeadsDelta) }}% vs last week</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">My active leads</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $myActiveLeadsCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #8A7D6C;">assigned to you</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Awaiting first contact</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $awaitingContactCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">clinic-wide</div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="db-main-grid">
        <!-- Left Column -->
        <div style="display: flex; flex-direction: column; gap: 18px;">
            <!-- My Leads Pipeline -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
                <div class="db-card-head" style="display: flex; align-items: baseline; gap: 10px; padding: 16px 20px 10px;">
                    <div class="db-card-title" style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">My leads pipeline</div>
                    <a href="{{ route('leads.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">View all →</a>
                </div>
                @forelse ($myLeadsPipeline as $lead)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 11px 20px; border-top: 1px solid #F3EDE3;">
                        <div>
                            <div style="font: 800 13px 'Nunito Sans'; color: #2B3A4C;">{{ $lead->parent_guardian_name ?? $lead->child_name }}</div>
                            <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">{{ $lead->interested_in ?? 'Interest not captured' }} · {{ $lead->source }}</div>
                        </div>
                        <span style="background: #EEF0F2; color: #6B7A8C; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">{{ $lead->status_label }}</span>
                    </div>
                @empty
                    <div style="padding: 24px 20px; text-align: center; font: 700 12.5px 'Nunito Sans'; color: #98897A;">No leads assigned to you yet.</div>
                @endforelse
            </div>

            <!-- Lead Sources -->
            <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 20px;">
                <div class="db-card-head" style="display: flex; align-items: baseline; gap: 10px; margin-bottom: 12px;">
                    <div class="db-card-title" style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Lead sources · this week</div>
                </div>
                @if ($leadSources->isEmpty())
                    <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No leads captured this week yet.</div>
                @else
                    <div style="display: flex; flex-direction: column; gap: 9px;">
                        @foreach ($leadSources as $row)
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 86px; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">{{ $row->source ?: 'Other' }}</div>
                                <div style="flex: 1; height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                    <div style="width: {{ max(4, round($row->c / $leadSourcesMax * 100)) }}%; height: 100%; background: {{ $sourceColors[$row->source] ?? '#8A7D6C' }}; border-radius: 5px;"></div>
                                </div>
                                <div style="width: 20px; font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right;">{{ $row->c }}</div>
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
                    <div class="db-card-title" style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">WhatsApp inbox</div>
                    <a href="{{ route('whatsapp.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">Open →</a>
                </div>
                @forelse ($whatsappInbox as $contact)
                    <a href="{{ route('whatsapp.index', ['contact' => $contact->id]) }}" style="display: flex; gap: 11px; align-items: center; padding: 11px 20px; border-top: 1px solid #F3EDE3; cursor: pointer; text-decoration: none;">
                        @if ($contact->avatar_url)
                            <img src="{{ $contact->avatar_url }}" alt="" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                        @else
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: {{ $avatarColor($contact->id) }}; color: white; display: flex; align-items: center; justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;">{{ strtoupper(substr($contact->name ?? '?', 0, 1)) }}</div>
                        @endif
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
        </div>
    </div>
@endsection
