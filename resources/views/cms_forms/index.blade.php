@extends('layouts.admin-sidebar')

@section('title', 'Forms · CMS · Engage Clinic')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
@include('cms_forms.partials.styles')
<style>
/* ── Toolbar ───────────────────────────────────────────────────────── */
.fi-toolbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
.fi-tabs { display: inline-flex; padding: 3px; background: var(--cf-muted); border-radius: 8px; gap: 2px; }
.fi-tab { height: 30px; padding: 0 12px; border: 0; background: transparent; border-radius: 6px; font: 600 13px var(--cf-font); color: var(--cf-muted-foreground); cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }
.fi-tab:hover { color: var(--cf-foreground); }
.fi-tab.is-on { background: #fff; color: var(--cf-foreground); box-shadow: var(--cf-shadow-sm); }
.fi-tab .n { font-size: 11px; font-weight: 700; padding: 0 6px; border-radius: 999px; background: rgba(9, 9, 11, .06); line-height: 18px; }
.fi-search { position: relative; flex: 1 1 240px; max-width: 360px; margin-left: auto; }
.fi-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); font-size: 12.5px; color: var(--cf-subtle-foreground); }
.fi-search .cf-input { height: 36px; padding-left: 32px; }
.fi-toolbar .cf-select { width: auto; height: 36px; padding-top: 0; padding-bottom: 0; }
.fi-view { display: inline-flex; padding: 3px; background: var(--cf-muted); border-radius: 8px; gap: 2px; }
.fi-view button { width: 32px; height: 30px; border: 0; background: transparent; border-radius: 6px; color: var(--cf-muted-foreground); cursor: pointer; }
.fi-view button.is-on { background: #fff; color: var(--cf-foreground); box-shadow: var(--cf-shadow-sm); }

/* ── Grid of form cards ────────────────────────────────────────────── */
.fi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 16px; }
.fi-card { display: flex; flex-direction: column; border: 1px solid var(--cf-border); border-radius: 12px; background: #fff; box-shadow: var(--cf-shadow-sm); transition: box-shadow .15s, transform .15s, border-color .15s; }
.fi-card:hover { box-shadow: var(--cf-shadow-lg); transform: translateY(-2px); border-color: #d4d4d8; }
.fi-card-thumb { position: relative; display: block; height: 176px; border-bottom: 1px solid var(--cf-border); border-radius: 12px 12px 0 0; overflow: hidden; text-decoration: none; }
.fi-card-thumb .fi-thumb { height: 100%; }
.fi-card-open { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(9, 9, 11, .45); opacity: 0; transition: opacity .15s; }
.fi-card-open span { background: #fff; color: #09090b; font: 600 13px var(--cf-font); padding: 8px 14px; border-radius: 8px; display: inline-flex; gap: 8px; align-items: center; box-shadow: var(--cf-shadow-lg); }
.fi-card-thumb:hover .fi-card-open, .fi-card-thumb:focus-visible .fi-card-open { opacity: 1; }
.fi-card-body { padding: 14px 16px 12px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.fi-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.fi-card-title { font-weight: 800; font-size: 15px; color: var(--cf-foreground); text-decoration: none; line-height: 1.3; word-break: break-word; }
.fi-card-title:hover { color: var(--cf-primary); }
.fi-card-url { font-size: 12px; color: var(--cf-muted-foreground); margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fi-metrics { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--cf-border); border-radius: 8px; overflow: hidden; }
.fi-metric { padding: 8px 10px; text-decoration: none; color: inherit; }
.fi-metric + .fi-metric { border-left: 1px solid var(--cf-border); }
a.fi-metric:hover { background: #fafafa; }
.fi-metric b { display: block; font-size: 16px; font-weight: 800; line-height: 1.2; }
.fi-metric span { font-size: 11.5px; color: var(--cf-muted-foreground); font-weight: 600; }
.fi-metric.is-new b { color: var(--cf-primary); }
.fi-card-foot { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto; }
.fi-when { font-size: 12px; color: var(--cf-muted-foreground); display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
.fi-foot-actions { display: flex; gap: 4px; align-items: center; }

/* New-form tile */
.fi-new { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; min-height: 300px; border: 1.5px dashed #d4d4d8; border-radius: 12px; background: transparent; color: var(--cf-muted-foreground); font: 600 14px var(--cf-font); cursor: pointer; transition: border-color .15s, color .15s, background-color .15s; }
.fi-new:hover { border-color: var(--cf-primary); color: var(--cf-primary); background: #fff; }
.fi-new-icon { width: 46px; height: 46px; border-radius: 12px; border: 1px solid currentColor; display: flex; align-items: center; justify-content: center; font-size: 17px; }

/* ── List view ─────────────────────────────────────────────────────── */
.fi-list-form { display: flex; align-items: center; gap: 12px; min-width: 0; }
.fi-list-thumb { width: 64px; height: 46px; flex-shrink: 0; border-radius: 6px; overflow: hidden; border: 1px solid var(--cf-border); }
.fi-list-thumb .fi-thumb { height: 100%; }

/* ── Miniature form (partials/thumb) ───────────────────────────────── */
.fi-thumb { position: relative; background: var(--t-page); padding: 12px 18px 0; overflow: hidden; }
.fi-thumb::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 36px; background: linear-gradient(to bottom, transparent, var(--t-page)); }
.fi-t-frame { position: absolute; top: 0; left: 0; right: 0; height: 5px; background: var(--t-head); }
.fi-t-card { background: var(--t-card); border-radius: 6px 6px 0 0; padding: 10px 12px; box-shadow: 0 1px 3px rgba(0, 0, 0, .08); min-height: 100%; }
.fi-t-head { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
.fi-t-head.is-split { justify-content: space-between; }
.fi-t-head.is-centered { flex-direction: column; align-items: flex-start; gap: 4px; }
.fi-t-head.is-banner { margin: -10px -12px 8px; padding: 8px 12px; background: var(--t-head); border-radius: 6px 6px 0 0; }
.fi-t-head.is-banner .fi-t-title { color: var(--t-head-text); }
.fi-t-logo { width: 14px; height: 14px; border-radius: 4px; background: #C8355F; opacity: .85; flex-shrink: 0; }
.fi-t-title { font: 800 10px/1.2 var(--cf-font); color: var(--t-head); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fi-t-title.is-upper { text-transform: uppercase; letter-spacing: .03em; }
.fi-t-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: 6px 6px; }
.fi-t-sec { height: 8px; border-radius: 2px; background: var(--t-sec); }
.fi-t-sec.is-underline { background: none; border-bottom: 2px solid var(--t-primary); height: 6px; }
.fi-t-sec.is-plain { background: none; height: 5px; }
.fi-t-sec.is-plain::before { content: ''; display: block; width: 40%; height: 4px; border-radius: 2px; background: var(--t-head); }
.fi-t-item.is-left { display: flex; align-items: center; gap: 4px; }
.fi-t-item.is-left .fi-t-label { width: 32%; margin: 0; }
.fi-t-item.is-left .fi-t-box { flex: 1; }
.fi-t-label { width: 45%; height: 3px; border-radius: 2px; background: var(--t-label); opacity: .45; margin-bottom: 3px; }
.fi-t-box { height: 8px; border: 1px solid var(--t-border); border-radius: var(--t-radius); background: var(--t-field); }
.fi-t-box.is-tall { height: 20px; }
.fi-t-checks { display: flex; flex-direction: column; gap: 2px; }
.fi-t-checks i { display: block; width: 60%; height: 3px; border-radius: 2px; background: var(--t-border); opacity: .6; }
.fi-t-line { height: 3px; border-radius: 2px; background: var(--t-label); opacity: .3; margin-bottom: 3px; }
.fi-t-hr { height: 1px; background: var(--t-border); margin: 3px 0; }
.fi-t-empty { font: 500 9px var(--cf-font); color: var(--t-label); opacity: .5; text-align: center; padding: 10px 0; }
.fi-t-btn { height: 9px; width: 34%; margin: 8px auto 4px; border-radius: var(--t-radius); background: var(--t-primary); }
.fi-t-btn.is-full { width: 100%; }
.fi-list-thumb .fi-thumb { padding: 4px 6px 0; }
.fi-list-thumb .fi-t-card { padding: 4px 5px; }
.fi-list-thumb .fi-t-head { margin-bottom: 3px; }
.fi-list-thumb .fi-t-title { font-size: 5px; }
.fi-list-thumb .fi-t-grid { gap: 2px; }
.fi-list-thumb .fi-thumb::after { height: 14px; }

/* ── Create dialog ─────────────────────────────────────────────────── */
.fi-templates { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.fi-template { position: relative; display: flex; flex-direction: column; gap: 8px; padding: 12px; border: 1px solid var(--cf-border); border-radius: 10px; cursor: pointer; transition: border-color .12s, box-shadow .12s; }
.fi-template:hover { border-color: #a1a1aa; }
.fi-template input { position: absolute; opacity: 0; pointer-events: none; }
.fi-template:has(input:checked) { border-color: var(--cf-foreground); box-shadow: 0 0 0 1px var(--cf-foreground); }
.fi-template:has(input:focus-visible) { box-shadow: 0 0 0 3px var(--cf-ring); }
.fi-template-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--cf-primary-soft); color: var(--cf-primary); display: flex; align-items: center; justify-content: center; }
.fi-template strong { font-size: 13.5px; }
.fi-template small { font-size: 12px; color: var(--cf-muted-foreground); line-height: 1.4; }
.fi-template-check { position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; border-radius: 50%; border: 1px solid var(--cf-border); display: flex; align-items: center; justify-content: center; font-size: 9px; color: transparent; }
.fi-template:has(input:checked) .fi-template-check { background: var(--cf-foreground); border-color: var(--cf-foreground); color: #fff; }

.fi-hidden { display: none !important; }

@media (max-width: 640px) {
  .fi-search { max-width: none; order: -1; flex-basis: 100%; }
  .fi-templates { grid-template-columns: 1fr; }
}
</style>

@php
  $timeAgo = fn ($ts) => $ts ? \Carbon\Carbon::parse($ts)->diffForHumans() : null;
  $questionCount = fn ($form) => $form->fields->reject(fn ($f) => $f->isLayout())->count();
@endphp

<div class="cf-root">
  <div class="cf-crumbs"><span>CMS</span><i class="fas fa-chevron-right" style="font-size:9px"></i><span>Forms</span></div>
  <div class="cf-header">
    <div>
      <div class="cf-heading">Forms</div>
      <div class="cf-subheading">Design event and registration forms, share the link or embed them, and review what clients submit.</div>
    </div>
    <div class="cf-header-actions">
      <button type="button" class="cf-btn cf-btn-primary" onclick="cfOpen('cfCreateDialog')"><i class="fas fa-plus"></i> Create Form</button>
    </div>
  </div>

  @if ($errors->any())
    <div class="cf-alert is-error"><i class="fas fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="cf-stats">
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#EAF1F8;color:#16436E"><i class="fas fa-clipboard-list"></i></div>
      <div><div class="cf-stat-label">Forms</div><div class="cf-stat-value">{{ $stats['forms'] }}</div>
        <div class="cf-stat-sub">{{ $stats['published'] }} live · {{ $stats['drafts'] }} {{ \Illuminate\Support\Str::plural('draft', $stats['drafts']) }}</div></div>
    </div>
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#f0fdf4;color:#15803d"><i class="fas fa-globe"></i></div>
      <div><div class="cf-stat-label">Published</div><div class="cf-stat-value">{{ $stats['published'] }}</div>
        <div class="cf-stat-sub">accepting responses now</div></div>
    </div>
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#f4f4f5;color:#3f3f46"><i class="fas fa-inbox"></i></div>
      <div><div class="cf-stat-label">Total responses</div><div class="cf-stat-value">{{ number_format($stats['responses']) }}</div>
        <div class="cf-stat-sub">{{ $stats['this_week'] }} in the last 7 days</div></div>
    </div>
    <div class="cf-stat {{ $stats['new'] > 0 ? 'is-attention' : '' }}">
      <div class="cf-stat-icon" style="background:var(--cf-primary-soft);color:var(--cf-primary)"><i class="fas fa-bell"></i></div>
      <div><div class="cf-stat-label">Needs review</div><div class="cf-stat-value" style="{{ $stats['new'] > 0 ? 'color:var(--cf-primary)' : '' }}">{{ number_format($stats['new']) }}</div>
        <div class="cf-stat-sub">{!! $stats['new'] > 0 ? 'new responses <strong>waiting</strong>' : 'all caught up' !!}</div></div>
    </div>
  </div>

  @if ($forms->isEmpty())
    <div class="cf-card">
      <div class="cf-empty">
        <div class="cf-empty-icon"><i class="fas fa-clipboard-list"></i></div>
        <div class="cf-empty-title">Create your first form</div>
        <div class="cf-empty-sub">Start from a template - a service enquiry or an event registration - and make it yours in the designer. Every response lands here for your team to review.</div>
        <button type="button" class="cf-btn cf-btn-primary" onclick="cfOpen('cfCreateDialog')"><i class="fas fa-plus"></i> Create Form</button>
      </div>
    </div>
  @else
    <div class="fi-toolbar">
      <div class="fi-tabs" role="tablist" aria-label="Filter by status">
        <button type="button" class="fi-tab is-on" data-status="all">All <span class="n">{{ $stats['forms'] }}</span></button>
        <button type="button" class="fi-tab" data-status="published">Published <span class="n">{{ $stats['published'] }}</span></button>
        <button type="button" class="fi-tab" data-status="draft">Drafts <span class="n">{{ $stats['drafts'] }}</span></button>
      </div>
      <div class="fi-search">
        <i class="fas fa-search"></i>
        <input class="cf-input" type="search" id="fiSearch" placeholder="Search forms…" aria-label="Search forms">
      </div>
      <select class="cf-select" id="fiSort" aria-label="Sort forms">
        <option value="created">Newest first</option>
        <option value="activity">Latest response</option>
        <option value="responses">Most responses</option>
        <option value="title">Name A–Z</option>
      </select>
      <div class="fi-view" role="group" aria-label="View">
        <button type="button" class="is-on" data-view="grid" title="Grid view"><i class="fas fa-grip"></i></button>
        <button type="button" data-view="list" title="List view"><i class="fas fa-list"></i></button>
      </div>
    </div>

    {{-- Grid view --}}
    <div class="fi-grid" id="fiGrid">
      @foreach ($forms as $form)
        <article class="fi-card" data-item data-title="{{ strtolower($form->title.' '.$form->slug) }}" data-status="{{ $form->status }}"
                 data-created="{{ $form->created_at->timestamp }}" data-activity="{{ $form->submissions_max_submitted_at ? \Carbon\Carbon::parse($form->submissions_max_submitted_at)->timestamp : 0 }}" data-responses="{{ $form->submissions_count }}">
          <a class="fi-card-thumb" href="{{ route('cms.forms.edit', $form) }}" aria-label="Open {{ $form->title }} in the designer">
            @include('cms_forms.partials.thumb', ['form' => $form])
            <span class="fi-card-open"><span><i class="fas fa-pen-ruler"></i> Open designer</span></span>
          </a>
          <div class="fi-card-body">
            <div class="fi-card-top">
              <div style="min-width:0">
                <a class="fi-card-title" href="{{ route('cms.forms.edit', $form) }}">{{ $form->title }}</a>
                <div class="fi-card-url cf-mono">/forms/{{ $form->slug }}</div>
              </div>
              @include('cms_forms.partials.status-badge', ['status' => $form->status, 'label' => $form->isPublished() ? 'Live' : 'Draft'])
            </div>
            <div class="fi-metrics">
              <a class="fi-metric" href="{{ route('cms.forms.responses.index', $form) }}"><b>{{ number_format($form->submissions_count) }}</b><span>Responses</span></a>
              <a class="fi-metric {{ $form->new_submissions_count ? 'is-new' : '' }}" href="{{ route('cms.forms.responses.index', [$form, 'status' => 'new']) }}"><b>{{ number_format($form->new_submissions_count) }}</b><span>New</span></a>
              <div class="fi-metric"><b>{{ $questionCount($form) }}</b><span>Questions</span></div>
            </div>
            <div class="fi-card-foot">
              <span class="fi-when">
                <i class="far fa-clock"></i>
                @if ($form->submissions_max_submitted_at)
                  Last response {{ $timeAgo($form->submissions_max_submitted_at) }}
                @else
                  Created {{ $form->created_at->format('M j, Y') }}
                @endif
              </span>
              <div class="fi-foot-actions">
                @if ($form->isPublished())
                  <button type="button" class="cf-btn cf-btn-ghost cf-btn-icon" title="Copy public link" onclick="cfCopy('{{ $form->publicUrl() }}', this)"><i class="fas fa-link"></i></button>
                @endif
                @include('cms_forms.partials.form-menu', ['form' => $form])
              </div>
            </div>
          </div>
        </article>
      @endforeach
      <button type="button" class="fi-new" data-new-tile onclick="cfOpen('cfCreateDialog')">
        <span class="fi-new-icon"><i class="fas fa-plus"></i></span>
        Create a new form
      </button>
    </div>

    {{-- List view --}}
    <div class="cf-card cf-card-table fi-hidden" id="fiList">
      <table class="cf-table">
        <thead>
          <tr>
            <th>Form</th>
            <th>Status</th>
            <th class="num">Responses</th>
            <th class="num">Questions</th>
            <th>Last response</th>
            <th>Created</th>
            <th class="num"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
        @foreach ($forms as $form)
          <tr data-item data-title="{{ strtolower($form->title.' '.$form->slug) }}" data-status="{{ $form->status }}"
              data-created="{{ $form->created_at->timestamp }}" data-activity="{{ $form->submissions_max_submitted_at ? \Carbon\Carbon::parse($form->submissions_max_submitted_at)->timestamp : 0 }}" data-responses="{{ $form->submissions_count }}">
            <td>
              <div class="fi-list-form">
                <a class="fi-list-thumb" href="{{ route('cms.forms.edit', $form) }}" tabindex="-1">@include('cms_forms.partials.thumb', ['form' => $form, 'limit' => 5])</a>
                <div style="min-width:0">
                  <a href="{{ route('cms.forms.edit', $form) }}" class="cf-name">{{ $form->title }}</a>
                  <div class="cf-secondary cf-mono">/forms/{{ $form->slug }}</div>
                </div>
              </div>
            </td>
            <td>@include('cms_forms.partials.status-badge', ['status' => $form->status, 'label' => $form->isPublished() ? 'Published' : 'Draft'])</td>
            <td class="num">
              <a href="{{ route('cms.forms.responses.index', $form) }}" class="cf-name">{{ number_format($form->submissions_count) }}</a>
              @if ($form->new_submissions_count > 0)
                <span class="cf-count-pill" title="New responses">{{ $form->new_submissions_count }} new</span>
              @endif
            </td>
            <td class="num">{{ $questionCount($form) }}</td>
            <td>{{ $timeAgo($form->submissions_max_submitted_at) ?? '—' }}</td>
            <td>{{ $form->created_at->format('M j, Y') }}</td>
            <td class="num">
              <div style="display:inline-flex;gap:6px;align-items:center">
                <a href="{{ route('cms.forms.edit', $form) }}" class="cf-btn cf-btn-outline cf-btn-sm"><i class="fas fa-pen-ruler"></i> Edit</a>
                @include('cms_forms.partials.form-menu', ['form' => $form])
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>

    <div class="cf-card fi-hidden" id="fiNoResults">
      <div class="cf-empty">
        <div class="cf-empty-icon"><i class="fas fa-magnifying-glass"></i></div>
        <div class="cf-empty-title">No forms match</div>
        <div class="cf-empty-sub">Try a different search or status.</div>
        <button type="button" class="cf-btn cf-btn-outline" onclick="fiReset()">Clear filters</button>
      </div>
    </div>
  @endif
</div>

{{-- Create form --}}
<div class="cf-dialog-overlay" id="cfCreateDialog" onclick="if (event.target === this) cfClose('cfCreateDialog')">
  <form class="cf-dialog" method="POST" action="{{ route('cms.forms.store') }}" style="width:640px" onsubmit="this.querySelector('[type=submit]').disabled = true;">
    @csrf
    <h2>Create form</h2>
    <p class="cf-dialog-desc">Pick a starting point - every field, colour and layout can be changed in the designer. It starts as a draft, so nothing is public until you publish.</p>
    <div class="cf-field">
      <label class="cf-label" for="cfNewTitle">Form name</label>
      <input class="cf-input" id="cfNewTitle" name="title" maxlength="255" placeholder="e.g. Autism Awareness Day Registration">
      <span class="cf-hint">Leave empty to use the template's name.</span>
    </div>
    <div class="cf-label" style="margin:6px 0 8px">Start from</div>
    <div class="fi-templates">
      @foreach ($templates as $key => $t)
        <label class="fi-template">
          <input type="radio" name="template" value="{{ $key }}" @checked($loop->first)>
          <span class="fi-template-check"><i class="fas fa-check"></i></span>
          <span class="fi-template-icon"><i class="fas {{ $t['icon'] }}"></i></span>
          <strong>{{ $t['name'] }}</strong>
          <small>{{ $t['description'] }}</small>
        </label>
      @endforeach
    </div>
    <div class="cf-dialog-foot">
      <button type="button" class="cf-btn cf-btn-outline" onclick="cfClose('cfCreateDialog')">Cancel</button>
      <button type="submit" class="cf-btn cf-btn-primary">Create &amp; open designer <i class="fas fa-arrow-right"></i></button>
    </div>
  </form>
</div>

@include('cms_forms.partials.scripts')
<script>
(function () {
  const grid = document.getElementById('fiGrid');
  if (!grid) return;
  const list = document.getElementById('fiList');
  const tbody = list.querySelector('tbody');
  const none = document.getElementById('fiNoResults');
  const search = document.getElementById('fiSearch');
  const sort = document.getElementById('fiSort');
  const newTile = grid.querySelector('[data-new-tile]');
  const store = { get(k) { try { return localStorage.getItem(k); } catch (_) { return null; } }, set(k, v) { try { localStorage.setItem(k, v); } catch (_) {} } };
  let status = 'all';
  let view = store.get('cmsForms.view') === 'list' ? 'list' : 'grid';
  sort.value = store.get('cmsForms.sort') || 'created';

  function apply() {
    const q = search.value.trim().toLowerCase();
    const key = sort.value;
    const cmp = (a, b) => key === 'title'
      ? a.dataset.title.localeCompare(b.dataset.title)
      : Number(b.dataset[key]) - Number(a.dataset[key]);
    let shown = 0;
    [grid, tbody].forEach(container => {
      const items = [...container.querySelectorAll(':scope > [data-item]')].sort(cmp);
      items.forEach(el => {
        const ok = (status === 'all' || el.dataset.status === status) && (!q || el.dataset.title.includes(q));
        el.classList.toggle('fi-hidden', !ok);
        container.insertBefore(el, container === grid ? newTile : null);
        if (ok && container === grid) shown++;
      });
    });
    const filtering = q || status !== 'all';
    newTile.classList.toggle('fi-hidden', !!filtering);
    grid.classList.toggle('fi-hidden', view !== 'grid' || shown === 0);
    list.classList.toggle('fi-hidden', view !== 'list' || shown === 0);
    none.classList.toggle('fi-hidden', shown !== 0);
    document.querySelectorAll('[data-view]').forEach(b => b.classList.toggle('is-on', b.dataset.view === view));
    document.querySelectorAll('.fi-tab').forEach(b => b.classList.toggle('is-on', b.dataset.status === status));
  }
  window.fiReset = () => { search.value = ''; status = 'all'; apply(); };
  search.addEventListener('input', apply);
  sort.addEventListener('change', () => { store.set('cmsForms.sort', sort.value); apply(); });
  document.querySelectorAll('.fi-tab').forEach(b => b.addEventListener('click', () => { status = b.dataset.status; apply(); }));
  document.querySelectorAll('[data-view]').forEach(b => b.addEventListener('click', () => { view = b.dataset.view; store.set('cmsForms.view', view); apply(); }));
  apply();
})();
</script>
@endsection
