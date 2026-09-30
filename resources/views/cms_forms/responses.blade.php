@extends('layouts.admin-sidebar')

@section('title', 'Responses · '.$form->title.' · Engage Clinic')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
@include('cms_forms.partials.styles')
<style>
/* Toolbar */
.rs-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
.rs-filters { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-left: auto; flex: 1 1 520px; justify-content: flex-end; }
.rs-search { flex: 1 1 240px; max-width: 360px; position: relative; }
.rs-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--cf-subtle-foreground); font-size: 12.5px; }
.rs-search .cf-input { padding-left: 32px; height: 36px; }
.rs-dates { display: inline-flex; align-items: center; height: 36px; border: 1px solid var(--cf-input); border-radius: var(--cf-radius-sm); background: #fff; box-shadow: var(--cf-shadow-sm); }
.rs-dates:focus-within { border-color: var(--cf-primary); box-shadow: 0 0 0 3px var(--cf-ring); }
.rs-dates > i { padding: 0 4px 0 11px; font-size: 12.5px; color: var(--cf-subtle-foreground); }
.rs-dates input { height: 34px; border: 0; background: transparent; font: 500 13px var(--cf-font); color: var(--cf-foreground); padding: 0 6px; outline: none; width: 132px; }
.rs-dates .sep { color: var(--cf-subtle-foreground); font-size: 12px; }
.rs-filter-note { display: flex; align-items: center; gap: 8px; margin: -4px 0 12px; font-size: 13px; color: var(--cf-muted-foreground); }

/* Table */
.rs-table tbody tr { cursor: pointer; }
.rs-table tbody tr td:first-child { position: relative; }
.rs-table tbody tr.is-new td:first-child::before { content: ''; position: absolute; left: 0; top: 10px; bottom: 10px; width: 3px; border-radius: 0 3px 3px 0; background: var(--cf-primary); }
.rs-client { display: flex; align-items: center; gap: 12px; min-width: 0; }
.rs-client-text { min-width: 0; }
.rs-client .cf-name { display: inline-flex; align-items: center; gap: 7px; }
.rs-new-dot { width: 7px; height: 7px; border-radius: 99px; background: var(--cf-primary); flex-shrink: 0; }
.rs-contact { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 2px; font-size: 12.5px; color: var(--cf-muted-foreground); }
.rs-contact span { display: inline-flex; align-items: center; gap: 5px; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rs-contact i { font-size: 10.5px; color: var(--cf-subtle-foreground); }
.rs-id { font-size: 12px; color: var(--cf-subtle-foreground); font-weight: 700; }
.rs-preview { max-width: 380px; font-size: 13px; line-height: 1.45; }
.rs-preview div { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rs-preview b { font-weight: 600; color: var(--cf-muted-foreground); }
.rs-lead { display: inline-flex; align-items: center; gap: 5px; margin-top: 5px; font-size: 12px; font-weight: 700; color: var(--cf-success); text-decoration: none; }
a.rs-lead:hover { text-decoration: underline; }
.rs-go { color: var(--cf-subtle-foreground); font-size: 12px; }
.rs-table tbody tr:hover .rs-go { color: var(--cf-primary); }

.rs-pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 12px 16px; border-top: 1px solid var(--cf-border); font-size: 13px; color: var(--cf-muted-foreground); }
.rs-pager-links { display: flex; gap: 6px; align-items: center; }

@media (max-width: 760px) {
  .rs-filters { margin-left: 0; justify-content: stretch; }
  .rs-search { max-width: none; flex-basis: 100%; }
  .rs-dates { flex: 1; } .rs-dates input { flex: 1; width: auto; min-width: 0; }
  /* Rows become stacked cards on phones instead of a sideways-scrolling table. */
  .cf-card-table { overflow: visible; }
  .rs-table { min-width: 0; }
  .rs-table thead { display: none; }
  .rs-table, .rs-table tbody, .rs-table tr, .rs-table td { display: block; width: 100%; }
  .rs-table tr { padding: 12px 14px; border-bottom: 1px solid var(--cf-border); }
  .rs-table tbody tr:last-child { border-bottom: 0; }
  .rs-table td { padding: 0; border: 0 !important; }
  .rs-table td + td { margin-top: 8px; }
  .rs-table td.num { display: none; }
  .rs-table tbody tr:hover td { background: none; }
  .rs-table tbody tr.is-new td:first-child::before { left: -14px; top: 0; bottom: 0; }
  .rs-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
  .rs-meta .cf-secondary { margin: 0; }
  .rs-preview { max-width: none; }
}
</style>

@php
  $filtered = filled($filters['search'] ?? null) || filled($filters['from'] ?? null) || filled($filters['to'] ?? null);
  $currentStatus = $filters['status'] ?? 'all';
  $tabUrl = fn ($status) => route('cms.forms.responses.index', array_filter([$form, 'status' => $status === 'all' ? null : $status] + array_intersect_key($filters, array_flip(['search', 'from', 'to']))));
  $exportUrl = route('cms.forms.responses.export', array_filter([$form] + $filters, fn ($v) => $v !== null && $v !== 'all'));
@endphp

<div class="cf-root">
  <div class="cf-crumbs">
    <span>CMS</span><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <a href="{{ route('cms.forms.index') }}">Forms</a><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <a href="{{ route('cms.forms.edit', $form) }}">{{ $form->title }}</a><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <span>Responses</span>
  </div>
  <div class="cf-header">
    <div>
      <div class="cf-heading">{{ $form->title }}
        @include('cms_forms.partials.status-badge', ['status' => $form->status, 'label' => $form->isPublished() ? 'Live' : 'Draft'])
      </div>
      <div class="cf-subheading">Everything clients submitted through this form. Open a response to review it or turn it into a lead.</div>
    </div>
    <div class="cf-header-actions">
      @if ($form->isPublished())
        <button type="button" class="cf-btn cf-btn-outline" onclick="cfCopy('{{ $form->publicUrl() }}', this)"><i class="fas fa-link"></i> Copy public link</button>
      @endif
      @if ($totalCount > 0)
        <a href="{{ $exportUrl }}" class="cf-btn cf-btn-outline" title="Download the responses shown below as a spreadsheet"><i class="fas fa-file-arrow-down"></i> Export CSV</a>
      @endif
      <a href="{{ route('cms.forms.edit', $form) }}" class="cf-btn cf-btn-outline"><i class="fas fa-pen-ruler"></i> Edit form</a>
    </div>
  </div>

  <div class="cf-stats">
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#f4f4f5;color:#3f3f46"><i class="fas fa-inbox"></i></div>
      <div><div class="cf-stat-label">Total responses</div><div class="cf-stat-value">{{ number_format($totalCount) }}</div>
        <div class="cf-stat-sub">{{ $stats['this_week'] }} in the last 7 days</div></div>
    </div>
    <div class="cf-stat {{ $statusCounts['new'] > 0 ? 'is-attention' : '' }}">
      <div class="cf-stat-icon" style="background:var(--cf-primary-soft);color:var(--cf-primary)"><i class="fas fa-bell"></i></div>
      <div><div class="cf-stat-label">Needs review</div><div class="cf-stat-value" style="{{ $statusCounts['new'] > 0 ? 'color:var(--cf-primary)' : '' }}">{{ number_format($statusCounts['new']) }}</div>
        <div class="cf-stat-sub">{!! $statusCounts['new'] > 0 ? 'new responses <strong>waiting</strong>' : 'all caught up' !!}</div></div>
    </div>
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#f0fdf4;color:#15803d"><i class="fas fa-user-plus"></i></div>
      <div><div class="cf-stat-label">Converted to leads</div><div class="cf-stat-value">{{ number_format($statusCounts['converted']) }}</div>
        <div class="cf-stat-sub">{{ $stats['conversion_rate'] }}% of responses</div></div>
    </div>
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#EAF1F8;color:#16436E"><i class="far fa-clock"></i></div>
      <div style="min-width:0"><div class="cf-stat-label">Latest response</div>
        @if ($stats['last_at'])
          @php $lastAt = \Carbon\Carbon::parse($stats['last_at']); @endphp
          <div class="cf-stat-value is-text" title="{{ $lastAt->format('F j, Y g:i A') }}">{{ $lastAt->diffForHumans() }}</div>
          <div class="cf-stat-sub">{{ $lastAt->format('M j, Y · g:i A') }}</div>
        @else
          <div class="cf-stat-value is-text">None yet</div>
          <div class="cf-stat-sub">{{ $form->isPublished() ? 'waiting for the first client' : 'publish to start collecting' }}</div>
        @endif
      </div>
    </div>
  </div>

  @if ($totalCount > 0)
    <div class="rs-toolbar">
      <nav class="cf-tabs" aria-label="Filter by status">
        <a class="cf-tab {{ $currentStatus === 'all' ? 'is-on' : '' }}" href="{{ $tabUrl('all') }}">All <span class="n">{{ $totalCount }}</span></a>
        @foreach ($statuses as $key => $label)
          <a class="cf-tab {{ $currentStatus === $key ? 'is-on' : '' }} {{ $key === 'new' && $statusCounts[$key] > 0 ? 'is-attention' : '' }}" href="{{ $tabUrl($key) }}">{{ $label }} <span class="n">{{ $statusCounts[$key] }}</span></a>
        @endforeach
      </nav>
      <form class="rs-filters" method="GET" action="{{ route('cms.forms.responses.index', $form) }}" id="rsFilters">
        @if ($currentStatus !== 'all')<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
        <div class="rs-search">
          <i class="fas fa-search"></i>
          <input class="cf-input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email, any answer or #id" aria-label="Search responses">
        </div>
        <div class="rs-dates" title="Submitted between">
          <i class="far fa-calendar"></i>
          <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="Submitted from" onchange="this.form.submit()">
          <span class="sep">–</span>
          <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="Submitted to" onchange="this.form.submit()">
        </div>
      </form>
    </div>
    @if ($filtered)
      <div class="rs-filter-note">
        <span>{{ number_format($submissions->total()) }} {{ \Illuminate\Support\Str::plural('response', $submissions->total()) }} match
          @if (filled($filters['search'] ?? null)) “<strong style="color:var(--cf-foreground)">{{ $filters['search'] }}</strong>”@endif
          @if (filled($filters['from'] ?? null) || filled($filters['to'] ?? null))
            submitted {{ filled($filters['from'] ?? null) ? 'from '.\Carbon\Carbon::parse($filters['from'])->format('M j, Y') : '' }} {{ filled($filters['to'] ?? null) ? 'to '.\Carbon\Carbon::parse($filters['to'])->format('M j, Y') : '' }}
          @endif
        </span>
        <a href="{{ route('cms.forms.responses.index', array_filter([$form, 'status' => $currentStatus === 'all' ? null : $currentStatus])) }}" class="cf-btn cf-btn-ghost cf-btn-sm"><i class="fas fa-xmark"></i> Clear</a>
      </div>
    @endif
  @endif

  <div class="cf-card {{ $submissions->isEmpty() ? '' : 'cf-card-table' }}">
    @if ($submissions->isEmpty())
      <div class="cf-empty">
        @if ($totalCount > 0)
          <div class="cf-empty-icon"><i class="fas fa-magnifying-glass"></i></div>
          <div class="cf-empty-title">No responses match</div>
          <div class="cf-empty-sub">Try a different search, status or date range.</div>
          <a href="{{ route('cms.forms.responses.index', $form) }}" class="cf-btn cf-btn-outline">Clear filters</a>
        @else
          <div class="cf-empty-icon"><i class="fas fa-inbox"></i></div>
          <div class="cf-empty-title">No responses yet</div>
          <div class="cf-empty-sub">
            @if ($form->isPublished())
              Share the form's public link - every submission will appear here.
            @else
              This form is still a draft. Publish it to start collecting responses.
            @endif
          </div>
          @if ($form->isPublished())
            <button type="button" class="cf-btn cf-btn-primary" onclick="cfCopy('{{ $form->publicUrl() }}', this)"><i class="fas fa-link"></i> Copy public link</button>
          @else
            <a href="{{ route('cms.forms.edit', $form) }}" class="cf-btn cf-btn-primary"><i class="fas fa-pen-ruler"></i> Open designer</a>
          @endif
        @endif
      </div>
    @else
      <table class="cf-table rs-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Answers</th>
            <th>Submitted</th>
            <th>Status</th>
            <th class="num"><span class="sr-only">Open</span></th>
          </tr>
        </thead>
        <tbody>
        @foreach ($submissions as $submission)
          @php
            $isNew = $submission->status === \App\Models\FormSubmission::STATUS_NEW;
            $showUrl = route('cms.forms.responses.show', $submission);
          @endphp
          <tr class="{{ $isNew ? 'is-new' : '' }}" data-href="{{ $showUrl }}">
            <td>
              <div class="rs-client">
                @include('cms_forms.partials.avatar', ['name' => $submission->identity['name'], 'size' => 36])
                <div class="rs-client-text">
                  <a class="cf-name" href="{{ $showUrl }}">
                    @if ($isNew)<span class="rs-new-dot" title="New"></span>@endif
                    {{ $submission->identity['name'] ?: 'No name given' }}
                    <span class="rs-id">#{{ $submission->id }}</span>
                  </a>
                  <div class="rs-contact">
                    @if ($submission->identity['email'])<span><i class="fas fa-envelope"></i>{{ $submission->identity['email'] }}</span>@endif
                    @if ($submission->identity['phone'])<span><i class="fas fa-phone"></i>{{ $submission->identity['phone'] }}</span>@endif
                    @if (! $submission->identity['email'] && ! $submission->identity['phone'])<span>No contact details</span>@endif
                  </div>
                </div>
              </div>
            </td>
            <td>
              <div class="rs-preview">
                @forelse ($submission->preview as $answer)
                  <div title="{{ $answer['label'] }}: {{ $answer['text'] }}"><b>{{ $answer['label'] }}:</b> {{ $answer['text'] }}</div>
                @empty
                  <span class="cf-secondary">—</span>
                @endforelse
              </div>
            </td>
            <td class="rs-meta">
              <span title="{{ $submission->submitted_at->format('F j, Y g:i A') }}">{{ $submission->submitted_at->diffForHumans() }}</span>
              <div class="cf-secondary">{{ $submission->submitted_at->format('M j, Y · g:i A') }}</div>
            </td>
            <td>
              @include('cms_forms.partials.status-badge', ['status' => $submission->status, 'label' => $submission->status_label])
              @if ($submission->lead_id)
                <div><a class="rs-lead" href="{{ route('leads.index', ['lead' => $submission->lead_id]) }}"><i class="fas fa-arrow-right"></i> Lead {{ \App\Services\CmsForms\LeadConversionService::reference($submission->lead_id) }}</a></div>
              @endif
            </td>
            <td class="num"><i class="fas fa-chevron-right rs-go"></i></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div class="rs-pager">
        <span>Showing {{ $submissions->firstItem() }}–{{ $submissions->lastItem() }} of {{ number_format($submissions->total()) }}</span>
        @if ($submissions->hasPages())
          <div class="rs-pager-links">
            <a class="cf-btn cf-btn-outline cf-btn-sm {{ $submissions->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $submissions->previousPageUrl() ?? '#' }}"><i class="fas fa-chevron-left"></i> Previous</a>
            <span>Page {{ $submissions->currentPage() }} of {{ $submissions->lastPage() }}</span>
            <a class="cf-btn cf-btn-outline cf-btn-sm {{ $submissions->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $submissions->nextPageUrl() ?? '#' }}">Next <i class="fas fa-chevron-right"></i></a>
          </div>
        @endif
      </div>
    @endif
  </div>
</div>

@include('cms_forms.partials.scripts')
<script>
  // Whole row opens the response; real links inside it (lead, name) keep
  // their own target, and Ctrl/Cmd/middle-click opens a new tab.
  document.querySelectorAll('.rs-table tr[data-href]').forEach(row => {
    row.addEventListener('click', (e) => {
      if (e.target.closest('a, button') || window.getSelection().toString()) return;
      if (e.ctrlKey || e.metaKey) window.open(row.dataset.href, '_blank');
      else window.location = row.dataset.href;
    });
    row.addEventListener('auxclick', (e) => { if (e.button === 1 && !e.target.closest('a')) window.open(row.dataset.href, '_blank'); });
  });
</script>
@endsection
