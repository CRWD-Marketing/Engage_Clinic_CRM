@php
  // A miniature of the form drawn from its real theme and field layout.
  $d = $form->designSettings();
  $c = $d['colors'];
  $items = $form->fields->take($limit ?? 8);
@endphp
<div class="fi-thumb {{ $size ?? '' }}" style="--t-page:{{ $c['page_bg'] }};--t-card:{{ $c['card_bg'] }};--t-head:{{ $c['header_bg'] }};--t-head-text:{{ $c['header_text'] }};--t-sec:{{ $c['section_bg'] }};--t-field:{{ $d['field_style'] === 'filled' ? $c['field_bg'] : $c['card_bg'] }};--t-border:{{ $c['field_border'] }};--t-primary:{{ $c['primary'] }};--t-label:{{ $c['label'] }};--t-radius:{{ min($d['radius'], 6) / 2 }}px" aria-hidden="true">
  @if ($d['frame'])<div class="fi-t-frame"></div>@endif
  <div class="fi-t-card">
    <div class="fi-t-head is-{{ $d['header_style'] }}">
      @if ($d['show_logo'] && $d['header_style'] !== 'minimal')<span class="fi-t-logo"></span>@endif
      <span class="fi-t-title {{ $d['title_uppercase'] ? 'is-upper' : '' }}">{{ \Illuminate\Support\Str::limit($form->title, 34) }}</span>
    </div>
    <div class="fi-t-grid">
      @forelse ($items as $field)
        @php $l = $field->layout(); @endphp
        @if ($field->type === 'section')
          <div class="fi-t-sec is-{{ $l['section_style'] }}" style="grid-column: span {{ $l['width'] }}"></div>
        @elseif ($field->type === 'paragraph')
          <div style="grid-column: span {{ $l['width'] }}"><div class="fi-t-line" style="width:90%"></div><div class="fi-t-line" style="width:60%"></div></div>
        @elseif ($field->type === 'divider')
          <div class="fi-t-hr" style="grid-column: span {{ $l['width'] }}"></div>
        @else
          @php $left = ($l['label_position'] ?: $d['label_position']) === 'left' && ! in_array($field->type, ['consent', 'checkbox', 'radio', 'long_text'], true); @endphp
          <div class="fi-t-item {{ $left ? 'is-left' : '' }}" style="grid-column: span {{ $l['width'] }}">
            <div class="fi-t-label"></div>
            @if (in_array($field->type, ['checkbox', 'radio'], true) || $field->type === 'consent')
              <div class="fi-t-checks"><i></i><i></i>@if($field->type !== 'consent')<i></i>@endif</div>
            @else
              <div class="fi-t-box {{ $field->type === 'long_text' ? 'is-tall' : '' }}"></div>
            @endif
          </div>
        @endif
      @empty
        <div class="fi-t-empty" style="grid-column: span 12">No fields yet</div>
      @endforelse
    </div>
    <div class="fi-t-btn {{ $d['button_style'] === 'full' ? 'is-full' : '' }}"></div>
  </div>
</div>
