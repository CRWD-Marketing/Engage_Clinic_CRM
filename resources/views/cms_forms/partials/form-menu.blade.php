@php $live = $form->isPublished(); @endphp
<div class="cf-menu-wrap" data-cf-menu>
  <button type="button" class="cf-btn cf-btn-ghost cf-btn-icon" title="More actions" aria-haspopup="menu" aria-expanded="false" aria-label="More actions for {{ $form->title }}" onclick="cfToggleMenu(this)"><i class="fas fa-ellipsis"></i></button>
  {{-- Kept short on purpose (~270px) so it fits above or below the button on
       small screens without scrolling - the card already shows the title/status. --}}
  <div class="cf-menu" role="menu" aria-label="Actions for {{ $form->title }}">
    <a class="cf-menu-item" role="menuitem" href="{{ route('cms.forms.edit', $form) }}"><i class="fas fa-pen-ruler"></i> Edit design</a>
    <a class="cf-menu-item" role="menuitem" href="{{ route('cms.forms.responses.index', $form) }}"><i class="fas fa-inbox"></i> View responses
      @if ($form->new_submissions_count)<span class="cf-count-pill" style="margin-left:auto">{{ $form->new_submissions_count }} new</span>@endif
    </a>
    <a class="cf-menu-item" role="menuitem" href="{{ route('cms.forms.preview', $form) }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Preview <i class="fas fa-arrow-up-right-from-square cf-menu-hint"></i></a>

    @if ($live)
      <div class="cf-menu-sep"></div>
      <button type="button" class="cf-menu-item" role="menuitem" data-copy onclick="cfCopy({{ json_encode($form->publicUrl()) }}, this)"><i class="fas fa-link"></i> Copy public link</button>
      <button type="button" class="cf-menu-item" role="menuitem" data-copy onclick="cfCopy({{ json_encode($form->embedCode()) }}, this)"><i class="fas fa-code"></i> Copy embed code</button>
    @endif

    <div class="cf-menu-sep"></div>
    @if ($live)
      <button type="button" class="cf-menu-item" role="menuitem"
        onclick="cfConfirm({ title: 'Unpublish this form?', message: {{ json_encode('"'.$form->title.'" will stop accepting responses and its public link and embeds will stop working. Existing responses are kept.') }}, action: {{ json_encode(route('cms.forms.unpublish', $form)) }}, confirmLabel: 'Unpublish' })">
        <i class="fas fa-eye-slash"></i> Unpublish
      </button>
    @else
      <form method="POST" action="{{ route('cms.forms.publish', $form) }}">@csrf
        <button class="cf-menu-item" role="menuitem"><i class="fas fa-rocket"></i> Publish</button>
      </form>
    @endif
    <form method="POST" action="{{ route('cms.forms.duplicate', $form) }}">@csrf
      <button class="cf-menu-item" role="menuitem"><i class="fas fa-clone"></i> Duplicate</button>
    </form>

    <div class="cf-menu-sep"></div>
    <button type="button" class="cf-menu-item danger" role="menuitem" data-delete-form="{{ $form->deleteDialogJson() }}">
      <i class="fas fa-trash"></i> Delete…
      @if ($form->submissions_count > 0)<span class="cf-menu-hint">{{ number_format($form->submissions_count) }} {{ \Illuminate\Support\Str::plural('response', $form->submissions_count) }}</span>@endif
    </button>
  </div>
</div>
