@extends('layouts.admin-sidebar')

@section('title', 'Job Application · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
<style>
/* ── shadcn-style token system, namespaced --jb-* so it can't collide with
   the admin layout's own variables ───────────────────────────────────── */
:root {
  --jb-background: #ffffff;
  --jb-foreground: #09090b;
  --jb-card: #ffffff;
  --jb-muted: #f4f4f5;
  --jb-muted-foreground: #71717a;
  --jb-subtle-foreground: #a1a1aa;
  --jb-border: #e4e4e7;
  --jb-input: #e4e4e7;
  --jb-accent: #f4f4f5;
  --jb-primary: #C8355F;
  --jb-primary-hover: #A82348;
  --jb-primary-foreground: #ffffff;
  --jb-destructive: #dc2626;
  --jb-destructive-subtle: #fef2f2;
  --jb-ring: rgba(200, 53, 95, .35);
  --jb-radius: 8px;
  --jb-radius-sm: 6px;
  --jb-font: 'Nunito Sans', ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --jb-font-display: 'Baloo 2', 'Nunito Sans', ui-sans-serif, system-ui, sans-serif;
  --jb-shadow-sm: 0 1px 2px 0 rgba(9, 9, 11, .05);
  --jb-shadow-md: 0 4px 6px -1px rgba(9, 9, 11, .07), 0 2px 4px -2px rgba(9, 9, 11, .05);
  --jb-shadow-lg: 0 10px 15px -3px rgba(9, 9, 11, .1), 0 4px 6px -4px rgba(9, 9, 11, .1);
  --jb-shadow-xl: 0 20px 25px -5px rgba(9, 9, 11, .12), 0 8px 10px -6px rgba(9, 9, 11, .1);
}

.main-content-inner.pt-tight-padding { padding-left: 24px; padding-right: 24px; }

.jb-root, .jb-dialog-overlay {
  font-family: var(--jb-font);
  -webkit-font-smoothing: antialiased;
  color: var(--jb-foreground);
}
.jb-root *, .jb-dialog-overlay * { box-sizing: border-box; }

/* ── Page header ─────────────────────────────────────────────────────── */
.jb-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 16px; flex-wrap: wrap; margin-bottom: 20px;
}
.jb-heading { font-family: var(--jb-font-display); font-size: 25px; font-weight: 600; letter-spacing: 0; line-height: 1.2; }
.jb-subheading { margin-top: 5px; font-size: 13.5px; color: var(--jb-muted-foreground); }
.jb-header-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* ── Buttons ─────────────────────────────────────────────────────────── */
.jb-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  height: 36px; padding: 0 14px; border-radius: var(--jb-radius-sm);
  font-size: 13.5px; font-weight: 600; line-height: 1;
  border: 1px solid transparent; background: none; cursor: pointer;
  text-decoration: none; white-space: nowrap;
  transition: background-color .15s, border-color .15s, color .15s;
}
.jb-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--jb-ring); }
.jb-btn svg { flex-shrink: 0; }

.jb-btn-primary { background: var(--jb-primary); color: var(--jb-primary-foreground); box-shadow: var(--jb-shadow-sm); }
.jb-btn-primary:hover { background: var(--jb-primary-hover); }

.jb-btn-outline { background: var(--jb-background); color: var(--jb-foreground); border-color: var(--jb-input); box-shadow: var(--jb-shadow-sm); }
.jb-btn-outline:hover { background: var(--jb-accent); }

.jb-btn-ghost { background: transparent; color: var(--jb-foreground); }
.jb-btn-ghost:hover { background: var(--jb-accent); }

.jb-btn-destructive-ghost { background: transparent; color: var(--jb-destructive); }
.jb-btn-destructive-ghost:hover { background: var(--jb-destructive-subtle); }

.jb-btn-sm { height: 32px; padding: 0 11px; font-size: 12.5px; }
.jb-btn[disabled], .jb-btn.is-disabled {
  opacity: .5; pointer-events: none; cursor: not-allowed;
}

/* ── Status filter dropdown (replaces the old chip row) ──────────────── */
.jb-dropdown { position: relative; }
.jb-dropdown-trigger { min-width: 210px; justify-content: space-between; padding-right: 11px; }
.jb-dropdown-trigger .jb-trigger-body { display: inline-flex; align-items: center; gap: 8px; min-width: 0; }
.jb-chevron { transition: transform .18s ease; color: var(--jb-subtle-foreground); }
.jb-dropdown.is-open .jb-chevron { transform: rotate(180deg); }

.jb-dropdown-menu {
  display: none; position: absolute; right: 0; top: calc(100% + 6px); z-index: 60;
  min-width: 250px; padding: 4px;
  background: var(--jb-card); border: 1px solid var(--jb-border);
  border-radius: var(--jb-radius); box-shadow: var(--jb-shadow-lg);
  animation: jbPop .14s ease-out;
}
.jb-dropdown.is-open .jb-dropdown-menu { display: block; }
@keyframes jbPop {
  from { opacity: 0; transform: translateY(-4px) scale(.97); }
  to { opacity: 1; transform: none; }
}

.jb-dropdown-label {
  padding: 7px 9px 5px; font-size: 11.5px; font-weight: 700;
  color: var(--jb-muted-foreground); letter-spacing: .02em;
}
.jb-dropdown-separator { height: 1px; margin: 4px -4px; background: var(--jb-border); }

.jb-dropdown-item {
  display: flex; align-items: center; gap: 9px;
  padding: 8px 9px; border-radius: var(--jb-radius-sm);
  font-size: 13.5px; font-weight: 600; color: var(--jb-foreground);
  text-decoration: none; cursor: pointer;
  transition: background-color .12s;
}
.jb-dropdown-item:hover { background: var(--jb-accent); }
.jb-dropdown-item.is-active { background: var(--jb-accent); }
.jb-dropdown-item .jb-check { width: 14px; flex-shrink: 0; color: var(--jb-primary); opacity: 0; }
.jb-dropdown-item.is-active .jb-check { opacity: 1; }
.jb-dropdown-item .jb-item-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.jb-dot { width: 7px; height: 7px; border-radius: 999px; flex-shrink: 0; }
.jb-count {
  min-width: 22px; padding: 2px 7px; border-radius: 999px;
  background: var(--jb-muted); color: var(--jb-muted-foreground);
  font-size: 11.5px; font-weight: 700; text-align: center; flex-shrink: 0;
}
.jb-dropdown-item.is-active .jb-count { background: var(--jb-background); border: 1px solid var(--jb-border); }

/* ── Alert ───────────────────────────────────────────────────────────── */
.jb-alert {
  display: flex; align-items: flex-start; gap: 10px;
  margin-bottom: 18px; padding: 12px 14px;
  border: 1px solid #bbf7d0; border-radius: var(--jb-radius);
  background: #f0fdf4; color: #15803d;
  font-size: 13.5px; font-weight: 600;
}
.jb-alert svg { flex-shrink: 0; margin-top: 1px; }

/* ── Card + table ────────────────────────────────────────────────────── */
.jb-card {
  border: 1px solid var(--jb-border); border-radius: var(--jb-radius);
  background: var(--jb-card); box-shadow: var(--jb-shadow-sm);
  overflow: hidden; overflow-x: auto;
}
.jb-table { width: 100%; min-width: 940px; border-collapse: collapse; }
.jb-table th {
  padding: 11px 16px; border-bottom: 1px solid var(--jb-border);
  text-align: left; white-space: nowrap;
  font-size: 12.5px; font-weight: 600; color: var(--jb-muted-foreground);
}
.jb-table td {
  padding: 13px 16px; border-bottom: 1px solid var(--jb-border);
  vertical-align: middle; font-size: 13.5px; color: var(--jb-foreground);
}
.jb-table tbody tr:last-child td { border-bottom: 0; }
.jb-row { cursor: pointer; transition: background-color .12s; }
.jb-row:hover td { background: #fafafa; }

.jb-person { display: flex; align-items: center; gap: 11px; }
.jb-avatar {
  width: 34px; height: 34px; flex-shrink: 0; border-radius: 999px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-family: var(--jb-font-display); font-size: 13px; font-weight: 600;
}
.jb-name { font-weight: 700; }
.jb-secondary { margin-top: 2px; font-size: 12.5px; color: var(--jb-muted-foreground); }
.jb-truncate { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--jb-muted-foreground); }

.jb-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 3px 9px; border-radius: 999px; border: 1px solid transparent;
  font-size: 12px; font-weight: 600; white-space: nowrap;
}

/* ── Empty state ─────────────────────────────────────────────────────── */
.jb-empty { padding: 64px 20px; text-align: center; }
.jb-empty-icon {
  width: 46px; height: 46px; margin: 0 auto 16px; border-radius: var(--jb-radius);
  border: 1px solid var(--jb-border); background: var(--jb-muted);
  color: var(--jb-muted-foreground);
  display: flex; align-items: center; justify-content: center;
}
.jb-empty-title { font-size: 15px; font-weight: 700; margin-bottom: 5px; }
.jb-empty-sub { max-width: 380px; margin: 0 auto; font-size: 13.5px; color: var(--jb-muted-foreground); line-height: 1.55; }
.jb-empty .jb-btn { margin-top: 18px; }

/* ── Dialog ──────────────────────────────────────────────────────────── */
.jb-dialog-overlay {
  display: none; position: fixed; inset: 0; z-index: 9999;
  align-items: center; justify-content: center; padding: 18px;
  background: rgba(9, 9, 11, .55); backdrop-filter: blur(2px);
}
.jb-dialog-overlay.open { display: flex; }
.jb-dialog {
  width: 840px; max-width: 100%; max-height: 88vh; overflow-y: auto;
  background: var(--jb-background); border: 1px solid var(--jb-border);
  border-radius: 12px; box-shadow: var(--jb-shadow-xl);
  padding: 24px; animation: jbDialogIn .16s ease-out;
}
@keyframes jbDialogIn {
  from { opacity: 0; transform: translateY(6px) scale(.985); }
  to { opacity: 1; transform: none; }
}
.jb-dialog-head { display: flex; align-items: flex-start; gap: 13px; flex-wrap: wrap; }
.jb-dialog-title { flex: 1; min-width: 170px; }
.jb-dialog-title h2 { font-family: var(--jb-font-display); font-size: 20px; font-weight: 600; letter-spacing: 0; }
.jb-dialog-desc { margin-top: 3px; font-size: 13px; color: var(--jb-muted-foreground); }
.jb-dialog-contact { text-align: right; font-size: 13px; font-weight: 700; }
.jb-dialog-contact-sub { text-align: right; margin-top: 2px; font-size: 12.5px; color: var(--jb-muted-foreground); }
.jb-dialog-close {
  width: 30px; height: 30px; flex-shrink: 0; border-radius: var(--jb-radius-sm);
  border: 1px solid var(--jb-input); background: var(--jb-background);
  color: var(--jb-muted-foreground); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background-color .12s, color .12s;
}
.jb-dialog-close:hover { background: var(--jb-accent); color: var(--jb-foreground); }

.jb-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 18px 0; }
.jb-grid { display: grid; grid-template-columns: 1.4fr .85fr; gap: 16px; }
.jb-col { display: flex; flex-direction: column; gap: 16px; }

.jb-panel {
  display: flex; flex-direction: column; gap: 12px;
  border: 1px solid var(--jb-border); border-radius: var(--jb-radius);
  background: var(--jb-card); padding: 16px 18px; box-shadow: var(--jb-shadow-sm);
}
.jb-panel-title { font-family: var(--jb-font-display); font-size: 16px; font-weight: 600; letter-spacing: 0; }
.jb-prose { font-size: 13.5px; line-height: 1.65; white-space: pre-wrap; }
.jb-panel-empty { font-size: 13px; color: var(--jb-muted-foreground); line-height: 1.55; }

.jb-field-row {
  display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
  padding: 9px 0; border-bottom: 1px solid var(--jb-border);
}
.jb-field-row:last-child { border-bottom: 0; padding-bottom: 0; }
.jb-field-label { font-size: 12.5px; color: var(--jb-muted-foreground); flex-shrink: 0; }
.jb-field-value { font-size: 13.5px; font-weight: 700; text-align: right; word-break: break-word; }

.jb-notes { display: flex; flex-direction: column; gap: 8px; max-height: 230px; overflow-y: auto; }
.jb-note {
  border: 1px solid var(--jb-border); border-radius: var(--jb-radius-sm);
  background: #fafafa; padding: 10px 12px;
}
.jb-note-meta { display: flex; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
.jb-note-author { font-size: 12.5px; font-weight: 700; }
.jb-note-time { font-size: 12px; color: var(--jb-muted-foreground); white-space: nowrap; }
.jb-note-body { font-size: 13px; line-height: 1.55; white-space: pre-wrap; }

.jb-input, .jb-select, .jb-textarea {
  width: 100%; box-sizing: border-box; padding: 8px 11px;
  border: 1px solid var(--jb-input); border-radius: var(--jb-radius-sm);
  background: var(--jb-background); color: var(--jb-foreground);
  font-family: var(--jb-font); font-size: 13.5px; line-height: 1.5;
  transition: border-color .12s, box-shadow .12s;
}
.jb-input:focus, .jb-select:focus, .jb-textarea:focus {
  outline: none; border-color: var(--jb-primary); box-shadow: 0 0 0 3px var(--jb-ring);
}
.jb-textarea { resize: vertical; min-height: 72px; }
.jb-select {
  height: 36px; cursor: pointer; appearance: none;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 10px center; padding-right: 32px;
}

/* ── Phone: one card per applicant ────────────────────────────────────────
   The table needs 940px before it stops cramping, so below that each row
   becomes a card built like the WhatsApp inbox rows - a round avatar, the
   name with its status out on the right, and two grey lines under it. Cards
   are links to the same application, so nothing else has to change. */
.jb-cards { display: none; flex-direction: column; }
.jb-mcard {
  display: flex; align-items: center; gap: 11px;
  padding: 10px 14px; text-decoration: none; color: inherit;
  border-top: 1px solid var(--jb-border);
}
.jb-cards .jb-mcard:first-child { border-top: none; }
.jb-mcard .jb-avatar { width: 46px; height: 46px; font-size: 16px; }
.jb-mcard-ident { flex: 1 1 0; min-width: 0; }
.jb-mcard-top, .jb-mcard-bottom { display: flex; align-items: center; gap: 8px; }
.jb-mcard-bottom { margin-top: 1px; }
.jb-mcard .jb-name, .jb-mcard-line { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.jb-mcard .jb-name { font-size: 14.5px; }
.jb-mcard .jb-badge { flex-shrink: 0; padding: 2px 8px; font-size: 10px; }
.jb-mcard-line { font-size: 12.5px; color: var(--jb-muted-foreground); }
.jb-mcard-meta { flex-shrink: 0; font-size: 11px; color: var(--jb-muted-foreground); }

@media (max-width: 860px) {
  .jb-grid { grid-template-columns: 1fr; }
  .jb-dialog { padding: 20px 16px; }
  .jb-dialog-contact, .jb-dialog-contact-sub { text-align: left; }

  .main-content-inner.pt-tight-padding { padding-left: 12px; padding-right: 12px; }
  .jb-header { margin-bottom: 14px; gap: 10px; }
  .jb-heading { font-size: 21px; }
  .jb-subheading { font-size: 12.5px; }
  /* The actions take the whole row and end flush with the right edge: the
     filter stretches into the slack and the posting button keeps the corner,
     rather than both sitting left with dead space beside them. */
  .jb-header-actions { width: 100%; justify-content: flex-end; flex-wrap: nowrap; }
  .jb-dropdown { flex: 1 1 auto; min-width: 0; }
  .jb-dropdown-trigger { width: 100%; min-width: 0; }
  .jb-header-actions .jb-btn { flex: 0 0 auto; }
  .jb-card-table { display: none; }
  .jb-cards { display: flex; }
}
@media (max-width: 520px) {
  .jb-dropdown-trigger { min-width: 0; width: 100%; }
  .jb-dropdown { flex: 1; }
  .jb-dropdown-menu { left: 0; right: auto; width: 100%; }
}
</style>

@php
    $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];

    // shadcn-ish subtle badge tokens: tinted surface, hairline border, saturated text.
    $statusTokens = [
        'new'          => ['dot' => '#2563eb', 'bg' => '#eff6ff', 'fg' => '#1d4ed8', 'border' => '#bfdbfe'],
        'reviewed'     => ['dot' => '#ca8a04', 'bg' => '#fefce8', 'fg' => '#a16207', 'border' => '#fef08a'],
        'interviewing' => ['dot' => '#7c3aed', 'bg' => '#f5f3ff', 'fg' => '#6d28d9', 'border' => '#ddd6fe'],
        'hired'        => ['dot' => '#16a34a', 'bg' => '#f0fdf4', 'fg' => '#15803d', 'border' => '#bbf7d0'],
        'rejected'     => ['dot' => '#71717a', 'bg' => '#fafafa', 'fg' => '#52525b', 'border' => '#e4e4e7'],
    ];
    $tokensFor = fn ($status) => $statusTokens[$status] ?? $statusTokens['rejected'];

    // Normalise the filter once: an unknown ?status= falls back to "all" rather
    // than rendering a dropdown trigger with a blank label.
    $currentStatus = request('status');
    if (! $currentStatus || ! array_key_exists($currentStatus, $statuses)) {
        $currentStatus = 'all';
    }
    $isAllStatus = $currentStatus === 'all';
    $currentLabel = $isAllStatus ? 'All applications' : $statuses[$currentStatus];
    $currentCount = $isAllStatus ? $totalCount : ($statusCounts[$currentStatus] ?? 0);

    $applicationUrl = fn ($application) => route('job-applications.index', ['application' => $application->id] + (! $isAllStatus ? ['status' => $currentStatus] : []));
@endphp

<div class="jb-root">
  <div class="jb-header">
    <div>
      <div class="jb-heading">Job Applications</div>
      <div class="jb-subheading">{{ $applications->count() }} {{ Str::plural('applicant', $applications->count()) }} shown &middot; {{ $newCount }} new</div>
    </div>

    <div class="jb-header-actions">
      <!-- Status filter: one dropdown in place of the old chip row -->
      <div class="jb-dropdown" id="jbStatusDropdown">
        <button type="button" class="jb-btn jb-btn-outline jb-dropdown-trigger" id="jbStatusTrigger"
                aria-haspopup="menu" aria-expanded="false" aria-controls="jbStatusMenu">
          <span class="jb-trigger-body">
            @unless ($isAllStatus)
              <span class="jb-dot" style="background: {{ $tokensFor($currentStatus)['dot'] }};"></span>
            @endunless
            <span>{{ $currentLabel }}</span>
            <span class="jb-count">{{ $currentCount }}</span>
          </span>
          <svg class="jb-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div class="jb-dropdown-menu" id="jbStatusMenu" role="menu" aria-labelledby="jbStatusTrigger">
          <div class="jb-dropdown-label">Filter by status</div>
          <a href="{{ route('job-applications.index') }}" role="menuitem" class="jb-dropdown-item {{ $isAllStatus ? 'is-active' : '' }}">
            <svg class="jb-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="jb-item-label">All applications</span>
            <span class="jb-count">{{ $totalCount }}</span>
          </a>
          <div class="jb-dropdown-separator"></div>
          @foreach ($statuses as $key => $label)
            <a href="{{ route('job-applications.index', ['status' => $key]) }}" role="menuitem" class="jb-dropdown-item {{ $currentStatus === $key ? 'is-active' : '' }}">
              <svg class="jb-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span class="jb-dot" style="background: {{ $tokensFor($key)['dot'] }};"></span>
              <span class="jb-item-label">{{ $label }}</span>
              <span class="jb-count">{{ $statusCounts[$key] ?? 0 }}</span>
            </a>
          @endforeach
        </div>
      </div>

      <a href="{{ route('job-postings.index') }}" class="jb-btn jb-btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        Job Posting
      </a>
    </div>
  </div>

  @if (session('success'))
    <div class="jb-alert">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  <div class="jb-card {{ $applications->isEmpty() ? '' : 'jb-card-table' }}">
    @if ($applications->isEmpty())
      <div class="jb-empty">
        <div class="jb-empty-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
        </div>
        @if (! $isAllStatus)
          <div class="jb-empty-title">No {{ strtolower($statuses[$currentStatus]) }} applications</div>
          <div class="jb-empty-sub">Try a different filter, or check back once candidates move into this stage.</div>
          <a href="{{ route('job-applications.index') }}" class="jb-btn jb-btn-outline">Clear filter</a>
        @else
          <div class="jb-empty-title">No applications yet</div>
          {{-- No button here: "+ Job Posting" sits in the header a few
               centimetres above, and two of the same action on one screen just
               makes the reader stop to work out whether they differ. --}}
          <div class="jb-empty-sub">Submissions from the Careers page will show up here as soon as candidates apply.</div>
        @endif
      </div>
    @else
      <table class="jb-table">
        <thead>
          <tr>
            <th>Applicant</th>
            <th>Job title</th>
            <th>Email &middot; Experience</th>
            <th>Cover letter</th>
            <th>Applied</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        @foreach ($applications as $application)
          @php $tokens = $tokensFor($application->status); @endphp
          <tr class="jb-row" onclick="window.location='{{ $applicationUrl($application) }}'">
            <td>
              <div class="jb-person">
                <div class="jb-avatar" style="background: {{ $avatarColor($application->id) }};">{{ strtoupper(substr($application->first_name ?? '?', 0, 1)) }}</div>
                <div>
                  <div class="jb-name">{{ $application->full_name }}</div>
                  <div class="jb-secondary">Careers page enquiry</div>
                </div>
              </div>
            </td>
            <td>{{ $application->job_title }}</td>
            <td>
              <div>{{ $application->email }}</div>
              <div class="jb-secondary">{{ $application->years_experience ? $application->years_experience.' yrs experience' : 'Experience not stated' }}</div>
            </td>
            <td><div class="jb-truncate">{{ $application->cover_letter ?: 'No cover letter provided' }}</div></td>
            <td>
              {{ $application->created_at->format('M j, Y') }}
              <div class="jb-secondary">{{ $application->created_at->format('H:i') }}</div>
            </td>
            <td>
              <span class="jb-badge" style="background: {{ $tokens['bg'] }}; color: {{ $tokens['fg'] }}; border-color: {{ $tokens['border'] }};">
                <span class="jb-dot" style="background: {{ $tokens['dot'] }};"></span>
                {{ $application->status_label }}
              </span>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>

  @if ($applications->isNotEmpty())
    <div class="jb-card jb-cards">
      @foreach ($applications as $application)
        @php $tokens = $tokensFor($application->status); @endphp
        <a href="{{ $applicationUrl($application) }}" class="jb-mcard">
          <div class="jb-avatar" style="background: {{ $avatarColor($application->id) }};">{{ strtoupper(substr($application->first_name ?? '?', 0, 1)) }}</div>
          <div class="jb-mcard-ident">
            <div class="jb-mcard-top">
              <div class="jb-name">{{ $application->full_name }}</div>
              <span class="jb-badge" style="background: {{ $tokens['bg'] }}; color: {{ $tokens['fg'] }}; border-color: {{ $tokens['border'] }};">
                <span class="jb-dot" style="background: {{ $tokens['dot'] }};"></span>
                {{ $application->status_label }}
              </span>
            </div>
            <div class="jb-mcard-bottom">
              <div class="jb-mcard-line">{{ $application->job_title }}</div>
              <div class="jb-mcard-meta">{{ $application->created_at->format('M j') }}</div>
            </div>
            <div class="jb-mcard-bottom">
              <div class="jb-mcard-line">{{ $application->email }}</div>
              <div class="jb-mcard-meta">{{ $application->years_experience ? $application->years_experience.' yrs' : '—' }}</div>
            </div>
          </div>
        </a>
      @endforeach
    </div>
  @endif
</div>

@if ($activeApplication)
  @php $tokens = $tokensFor($activeApplication->status); @endphp
  <div id="jbDialogOverlay" class="jb-dialog-overlay open" role="dialog" aria-modal="true" aria-labelledby="jbDialogTitle">
    <div class="jb-dialog">
      <div class="jb-dialog-head">
        <div class="jb-avatar" style="width: 44px; height: 44px; font-size: 16px; background: {{ $avatarColor($activeApplication->id) }};">{{ strtoupper(substr($activeApplication->first_name ?? '?', 0, 1)) }}</div>
        <div class="jb-dialog-title">
          <h2 id="jbDialogTitle">{{ $activeApplication->full_name }}</h2>
          <div class="jb-dialog-desc">Applied for {{ $activeApplication->job_title }} &middot; {{ $activeApplication->created_at->diffForHumans() }}</div>
        </div>
        <div>
          <div class="jb-dialog-contact">{{ $activeApplication->email }}</div>
          <div class="jb-dialog-contact-sub">{{ $activeApplication->years_experience ? $activeApplication->years_experience.' yrs experience' : 'Experience not stated' }}</div>
        </div>
        <button class="jb-dialog-close" type="button" onclick="closeApplicationDetail()" aria-label="Close application details">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="jb-toolbar">
        <span class="jb-badge" style="background: var(--jb-muted); color: var(--jb-muted-foreground); border-color: var(--jb-border);">{{ $activeApplication->job_title }}</span>
        <span class="jb-badge" style="background: {{ $tokens['bg'] }}; color: {{ $tokens['fg'] }}; border-color: {{ $tokens['border'] }};">
          <span class="jb-dot" style="background: {{ $tokens['dot'] }};"></span>
          {{ $activeApplication->status_label }}
        </span>
        <div style="flex: 1;"></div>
        @if ($activeApplication->resume_path)
          <a href="{{ route('job-applications.resume.view', $activeApplication->id) }}" target="_blank" rel="noopener" class="jb-btn jb-btn-outline jb-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
            View resume
          </a>
          <a href="{{ route('job-applications.resume', $activeApplication->id) }}" class="jb-btn jb-btn-primary jb-btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download
          </a>
        @else
          <span class="jb-btn jb-btn-outline jb-btn-sm is-disabled">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            No resume
          </span>
        @endif
      </div>

      <div class="jb-grid">
        <div class="jb-col">
          <div class="jb-panel">
            <div class="jb-panel-title">Cover letter</div>
            @if ($activeApplication->cover_letter)
              <div class="jb-prose">{{ $activeApplication->cover_letter }}</div>
            @else
              <div class="jb-panel-empty">No cover letter provided.</div>
            @endif
          </div>

          <div class="jb-panel">
            <div class="jb-panel-title">Internal notes</div>
            @if ($activeApplication->notesLog->isNotEmpty())
              <div class="jb-notes">
                @foreach ($activeApplication->notesLog as $note)
                  <div class="jb-note">
                    <div class="jb-note-meta">
                      <span class="jb-note-author">{{ $note->author_name }}</span>
                      <span class="jb-note-time">{{ $note->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="jb-note-body">{{ $note->body }}</div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="jb-panel-empty">No notes yet.</div>
            @endif
            <form action="{{ route('job-applications.notes.store', $activeApplication->id) }}" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
              @csrf
              <textarea name="body" rows="3" class="jb-textarea" placeholder="Interview feedback, next steps, etc." required></textarea>
              <button type="submit" class="jb-btn jb-btn-outline jb-btn-sm" style="align-self: flex-start;">Add note</button>
            </form>
          </div>
        </div>

        <div class="jb-col">
          <div class="jb-panel">
            <div class="jb-panel-title">Applicant details</div>
            <div>
              <div class="jb-field-row"><span class="jb-field-label">Email</span><span class="jb-field-value">{{ $activeApplication->email }}</span></div>
              <div class="jb-field-row"><span class="jb-field-label">Experience</span><span class="jb-field-value">{{ $activeApplication->years_experience ?: '—' }}</span></div>
              <div class="jb-field-row"><span class="jb-field-label">Applied for</span><span class="jb-field-value">{{ $activeApplication->job_title }}</span></div>
              <div class="jb-field-row"><span class="jb-field-label">Applied</span><span class="jb-field-value">{{ $activeApplication->created_at->format('M j, Y · H:i') }}</span></div>
            </div>
          </div>

          <div class="jb-panel">
            <div class="jb-panel-title">Status</div>
            <form action="{{ route('job-applications.update-status', $activeApplication->id) }}" method="POST">
              @csrf
              @method('PATCH')
              <select name="status" onchange="this.form.submit()" class="jb-select">
                @foreach ($statuses as $key => $label)
                  <option value="{{ $key }}" {{ $activeApplication->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
              </select>
            </form>
            <form action="{{ route('job-applications.destroy', $activeApplication->id) }}" method="POST" onsubmit="return confirm('Delete this application? This also removes the stored resume and cannot be undone.');">
              @csrf
              @method('DELETE')
              <button type="submit" class="jb-btn jb-btn-destructive-ghost jb-btn-sm" style="align-self: flex-start; padding-left: 0;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                Delete application
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif
@endsection

@push('scripts')
<script>
function closeApplicationDetail() {
    const url = new URL(window.location.href);
    url.searchParams.delete('application');
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    // ---- Status dropdown ----
    const dropdown = document.getElementById('jbStatusDropdown');
    const trigger = document.getElementById('jbStatusTrigger');

    function closeDropdown() {
        if (!dropdown) return;
        dropdown.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
    }

    if (dropdown && trigger) {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = dropdown.classList.toggle('is-open');
            trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Any click outside the dropdown dismisses it.
        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) closeDropdown();
        });

        // Arrow keys walk the menu items; Enter follows the focused link.
        dropdown.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
            e.preventDefault();
            if (!dropdown.classList.contains('is-open')) {
                dropdown.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
            }
            const items = Array.from(dropdown.querySelectorAll('.jb-dropdown-item'));
            if (!items.length) return;
            const index = items.indexOf(document.activeElement);
            const next = e.key === 'ArrowDown'
                ? (index + 1) % items.length
                : (index <= 0 ? items.length - 1 : index - 1);
            items[next].focus();
        });
    }

    // ---- Dialog ----
    const overlay = document.getElementById('jbDialogOverlay');
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeApplicationDetail();
        });
    }

    // Escape closes the dropdown first if it's open, so one press never does
    // two things at once.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (dropdown && dropdown.classList.contains('is-open')) {
            closeDropdown();
            trigger.focus();
            return;
        }
        if (overlay) closeApplicationDetail();
    });
});
</script>
@endpush