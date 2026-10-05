@extends('layouts.admin-sidebar')

@section('title', 'Vendors · Engage Clinic')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
@include('cms_forms.partials.styles')
<style>
/* Toolbar */
.vn-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 14px; }
.vn-filters { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-left: auto; flex: 1 1 420px; justify-content: flex-end; }
.vn-search { flex: 1 1 240px; max-width: 340px; position: relative; }
.vn-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--cf-subtle-foreground); font-size: 12.5px; }
.vn-search .cf-input { padding-left: 32px; height: 36px; }
.vn-filters .cf-select { width: auto; max-width: 260px; height: 36px; padding-top: 0; padding-bottom: 0; }
.vn-filter-note { display: flex; align-items: center; gap: 8px; margin: -4px 0 12px; font-size: 13px; color: var(--cf-muted-foreground); }

/* Table */
.vn-table tbody tr { cursor: pointer; }
.vn-table tbody tr td:first-child { position: relative; }
.vn-table tbody tr.is-new td:first-child::before { content: ''; position: absolute; left: 0; top: 10px; bottom: 10px; width: 3px; border-radius: 0 3px 3px 0; background: var(--cf-primary); }
.vn-vendor { display: flex; align-items: center; gap: 12px; min-width: 0; }
.vn-vendor-text { min-width: 0; }
.vn-vendor .cf-name { display: inline-flex; align-items: center; gap: 7px; }
.vn-new-dot { width: 7px; height: 7px; border-radius: 99px; background: var(--cf-primary); flex-shrink: 0; }
.vn-ref { font-size: 12px; color: var(--cf-subtle-foreground); font-weight: 700; }
.vn-contact { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 2px; font-size: 12.5px; color: var(--cf-muted-foreground); }
.vn-contact span { display: inline-flex; align-items: center; gap: 5px; max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vn-contact i { font-size: 10.5px; color: var(--cf-subtle-foreground); }
.vn-service { max-width: 300px; }
.vn-service div { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vn-badges { display: flex; gap: 6px; flex-wrap: wrap; }
.vn-go { color: var(--cf-subtle-foreground); font-size: 12px; }
.vn-table tbody tr:hover .vn-go { color: var(--cf-primary); }

.vn-pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding: 12px 16px; border-top: 1px solid var(--cf-border); font-size: 13px; color: var(--cf-muted-foreground); }
.vn-pager-links { display: flex; gap: 6px; align-items: center; }

@media (max-width: 760px) {
  .vn-filters { margin-left: 0; justify-content: stretch; }
  .vn-search { max-width: none; flex-basis: 100%; }
  .vn-filters .cf-select { flex: 1; max-width: none; }
  /* Rows become stacked cards on phones instead of a sideways-scrolling table. */
  .cf-card-table { overflow: visible; }
  .vn-table { min-width: 0; }
  .vn-table thead { display: none; }
  .vn-table, .vn-table tbody, .vn-table tr, .vn-table td { display: block; width: 100%; }
  .vn-table tr { padding: 12px 14px; border-bottom: 1px solid var(--cf-border); }
  .vn-table tbody tr:last-child { border-bottom: 0; }
  .vn-table td { padding: 0; border: 0 !important; }
  .vn-table td + td { margin-top: 8px; }
  .vn-table td.num { display: none; }
  .vn-table tbody tr:hover td { background: none; }
  .vn-table tbody tr.is-new td:first-child::before { left: -14px; top: 0; bottom: 0; }
  .vn-service { max-width: none; }
}
</style>

@php
  $currentStatus = $filters['status'] ?? 'all';
  $search = $filters['search'] ?? null;
  $category = $filters['category'] ?? null;
  $filtered = filled($search) || filled($category);
  $tabUrl = fn ($status) => route('vendors.index', array_filter(['status' => $status === 'all' ? null : $status, 'search' => $search, 'category' => $category]));
  $complianceLabels = ['complete' => 'Complete', 'pending' => 'Pending', 'expired' => 'Expired'];
  $registrationUrl = route('vendor-registration');
@endphp

<div class="cf-root">
  <div class="cf-header">
    <div>
      <div class="cf-heading">Vendors</div>
      <div class="cf-subheading">Every registration submitted through the website's vendor form. Open one to review its documents and set its status.</div>
    </div>
    <div class="cf-header-actions">
      <button type="button" class="cf-btn cf-btn-outline" onclick="cfCopy(@js($registrationUrl), this)"><i class="fas fa-link"></i> Copy form link</button>
      <a href="{{ $registrationUrl }}" target="_blank" rel="noopener" class="cf-btn cf-btn-outline"><i class="fas fa-arrow-up-right-from-square"></i> Open form</a>
    </div>
  </div>

  <div class="cf-stats">
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#f4f4f5;color:#3f3f46"><i class="fas fa-handshake"></i></div>
      <div><div class="cf-stat-label">Total vendors</div><div class="cf-stat-value">{{ number_format($totalCount) }}</div>
        <div class="cf-stat-sub">{{ number_format($statusCounts['active']) }} active</div></div>
    </div>
    <div class="cf-stat {{ $stats['new'] > 0 ? 'is-attention' : '' }}">
      <div class="cf-stat-icon" style="background:var(--cf-primary-soft);color:var(--cf-primary)"><i class="fas fa-bell"></i></div>
      <div><div class="cf-stat-label">Under review</div><div class="cf-stat-value" style="{{ $stats['new'] > 0 ? 'color:var(--cf-primary)' : '' }}">{{ number_format($statusCounts['under_review']) }}</div>
        <div class="cf-stat-sub">{!! $stats['new'] > 0 ? '<strong>'.number_format($stats['new']).' new</strong> not opened yet' : 'none waiting unopened' !!}</div></div>
    </div>
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#fefce8;color:#a16207"><i class="fas fa-file-circle-exclamation"></i></div>
      <div><div class="cf-stat-label">Documents expiring</div><div class="cf-stat-value">{{ number_format($stats['documents_expiring']) }}</div>
        <div class="cf-stat-sub">expired or within 30 days · {{ $stats['contracts_expiring'] }} {{ \Illuminate\Support\Str::plural('contract', $stats['contracts_expiring']) }} ending</div></div>
    </div>
    <div class="cf-stat">
      <div class="cf-stat-icon" style="background:#EAF1F8;color:#16436E"><i class="fas fa-shield-halved"></i></div>
      <div><div class="cf-stat-label">Critical vendors</div><div class="cf-stat-value">{{ number_format($stats['critical']) }}</div>
        <div class="cf-stat-sub">{{ $statusCounts['suspended'] + $statusCounts['terminated'] }} suspended or terminated</div></div>
    </div>
  </div>

  @if ($hasVendors)
    <div class="vn-toolbar">
      <nav class="cf-tabs" aria-label="Filter by status">
        <a class="cf-tab {{ $currentStatus === 'all' ? 'is-on' : '' }}" href="{{ $tabUrl('all') }}">All <span class="n">{{ $totalCount }}</span></a>
        @foreach ($statuses as $key => $label)
          <a class="cf-tab {{ $currentStatus === $key ? 'is-on' : '' }} {{ $key === 'under_review' && $stats['new'] > 0 ? 'is-attention' : '' }}" href="{{ $tabUrl($key) }}">{{ $label }} <span class="n">{{ $statusCounts[$key] }}</span></a>
        @endforeach
      </nav>
      <form class="vn-filters" method="GET" action="{{ route('vendors.index') }}">
        @if ($currentStatus !== 'all')<input type="hidden" name="status" value="{{ $currentStatus }}">@endif
        <div class="vn-search">
          <i class="fas fa-search"></i>
          <input class="cf-input" type="search" name="search" value="{{ $search }}" placeholder="Search company, contact, email or vendor ID" aria-label="Search vendors">
        </div>
        <select class="cf-select" name="category" aria-label="Filter by category" onchange="this.form.submit()">
          <option value="">All categories</option>
          @foreach ($categories as $option)
            <option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>
          @endforeach
        </select>
      </form>
    </div>
    @if ($filtered)
      <div class="vn-filter-note">
        <span>{{ number_format($vendors->total()) }} {{ \Illuminate\Support\Str::plural('vendor', $vendors->total()) }} match
          @if (filled($search)) “<strong style="color:var(--cf-foreground)">{{ $search }}</strong>”@endif
          @if (filled($category)) in {{ $category }}@endif
        </span>
        <a href="{{ route('vendors.index', array_filter(['status' => $currentStatus === 'all' ? null : $currentStatus])) }}" class="cf-btn cf-btn-ghost cf-btn-sm"><i class="fas fa-xmark"></i> Clear</a>
      </div>
    @endif
  @endif

  <div class="cf-card {{ $vendors->isEmpty() ? '' : 'cf-card-table' }}">
    @if ($vendors->isEmpty())
      <div class="cf-empty">
        @if ($hasVendors)
          <div class="cf-empty-icon"><i class="fas fa-magnifying-glass"></i></div>
          <div class="cf-empty-title">No vendors match</div>
          <div class="cf-empty-sub">Try a different search, status or category.</div>
          <a href="{{ route('vendors.index') }}" class="cf-btn cf-btn-outline">Clear filters</a>
        @else
          <div class="cf-empty-icon"><i class="fas fa-handshake"></i></div>
          <div class="cf-empty-title">No vendor registrations yet</div>
          <div class="cf-empty-sub">Share the registration form - it is also linked in the website footer. Every submission will appear here.</div>
          <button type="button" class="cf-btn cf-btn-primary" onclick="cfCopy(@js($registrationUrl), this)"><i class="fas fa-link"></i> Copy form link</button>
        @endif
      </div>
    @else
      <table class="cf-table vn-table">
        <thead>
          <tr>
            <th>Vendor</th>
            <th>Category &amp; service</th>
            <th>Compliance</th>
            <th>Submitted</th>
            <th>Status</th>
            <th class="num"><span class="sr-only">Open</span></th>
          </tr>
        </thead>
        <tbody>
        @foreach ($vendors as $vendor)
          @php $showUrl = route('vendors.show', $vendor); @endphp
          <tr class="{{ $vendor->isNew() ? 'is-new' : '' }}" data-href="{{ $showUrl }}">
            <td>
              <div class="vn-vendor">
                @include('cms_forms.partials.avatar', ['name' => $vendor->legal_name, 'size' => 36])
                <div class="vn-vendor-text">
                  <a class="cf-name" href="{{ $showUrl }}">
                    @if ($vendor->isNew())<span class="vn-new-dot" title="New"></span>@endif
                    {{ $vendor->legal_name }}
                    <span class="vn-ref">{{ $vendor->reference }}</span>
                  </a>
                  <div class="vn-contact">
                    <span><i class="fas fa-user"></i>{{ $vendor->contact_name }}</span>
                    <span><i class="fas fa-envelope"></i>{{ $vendor->email }}</span>
                    <span><i class="fas fa-phone"></i>{{ $vendor->phone }}</span>
                  </div>
                </div>
              </div>
            </td>
            <td>
              <div class="vn-service">
                <div title="{{ $vendor->category_label }}">{{ $vendor->category_label }}</div>
                <div class="cf-secondary" title="{{ $vendor->primary_service }}">{{ $vendor->primary_service }}</div>
              </div>
            </td>
            <td>
              @include('vendor.partials.status-badge', ['status' => $vendor->compliance_status, 'label' => $complianceLabels[$vendor->compliance_status]])
              <div class="cf-secondary">{{ $vendor->documents->count() }} {{ \Illuminate\Support\Str::plural('document', $vendor->documents->count()) }}</div>
            </td>
            <td>
              <span title="{{ $vendor->created_at->format('F j, Y g:i A') }}">{{ $vendor->created_at->diffForHumans() }}</span>
              <div class="cf-secondary">{{ $vendor->created_at->format('M j, Y · g:i A') }}</div>
            </td>
            <td>
              <div class="vn-badges">
                @include('vendor.partials.status-badge', ['status' => $vendor->status, 'label' => $vendor->status_label])
                @if ($vendor->is_critical)
                  @include('vendor.partials.status-badge', ['status' => 'critical', 'label' => 'Critical'])
                @endif
              </div>
            </td>
            <td class="num"><i class="fas fa-chevron-right vn-go"></i></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div class="vn-pager">
        <span>Showing {{ $vendors->firstItem() }}–{{ $vendors->lastItem() }} of {{ number_format($vendors->total()) }}</span>
        @if ($vendors->hasPages())
          <div class="vn-pager-links">
            <a class="cf-btn cf-btn-outline cf-btn-sm {{ $vendors->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $vendors->previousPageUrl() ?? '#' }}"><i class="fas fa-chevron-left"></i> Previous</a>
            <span>Page {{ $vendors->currentPage() }} of {{ $vendors->lastPage() }}</span>
            <a class="cf-btn cf-btn-outline cf-btn-sm {{ $vendors->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $vendors->nextPageUrl() ?? '#' }}">Next <i class="fas fa-chevron-right"></i></a>
          </div>
        @endif
      </div>
    @endif
  </div>
</div>

@include('cms_forms.partials.scripts')
<script>
  // Whole row opens the vendor; the name link keeps its own target, and
  // Ctrl/Cmd/middle-click opens a new tab.
  document.querySelectorAll('.vn-table tr[data-href]').forEach(row => {
    row.addEventListener('click', (e) => {
      if (e.target.closest('a, button') || window.getSelection().toString()) return;
      if (e.ctrlKey || e.metaKey) window.open(row.dataset.href, '_blank');
      else window.location = row.dataset.href;
    });
    row.addEventListener('auxclick', (e) => { if (e.button === 1 && !e.target.closest('a')) window.open(row.dataset.href, '_blank'); });
  });
</script>
@endsection
