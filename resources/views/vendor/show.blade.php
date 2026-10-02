@extends('layouts.admin-sidebar')

@section('title', $vendor->legal_name.' · Vendors · Engage Clinic')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
@include('cms_forms.partials.styles')
<style>
/* Vendor header */
.vd-hero { display: flex; align-items: center; gap: 16px; padding: 18px 20px; margin-bottom: 18px; flex-wrap: wrap; }
.vd-hero-main { flex: 1 1 360px; min-width: 0; }
.vd-hero-name { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0; font-family: var(--cf-font-display); font-size: 24px; font-weight: 600; line-height: 1.2; }
.vd-hero-meta { display: flex; align-items: center; gap: 6px 14px; flex-wrap: wrap; margin-top: 4px; font-size: 13px; color: var(--cf-muted-foreground); }
.vd-hero-meta i { font-size: 11px; margin-right: 4px; color: var(--cf-subtle-foreground); }
.vd-contacts { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.vd-contact { display: inline-flex; align-items: stretch; border: 1px solid var(--cf-border); border-radius: 999px; background: #fff; box-shadow: var(--cf-shadow-sm); overflow: hidden; max-width: 100%; }
.vd-contact a { display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px; font-size: 13px; font-weight: 600; color: var(--cf-foreground); text-decoration: none; min-width: 0; }
.vd-contact a span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vd-contact a:hover { background: var(--cf-accent); }
.vd-contact a i { color: var(--cf-muted-foreground); font-size: 12px; }
.vd-contact button { border: 0; border-left: 1px solid var(--cf-border); background: none; padding: 0 10px; color: var(--cf-subtle-foreground); cursor: pointer; font-size: 12px; }
.vd-contact button:hover { background: var(--cf-accent); color: var(--cf-foreground); }
.vd-nav { display: flex; align-items: center; gap: 6px; margin-left: auto; }

/* Details */
.vd-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 18px; align-items: start; }
.vd-main, .vd-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.vd-section-title { display: flex; align-items: center; gap: 8px; padding: 12px 20px 10px; background: #fafafa; border-bottom: 1px solid var(--cf-border); font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--cf-navy); }
.vd-section:first-child .vd-section-title { border-radius: var(--cf-radius) var(--cf-radius) 0 0; }
.vd-section + .vd-section .vd-section-title { border-top: 1px solid var(--cf-border); }
.vd-row { display: grid; grid-template-columns: minmax(140px, 32%) minmax(0, 1fr); gap: 6px 20px; padding: 12px 20px; border-bottom: 1px solid var(--cf-border); }
.vd-section:last-child .vd-row:last-child { border-bottom: 0; }
.vd-q { font-size: 13px; font-weight: 600; color: var(--cf-muted-foreground); line-height: 1.5; padding-top: 1px; }
.vd-a { font-size: 14.5px; font-weight: 600; line-height: 1.55; word-break: break-word; min-width: 0; }
.vd-a.is-long { white-space: pre-wrap; font-weight: 500; }
.vd-a a { color: var(--cf-navy); text-decoration: none; border-bottom: 1px dashed #b8c7d6; }
.vd-a a:hover { color: var(--cf-primary); }
.vd-none { color: var(--cf-subtle-foreground); font-weight: 500; font-style: italic; font-size: 13.5px; }
.vd-yes { color: #15803d; }

/* Documents */
.vd-docs-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--cf-border); }
.vd-docs-head .cf-panel-title { margin: 0; }
.vd-doc { display: flex; align-items: center; gap: 14px; padding: 14px 20px; border-bottom: 1px solid var(--cf-border); flex-wrap: wrap; }
.vd-doc:last-child { border-bottom: 0; }
.vd-doc-icon { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: var(--cf-primary-soft); color: var(--cf-primary); flex-shrink: 0; font-size: 16px; }
.vd-doc-main { flex: 1 1 260px; min-width: 0; }
.vd-doc-title { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 14px; font-weight: 700; }
.vd-doc-file { margin-top: 2px; font-size: 12.5px; color: var(--cf-muted-foreground); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vd-doc-meta { display: flex; gap: 4px 16px; flex-wrap: wrap; margin-top: 6px; font-size: 12.5px; color: var(--cf-muted-foreground); }
.vd-doc-meta b { font-weight: 700; color: var(--cf-foreground); }
.vd-doc-actions { display: flex; gap: 6px; }

/* Side panels */
.vd-switch { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 12px; margin-bottom: 12px; border: 1px solid var(--cf-border); border-radius: var(--cf-radius-sm); cursor: pointer; }
.vd-switch-text { font-size: 13.5px; font-weight: 700; }
.vd-switch-text small { display: block; margin-top: 2px; font-size: 12px; font-weight: 500; color: var(--cf-muted-foreground); line-height: 1.4; }
.vd-switch input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.vd-switch-track { position: relative; flex-shrink: 0; width: 36px; height: 20px; border-radius: 999px; background: #d4d4d8; transition: background-color .15s; }
.vd-switch-track::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 999px; background: #fff; box-shadow: var(--cf-shadow-sm); transition: transform .15s; }
.vd-switch input:checked + .vd-switch-track { background: var(--cf-primary); }
.vd-switch input:checked + .vd-switch-track::after { transform: translateX(16px); }
.vd-switch input:focus-visible + .vd-switch-track { box-shadow: 0 0 0 3px var(--cf-ring); }
.vd-meta-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--cf-border); font-size: 13px; }
.vd-meta-row:last-child { border-bottom: 0; padding-bottom: 0; }
.vd-meta-row > span:first-child { color: var(--cf-muted-foreground); white-space: nowrap; }
.vd-meta-row > span:last-child { font-weight: 700; text-align: right; min-width: 0; overflow-wrap: anywhere; }

@media (max-width: 1100px) { .vd-grid { grid-template-columns: 1fr; } }
@media (max-width: 640px) {
  .vd-hero { padding: 16px; }
  .vd-hero .cf-avatar { display: none !important; }
  .vd-hero-name { font-size: 21px; }
  .vd-nav { margin-left: 0; width: 100%; }
  .vd-nav .cf-btn { flex: 1; }
  .vd-row { grid-template-columns: 1fr; gap: 3px; padding: 12px 16px; }
  .vd-section-title, .vd-docs-head, .vd-doc { padding-left: 16px; padding-right: 16px; }
}
</style>

@php
  $canEdit = auth()->user()->levelFor('vendors') !== 'view';
  $phoneHref = preg_replace('/[^0-9+]/', '', $vendor->phone);
  $date = fn ($d) => $d?->format('M j, Y');
  $yesNo = fn ($v) => $v === null ? null : ($v ? 'Yes' : 'No');
  $iban = trim(chunk_split((string) $vendor->iban, 4, ' '));

  // label => value; a null value renders as "Not provided".
  $sections = [
    'Company details' => [
      'Registered company name' => $vendor->legal_name,
      'Trade name' => $vendor->trade_name,
      'Website' => $vendor->website,
      'Address' => $vendor->address,
      'City' => $vendor->city,
      'Emirate' => $vendor->emirate,
    ],
    'Contact person' => [
      'Full name' => $vendor->contact_name,
      'Position' => $vendor->position,
      'Mobile number' => $vendor->phone,
      'Email' => $vendor->email,
      'Alternate contact' => $vendor->alt_contact,
      'Accounts / finance email' => $vendor->finance_email,
    ],
    'Category & services' => [
      'Vendor category' => $vendor->category_label,
      'Primary service or product' => $vendor->primary_service,
      'Description of offer' => $vendor->description,
      'Years in business' => $vendor->years_in_business,
      'Referred by' => $vendor->referred_by,
    ],
    'Commercial terms' => [
      'Pricing / rate' => $vendor->pricing,
      'Estimated monthly cost' => $vendor->monthly_cost !== null ? 'AED '.number_format((float) $vendor->monthly_cost, 2) : null,
      'Payment terms' => $vendor->payment_terms,
      'Preferred payment method' => $vendor->payment_method,
      'VAT registered' => $yesNo($vendor->vat_registered),
      'TRN' => $vendor->trn,
      'Works against purchase orders' => $yesNo($vendor->accepts_po),
      'Proposed contract start' => $date($vendor->contract_start),
      'Proposed contract end' => $date($vendor->contract_end),
    ],
    'Bank details' => [
      'Bank name' => $vendor->bank_name,
      'Account holder name' => $vendor->account_holder,
      'IBAN' => $iban,
      'SWIFT / BIC' => $vendor->swift,
    ],
    'Declaration' => [
      'Declarations' => 'All three accepted',
      'Authorised signatory' => $vendor->signatory_name,
      'Signed' => $vendor->declared_at?->format('M j, Y · g:i A'),
    ],
  ];
  $links = ['Website' => fn ($v) => $v, 'Email' => fn ($v) => 'mailto:'.$v, 'Accounts / finance email' => fn ($v) => 'mailto:'.$v, 'Mobile number' => fn ($v) => 'tel:'.preg_replace('/[^0-9+]/', '', $v)];

  $documentStates = ['valid' => 'Valid', 'expiring' => 'Expiring soon', 'expired' => 'Expired', 'pending' => 'Pending expiry date', 'on_file' => 'On file'];
  $complianceLabels = ['complete' => 'Complete', 'pending' => 'Pending', 'expired' => 'Expired'];
  $fileSize = function ($bytes) {
    if (! $bytes) return null;
    if ($bytes < 1048576) return number_format($bytes / 1024, $bytes < 10240 ? 1 : 0).' KB';
    return number_format($bytes / 1048576, 1).' MB';
  };
  $fileIcon = fn ($mime) => match (true) {
    str_starts_with((string) $mime, 'image/') => 'fa-file-image',
    $mime === 'application/pdf' => 'fa-file-pdf',
    default => 'fa-file-lines',
  };
@endphp

<div class="cf-root">
  <div class="cf-crumbs">
    <a href="{{ route('vendors.index') }}">Vendors</a><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <span>{{ $vendor->reference }}</span>
  </div>

  <div class="cf-card vd-hero">
    @include('cms_forms.partials.avatar', ['name' => $vendor->legal_name, 'size' => 56])
    <div class="vd-hero-main">
      <h1 class="vd-hero-name">
        {{ $vendor->legal_name }}
        @include('vendor.partials.status-badge', ['status' => $vendor->status, 'label' => $vendor->status_label])
        @if ($vendor->is_critical)
          @include('vendor.partials.status-badge', ['status' => 'critical', 'label' => 'Critical vendor'])
        @endif
      </h1>
      <div class="vd-hero-meta">
        <span><i class="fas fa-hashtag"></i>{{ $vendor->reference }}</span>
        <span><i class="fas fa-layer-group"></i>{{ $vendor->category_label }}</span>
        <span title="{{ $vendor->created_at->format('F j, Y g:i A') }}"><i class="far fa-clock"></i>Submitted {{ $vendor->created_at->diffForHumans() }}</span>
      </div>
      <div class="vd-contacts">
        <span class="vd-contact"><a href="mailto:{{ $vendor->email }}" title="Send an email"><i class="fas fa-envelope"></i><span>{{ $vendor->email }}</span></a><button type="button" title="Copy email" aria-label="Copy email" onclick="cfCopy(@js($vendor->email), this)"><i class="far fa-copy"></i></button></span>
        <span class="vd-contact"><a href="tel:{{ $phoneHref }}" title="Call"><i class="fas fa-phone"></i><span>{{ $vendor->phone }}</span></a><button type="button" title="Copy phone number" aria-label="Copy phone number" onclick="cfCopy(@js($vendor->phone), this)"><i class="far fa-copy"></i></button></span>
      </div>
    </div>
    <div class="vd-nav">
      <a class="cf-btn cf-btn-outline {{ $neighbours['newer'] ? '' : 'is-disabled' }}" href="{{ $neighbours['newer'] ? route('vendors.show', $neighbours['newer']) : '#' }}"><i class="fas fa-chevron-left"></i> Newer</a>
      <a class="cf-btn cf-btn-outline {{ $neighbours['older'] ? '' : 'is-disabled' }}" href="{{ $neighbours['older'] ? route('vendors.show', $neighbours['older']) : '#' }}">Older <i class="fas fa-chevron-right"></i></a>
    </div>
  </div>

  @if ($errors->any())
    <div class="cf-alert is-error"><i class="fas fa-circle-exclamation"></i><span>{{ $errors->first() }}</span></div>
  @endif

  <div class="vd-grid">
    <div class="vd-main">
      {{-- Compliance documents --}}
      <div class="cf-card">
        <div class="vd-docs-head">
          <div class="cf-panel-title">Compliance documents</div>
          @include('vendor.partials.status-badge', ['status' => $vendor->compliance_status, 'label' => 'Compliance: '.$complianceLabels[$vendor->compliance_status]])
        </div>
        @foreach ($vendor->documents as $document)
          @php
            $state = $document->state;
            $days = $document->daysToExpiry();
            $stateLabel = $state === 'expiring' ? 'Expires in '.$days.' '.\Illuminate\Support\Str::plural('day', $days) : $documentStates[$state];
            $fileUrl = route('vendors.document', [$vendor, $document]);
          @endphp
          <div class="vd-doc">
            <span class="vd-doc-icon"><i class="fas {{ $fileIcon($document->file_mime) }}"></i></span>
            <div class="vd-doc-main">
              <div class="vd-doc-title">{{ $document->label }}
                @include('vendor.partials.status-badge', ['status' => $state, 'label' => $stateLabel])
              </div>
              <div class="vd-doc-file" title="{{ $document->file_original_name }}">{{ $document->file_original_name }}@if ($fileSize($document->file_size)) · {{ $fileSize($document->file_size) }}@endif</div>
              @if ($document->document_number || $document->issue_date || $document->expiry_date)
                <div class="vd-doc-meta">
                  @if ($document->document_number)<span>No. <b>{{ $document->document_number }}</b></span>@endif
                  @if ($document->issue_date)<span>Issued <b>{{ $date($document->issue_date) }}</b></span>@endif
                  @if ($document->expiry_date)<span>Expires <b>{{ $date($document->expiry_date) }}</b></span>@endif
                </div>
              @endif
            </div>
            <div class="vd-doc-actions">
              <a class="cf-btn cf-btn-outline cf-btn-sm" href="{{ $fileUrl }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> View</a>
              <a class="cf-btn cf-btn-outline cf-btn-sm" href="{{ $fileUrl }}?download=1"><i class="fas fa-download"></i> Download</a>
            </div>
          </div>
        @endforeach
      </div>

      {{-- What the vendor submitted --}}
      <div class="cf-card">
        @foreach ($sections as $title => $rows)
          <div class="vd-section">
            <div class="vd-section-title">{{ $title }}</div>
            @foreach ($rows as $label => $value)
              <div class="vd-row">
                <div class="vd-q">{{ $label }}</div>
                @if ($value === null || $value === '')
                  <div class="vd-a"><span class="vd-none">Not provided</span></div>
                @elseif (isset($links[$label]))
                  <div class="vd-a"><a href="{{ $links[$label]($value) }}" @if ($label === 'Website') target="_blank" rel="noopener noreferrer" @endif>{{ $value }}</a></div>
                @elseif ($label === 'Declarations')
                  <div class="vd-a"><span class="vd-yes"><i class="fas fa-circle-check"></i> {{ $value }}</span></div>
                @elseif ($label === 'IBAN')
                  <div class="vd-a cf-mono" style="font-size:13.5px">{{ $value }}</div>
                @else
                  <div class="vd-a {{ $label === 'Description of offer' ? 'is-long' : '' }}">{{ $value }}</div>
                @endif
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>

    <div class="vd-side">
      <form class="cf-panel" method="POST" action="{{ route('vendors.update', $vendor) }}">
        @csrf
        @method('PATCH')
        <div class="cf-panel-title">Manage vendor</div>
        <fieldset @disabled(! $canEdit) style="border:0;margin:0;padding:0;min-width:0">
          <div class="cf-field">
            <label class="cf-label" for="vdStatus">Status</label>
            <select class="cf-select" id="vdStatus" name="status">
              @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $vendor->status) === $key)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <label class="vd-switch">
            <span class="vd-switch-text">Critical vendor<small>Affects patient safety, compliance or essential services</small></span>
            <input type="hidden" name="is_critical" value="0">
            <input type="checkbox" name="is_critical" value="1" @checked(old('is_critical', $vendor->is_critical))>
            <span class="vd-switch-track"></span>
          </label>
          <div class="cf-field">
            <label class="cf-label" for="vdOwner">Internal owner</label>
            <select class="cf-select" id="vdOwner" name="internal_owner_id">
              <option value="">Unassigned</option>
              @foreach ($owners as $owner)
                <option value="{{ $owner->id }}" @selected((int) old('internal_owner_id', $vendor->internal_owner_id) === $owner->id)>{{ $owner->full_name }}</option>
              @endforeach
            </select>
          </div>
          <div class="cf-field">
            <label class="cf-label" for="vdNotes">Internal notes</label>
            <textarea class="cf-textarea" id="vdNotes" name="notes" rows="5" maxlength="5000" placeholder="Due diligence, approvals, follow-ups…">{{ old('notes', $vendor->notes) }}</textarea>
          </div>
          @if ($canEdit)
            <button type="submit" class="cf-btn cf-btn-primary" style="width:100%"><i class="fas fa-floppy-disk"></i> Save changes</button>
          @endif
        </fieldset>
      </form>

      <div class="cf-panel">
        <div class="cf-panel-title">Record</div>
        <div class="vd-meta-row"><span>Vendor ID</span><span>{{ $vendor->reference }}</span></div>
        <div class="vd-meta-row"><span>Submitted</span><span>{{ $vendor->created_at->format('M j, Y · g:i A') }}</span></div>
        <div class="vd-meta-row"><span>Source</span><span>Website form</span></div>
        <div class="vd-meta-row"><span>Internal owner</span><span>{{ $vendor->internalOwner?->full_name ?? 'Unassigned' }}</span></div>
        <div class="vd-meta-row"><span>Last updated</span><span>{{ $vendor->updated_at->diffForHumans() }}</span></div>
      </div>

      @if ($canEdit)
        <button type="button" class="cf-btn cf-btn-danger-ghost" style="align-self:flex-start"
          onclick="cfConfirm({ title: 'Delete this vendor?', message: @js($vendor->legal_name.' and all of its uploaded documents will be permanently deleted. This cannot be undone.'), action: @js(route('vendors.destroy', $vendor)), method: 'DELETE', confirmLabel: 'Delete vendor', danger: true })">
          <i class="fas fa-trash"></i> Delete vendor
        </button>
      @endif
    </div>
  </div>
</div>

@include('cms_forms.partials.scripts')
@endsection
