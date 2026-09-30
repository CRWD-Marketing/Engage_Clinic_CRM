@extends('layouts.admin-sidebar')

@section('title', 'Response #'.$submission->id.' · '.$form->title.' · Engage Clinic')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
@include('cms_forms.partials.styles')
<style>
/* Client header */
.rd-hero { display: flex; align-items: center; gap: 16px; padding: 18px 20px; margin-bottom: 18px; flex-wrap: wrap; }
.rd-hero-main { flex: 1 1 360px; min-width: 0; }
.rd-hero-name { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-family: var(--cf-font-display); font-size: 24px; font-weight: 600; line-height: 1.2; }
.rd-hero-meta { display: flex; align-items: center; gap: 6px 14px; flex-wrap: wrap; margin-top: 4px; font-size: 13px; color: var(--cf-muted-foreground); }
.rd-hero-meta a { color: inherit; text-decoration: none; font-weight: 600; }
.rd-hero-meta a:hover { color: var(--cf-primary); }
.rd-hero-meta i { font-size: 11px; margin-right: 4px; color: var(--cf-subtle-foreground); }
.rd-contacts { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.rd-contact { display: inline-flex; align-items: stretch; border: 1px solid var(--cf-border); border-radius: 999px; background: #fff; box-shadow: var(--cf-shadow-sm); overflow: hidden; max-width: 100%; }
.rd-contact a { display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px; font-size: 13px; font-weight: 600; color: var(--cf-foreground); text-decoration: none; min-width: 0; }
.rd-contact a span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rd-contact a:hover { background: var(--cf-accent); }
.rd-contact a i { color: var(--cf-muted-foreground); font-size: 12px; }
.rd-contact button { border: 0; border-left: 1px solid var(--cf-border); background: none; padding: 0 10px; color: var(--cf-subtle-foreground); cursor: pointer; font-size: 12px; }
.rd-contact button:hover { background: var(--cf-accent); color: var(--cf-foreground); }
.rd-contact.is-wa a i { color: #16a34a; }
.rd-nav { display: flex; align-items: center; gap: 6px; margin-left: auto; }
.rd-nav-pos { font-size: 12.5px; font-weight: 600; color: var(--cf-muted-foreground); margin-right: 4px; white-space: nowrap; }

/* Answers */
.rd-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 18px; align-items: start; }
.rd-side { display: flex; flex-direction: column; gap: 16px; }
.rd-answers-head { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--cf-border); }
.rd-answers-head .cf-panel-title { margin: 0; }
.rd-progress { display: inline-flex; align-items: center; gap: 8px; margin-left: auto; font-size: 12.5px; color: var(--cf-muted-foreground); font-weight: 600; }
.rd-progress-bar { width: 70px; height: 6px; border-radius: 99px; background: var(--cf-muted); overflow: hidden; }
.rd-progress-bar span { display: block; height: 100%; background: var(--cf-success); border-radius: 99px; }
.rd-section-title { display: flex; align-items: center; gap: 8px; padding: 12px 20px 10px; background: #fafafa; border-bottom: 1px solid var(--cf-border); font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--cf-navy); }
.rd-row { position: relative; display: grid; grid-template-columns: minmax(140px, 32%) minmax(0, 1fr); gap: 6px 20px; padding: 13px 52px 13px 20px; border-bottom: 1px solid var(--cf-border); }
.rd-answers > :last-child, .rd-section:last-child .rd-row:last-child { border-bottom: 0; }
.rd-row:hover { background: #fcfcfc; }
.rd-q { font-size: 13px; font-weight: 600; color: var(--cf-muted-foreground); line-height: 1.5; padding-top: 1px; }
.rd-a { font-size: 14.5px; font-weight: 600; line-height: 1.55; word-break: break-word; min-width: 0; }
.rd-a.is-long { white-space: pre-wrap; font-weight: 500; }
.rd-a a { color: var(--cf-navy); text-decoration: none; border-bottom: 1px dashed #b8c7d6; }
.rd-a a:hover { color: var(--cf-primary); }
.rd-none { color: var(--cf-subtle-foreground); font-weight: 500; font-style: italic; font-size: 13.5px; }
.rd-yes { color: #15803d; }
.rd-no { color: #b91c1c; }
.rd-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.rd-chip { padding: 3px 10px; border-radius: 999px; background: var(--cf-muted); border: 1px solid var(--cf-border); font-size: 13px; font-weight: 600; }
.rd-a a.rd-file { display: inline-flex; align-items: center; gap: 12px; padding: 9px 14px 9px 10px; border: 1px solid var(--cf-border); border-radius: 8px; background: #fff; color: var(--cf-foreground); font-size: 13.5px; max-width: 100%; box-shadow: var(--cf-shadow-sm); }
.rd-a a.rd-file:hover { border-color: #d4d4d8; background: var(--cf-accent); color: var(--cf-foreground); }
.rd-file-icon { width: 34px; height: 34px; border-radius: 7px; display: flex; align-items: center; justify-content: center; background: var(--cf-primary-soft); color: var(--cf-primary); flex-shrink: 0; font-size: 15px; }
.rd-file-name { display: block; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rd-file-size { display: block; font-size: 12px; font-weight: 500; color: var(--cf-muted-foreground); }
.rd-copy { position: absolute; top: 10px; right: 12px; opacity: 0; color: var(--cf-muted-foreground); }
.rd-row:hover .rd-copy, .rd-copy:focus-visible { opacity: 1; }

/* Side panels */
.rd-convert-btn { width: 100%; height: 44px; font-size: 14.5px; }
.rd-status-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
.rd-seg { display: flex; padding: 3px; gap: 2px; background: var(--cf-muted); border-radius: 8px; }
.rd-seg form { flex: 1; margin: 0; }
.rd-seg button { width: 100%; height: 30px; border: 0; background: transparent; border-radius: 6px; font: 600 12.5px var(--cf-font); color: var(--cf-muted-foreground); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
.rd-seg button:hover { color: var(--cf-foreground); }
.rd-seg button.is-on { background: #fff; color: var(--cf-foreground); box-shadow: var(--cf-shadow-sm); cursor: default; }
.rd-converted { border: 1px solid var(--cf-success-border); background: var(--cf-success-subtle); border-radius: var(--cf-radius); padding: 14px 16px; }
.rd-converted-title { display: flex; align-items: center; gap: 8px; font-weight: 800; color: var(--cf-success); font-size: 14px; }
.rd-converted-ref { font-family: var(--cf-font-display); font-size: 22px; font-weight: 600; color: var(--cf-navy); margin: 4px 0 2px; }

.rd-timeline { list-style: none; margin: 0; padding: 0; }
.rd-step { position: relative; display: flex; gap: 12px; padding-bottom: 16px; }
.rd-step:last-child { padding-bottom: 0; }
.rd-step:not(:last-child)::before { content: ''; position: absolute; left: 11px; top: 26px; bottom: 2px; width: 2px; background: var(--cf-border); }
.rd-step.is-done:not(:last-child)::before { background: #bbf7d0; }
.rd-step-dot { width: 24px; height: 24px; border-radius: 99px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 10.5px; border: 2px solid var(--cf-border); background: #fff; color: var(--cf-subtle-foreground); }
.rd-step.is-done .rd-step-dot { background: #16a34a; border-color: #16a34a; color: #fff; }
.rd-step.is-muted .rd-step-dot { background: #a1a1aa; border-color: #a1a1aa; color: #fff; }
.rd-step-title { font-size: 13.5px; font-weight: 700; line-height: 24px; }
.rd-step:not(.is-done):not(.is-muted) .rd-step-title { color: var(--cf-muted-foreground); font-weight: 600; }
.rd-step-sub { font-size: 12.5px; color: var(--cf-muted-foreground); line-height: 1.45; }

.rd-meta-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--cf-border); font-size: 13px; }
.rd-meta-row:last-child { border-bottom: 0; padding-bottom: 0; }
.rd-meta-row > span:first-child { color: var(--cf-muted-foreground); white-space: nowrap; }
.rd-meta-row > span:last-child { font-weight: 700; text-align: right; min-width: 0; overflow-wrap: anywhere; }
.rd-meta-row a { color: var(--cf-foreground); text-decoration: none; }
.rd-meta-row a:hover { color: var(--cf-primary); }
.rd-kbd-hint { margin-top: 12px; font-size: 12px; color: var(--cf-muted-foreground); display: flex; align-items: center; gap: 5px; }

.rd-convert-dialog { width: 620px; }
.rd-convert-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px; }
.rd-convert-grid .span-2 { grid-column: 1 / -1; }

@media (max-width: 1100px) { .rd-grid { grid-template-columns: 1fr; } }
@media (max-width: 640px) {
  .rd-hero { padding: 16px; }
  .rd-hero .cf-avatar { display: none !important; }
  .rd-hero-name { font-size: 21px; }
  .rd-nav { margin-left: 0; width: 100%; }
  .rd-nav .cf-btn { flex: 1; }
  .rd-row { grid-template-columns: 1fr; gap: 3px; padding: 12px 16px; }
  .rd-copy { display: none; }
  .rd-answers-head, .rd-section-title { padding-left: 16px; padding-right: 16px; }
  .rd-convert-grid { grid-template-columns: 1fr; }
}
</style>

@php
  $leadUrl = $submission->lead_id ? route('leads.index', ['lead' => $submission->lead_id]) : null;
  $convertFields = ['parent_guardian_name', 'child_name', 'child_age', 'email', 'phone', 'interested_in', 'notes', 'source', 'assigned_to'];
  $convertHasErrors = collect($convertFields)->contains(fn ($f) => $errors->has($f));
  $val = fn ($f) => old($f, $leadDefaults[$f] ?? '');

  $name = $identity['name'] ?: null;
  $email = $identity['email'] ?: null;
  $phone = $identity['phone'] ?: null;
  $phoneDigits = $phone ? preg_replace('/\D/', '', $phone) : '';
  // wa.me needs the international number; only offer it when the client typed one.
  $whatsApp = $phone && preg_match('/^\s*(\+|00)/', $phone) ? 'https://wa.me/'.preg_replace('/^00/', '', $phoneDigits) : null;

  $values = $submission->values;
  $answered = $values->reject(fn ($v) => $v->isEmpty() || ($v->field_type === 'checkbox' && $v->value === '0'))->count();
  $answerPct = $values->count() ? (int) round($answered / $values->count() * 100) : 0;

  $fileSize = function ($bytes) {
    if (! $bytes) return null;
    if ($bytes < 1024) return $bytes.' B';
    if ($bytes < 1048576) return number_format($bytes / 1024, $bytes < 10240 ? 1 : 0).' KB';
    return number_format($bytes / 1048576, 1).' MB';
  };
  $fileIcon = fn ($mime) => match (true) {
    str_starts_with((string) $mime, 'image/') => 'fa-file-image',
    $mime === 'application/pdf' => 'fa-file-pdf',
    str_contains((string) $mime, 'word') => 'fa-file-word',
    str_contains((string) $mime, 'sheet') || str_contains((string) $mime, 'excel') => 'fa-file-excel',
    default => 'fa-file-lines',
  };

  $status = $submission->status;
  $wasReviewed = $submission->reviewed_at && $status !== \App\Models\FormSubmission::STATUS_NEW;
@endphp

<div class="cf-root">
  <div class="cf-crumbs">
    <span>CMS</span><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <a href="{{ route('cms.forms.index') }}">Forms</a><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <a href="{{ route('cms.forms.edit', $form) }}">{{ $form->title }}</a><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <a href="{{ route('cms.forms.responses.index', $form) }}">Responses</a><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <span>#{{ $submission->id }}</span>
  </div>

  {{-- Who sent it, and how to reach them --}}
  <div class="cf-card rd-hero">
    @include('cms_forms.partials.avatar', ['name' => $name, 'size' => 56])
    <div class="rd-hero-main">
      <h1 class="rd-hero-name" style="margin:0">
        {{ $name ?? 'No name given' }}
        @include('cms_forms.partials.status-badge', ['status' => $status, 'label' => $submission->status_label])
      </h1>
      <div class="rd-hero-meta">
        <span><i class="fas fa-hashtag"></i>Client Response #{{ $submission->id }}</span>
        <a href="{{ route('cms.forms.responses.index', $form) }}"><i class="fas fa-clipboard-list"></i>{{ $form->title }}</a>
        <span title="{{ $submission->submitted_at->format('F j, Y g:i A') }}"><i class="far fa-clock"></i>Submitted {{ $submission->submitted_at->diffForHumans() }}</span>
      </div>
      @if ($email || $phone)
        <div class="rd-contacts">
          @if ($email)
            <span class="rd-contact"><a href="mailto:{{ $email }}" title="Send an email"><i class="fas fa-envelope"></i><span>{{ $email }}</span></a><button type="button" title="Copy email" aria-label="Copy email" onclick="cfCopy(@js($email), this)"><i class="far fa-copy"></i></button></span>
          @endif
          @if ($phone)
            <span class="rd-contact"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" title="Call"><i class="fas fa-phone"></i><span>{{ $phone }}</span></a><button type="button" title="Copy phone number" aria-label="Copy phone number" onclick="cfCopy(@js($phone), this)"><i class="far fa-copy"></i></button></span>
          @endif
          @if ($whatsApp)
            <span class="rd-contact is-wa"><a href="{{ $whatsApp }}" target="_blank" rel="noopener" title="Open a WhatsApp chat"><i class="fab fa-whatsapp"></i><span>WhatsApp</span></a></span>
          @endif
        </div>
      @endif
    </div>
    <div class="rd-nav">
      <span class="rd-nav-pos">{{ $position['index'] }} of {{ $position['total'] }}</span>
      <a class="cf-btn cf-btn-outline {{ $neighbours['newer'] ? '' : 'is-disabled' }}" id="rdNewer" href="{{ $neighbours['newer'] ? route('cms.forms.responses.show', $neighbours['newer']) : '#' }}" title="Newer response (←)"><i class="fas fa-chevron-left"></i> Newer</a>
      <a class="cf-btn cf-btn-outline {{ $neighbours['older'] ? '' : 'is-disabled' }}" id="rdOlder" href="{{ $neighbours['older'] ? route('cms.forms.responses.show', $neighbours['older']) : '#' }}" title="Older response (→)">Older <i class="fas fa-chevron-right"></i></a>
    </div>
  </div>

  @if ($errors->has('message'))
    <div class="cf-alert is-error"><i class="fas fa-circle-exclamation"></i><span>{{ $errors->first('message') }}</span></div>
  @endif

  <div class="rd-grid">
    {{-- What the client actually submitted --}}
    <div class="cf-card rd-answers" id="rdAnswers">
      <div class="rd-answers-head">
        <div class="cf-panel-title">Answers</div>
        @if ($values->isNotEmpty())
          <span class="rd-progress" title="{{ $answered }} of {{ $values->count() }} questions answered">
            <span class="rd-progress-bar"><span style="width:{{ $answerPct }}%"></span></span>
            {{ $answered }}/{{ $values->count() }} answered
          </span>
          <button type="button" class="cf-btn cf-btn-outline cf-btn-sm" onclick="rdCopyAll(this)" title="Copy every question and answer as text"><i class="far fa-copy"></i> Copy all</button>
        @endif
      </div>

      @forelse ($sections as $section)
        <div class="rd-section">
          @if ($section['title'])
            <div class="rd-section-title"><i class="fas fa-layer-group" style="font-size:11px;opacity:.7"></i>{{ $section['title'] }}</div>
          @endif
          @foreach ($section['values'] as $value)
            @php $text = $value->displayText(); @endphp
            <div class="rd-row" data-q="{{ $value->field_label }}" data-a="{{ $text }}">
              <div class="rd-q">{{ $value->field_label }}</div>
              @if ($value->isEmpty() && ! in_array($value->field_type, ['consent', 'checkbox'], true))
                <div class="rd-a"><span class="rd-none">Not answered</span></div>
              @else
                @switch($value->field_type)
                  @case('consent')
                    <div class="rd-a">
                      @if ($value->value === '1')
                        <span class="rd-yes"><i class="fas fa-circle-check"></i> Agreed</span>
                      @else
                        <span class="rd-no"><i class="fas fa-circle-xmark"></i> Not agreed</span>
                      @endif
                    </div>
                    @break
                  @case('checkbox')
                    @if (str_starts_with((string) $value->value, '['))
                      <div class="rd-a rd-chips">@foreach ($value->listValue() as $choice)<span class="rd-chip">{{ $choice }}</span>@endforeach</div>
                    @elseif ($value->value === '1')
                      <div class="rd-a"><span class="rd-yes"><i class="fas fa-check"></i> Yes</span></div>
                    @elseif ($value->value === '0')
                      <div class="rd-a"><span class="rd-none">No</span></div>
                    @else
                      <div class="rd-a"><span class="rd-none">Not answered</span></div>
                    @endif
                    @break
                  @case('file')
                    <div class="rd-a">
                      <a class="rd-file" href="{{ route('cms.forms.responses.file', [$submission, $value]) }}" title="Download {{ $value->file_original_name }}">
                        <span class="rd-file-icon"><i class="fas {{ $fileIcon($value->file_mime) }}"></i></span>
                        <span style="min-width:0">
                          <span class="rd-file-name">{{ $value->file_original_name }}</span>
                          <span class="rd-file-size">{{ $fileSize($value->file_size) ?? 'File' }} · Download</span>
                        </span>
                        <i class="fas fa-download" style="color:var(--cf-muted-foreground)"></i>
                      </a>
                    </div>
                    @break
                  @case('email')
                    <div class="rd-a"><a href="mailto:{{ $value->value }}">{{ $value->value }}</a></div>
                    @break
                  @case('phone')
                    <div class="rd-a"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $value->value) }}">{{ $value->value }}</a></div>
                    @break
                  @case('long_text')
                    <div class="rd-a is-long">{{ $value->value }}</div>
                    @break
                  @default
                    <div class="rd-a">{{ $text }}</div>
                @endswitch
              @endif
              @if ($text !== '' && $value->field_type !== 'file')
                <button type="button" class="cf-btn cf-btn-ghost cf-btn-icon rd-copy" title="Copy answer" aria-label="Copy answer to {{ $value->field_label }}" onclick="cfCopy(this.closest('.rd-row').dataset.a, this)"><i class="far fa-copy"></i></button>
              @endif
            </div>
          @endforeach
        </div>
      @empty
        <div class="rd-row"><span class="rd-none">This response has no answers.</span></div>
      @endforelse
    </div>

    <div class="rd-side">
      {{-- Next step: convert, or triage --}}
      @if ($submission->isConverted())
        <div class="rd-converted">
          <div class="rd-converted-title"><i class="fas fa-circle-check"></i> Converted to lead</div>
          <div class="rd-converted-ref">{{ $leadRef ?? 'Deleted' }}</div>
          @if ($leadUrl && $submission->lead)
            <a href="{{ $leadUrl }}" class="cf-btn cf-btn-primary rd-convert-btn" style="margin-top:10px"><i class="fas fa-arrow-right"></i> View Lead</a>
          @elseif (! $submission->lead)
            <div class="cf-hint" style="margin-top:6px">The lead created from this response has since been deleted from the pipeline. The original response is kept here.</div>
          @endif
        </div>
      @else
        <div class="cf-panel">
          @if ($canConvert)
            <button type="button" class="cf-btn cf-btn-primary rd-convert-btn" onclick="cfOpen('rdConvertDialog')"><i class="fas fa-user-plus"></i> Convert to Lead</button>
            <div class="cf-hint" style="margin:8px 0 16px">Creates a new lead in the Leads pipeline from these answers. This response stays here, linked to it.</div>
          @else
            <div class="cf-hint" style="margin-bottom:16px">Converting to a lead needs editable access to the Leads module.</div>
          @endif

          <div class="cf-eyebrow" style="margin-bottom:8px">Status</div>
          <div class="rd-seg" role="group" aria-label="Set status">
            @foreach (['new' => ['fa-circle-dot', 'New'], 'reviewed' => ['fa-check', 'Reviewed'], 'ignored' => ['fa-ban', 'Ignored']] as $key => [$icon, $label])
              <form method="POST" action="{{ route('cms.forms.responses.status', $submission) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $key }}">
                <button type="{{ $status === $key ? 'button' : 'submit' }}" class="{{ $status === $key ? 'is-on' : '' }}" @if ($status === $key) aria-pressed="true" @else title="Mark as {{ strtolower($label) }}" @endif><i class="fas {{ $icon }}"></i> {{ $label }}</button>
              </form>
            @endforeach
          </div>
        </div>
      @endif

      {{-- What has happened to it so far --}}
      <div class="cf-panel">
        <div class="cf-panel-title">Progress</div>
        <ol class="rd-timeline">
          <li class="rd-step is-done">
            <span class="rd-step-dot"><i class="fas fa-check"></i></span>
            <div><div class="rd-step-title">Submitted</div>
              <div class="rd-step-sub">{{ $submission->submitted_at->format('M j, Y · g:i A') }}</div></div>
          </li>
          @if ($status === \App\Models\FormSubmission::STATUS_IGNORED)
            <li class="rd-step is-muted">
              <span class="rd-step-dot"><i class="fas fa-ban"></i></span>
              <div><div class="rd-step-title">Ignored</div>
                <div class="rd-step-sub">Set aside - won't be followed up</div></div>
            </li>
          @else
            <li class="rd-step {{ $wasReviewed || $submission->isConverted() ? 'is-done' : '' }}">
              <span class="rd-step-dot"><i class="fas {{ $wasReviewed || $submission->isConverted() ? 'fa-check' : 'fa-eye' }}"></i></span>
              <div><div class="rd-step-title">Reviewed</div>
                <div class="rd-step-sub">
                  @if ($wasReviewed)
                    {{ $submission->reviewedByUser?->full_name ?? 'System' }} · {{ $submission->reviewed_at->format('M j, g:i A') }}
                  @elseif ($submission->isConverted())
                    Converted straight away
                  @else
                    Waiting for someone to review
                  @endif
                </div></div>
            </li>
          @endif
          <li class="rd-step {{ $submission->isConverted() ? 'is-done' : '' }}">
            <span class="rd-step-dot"><i class="fas {{ $submission->isConverted() ? 'fa-check' : 'fa-user-plus' }}"></i></span>
            <div><div class="rd-step-title">Converted to lead</div>
              <div class="rd-step-sub">
                @if ($submission->isConverted())
                  {{ $leadRef ?? 'Lead deleted' }} · {{ $submission->convertedByUser?->full_name ?? 'System (automatic)' }}
                  @if ($submission->converted_at)<br>{{ $submission->converted_at->format('M j, Y · g:i A') }}@endif
                @else
                  Not converted yet
                @endif
              </div></div>
          </li>
        </ol>
      </div>

      <div class="cf-panel">
        <div class="cf-panel-title">Details</div>
        <div class="rd-meta-row"><span>Response</span><span class="cf-mono">#{{ $submission->id }}</span></div>
        <div class="rd-meta-row"><span>Form</span><span><a href="{{ route('cms.forms.responses.index', $form) }}">{{ $form->title }}</a></span></div>
        <div class="rd-meta-row"><span>Submitted</span><span>{{ $submission->submitted_at->format('F j, Y') }}<br>{{ $submission->submitted_at->format('g:i A') }}</span></div>
        @if ($device)
          <div class="rd-meta-row"><span>Device</span><span><i class="fas {{ $device['icon'] }}" style="color:var(--cf-muted-foreground);margin-right:4px"></i>{{ $device['label'] }}</span></div>
        @endif
        @if ($submission->ip_address)
          <div class="rd-meta-row"><span>IP address</span><span class="cf-mono">{{ $submission->ip_address }}</span></div>
        @endif
        @if ($position['total'] > 1)
          <div class="rd-kbd-hint"><kbd class="cf-kbd">←</kbd><kbd class="cf-kbd">→</kbd> move between responses</div>
        @endif
      </div>
    </div>
  </div>
</div>

@if (! $submission->isConverted() && $canConvert)
<div class="cf-dialog-overlay {{ $convertHasErrors ? 'open' : '' }}" id="rdConvertDialog" onclick="if (event.target === this) cfClose('rdConvertDialog')">
  <form class="cf-dialog rd-convert-dialog" method="POST" action="{{ route('cms.forms.responses.convert', $submission) }}" onsubmit="const b = this.querySelector('[type=submit]'); b.disabled = true; b.innerHTML = 'Converting…';">
    @csrf
    <h2>Convert to Lead</h2>
    <p class="cf-dialog-desc">The submitted information will be used to create a new CRM Lead in the <strong>New</strong> stage of the pipeline. Check and adjust anything before converting.</p>

    <div class="rd-convert-grid">
      <div class="cf-field">
        <label class="cf-label" for="cvName">Name *</label>
        <input class="cf-input {{ $errors->has('parent_guardian_name') ? 'is-invalid' : '' }}" id="cvName" name="parent_guardian_name" value="{{ $val('parent_guardian_name') }}" maxlength="255" required>
        @error('parent_guardian_name')<span class="cf-error">{{ $message }}</span>@enderror
      </div>
      <div class="cf-field">
        <label class="cf-label" for="cvSource">Lead Source *</label>
        <input class="cf-input {{ $errors->has('source') ? 'is-invalid' : '' }}" id="cvSource" name="source" value="{{ $val('source') }}" maxlength="50" required>
        @error('source')<span class="cf-error">{{ $message }}</span>@enderror
      </div>
      <div class="cf-field">
        <label class="cf-label" for="cvEmail">Email</label>
        <input class="cf-input {{ $errors->has('email') ? 'is-invalid' : '' }}" id="cvEmail" type="email" name="email" value="{{ $val('email') }}" maxlength="255">
        @error('email')<span class="cf-error">{{ $message }}</span>@enderror
      </div>
      <div class="cf-field">
        <label class="cf-label" for="cvPhone">Phone</label>
        <input class="cf-input {{ $errors->has('phone') ? 'is-invalid' : '' }}" id="cvPhone" name="phone" value="{{ $val('phone') }}" maxlength="20">
        @error('phone')<span class="cf-error">{{ $message }}</span>@enderror
      </div>
      <div class="cf-field">
        <label class="cf-label" for="cvChild">Child's name</label>
        <input class="cf-input {{ $errors->has('child_name') ? 'is-invalid' : '' }}" id="cvChild" name="child_name" value="{{ $val('child_name') }}" maxlength="255">
        @error('child_name')<span class="cf-error">{{ $message }}</span>@enderror
      </div>
      <div class="cf-field">
        <label class="cf-label" for="cvAge">Child's age</label>
        <input class="cf-input {{ $errors->has('child_age') ? 'is-invalid' : '' }}" id="cvAge" name="child_age" value="{{ $val('child_age') }}" maxlength="10">
        @error('child_age')<span class="cf-error">{{ $message }}</span>@enderror
      </div>
      <div class="cf-field span-2">
        <label class="cf-label" for="cvService">Service</label>
        <input class="cf-input {{ $errors->has('interested_in') ? 'is-invalid' : '' }}" id="cvService" name="interested_in" value="{{ $val('interested_in') }}" maxlength="100" list="cvServices">
        <datalist id="cvServices">@foreach ($services as $service)<option value="{{ $service }}">@endforeach</datalist>
        @error('interested_in')<span class="cf-error">{{ $message }}</span>@enderror
      </div>
      <div class="cf-field span-2">
        <label class="cf-label" for="cvNotes">Notes</label>
        <textarea class="cf-textarea" id="cvNotes" name="notes" rows="4">{{ $val('notes') }}</textarea>
      </div>
      @if ($canAssign)
        <div class="cf-field span-2">
          <label class="cf-label" for="cvOwner">Assign to <span style="font-weight:400">(optional)</span></label>
          <select class="cf-select" id="cvOwner" name="assigned_to">
            <option value="">Unassigned</option>
            @foreach ($assignableUsers as $u)
              <option value="{{ $u->id }}" @selected((string) old('assigned_to') === (string) $u->id)>{{ trim($u->first_name.' '.$u->last_name) }}</option>
            @endforeach
          </select>
          @error('assigned_to')<span class="cf-error">{{ $message }}</span>@enderror
        </div>
      @endif
    </div>

    <div class="cf-dialog-foot">
      <button type="button" class="cf-btn cf-btn-outline" onclick="cfClose('rdConvertDialog')">Cancel</button>
      <button type="submit" class="cf-btn cf-btn-primary"><i class="fas fa-user-plus"></i> Convert to Lead</button>
    </div>
  </form>
</div>
@endif

@include('cms_forms.partials.scripts')
<script>
  @if (session('converted'))
    cfToast(@js('Converted to lead '.session('converted')), {
      duration: 8000,
      action: @json($leadUrl ? ['label' => 'View Lead', 'href' => $leadUrl] : null),
    });
  @endif

  function rdCopyAll(btn) {
    const lines = [...document.querySelectorAll('#rdAnswers .rd-row[data-q]')]
      .map(r => `${r.dataset.q}: ${r.dataset.a || '—'}`);
    cfCopy(lines.join('\n'), btn);
  }

  // ← / → step through this form's responses, unless typing or a dialog is open.
  document.addEventListener('keydown', (e) => {
    if (e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
    if (e.target.closest('input, textarea, select, [contenteditable]')) return;
    if (document.querySelector('.cf-dialog-overlay.open, .cf-menu.is-open')) return;
    const link = document.getElementById(e.key === 'ArrowLeft' ? 'rdNewer' : e.key === 'ArrowRight' ? 'rdOlder' : '');
    if (link && !link.classList.contains('is-disabled')) window.location = link.href;
  });
</script>
@endsection
