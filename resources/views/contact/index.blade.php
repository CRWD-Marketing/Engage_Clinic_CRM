@extends('layouts.admin-sidebar')

@section('title', 'Contact Us · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-fill-height content-full-width')

@section('content')
<style>
    .ct-wrap { flex: 1; display: flex; min-height: 0; overflow-x: auto; margin: -22px -28px; }

    .ct-sidebar { width: 330px; border-right: 1px solid #EBE4DA; background: #FFFDFA; display: flex; flex-direction: column; flex-shrink: 0; }
    .ct-sidebar-head { padding: 16px 18px; border-bottom: 1px solid #EBE4DA; display: flex; flex-direction: column; gap: 12px; }
    .ct-sidebar-title { font: 600 18px 'Baloo 2'; color: #16436E; }
    .ct-sidebar-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
    .ct-filter-bar { display: flex; gap: 6px; flex-wrap: wrap; }
    .ct-filter-pill {
        border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px;
        padding: 5px 12px; font: 700 11px 'Nunito Sans'; text-decoration: none; white-space: nowrap;
        transition: background 0.12s ease, color 0.12s ease;
    }
    .ct-filter-pill:hover { background: #EFE8DD; }
    .ct-filter-pill.is-active { background: #2B3A4C; color: #fff; border-color: #2B3A4C; }
    .ct-legend { display: flex; flex-wrap: wrap; gap: 10px 14px; padding-top: 2px; }
    .ct-legend-title { font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; }
    .ct-legend-item { display: flex; align-items: center; gap: 6px; }
    .ct-legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .ct-legend-label { font: 700 11px 'Nunito Sans'; color: #5A6B7E; }
    .ct-list { flex: 1; overflow-y: auto; }
    .ct-row {
        display: flex; gap: 11px; align-items: center; padding: 13px 18px; cursor: pointer;
        border-bottom: 1px solid #F3EDE3; background: transparent; text-decoration: none; transition: background-color .12s ease;
    }
    .ct-row:hover { background: #F5EFE7; }
    .ct-row.is-active { background: #F5EFE7; }
    .ct-avatar {
        width: 36px; height: 36px; border-radius: 50%; color: #fff; display: flex; align-items: center;
        justify-content: center; font: 600 13px 'Baloo 2'; flex-shrink: 0;
    }
    .ct-row-name { font: 800 13.5px 'Nunito Sans'; color: #2B3A4C; }
    .ct-row-sub { font: 600 11.5px 'Nunito Sans'; color: #98897A; }
    .ct-chip { border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap; flex-shrink: 0; }
    .ct-empty-list { padding: 48px 20px; text-align: center; color: #98897A; font: 700 12.5px 'Nunito Sans'; }

    .ct-detail { flex: 1; overflow-y: auto; padding: 24px 28px; display: flex; flex-direction: column; gap: 18px; min-width: 420px; }
    .ct-detail-empty { flex: 1; display: flex; align-items: center; justify-content: center; color: #98897A; font: 700 13px 'Nunito Sans'; }

    .ct-header { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
    .ct-header-avatar { width: 52px; height: 52px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font: 600 19px 'Baloo 2'; flex-shrink: 0; }
    .ct-header-name { font: 600 22px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .ct-header-sub { font: 600 13px 'Nunito Sans'; color: #98897A; }
    .ct-header-contact { font: 800 13px 'Nunito Sans'; color: #2B3A4C; text-align: right; }
    .ct-header-contact-sub { font: 600 12px 'Nunito Sans'; color: #98897A; text-align: right; }
    .ct-action-btn {
        background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 12px 20px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; flex: none; white-space: nowrap;
        box-sizing: border-box; box-shadow: 0 4px 14px rgba(200,53,95,0.28); transition: background 0.15s ease, transform 0.05s ease;
    }
    .ct-action-btn:hover { background: #A82348; }
    .ct-action-btn:active { transform: translateY(1px); }
    .ct-action-link {
        background: #E3F1E9; color: #1F7A4D; border-radius: 10px; padding: 12px 20px;
        font: 800 12.5px 'Nunito Sans'; text-decoration: none; white-space: nowrap; box-sizing: border-box;
    }

    .ct-chips-row { display: flex; gap: 8px; flex-wrap: wrap; }

    .ct-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; align-items: start; }
    .ct-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
    .ct-card-title { font: 600 16px 'Baloo 2'; color: #16436E; }
    .ct-card-empty { font: 600 12.5px/1.5 'Nunito Sans'; color: #98897A; }
    .ct-message-body { font: 600 13.5px/1.6 'Nunito Sans'; color: #2B3A4C; white-space: pre-wrap; }

    .ct-field-row { display: flex; flex-direction: column; gap: 2px; padding: 8px 0; border-bottom: 1px solid #F3EDE3; }
    .ct-field-row:last-child { border-bottom: none; }
    .ct-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; }
    .ct-field-value { font: 800 13px 'Nunito Sans'; color: #2B3A4C; }

    .ct-status-select {
        padding: 9px 12px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE;
        font: 700 12px 'Nunito Sans'; color: #2B3A4C; outline: none; cursor: pointer;
    }
    .ct-delete-btn {
        background: none; border: none; color: #B91C1C; font: 700 12px 'Nunito Sans'; cursor: pointer;
        text-align: left; padding: 4px 0;
    }
    .ct-delete-btn:hover { text-decoration: underline; }

    @media (max-width: 1000px) {
        .ct-wrap { flex-direction: column; overflow-x: hidden; }
        .ct-sidebar { width: 100%; max-height: 240px; border-right: none; border-bottom: 1px solid #EBE4DA; }
        .ct-detail { min-width: 0; }
    }

    @media (max-width: 900px) {
        .ct-grid { grid-template-columns: 1fr; }
    }
</style>

@php
    $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];
    $statusColors = [
        'new' => ['bg' => '#E7F0FB', 'fg' => '#2563AE'],
        'contacted' => ['bg' => '#FDF3D6', 'fg' => '#96751B'],
        'converted' => ['bg' => '#E4F6EB', 'fg' => '#1E8A4C'],
        'closed' => ['bg' => '#EFEBE3', 'fg' => '#7A6E60'],
    ];
@endphp

<div class="ct-wrap">

    <!-- Left Sidebar - Submission List -->
    <div class="ct-sidebar">
        <div class="ct-sidebar-head">
            <div>
                <div class="ct-sidebar-title">Contact Us</div>
                <div class="ct-sidebar-sub">{{ $contacts->count() }} {{ Str::plural('submission', $contacts->count()) }} &middot; {{ $newCount }} new</div>
            </div>
            <div class="ct-filter-bar">
                <a href="{{ route('contacts.index') }}" class="ct-filter-pill {{ !request('status') || request('status') === 'all' ? 'is-active' : '' }}">All</a>
                @foreach ($statuses as $key => $label)
                    <a href="{{ route('contacts.index', ['status' => $key]) }}" class="ct-filter-pill {{ request('status') === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="ct-legend">
                <span class="ct-legend-title">Status legend</span>
                @foreach ($statuses as $key => $label)
                    @php $legendColor = $statusColors[$key] ?? $statusColors['closed']; @endphp
                    <span class="ct-legend-item">
                        <span class="ct-legend-dot" style="background: {{ $legendColor['fg'] }};"></span>
                        <span class="ct-legend-label">{{ $label }}</span>
                    </span>
                @endforeach
            </div>
        </div>
        <div class="ct-list">
            @forelse ($contacts as $contact)
                @php
                    $isActive = $activeContact && $activeContact->id === $contact->id;
                    $colors = $statusColors[$contact->status] ?? $statusColors['closed'];
                @endphp
                <a href="{{ route('contacts.index', ['contact' => $contact->id] + (request('status') ? ['status' => request('status')] : [])) }}" class="ct-row {{ $isActive ? 'is-active' : '' }}">
                    <div class="ct-avatar" style="background: {{ $avatarColor($contact->id) }};">
                        {{ strtoupper(substr($contact->name ?? '?', 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="ct-row-name">{{ $contact->name ?: 'Unknown' }}</div>
                        <div class="ct-row-sub">{{ $contact->child_age ? 'Age '.$contact->child_age : ($contact->interested_in ?: 'No details provided') }}</div>
                    </div>
                    <span class="ct-chip" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">{{ $contact->status_label }}</span>
                </a>
            @empty
                <div class="ct-empty-list">
                    @if (request('status') && request('status') !== 'all')
                        No {{ strtolower($statuses[request('status')] ?? request('status')) }} submissions.
                    @else
                        No submissions yet. Messages sent through the website contact form will show up here.
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <!-- Right Side - Submission Detail -->
    <div class="ct-detail">
        @if ($activeContact)
            @php $colors = $statusColors[$activeContact->status] ?? $statusColors['closed']; @endphp
            <div class="ct-header">
                <div class="ct-header-avatar" style="background: {{ $avatarColor($activeContact->id) }};">
                    {{ strtoupper(substr($activeContact->name ?? '?', 0, 1)) }}
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div class="ct-header-name">{{ $activeContact->name ?: 'Unknown' }}</div>
                    <div class="ct-header-sub">
                        {{ $activeContact->child_age ? 'Age '.$activeContact->child_age.' · ' : '' }}received {{ $activeContact->created_at->diffForHumans() }}
                    </div>
                </div>
                <div>
                    <div class="ct-header-contact">{{ $activeContact->phone ?: '—' }}</div>
                    <div class="ct-header-contact-sub">{{ $activeContact->email ?: 'No email on file' }}</div>
                </div>

                @if ($activeContact->status === 'converted' && $activeContact->lead)
                    <a href="{{ route('leads.index') }}" class="ct-action-link">Converted &#10003;</a>
                @else
                    <form action="{{ route('contacts.convert-to-lead', $activeContact->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="ct-action-btn">Convert to Lead</button>
                    </form>
                @endif
            </div>

            <div class="ct-chips-row">
                @if ($activeContact->interested_in)
                    <span class="ct-chip" style="background: #F9E7EC; color: #C8355F; padding: 5px 12px; font-size: 12px;">{{ $activeContact->interested_in }}</span>
                @endif
                <span class="ct-chip" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }}; padding: 5px 12px; font-size: 12px;">{{ $activeContact->status_label }}</span>
            </div>

            <div class="ct-grid">
                <!-- Left column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div class="ct-card">
                        <div class="ct-card-title">Message</div>
                        @if ($activeContact->message)
                            <div class="ct-message-body">{{ $activeContact->message }}</div>
                        @else
                            <div class="ct-card-empty">No message provided.</div>
                        @endif
                    </div>
                </div>

                <!-- Right column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div class="ct-card">
                        <div class="ct-card-title">Contact details</div>
                        <div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Phone</span>
                                <span class="ct-field-value">{{ $activeContact->phone ?: '—' }}</span>
                            </div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Email</span>
                                <span class="ct-field-value">{{ $activeContact->email ?: '—' }}</span>
                            </div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Child's age</span>
                                <span class="ct-field-value">{{ $activeContact->child_age ?: '—' }}</span>
                            </div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Received</span>
                                <span class="ct-field-value">{{ $activeContact->created_at->format('M j, Y · H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="ct-card">
                        <div class="ct-card-title">Status</div>
                        <form action="{{ route('contacts.update-status', $activeContact->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="ct-status-select" style="width: 100%;" {{ $activeContact->status === 'converted' ? 'disabled' : '' }}>
                                <option value="new" {{ $activeContact->status === 'new' ? 'selected' : '' }}>New</option>
                                <option value="contacted" {{ $activeContact->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                <option value="closed" {{ $activeContact->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </form>
                        <form action="{{ route('contacts.destroy', $activeContact->id) }}" method="POST" onsubmit="return confirm('Delete this submission? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ct-delete-btn">Delete submission</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="ct-detail-empty">Select a submission to see its details.</div>
        @endif
    </div>
</div>
@endsection
