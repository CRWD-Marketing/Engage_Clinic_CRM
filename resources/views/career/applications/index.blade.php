@extends('layouts.admin-sidebar')

@section('title', 'Job Application · Engage Clinic')
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
    .ct-add-posting-btn {
        background: #C8355F; color: #fff; border: none; border-radius: 8px; padding: 8px 14px;
        font: 800 12px 'Nunito Sans'; text-decoration: none; white-space: nowrap; flex-shrink: 0;
        display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 14px rgba(200,53,95,0.28);
        transition: background 0.15s ease, transform 0.05s ease;
    }
    .ct-add-posting-btn:hover { background: #A82348; }
    .ct-add-posting-btn:active { transform: translateY(1px); }
    .ct-filter-bar { display: flex; gap: 6px; flex-wrap: wrap; }
    .ct-filter-pill {
        border: 1px solid #E2DACE; background: #F6F3EE; color: #5A6B7E; border-radius: 20px;
        padding: 5px 12px; font: 700 11px 'Nunito Sans'; text-decoration: none; white-space: nowrap;
        transition: background 0.12s ease, color 0.12s ease;
    }
    .ct-filter-pill:hover { background: #EFE8DD; }
    .ct-filter-pill.is-active { background: #2B3A4C; color: #fff; border-color: #2B3A4C; }
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
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; flex: none; white-space: nowrap; text-decoration: none;
        display: inline-flex; align-items: center; gap: 8px;
        box-sizing: border-box; box-shadow: 0 4px 14px rgba(200,53,95,0.28); transition: background 0.15s ease, transform 0.05s ease;
    }
    .ct-action-btn:hover { background: #A82348; }
    .ct-action-btn:active { transform: translateY(1px); }
    .ct-action-btn.is-disabled { background: #D9CFC2; cursor: not-allowed; box-shadow: none; pointer-events: none; }
    .ct-resume-actions { display: flex; gap: 8px; flex-shrink: 0; }
    .ct-action-btn-outline { background: #fff; color: #C8355F; border: 1px solid #E8B9C6; box-shadow: none; }
    .ct-action-btn-outline:hover { background: #FBEEF1; }

    .ct-notes-log { display: flex; flex-direction: column; gap: 10px; max-height: 260px; overflow-y: auto; margin-bottom: 4px; }
    .ct-note-entry { background: #F6F3EE; border-radius: 10px; padding: 10px 12px; }
    .ct-note-meta { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
    .ct-note-author { font: 800 11.5px 'Nunito Sans'; color: #2B3A4C; }
    .ct-note-time { font: 600 11px 'Nunito Sans'; color: #98897A; white-space: nowrap; }
    .ct-note-body { font: 600 12.5px/1.5 'Nunito Sans'; color: #2B3A4C; white-space: pre-wrap; }

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
    .ct-notes-textarea {
        padding: 10px 12px; border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE;
        font: 600 12.5px 'Nunito Sans'; color: #2B3A4C; outline: none; resize: vertical; width: 100%;
    }
    .ct-save-btn {
        background: #2B3A4C; color: #fff; border: none; border-radius: 8px; padding: 8px 14px;
        font: 700 11.5px 'Nunito Sans'; cursor: pointer; align-self: flex-start;
    }
    .ct-save-btn:hover { background: #1E2A38; }
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
        'reviewed' => ['bg' => '#FDF3D6', 'fg' => '#96751B'],
        'interviewing' => ['bg' => '#F1EAFB', 'fg' => '#6E4FA8'],
        'hired' => ['bg' => '#E4F6EB', 'fg' => '#1E8A4C'],
        'rejected' => ['bg' => '#EFEBE3', 'fg' => '#7A6E60'],
    ];
@endphp

<div class="ct-wrap">

    <!-- Left Sidebar - Applicant List -->
    <div class="ct-sidebar">
        <div class="ct-sidebar-head">
            <div style="display:flex; align-items:center; justify-content:space-between; gap: 10px;">
                <div>
                    <div class="ct-sidebar-title">Job Application</div>
                    <div class="ct-sidebar-sub">{{ $applications->count() }} {{ Str::plural('applicant', $applications->count()) }} &middot; {{ $newCount }} new</div>
                </div>
                <a href="{{ route('job-postings.index') }}" class="ct-add-posting-btn">
                    <i class="fas fa-plus"></i> Job Posting
                </a>
            </div>
            <div class="ct-filter-bar">
                <a href="{{ route('job-applications.index') }}" class="ct-filter-pill {{ !request('status') || request('status') === 'all' ? 'is-active' : '' }}">All</a>
                @foreach ($statuses as $key => $label)
                    <a href="{{ route('job-applications.index', ['status' => $key]) }}" class="ct-filter-pill {{ request('status') === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="ct-list">
            @forelse ($applications as $application)
                @php
                    $isActive = $activeApplication && $activeApplication->id === $application->id;
                    $colors = $statusColors[$application->status] ?? $statusColors['rejected'];
                @endphp
                <a href="{{ route('job-applications.index', ['application' => $application->id] + (request('status') ? ['status' => request('status')] : [])) }}" class="ct-row {{ $isActive ? 'is-active' : '' }}">
                    <div class="ct-avatar" style="background: {{ $avatarColor($application->id) }};">
                        {{ strtoupper(substr($application->first_name ?? '?', 0, 1)) }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div class="ct-row-name">{{ $application->full_name }}</div>
                        <div class="ct-row-sub">{{ $application->job_title }}</div>
                    </div>
                    <span class="ct-chip" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }};">{{ $application->status_label }}</span>
                </a>
            @empty
                <div class="ct-empty-list">
                    @if (request('status') && request('status') !== 'all')
                        No {{ strtolower($statuses[request('status')] ?? request('status')) }} applications.
                    @else
                        No applications yet. Submissions from the Careers page will show up here.
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    <!-- Right Side - Applicant Detail -->
    <div class="ct-detail">
        @if ($activeApplication)
            @php $colors = $statusColors[$activeApplication->status] ?? $statusColors['rejected']; @endphp
            <div class="ct-header">
                <div class="ct-header-avatar" style="background: {{ $avatarColor($activeApplication->id) }};">
                    {{ strtoupper(substr($activeApplication->first_name ?? '?', 0, 1)) }}
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <div class="ct-header-name">{{ $activeApplication->full_name }}</div>
                    <div class="ct-header-sub">Applied for {{ $activeApplication->job_title }} &middot; {{ $activeApplication->created_at->diffForHumans() }}</div>
                </div>
                <div>
                    <div class="ct-header-contact">{{ $activeApplication->email }}</div>
                    <div class="ct-header-contact-sub">{{ $activeApplication->years_experience ? $activeApplication->years_experience.' yrs experience' : 'Experience not stated' }}</div>
                </div>

                @if ($activeApplication->resume_path)
                    <div class="ct-resume-actions">
                        <a href="{{ route('job-applications.resume.view', $activeApplication->id) }}" target="_blank" rel="noopener" class="ct-action-btn ct-action-btn-outline">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('job-applications.resume', $activeApplication->id) }}" class="ct-action-btn">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                @else
                    <span class="ct-action-btn is-disabled"><i class="fas fa-file-slash"></i> No Resume</span>
                @endif
            </div>

            <div class="ct-chips-row">
                <span class="ct-chip" style="background: #F9E7EC; color: #C8355F; padding: 5px 12px; font-size: 12px;">{{ $activeApplication->job_title }}</span>
                <span class="ct-chip" style="background: {{ $colors['bg'] }}; color: {{ $colors['fg'] }}; padding: 5px 12px; font-size: 12px;">{{ $activeApplication->status_label }}</span>
            </div>

            <div class="ct-grid">
                <!-- Left column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div class="ct-card">
                        <div class="ct-card-title">Cover Letter</div>
                        @if ($activeApplication->cover_letter)
                            <div class="ct-message-body">{{ $activeApplication->cover_letter }}</div>
                        @else
                            <div class="ct-card-empty">No cover letter provided.</div>
                        @endif
                    </div>

                    <div class="ct-card">
                        <div class="ct-card-title">Internal Notes</div>

                        @if ($activeApplication->notesLog->isNotEmpty())
                            <div class="ct-notes-log">
                                @foreach ($activeApplication->notesLog as $note)
                                    <div class="ct-note-entry">
                                        <div class="ct-note-meta">
                                            <span class="ct-note-author">{{ $note->author_name }}</span>
                                            <span class="ct-note-time">{{ $note->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="ct-note-body">{{ $note->body }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="ct-card-empty">No notes yet.</div>
                        @endif

                        <form action="{{ route('job-applications.notes.store', $activeApplication->id) }}" method="POST" style="display:flex; flex-direction:column; gap:10px;">
                            @csrf
                            <textarea name="body" rows="3" class="ct-notes-textarea" placeholder="Interview feedback, next steps, etc." required></textarea>
                            <button type="submit" class="ct-save-btn" style="align-self:flex-start;">Add Note</button>
                        </form>
                    </div>
                </div>

                <!-- Right column -->
                <div style="display: flex; flex-direction: column; gap: 18px;">
                    <div class="ct-card">
                        <div class="ct-card-title">Applicant details</div>
                        <div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Email</span>
                                <span class="ct-field-value">{{ $activeApplication->email }}</span>
                            </div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Experience</span>
                                <span class="ct-field-value">{{ $activeApplication->years_experience ?: '—' }}</span>
                            </div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Applied for</span>
                                <span class="ct-field-value">{{ $activeApplication->job_title }}</span>
                            </div>
                            <div class="ct-field-row">
                                <span class="ct-field-label">Applied</span>
                                <span class="ct-field-value">{{ $activeApplication->created_at->format('M j, Y · H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="ct-card">
                        <div class="ct-card-title">Status</div>
                        <form action="{{ route('job-applications.update-status', $activeApplication->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="ct-status-select" style="width: 100%;">
                                @foreach ($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ $activeApplication->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </form>
                        <form action="{{ route('job-applications.destroy', $activeApplication->id) }}" method="POST" onsubmit="return confirm('Delete this application? This also removes the stored resume and cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ct-delete-btn">Delete application</button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="ct-detail-empty">Select an applicant to see their details.</div>
        @endif
    </div>
</div>
@endsection
