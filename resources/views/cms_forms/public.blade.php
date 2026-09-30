<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@if ($preview)<meta name="robots" content="noindex, nofollow">@endif
<title>{{ $form->title }} · Engage Clinic</title>
@php
  $d = $form->designSettings();
  $submitted = $submitted ?? false;
  $old = old('fields', []);
@endphp
<link rel="icon" type="image/png" href="{{ asset('uploads/engage.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="{{ \App\Support\CmsForms\FormDesign::fontUrl($d) }}" rel="stylesheet">
<style>body { margin: 0; }</style>
@include('cms_forms.partials.public-styles')
</head>
<body>
<div class="pf-page {{ $embed ? 'is-embed' : '' }}" style="{{ \App\Support\CmsForms\FormDesign::cssVars($d) }}" data-field-style="{{ $d['field_style'] }}">
  @if ($d['frame'] && ! $embed)<div class="pf-frame"></div>@endif
  <div class="pf-body">
    <div class="pf-shell">
      <div class="pf-card">
        @if ($preview)
          <div class="pf-banner is-preview">Preview{{ $form->isPublished() ? '' : ' of a draft' }} - submitting is disabled here.</div>
        @endif

        <header class="pf-header pf-header--{{ $d['header_style'] }}" data-align="{{ $d['title_align'] }}" data-logo="{{ $d['logo_align'] }}">
          @if ($d['show_logo'] && $d['header_style'] !== 'minimal')
            <div class="pf-logo-wrap {{ $d['logo_plate'] ? 'has-plate' : '' }}"><img class="pf-logo" src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic"></div>
          @endif
          <div class="pf-heading">
            @if ($d['header_kicker'] !== '')<div class="pf-kicker">{{ $d['header_kicker'] }}</div>@endif
            <h1 class="pf-title {{ $d['title_uppercase'] ? 'is-upper' : '' }}">{{ $form->title }}</h1>
            @if ($d['header_subtitle'] !== '')<p class="pf-subtitle">{{ $d['header_subtitle'] }}</p>@endif
          </div>
        </header>

        <div id="pfSuccess" class="pf-success" @unless($submitted) style="display:none" @endunless>
          <div class="pf-success-icon">&#10003;</div>
          <p id="pfSuccessText">{{ $form->successMessage() }}</p>
        </div>

        <div id="pfFormWrap" @if($submitted) style="display:none" @endif>
          @if ($form->description)
            <div class="pf-desc">{{ $form->description }}</div>
          @endif

          <div class="pf-banner is-error" id="pfErrorBanner" @unless($errors->any()) style="display:none" @endunless>
            {{ $errors->any() ? 'Please check the highlighted fields.' : '' }}
          </div>

          <form id="pfForm" method="POST" action="{{ $preview ? '#' : route('forms.public.submit', $form->slug) }}" enctype="multipart/form-data" novalidate>
            @if ($embed)<input type="hidden" name="embed" value="1">@endif
            <div class="pf-honeypot" aria-hidden="true">
              <label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="pf-grid">
            @foreach ($form->fields as $field)
              @php
                $l = $field->layout();
                $span = $l['width'];
              @endphp

              @if ($field->type === 'section')
                <div class="pf-item pf-section pf-section--{{ $l['section_style'] }} {{ $d['title_uppercase'] ? 'is-upper' : '' }}" style="--span:{{ $span }}" data-align="{{ $l['align'] }}">
                  <h2>{{ $field->label }}</h2>
                  @if ($field->help_text)<p>{{ $field->help_text }}</p>@endif
                </div>
                @continue
              @elseif ($field->type === 'paragraph')
                <div class="pf-item pf-paragraph {{ $l['label_bold'] ? 'is-bold' : '' }}" style="--span:{{ $span }}" data-align="{{ $l['align'] }}">{{ $l['content'] }}</div>
                @continue
              @elseif ($field->type === 'divider')
                <div class="pf-item" style="--span:{{ $span }}"><hr class="pf-divider"></div>
                @continue
              @endif

              @php
                $key = 'fields.'.$field->name;
                $input = 'fields['.$field->name.']';
                $id = 'pf_'.$field->id;
                $err = $errors->first($key) ?: $errors->first($key.'.*');
                $value = $old[$field->name] ?? null;
                $single = $field->type === 'consent' || ($field->type === 'checkbox' && ! $field->isMultiChoice());
                $labelLeft = ! $single && ($l['label_position'] ?: $d['label_position']) === 'left';
                $tall = in_array($field->type, ['long_text', 'radio', 'checkbox'], true);
                $labelClass = 'pf-label size-'.$l['label_size'].($l['label_bold'] ? ' is-bold' : '');
              @endphp
              <div class="pf-item {{ $labelLeft ? 'is-label-left' : '' }} {{ $tall ? 'is-tall' : '' }} {{ $err ? 'has-error' : '' }}" style="--span:{{ $span }}" data-field="{{ $field->name }}">
                @if ($single)
                  <label class="{{ $field->type === 'consent' ? 'pf-consent' : 'pf-choice' }}">
                    <input type="checkbox" id="{{ $id }}" name="{{ $input }}" value="1" @checked($value) @if($field->is_required) required @endif>
                    <span class="{{ $l['label_bold'] ? 'pf-label is-bold' : '' }}" style="margin:0">{{ $field->label }}@if($field->is_required) <span class="pf-req">*</span>@endif</span>
                  </label>
                @else
                  <label class="{{ $labelClass }}" for="{{ $id }}">{{ $field->label }}@if($field->is_required) <span class="pf-req">*</span>@endif</label>
                @endif

                <div class="pf-control">
                  @switch($field->type)
                    @case('consent')
                    @case('checkbox')
                      @if ($field->isMultiChoice())
                        <div class="pf-choices" id="{{ $id }}" style="--cols:{{ $l['option_columns'] }}">
                          @foreach ($field->options ?? [] as $option)
                            <label class="pf-choice"><input type="checkbox" name="{{ $input }}[]" value="{{ $option }}" @checked(in_array($option, (array) $value, true))> {{ $option }}</label>
                          @endforeach
                        </div>
                      @endif
                      @break
                    @case('long_text')
                      <textarea class="pf-input" id="{{ $id }}" name="{{ $input }}" rows="{{ $l['rows'] }}" maxlength="5000" placeholder="{{ $field->placeholder }}" @if($field->is_required) required @endif>{{ $value }}</textarea>
                      @break
                    @case('dropdown')
                      <select class="pf-input" id="{{ $id }}" name="{{ $input }}" @if($field->is_required) required @endif>
                        <option value="">{{ $field->placeholder ?: 'Select…' }}</option>
                        @foreach ($field->options ?? [] as $option)
                          <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                        @endforeach
                      </select>
                      @break
                    @case('radio')
                      <div class="pf-choices" id="{{ $id }}" style="--cols:{{ $l['option_columns'] }}">
                        @foreach ($field->options ?? [] as $option)
                          <label class="pf-choice"><input type="radio" name="{{ $input }}" value="{{ $option }}" @checked($value === $option) @if($field->is_required) required @endif> {{ $option }}</label>
                        @endforeach
                      </div>
                      @break
                    @case('file')
                      <input class="pf-input pf-file" type="file" id="{{ $id }}" name="{{ $input }}" accept="{{ collect(\App\Models\FormField::FILE_MIMES)->map(fn ($e) => '.'.$e)->implode(',') }}" @if($field->is_required) required @endif>
                      @break
                    @default
                      <input class="pf-input" id="{{ $id }}" name="{{ $input }}" value="{{ $value }}" placeholder="{{ $field->placeholder }}"
                        type="{{ ['email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => 'date'][$field->type] ?? 'text' }}"
                        @if($field->type === 'short_text') maxlength="500" @endif
                        @if($field->type === 'email') autocomplete="email" @elseif($field->type === 'phone') autocomplete="tel" @endif
                        @if($field->type === 'number') step="any" @endif
                        @if($field->is_required) required @endif>
                  @endswitch

                  @if ($field->help_text)<div class="pf-help">{{ $field->help_text }}</div>@endif
                  @if ($field->type === 'file')<div class="pf-help">PDF, JPG, PNG, HEIC or Word · up to 10 MB</div>@endif
                  <div class="pf-error">{{ $err }}</div>
                </div>
              </div>
            @endforeach
            </div>

            <div class="pf-actions {{ $d['button_style'] === 'full' ? 'is-full' : '' }}" data-align="{{ $d['button_align'] }}">
              <button type="submit" class="pf-submit" id="pfSubmit" @if($preview) disabled title="Preview only" @endif>{{ $d['submit_label'] }}</button>
            </div>
          </form>
        </div>

        @if (($d['footer_left'] !== '' || $d['footer_right'] !== '') && ! $embed)
          <footer class="pf-footer">
            <span>@if($d['footer_left'] !== '')<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>{{ $d['footer_left'] }}@endif</span>
            <span>@if($d['footer_right'] !== '')<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>{{ $d['footer_right'] }}@endif</span>
          </footer>
        @endif
      </div>
    </div>
  </div>
  @if ($d['frame'] && ! $embed)<div class="pf-frame is-bottom"></div>@endif
</div>

@unless ($preview)
<script>
(function () {
  const form = document.getElementById('pfForm');
  const btn = document.getElementById('pfSubmit');
  const label = btn.textContent;
  const banner = document.getElementById('pfErrorBanner');

  function clearErrors() {
    banner.style.display = 'none';
    form.querySelectorAll('.pf-item.has-error').forEach(f => { f.classList.remove('has-error'); f.querySelector('.pf-error').textContent = ''; });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    btn.disabled = true;
    btn.textContent = 'Sending…';
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' } });
      const data = await res.json().catch(() => ({}));
      if (res.ok) {
        document.getElementById('pfSuccessText').textContent = data.message;
        document.getElementById('pfFormWrap').style.display = 'none';
        document.getElementById('pfSuccess').style.display = '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        if (window.parent !== window) window.parent.postMessage({ type: 'engage-form-submitted', form: @json($form->slug) }, '*');
        return;
      }
      let first = null;
      Object.entries(data.errors || {}).forEach(([key, messages]) => {
        const name = key.replace(/^fields\./, '').split('.')[0];
        const field = form.querySelector(`.pf-item[data-field="${CSS.escape(name)}"]`);
        if (!field) return;
        field.classList.add('has-error');
        field.querySelector('.pf-error').textContent = messages[0];
        first = first || field;
      });
      banner.textContent = res.status === 429 ? 'Too many attempts - please wait a minute and try again.' : (data.message || 'Something went wrong. Please try again.');
      banner.style.display = 'block';
      (first || banner).scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch (_) {
      banner.textContent = 'We couldn\'t reach the server. Please check your connection and try again.';
      banner.style.display = 'block';
    } finally {
      btn.disabled = false;
      btn.textContent = label;
    }
  });
})();
</script>
@endunless
</body>
</html>
