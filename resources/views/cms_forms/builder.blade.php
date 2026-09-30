@extends('layouts.admin-sidebar')

@section('title', $form->title.' · Form designer · Engage Clinic')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
@include('cms_forms.partials.styles')
@include('cms_forms.partials.public-styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link id="fdFontLink" rel="stylesheet" href="{{ \App\Support\CmsForms\FormDesign::fontUrl($form->designSettings()) }}">
<style>
/* ── Form designer (shadcn-style chrome around the live form canvas) ───── */
.fd-top { display: flex; align-items: flex-end; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
.fd-top .cf-heading { font-size: 22px; }
.fd-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.fd-saved { font-size: 12.5px; color: var(--cf-muted-foreground); }
.fd-saved.is-dirty { color: #b45309; font-weight: 600; }
.fd-saved i { font-size: 11px; margin-right: 2px; }
.fd-restored { align-items: center; border-color: #bfdbfe; background: #eff6ff; color: #1d4ed8; }
.fd-kbd { font: 600 10.5px ui-monospace, monospace; padding: 1px 5px; border: 1px solid var(--cf-border); border-bottom-width: 2px; border-radius: 4px; background: #fff; color: var(--cf-muted-foreground); }

.fd-layout { display: grid; grid-template-columns: 236px minmax(0, 1fr) 340px; border: 1px solid var(--cf-border); border-radius: 12px; overflow: hidden; background: #fff; height: calc(100vh - 205px); min-height: 580px; box-shadow: var(--cf-shadow-sm); }

/* Palette */
.fd-palette { border-right: 1px solid var(--cf-border); overflow-y: auto; padding: 14px 12px; background: #fafafa; }
.fd-pal-title { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--cf-muted-foreground); margin: 4px 4px 8px; }
.fd-pal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 16px; }
.fd-pal-item { display: flex; flex-direction: column; align-items: flex-start; gap: 7px; padding: 9px 10px; border: 1px solid var(--cf-border); border-radius: 8px; background: #fff; font: 600 12px var(--cf-font); color: var(--cf-foreground); cursor: grab; text-align: left; transition: border-color .12s, box-shadow .12s, transform .12s; user-select: none; }
.fd-pal-item i { font-size: 13px; color: var(--cf-muted-foreground); }
.fd-pal-item:hover { border-color: #a1a1aa; box-shadow: var(--cf-shadow-sm); }
.fd-pal-item:hover i { color: var(--cf-primary); }
.fd-pal-item:active { cursor: grabbing; transform: scale(.98); }
.fd-pal-tip { font-size: 12px; color: var(--cf-muted-foreground); line-height: 1.5; padding: 10px; border: 1px dashed var(--cf-border); border-radius: 8px; background: #fff; }

/* Canvas */
.fd-canvas-wrap { overflow: auto; background-color: #f4f4f5; background-image: radial-gradient(#d4d4d8 1px, transparent 1px); background-size: 16px 16px; position: relative; }
.fd-canvas { min-height: 100%; transition: max-width .2s ease; margin: 0 auto; }
.fd-canvas.is-mobile { max-width: 400px; margin: 24px auto; min-height: 0; border: 10px solid #18181b; border-radius: 34px; overflow: hidden; box-shadow: var(--cf-shadow-xl); }
.fd-canvas .pf-page { min-height: 100%; }
/* The form renderer sets font-family: inherit on everything - keep icon fonts working on the canvas. */
.fd-canvas i.fas { font-family: 'Font Awesome 6 Free' !important; font-weight: 900; }
.fd-canvas.is-mobile .pf-page { min-height: 720px; }

.fd-editing .pf-item.fd-item { position: relative; cursor: pointer; border-radius: 6px; outline: 1.5px dashed transparent; outline-offset: 6px; transition: outline-color .1s; }
.fd-editing .fd-item * { pointer-events: none; }
.fd-editing .fd-item:hover { outline-color: #a1a1aa; }
.fd-editing .fd-item.is-selected { outline: 2px solid #2563eb; }
.fd-editing .fd-item.has-error { outline: 2px solid #dc2626; }
.fd-editing .fd-item.is-dragging { opacity: .35; }
.fd-editing .fd-item.drop-l { box-shadow: -8px 0 0 -4px #2563eb; }
.fd-editing .fd-item.drop-r { box-shadow: 8px 0 0 -4px #2563eb; }
.fd-editing .fd-item.drop-t { box-shadow: 0 -8px 0 -4px #2563eb; }
.fd-editing .fd-item.drop-b { box-shadow: 0 8px 0 -4px #2563eb; }
.fd-grid.drop-end::after { content: ''; grid-column: span 12; height: 4px; border-radius: 2px; background: #2563eb; }
.fd-chrome { display: none; position: absolute; top: -32px; left: -8px; align-items: center; gap: 3px; z-index: 5; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
.fd-item.is-selected > .fd-chrome { display: flex; }
.fd-chrome *, .fd-resize-x, .fd-resize-y, .fd-resize-x *, .fd-resize-y * { pointer-events: auto !important; }
.fd-badge { background: #2563eb; color: #fff; font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 5px; white-space: nowrap; }
.fd-tool { width: 25px; height: 25px; border-radius: 5px; background: #fff; border: 1px solid #e4e4e7; color: #3f3f46; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; cursor: pointer; box-shadow: var(--cf-shadow-sm); }
.fd-tool:hover { background: #f4f4f5; }
.fd-tool.danger:hover { background: #fef2f2; color: #dc2626; }
.fd-tool.is-drag { cursor: grab; }
.fd-resize-x { position: absolute; top: 50%; right: -13px; width: 10px; height: 36px; transform: translateY(-50%); border-radius: 6px; background: #fff; border: 2px solid #2563eb; cursor: ew-resize; display: none; z-index: 6; }
.fd-resize-y { position: absolute; left: 50%; bottom: -13px; width: 36px; height: 10px; transform: translateX(-50%); border-radius: 6px; background: #fff; border: 2px solid #2563eb; cursor: ns-resize; display: none; z-index: 6; }
.fd-editing .fd-item.is-selected > .fd-resize-x, .fd-editing .fd-item:hover > .fd-resize-x, .fd-editing .fd-item.is-selected > .fd-resize-y { display: block; }
.fd-canvas.is-mobile .fd-resize-x { display: none !important; }
.fd-size-tip { position: fixed; z-index: 10000; pointer-events: none; background: #18181b; color: #fff; font: 600 12px 'Inter', system-ui, sans-serif; padding: 5px 9px; border-radius: 6px; display: none; }
.fd-editing .pf-header, .fd-editing .pf-actions, .fd-editing .pf-footer, .fd-editing .pf-desc { cursor: pointer; border-radius: 6px; outline: 1.5px dashed transparent; outline-offset: 5px; }
.fd-editing .pf-header:hover, .fd-editing .pf-actions:hover, .fd-editing .pf-footer:hover, .fd-editing .pf-desc:hover { outline-color: #a1a1aa; }
.fd-editing .pf-actions * { pointer-events: none; }
/* Preview: panels hidden, the form full width with a bar to get back. */
.fd-layout.is-previewing { grid-template-columns: minmax(0, 1fr); }
.fd-layout.is-previewing .fd-palette, .fd-layout.is-previewing .fd-inspector { display: none; }
.fd-preview-bar { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; padding: 9px 14px; background: #eff6ff; border-bottom: 1px solid #bfdbfe; color: #1d4ed8; font-size: 13px; }
.fd-preview-bar[hidden] { display: none; }
.fd-preview-bar > span { flex: 1; min-width: 200px; }
.fd-logo-box { position: relative; display: inline-block; line-height: 0; max-width: 100%; }
.fd-editing .fd-logo-box { border-radius: 4px; outline: 1.5px dashed transparent; outline-offset: 4px; }
.fd-editing .pf-header:hover .fd-logo-box { outline-color: #2563eb; }
.fd-logo-handle { position: absolute; right: -9px; bottom: -9px; width: 13px; height: 13px; border-radius: 3px; background: #fff; border: 2px solid #2563eb; cursor: nwse-resize; display: none; z-index: 6; touch-action: none; }
.fd-editing .pf-header:hover .fd-logo-handle { display: block; }
.fd-sub-title { margin: 16px 0 10px; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--cf-muted-foreground); }
.fd-empty-drop { grid-column: span 12; border: 2px dashed #d4d4d8; border-radius: 10px; padding: 40px 20px; text-align: center; color: #71717a; font: 500 14px 'Inter', system-ui, sans-serif; }

/* Inspector */
.fd-inspector { border-left: 1px solid var(--cf-border); display: flex; flex-direction: column; min-height: 0; background: #fff; }
.fd-tabs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; padding: 3px; margin: 12px 14px 4px; background: var(--cf-muted); border-radius: 8px; }
.fd-tab { height: 30px; border: 0; border-radius: 6px; background: transparent; font: 600 12.5px var(--cf-font); color: var(--cf-muted-foreground); cursor: pointer; }
.fd-tab.is-on { background: #fff; color: var(--cf-foreground); box-shadow: var(--cf-shadow-sm); }
.fd-insp-body { overflow-y: auto; padding: 0 16px 24px; flex: 1; }
.fd-group { padding: 14px 0 6px; border-bottom: 1px solid var(--cf-border); }
.fd-group:last-child { border-bottom: 0; }
.fd-group-title { font-size: 12.5px; font-weight: 700; margin-bottom: 11px; color: var(--cf-foreground); display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.fd-row { margin-bottom: 12px; }
.fd-l { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: var(--cf-muted-foreground); margin-bottom: 5px; }
.fd-insp-body .cf-input, .fd-insp-body .cf-textarea, .fd-insp-body .cf-select { font-size: 13px; padding: 7px 10px; }
.fd-seg { display: flex; padding: 3px; background: var(--cf-muted); border-radius: 7px; gap: 2px; }
.fd-seg button { flex: 1; min-width: 0; height: 28px; border: 0; background: transparent; border-radius: 5px; font: 600 12px var(--cf-font); color: var(--cf-muted-foreground); cursor: pointer; white-space: nowrap; padding: 0 6px; }
.fd-seg button:hover { color: var(--cf-foreground); }
.fd-seg button.is-on { background: #fff; color: var(--cf-foreground); box-shadow: var(--cf-shadow-sm); }
.fd-switch-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 13px; font-weight: 600; margin-bottom: 12px; cursor: pointer; }
.fd-switch-row small { display: block; font-weight: 400; font-size: 11.5px; color: var(--cf-muted-foreground); margin-top: 1px; }
.fd-switch { width: 36px; height: 20px; border-radius: 999px; background: #e4e4e7; border: 0; position: relative; cursor: pointer; flex-shrink: 0; transition: background-color .15s; padding: 0; }
.fd-switch::after { content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .2); transition: transform .15s; }
.fd-switch[aria-checked=true] { background: #18181b; }
.fd-switch[aria-checked=true]::after { transform: translateX(16px); }
.fd-slider { display: flex; align-items: center; gap: 10px; }
.fd-slider input[type=range] { flex: 1; accent-color: #18181b; height: 18px; }
.fd-slider output { min-width: 46px; text-align: right; font-size: 12px; font-variant-numeric: tabular-nums; color: var(--cf-muted-foreground); font-weight: 600; }
.fd-color { display: flex; align-items: center; gap: 9px; padding: 4px 0; }
.fd-color input[type=color] { width: 30px; height: 30px; border: 1px solid var(--cf-border); border-radius: 7px; padding: 2px; background: #fff; cursor: pointer; flex-shrink: 0; }
.fd-color input[type=color]::-webkit-color-swatch-wrapper { padding: 0; }
.fd-color input[type=color]::-webkit-color-swatch { border: 0; border-radius: 4px; }
.fd-color span { flex: 1; font-size: 13px; font-weight: 500; }
.fd-color .fd-hex { width: 88px; height: 30px; padding: 0 8px; font: 500 12px ui-monospace, monospace; text-transform: uppercase; border: 1px solid var(--cf-input); border-radius: 6px; }
.fd-color .fd-hex:focus { outline: none; border-color: #18181b; box-shadow: 0 0 0 3px rgba(24, 24, 27, .12); }
.fd-themes { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.fd-theme { border: 1px solid var(--cf-border); border-radius: 9px; padding: 0; overflow: hidden; background: #fff; cursor: pointer; text-align: left; transition: box-shadow .12s; }
.fd-theme:hover { box-shadow: var(--cf-shadow-lg); }
.fd-theme.is-on { outline: 2px solid #18181b; outline-offset: 1px; }
.fd-theme-sw { height: 50px; position: relative; }
.fd-theme-sw .bar { height: 12px; }
.fd-theme-sw .l1, .fd-theme-sw .l2 { position: absolute; left: 10px; height: 6px; border-radius: 3px; }
.fd-theme-sw .l1 { top: 20px; width: 55%; }
.fd-theme-sw .l2 { top: 32px; width: 35%; }
.fd-theme-sw .dot { position: absolute; right: 10px; top: 22px; width: 14px; height: 14px; border-radius: 50%; }
.fd-theme-name { padding: 6px 9px; font-size: 12px; font-weight: 600; border-top: 1px solid var(--cf-border); }
.fd-field-head { display: flex; align-items: center; gap: 10px; padding: 14px 0 12px; border-bottom: 1px solid var(--cf-border); }
.fd-field-icon { width: 34px; height: 34px; border-radius: 8px; background: var(--cf-primary-soft); color: var(--cf-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fd-field-head strong { display: block; font-size: 14px; }
.fd-field-head small { font-size: 12px; color: var(--cf-muted-foreground); }
.fd-nothing { text-align: center; padding: 42px 10px; color: var(--cf-muted-foreground); font-size: 13px; line-height: 1.55; }
.fd-nothing i { font-size: 22px; margin-bottom: 10px; display: block; color: #a1a1aa; }
.fd-errors { display: none; }
.fd-errors ul { margin: 4px 0 0 18px; padding: 0; font-weight: 500; }
.fd-share-url { display: flex; gap: 6px; }

@media (max-width: 1180px) {
  .fd-layout { grid-template-columns: 1fr; height: auto; }
  .fd-palette { border-right: 0; border-bottom: 1px solid var(--cf-border); }
  .fd-pal-grid { grid-template-columns: repeat(auto-fill, minmax(118px, 1fr)); }
  .fd-canvas-wrap { min-height: 560px; max-height: 80vh; }
  .fd-inspector { border-left: 0; border-top: 1px solid var(--cf-border); }
  .fd-insp-body { max-height: none; }
}
</style>

@php
  $icons = ['short_text'=>'fa-font','long_text'=>'fa-align-left','email'=>'fa-at','phone'=>'fa-phone','number'=>'fa-hashtag','date'=>'fa-calendar','dropdown'=>'fa-square-caret-down','radio'=>'fa-circle-dot','checkbox'=>'fa-square-check','file'=>'fa-paperclip','consent'=>'fa-file-signature','section'=>'fa-heading','paragraph'=>'fa-paragraph','divider'=>'fa-minus'];
@endphp

<div class="cf-root">
  <div class="cf-crumbs">
    <span>CMS</span><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <a href="{{ route('cms.forms.index') }}">Forms</a><i class="fas fa-chevron-right" style="font-size:9px"></i>
    <span id="fdCrumb">{{ $form->title }}</span>
  </div>
  <div class="fd-top">
    <div>
      <div class="cf-heading"><span id="fdHeading">{{ $form->title }}</span> <span id="fdStatus"></span></div>
      <div class="cf-subheading" style="margin-top:3px">
        <a href="{{ route('cms.forms.responses.index', $form) }}" style="color:var(--cf-primary);font-weight:700">{{ number_format($responseCount) }} {{ \Illuminate\Support\Str::plural('response', $responseCount) }}</a>
        &middot; <span class="fd-saved" id="fdSaved">All changes saved</span>
        &middot; <span class="fd-kbd">Ctrl</span> <span class="fd-kbd">S</span> save
      </div>
    </div>
    <div class="fd-actions">
      <div class="fd-seg" style="width:auto">
        <button type="button" class="is-on" data-device="desktop" title="Desktop"><i class="fas fa-desktop"></i></button>
        <button type="button" data-device="mobile" title="Mobile"><i class="fas fa-mobile-screen"></i></button>
      </div>
      <div class="fd-seg" style="width:auto">
        <button type="button" class="is-on" data-mode="edit"><i class="fas fa-pen-ruler"></i> Design</button>
        <button type="button" data-mode="preview"><i class="fas fa-eye"></i> Preview</button>
      </div>
      <button type="button" class="cf-btn cf-btn-outline" id="fdSaveBtn" onclick="fdSave(false)"><i class="fas fa-floppy-disk"></i> <span>Save Draft</span></button>
      <button type="button" class="cf-btn cf-btn-primary" id="fdPublishBtn" onclick="fdSave(true)"><i class="fas fa-globe"></i> Publish</button>
      <form method="POST" action="{{ route('cms.forms.unpublish', $form) }}" id="fdUnpublishForm" style="display:none;margin:0" onsubmit="if (!confirm('Unpublish this form? Its public link will stop accepting responses. Your changes in the designer are kept.')) return false; fdFlush(); return true;">
        @csrf
        <button class="cf-btn cf-btn-outline"><i class="fas fa-eye-slash"></i> Unpublish</button>
      </form>
    </div>
  </div>

  <div class="cf-alert fd-restored" id="fdRestored" style="display:none"><i class="fas fa-clock-rotate-left"></i><span style="flex:1"></span>
    <button type="button" class="cf-btn cf-btn-outline cf-btn-sm" onclick="fdDiscardDraft(this)"><i class="fas fa-rotate-left"></i> Discard changes</button></div>
  <div class="cf-alert is-error fd-errors" id="fdErrors"><i class="fas fa-circle-exclamation"></i><div><strong>Couldn't save yet:</strong><ul></ul></div></div>

  <div class="fd-layout">
    <aside class="fd-palette">
      <div class="fd-pal-title">Questions</div>
      <div class="fd-pal-grid">
        @foreach ($fieldTypes as $type => $label)
          @continue (in_array($type, \App\Models\FormField::LAYOUT_TYPES, true))
          <button type="button" class="fd-pal-item" draggable="true" data-add="{{ $type }}"><i class="fas {{ $icons[$type] }}"></i>{{ $label }}</button>
        @endforeach
      </div>
      <div class="fd-pal-title">Layout</div>
      <div class="fd-pal-grid">
        @foreach (\App\Models\FormField::LAYOUT_TYPES as $type)
          <button type="button" class="fd-pal-item" draggable="true" data-add="{{ $type }}"><i class="fas {{ $icons[$type] }}"></i>{{ $fieldTypes[$type] }}</button>
        @endforeach
      </div>
      <div class="fd-pal-tip">
        <strong>Tips</strong><br>
        Drag blocks onto the form, or click to add.<br>
        Drag the <span style="color:#2563eb;font-weight:700">blue handle</span> on the right edge to resize - put two fields at ½ to place them side by side.<br>
        <span class="fd-kbd">Del</span> remove · <span class="fd-kbd">Ctrl</span>+<span class="fd-kbd">D</span> duplicate
      </div>
    </aside>

    <main class="fd-canvas-wrap" id="fdCanvasWrap">
      <div class="fd-preview-bar" id="fdPreviewBar" hidden>
        <span><i class="fas fa-eye"></i> <strong>Preview</strong> - this is what clients see. Try filling it in; nothing is sent.</span>
        <a class="cf-btn cf-btn-outline cf-btn-sm" href="{{ route('cms.forms.preview', $form) }}" target="_blank" rel="noopener" title="The last saved version, on its own page"><i class="fas fa-arrow-up-right-from-square"></i> Open full page</a>
        <button type="button" class="cf-btn cf-btn-primary cf-btn-sm" onclick="setPreview(false)"><i class="fas fa-pen-ruler"></i> Back to design</button>
      </div>
      <div class="fd-canvas fd-editing" id="fdCanvas"></div>
    </main>

    <aside class="fd-inspector">
      <div class="fd-tabs" role="tablist">
        <button type="button" class="fd-tab" data-tab="field">Field</button>
        <button type="button" class="fd-tab" data-tab="design">Design</button>
        <button type="button" class="fd-tab" data-tab="settings">Settings</button>
      </div>
      <div class="fd-insp-body" id="fdInspector"></div>
    </aside>
  </div>
</div>
<div class="fd-size-tip" id="fdSizeTip"></div>

@include('cms_forms.partials.scripts')
<script>
const FD_TYPES = @json($fieldTypes);
const FD_ICONS = @json($icons);
const FD_LAYOUT = @json(\App\Models\FormField::LAYOUT_TYPES);
const FD_OPTION_TYPES = ['dropdown', 'radio', 'checkbox'];
const FD_NO_PLACEHOLDER = ['date', 'radio', 'checkbox', 'file', 'consent'];
const FD_PRESETS = @json(\App\Support\CmsForms\FormDesign::PRESETS);
const FD_FONTS = @json(\App\Support\CmsForms\FormDesign::FONTS);
const FD_FIELD_DEFAULTS = @json(\App\Support\CmsForms\FormDesign::FIELD_DEFAULTS);
const FD_UPDATE_URL = @json(route('cms.forms.update', $form));
const FD_AUTOSAVE_URL = @json(route('cms.forms.autosave', $form));
const FD_RESTORE = @json($autosave);
const FD_LOGO = @json(asset('uploads/engage.png'));
const FD_DEFAULTS = {
  email: { label: 'Email Address', placeholder: 'name@example.com' },
  phone: { label: 'Phone Number', placeholder: '+971 5X XXX XXXX' },
  date: { label: 'Date' },
  dropdown: { label: 'Choose an option', options: ['Option 1', 'Option 2'] },
  radio: { label: 'Choose one', options: ['Option 1', 'Option 2'] },
  checkbox: { label: 'Check all that apply', options: ['Option 1', 'Option 2', 'Option 3'] },
  file: { label: 'Upload a document' },
  consent: { label: 'I agree to be contacted by Engage Clinic about this form', is_required: true },
  section: { label: 'Section title' },
  paragraph: { label: 'Text block', settings: { content: 'Add some text for your clients here.', label_bold: false } },
  divider: { label: 'Divider' },
};

let F = @json(\App\Http\Controllers\CmsForm\FormController::builderPayload($form));
let fdSel = null, fdTab = 'design', fdDevice = 'desktop', fdPreviewing = false, fdDirty = false, fdSeq = 0;
F.fields.forEach(f => { f._key = 'k' + (++fdSeq); });

const $ = (s, r = document) => r.querySelector(s);
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const isLayout = (f) => FD_LAYOUT.includes(f.type);
const isSingleTick = (f) => f.type === 'consent' || (f.type === 'checkbox' && !(f.options || []).filter(o => o.trim()).length);
const field = (key) => F.fields.find(f => f._key === key);
const toName = (label) => (label || '').toLowerCase().normalize('NFKD').replace(/[^\w\s]/g, '').trim().replace(/\s+/g, '_').replace(/^[^a-z]+/, '').slice(0, 60) || 'field';
function uniqueName(base, selfKey) {
  const taken = new Set(F.fields.filter(f => f._key !== selfKey).map(f => f.name));
  let name = base, i = 2;
  while (taken.has(name)) name = `${base}_${i++}`;
  return name;
}
// ── Autosave ───────────────────────────────────────────────────────────
// Every change is saved ~1s after you stop editing:
//  · a draft form is saved for real (so the list, preview and reopen all show it);
//  · a live form's changes are kept as a draft snapshot - the public page only
//    changes when you press "Save changes";
//  · a draft that can't be saved as-is (e.g. a dropdown with no options) is kept
//    as a snapshot too, so nothing is lost. Reopening restores the snapshot.
let fdVersion = 0, fdAutoTimer = null, fdSaving = false, fdQueued = false, fdHasSnapshot = false, fdSaveFailed = false;
const fdTime = (d) => new Date(d).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
function setSaved(state, detail = '') {
  const s = $('#fdSaved');
  const text = {
    dirty: 'Editing…',
    saving: '<i class="fas fa-circle-notch fa-spin"></i> Saving…',
    saved: `<i class="fas fa-check"></i> Saved ${detail}`,
    snapshot: F.status === 'published'
      ? `<i class="fas fa-cloud"></i> Draft saved ${detail} · not live until you press <strong>Save changes</strong>`
      : `<i class="fas fa-cloud"></i> Work kept ${detail} · the form can't be saved yet - press <strong>Save Draft</strong> to see why`,
    offline: '<i class="fas fa-triangle-exclamation"></i> Couldn\'t save - retrying…',
  }[state];
  s.innerHTML = text;
  s.classList.toggle('is-dirty', state === 'dirty' || state === 'offline' || state === 'snapshot');
}
function markDirty() {
  fdDirty = true;
  fdVersion++;
  setSaved('dirty');
  clearTimeout(fdAutoTimer);
  fdAutoTimer = setTimeout(fdAutosave, 1000);
}
function fdBody(publish = false) {
  return {
    title: F.title, slug: F.slug, description: F.description, success_message: F.success_message,
    auto_create_lead: !!F.auto_create_lead, design: F.design, publish,
    fields: F.fields.map(f => ({
      id: f.id, type: f.type, label: f.label, name: f.name, placeholder: f.placeholder, help_text: f.help_text,
      is_required: !!f.is_required, options: (f.options || []).map(o => o.trim()).filter(Boolean), settings: f.settings,
    })),
  };
}
const fdHeaders = () => ({ 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content });
async function fdAutosave() {
  clearTimeout(fdAutoTimer);
  if (!fdDirty) return;
  if (fdSaving) { fdQueued = true; return; }
  fdSaving = true;
  setSaved('saving');
  const version = fdVersion;
  const keys = F.fields.map(f => f._key);
  const body = fdBody(false);
  try {
    let done = false;
    if (F.status !== 'published') {
      const res = await fetch(FD_UPDATE_URL, { method: 'PUT', headers: fdHeaders(), body: JSON.stringify(body) });
      if (res.ok) {
        const data = await res.json();
        // New fields got ids - keep them so the next save updates instead of re-creating them.
        data.form.fields.forEach((saved, i) => { const f = field(keys[i]); if (f && !f.id) f.id = saved.id; });
        F.status = data.form.status; F.public_url = data.form.public_url; F.embed_code = data.form.embed_code;
        if (F.fields.some(f => f._error)) { F.fields.forEach(f => { f._error = false; }); $('#fdErrors').style.display = 'none'; renderCanvas(); }
        fdHasSnapshot = false; restoredNowSaved();
        done = true;
        if (version === fdVersion) { fdDirty = false; setSaved('saved', fdTime(Date.now())); }
      } else if (res.status === 422) {
        // Outline the fields the server rejected - quietly, without the error box or moving the panel.
        const errors = (await res.json().catch(() => ({}))).errors || {};
        const bad = new Set(Object.keys(errors).map(k => k.match(/^fields\.(\d+)\./)).filter(Boolean).map(m => +m[1]));
        F.fields.forEach(f => { f._error = bad.has(keys.indexOf(f._key)); });
        renderCanvas();
      } else {
        throw new Error('save failed');
      }
    }
    if (!done) {
      const res = await fetch(FD_AUTOSAVE_URL, { method: 'POST', headers: fdHeaders(), body: JSON.stringify(body) });
      if (!res.ok) throw new Error('autosave failed');
      const data = await res.json();
      fdHasSnapshot = true;
      if (version === fdVersion) { fdDirty = false; setSaved('snapshot', fdTime(data.saved_at)); }
    }
    fdSaveFailed = false;
  } catch (_) {
    fdSaveFailed = true;
    setSaved('offline');
    clearTimeout(fdAutoTimer);
    fdAutoTimer = setTimeout(fdAutosave, 5000);
  } finally {
    fdSaving = false;
    if (fdQueued || (fdDirty && !fdSaveFailed && version !== fdVersion)) { fdQueued = false; fdAutosave(); }
  }
}
// Closing the tab or navigating away flushes any pending change straight away.
function fdFlush() {
  if (!fdDirty) return;
  clearTimeout(fdAutoTimer);
  try {
    fetch(FD_AUTOSAVE_URL, { method: 'POST', headers: fdHeaders(), body: JSON.stringify(fdBody(false)), keepalive: true });
    fdDirty = false;
  } catch (_) { /* body too large for keepalive - beforeunload below still warns */ }
}
document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') fdFlush(); });
window.addEventListener('pagehide', fdFlush);
window.addEventListener('beforeunload', (e) => {
  fdFlush();
  if (fdDirty || fdSaving || fdSaveFailed) { e.preventDefault(); e.returnValue = ''; }
});

function hideRestored() { const n = $('#fdRestored'); if (n) n.style.display = 'none'; }
// Restored work is now on the form itself - there's nothing left to discard.
function restoredNowSaved() {
  const n = $('#fdRestored');
  if (!n || n.style.display === 'none' || n.dataset.saved) return;
  n.dataset.saved = '1';
  n.querySelector('span').innerHTML = 'Restored your unsaved changes - <strong>saved to the form</strong>.';
  n.querySelector('button').style.display = 'none';
  setTimeout(hideRestored, 6000);
}
async function fdDiscardDraft(btn) {
  const live = F.status === 'published';
  if (!confirm(live ? 'Discard your draft changes and go back to the live version of this form?' : 'Discard the unsaved changes and go back to the last saved version?')) return;
  btn.disabled = true;
  clearTimeout(fdAutoTimer); fdDirty = false;
  await fetch(FD_AUTOSAVE_URL, { method: 'DELETE', headers: fdHeaders() }).catch(() => {});
  window.location.reload();
}

// ── Canvas renderer (mirrors resources/views/cms_forms/public.blade.php) ──
function cssVars(d) {
  const v = Object.entries(d.colors).map(([k, c]) => `--pf-${k.replace(/_/g, '-')}:${c}`);
  const heading = d.heading_font || d.font;
  v.push(`--pf-heading-font:'${heading}', '${d.font}', system-ui, sans-serif`, `--pf-heading-weight:${heading === 'Baloo 2' ? 600 : 800}`);
  v.push(`--pf-font:'${d.font}', system-ui, sans-serif`, `--pf-radius:${d.radius}px`, `--pf-title-size:${d.title_size}px`, `--pf-max:${d.max_width}px`, `--pf-gap:${d.spacing}px`,
    `--pf-logo-size:${d.logo_size}px`, `--pf-header-pad:${d.header_padding}px`, `--pf-subtitle-size:${d.subtitle_size}px`);
  return v.join(';');
}
function controlHtml(f, s) {
  const ph = esc(f.placeholder);
  const opts = (f.options || []).filter(o => o.trim());
  switch (f.type) {
    case 'long_text': return `<textarea class="pf-input" rows="${s.rows}" placeholder="${ph}"></textarea>`;
    case 'email': return `<input class="pf-input" type="email" placeholder="${ph}">`;
    case 'phone': return `<input class="pf-input" type="tel" placeholder="${ph}">`;
    case 'number': return `<input class="pf-input" type="number" placeholder="${ph}">`;
    case 'date': return `<input class="pf-input" type="date">`;
    case 'dropdown': return `<select class="pf-input"><option>${esc(f.placeholder || 'Select…')}</option>${opts.map(o => `<option>${esc(o)}</option>`).join('')}</select>`;
    case 'radio': return `<div class="pf-choices" style="--cols:${s.option_columns}">${opts.map(o => `<label class="pf-choice"><input type="radio" name="p_${f._key}"> ${esc(o)}</label>`).join('')}</div>`;
    case 'checkbox': return opts.length ? `<div class="pf-choices" style="--cols:${s.option_columns}">${opts.map(o => `<label class="pf-choice"><input type="checkbox"> ${esc(o)}</label>`).join('')}</div>` : '';
    case 'file': return `<input class="pf-input pf-file" type="file">`;
    case 'consent': return '';
    default: return `<input class="pf-input" type="text" placeholder="${ph}">`;
  }
}
function itemHtml(f) {
  const d = F.design, s = f.settings;
  const sel = f._key === fdSel ? ' is-selected' : '';
  const err = f._error ? ' has-error' : '';
  const chrome = `
    <div class="fd-chrome">
      <span class="fd-tool is-drag" title="Drag to move" data-drag><i class="fas fa-grip-vertical"></i></span>
      <span class="fd-badge">${esc(FD_TYPES[f.type])} · ${s.width}/12</span>
      <button type="button" class="fd-tool" data-act="up" title="Move up"><i class="fas fa-arrow-up"></i></button>
      <button type="button" class="fd-tool" data-act="down" title="Move down"><i class="fas fa-arrow-down"></i></button>
      <button type="button" class="fd-tool" data-act="dup" title="Duplicate (Ctrl+D)"><i class="fas fa-clone"></i></button>
      <button type="button" class="fd-tool danger" data-act="del" title="Delete (Del)"><i class="fas fa-trash"></i></button>
    </div>
    <div class="fd-resize-x" data-resize="x" title="Drag to resize width"></div>
    ${f.type === 'long_text' ? '<div class="fd-resize-y" data-resize="y" title="Drag to change height"></div>' : ''}`;
  const attrs = `data-key="${f._key}" style="--span:${s.width}"`;

  if (f.type === 'section') {
    return `<div class="pf-item fd-item pf-section pf-section--${s.section_style} ${d.title_uppercase ? 'is-upper' : ''}${sel}${err}" ${attrs} data-align="${s.align}">
      <h2>${esc(f.label)}</h2>${f.help_text ? `<p>${esc(f.help_text)}</p>` : ''}${chrome}</div>`;
  }
  if (f.type === 'paragraph') {
    return `<div class="pf-item fd-item pf-paragraph ${s.label_bold ? 'is-bold' : ''}${sel}${err}" ${attrs} data-align="${s.align}">${esc(s.content) || '<span style="opacity:.45">Empty text block</span>'}${chrome}</div>`;
  }
  if (f.type === 'divider') {
    return `<div class="pf-item fd-item${sel}${err}" ${attrs}><hr class="pf-divider">${chrome}</div>`;
  }

  const req = f.is_required ? ' <span class="pf-req">*</span>' : '';
  const single = isSingleTick(f);
  const left = !single && (s.label_position || d.label_position) === 'left';
  const tall = ['long_text', 'radio', 'checkbox'].includes(f.type);
  const label = single
    ? `<label class="${f.type === 'consent' ? 'pf-consent' : 'pf-choice'}"><input type="checkbox"> <span class="${s.label_bold ? 'pf-label is-bold' : ''}" style="margin:0">${esc(f.label)}${req}</span></label>`
    : `<label class="pf-label size-${s.label_size}${s.label_bold ? ' is-bold' : ''}">${esc(f.label) || '<em style="opacity:.5">Untitled</em>'}${req}</label>`;
  return `<div class="pf-item fd-item${left ? ' is-label-left' : ''}${tall ? ' is-tall' : ''}${sel}${err}" ${attrs}>
    ${label}<div class="pf-control">${controlHtml(f, s)}${f.help_text ? `<div class="pf-help">${esc(f.help_text)}</div>` : ''}<div class="pf-error"></div></div>${chrome}</div>`;
}
function renderCanvas() {
  const d = F.design;
  const canvas = $('#fdCanvas');
  canvas.classList.toggle('is-mobile', fdDevice === 'mobile');
  canvas.classList.toggle('fd-editing', !fdPreviewing);
  const logo = d.show_logo && d.header_style !== 'minimal'
    ? `<div class="pf-logo-wrap ${d.logo_plate ? 'has-plate' : ''}"><span class="fd-logo-box"><img class="pf-logo" src="${FD_LOGO}" alt="">${fdPreviewing ? '' : '<span class="fd-logo-handle" data-resize="logo" title="Drag to resize the logo"></span>'}</span></div>`
    : '';
  const header = `<header class="pf-header pf-header--${d.header_style}" data-align="${d.title_align}" data-logo="${d.logo_align}" data-zone="header">
      ${logo}
      <div class="pf-heading">
        ${d.header_kicker ? `<div class="pf-kicker">${esc(d.header_kicker)}</div>` : ''}
        <h1 class="pf-title ${d.title_uppercase ? 'is-upper' : ''}">${esc(F.title) || 'Untitled form'}</h1>
        ${d.header_subtitle ? `<p class="pf-subtitle">${esc(d.header_subtitle)}</p>` : ''}
      </div></header>`;
  const footer = (d.footer_left || d.footer_right) ? `<footer class="pf-footer" data-zone="footer">
      <span>${d.footer_left ? `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>${esc(d.footer_left)}` : ''}</span>
      <span>${d.footer_right ? `<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>${esc(d.footer_right)}` : ''}</span></footer>` : '';
  canvas.innerHTML = `
    <div class="pf-page" style="${cssVars(d)}" data-field-style="${d.field_style}">
      ${d.frame ? '<div class="pf-frame"></div>' : ''}
      <div class="pf-body"><div class="pf-shell"><div class="pf-card" data-zone="card">
        ${header}
        ${F.description ? `<div class="pf-desc" data-zone="settings">${esc(F.description)}</div>` : ''}
        <div class="pf-grid fd-grid" id="fdGrid">
          ${F.fields.length ? F.fields.map(itemHtml).join('') : '<div class="fd-empty-drop">Drag a block here from the left, or click one to add it.</div>'}
        </div>
        <div class="pf-actions ${d.button_style === 'full' ? 'is-full' : ''}" data-align="${d.button_align}" data-zone="button">
          <button type="button" class="pf-submit">${esc(d.submit_label)}</button></div>
        ${footer}
      </div></div></div>
      ${d.frame ? '<div class="pf-frame is-bottom"></div>' : ''}
    </div>`;
}
function loadFont() {
  const families = [...new Set([F.design.font, F.design.heading_font].filter(Boolean))];
  $('#fdFontLink').href = `https://fonts.googleapis.com/css2?${families.map(f => 'family=' + FD_FONTS[f]).join('&')}&display=swap`;
}

// ── Inspector controls (shadcn-style) ──────────────────────────────────
const seg = (scope, key, value, options) => `<div class="fd-seg">${options.map(([v, l, t]) =>
  `<button type="button" class="${String(value) === String(v) ? 'is-on' : ''}" data-seg="${scope}" data-key="${key}" data-value="${esc(v)}" ${t ? `title="${esc(t)}"` : ''}>${l}</button>`).join('')}</div>`;
const sw = (scope, key, on, label, hint) => `<label class="fd-switch-row" data-switch-row><span>${label}${hint ? `<small>${hint}</small>` : ''}</span>
  <button type="button" role="switch" aria-checked="${!!on}" class="fd-switch" data-switch="${scope}" data-key="${key}"></button></label>`;
const slider = (scope, key, value, min, max, step, unit) => `<div class="fd-slider"><input type="range" min="${min}" max="${max}" step="${step}" value="${value}" data-${scope}="${key}" data-unit="${unit}"><output>${value}${unit}</output></div>`;
const row = (label, control, extra = '') => `<div class="fd-row"><div class="fd-l"><span>${label}</span>${extra}</div>${control}</div>`;
const group = (title, body, extra = '', id = '') => `<div class="fd-group" ${id ? `data-group="${id}"` : ''}><div class="fd-group-title"><span>${title}</span>${extra}</div>${body}</div>`;
const color = (key, label) => `<label class="fd-color"><input type="color" value="${F.design.colors[key]}" data-c="${key}"><span>${label}</span><input class="fd-hex" value="${F.design.colors[key]}" data-c="${key}" maxlength="7" spellcheck="false"></label>`;

function inspectorField() {
  const f = field(fdSel);
  if (!f) return `<div class="fd-nothing"><i class="fas fa-arrow-pointer"></i>Select a block on the form to edit its content, size and text style.</div>`;
  const s = f.settings;
  const hasOptions = FD_OPTION_TYPES.includes(f.type);
  let content = '';
  if (f.type === 'section') {
    content = row('Heading', `<input class="cf-input" data-f="label" value="${esc(f.label)}" maxlength="255">`)
      + row('Subtext', `<input class="cf-input" data-f="help_text" value="${esc(f.help_text)}" maxlength="500" placeholder="Optional">`);
  } else if (f.type === 'paragraph') {
    content = row('Text', `<textarea class="cf-textarea" rows="5" data-s="content" maxlength="3000">${esc(s.content)}</textarea>`);
  } else if (f.type !== 'divider') {
    content = row('Label', `<input class="cf-input" data-f="label" value="${esc(f.label)}" maxlength="255">`)
      + row('Field name', `<input class="cf-input cf-mono" data-f="name" value="${esc(f.name)}" maxlength="100">`, '<span style="font-weight:400">a-z 0-9 _</span>')
      + (FD_NO_PLACEHOLDER.includes(f.type) ? '' : row(f.type === 'dropdown' ? 'Empty option text' : 'Placeholder', `<input class="cf-input" data-f="placeholder" value="${esc(f.placeholder)}" maxlength="255">`))
      + row('Help text', `<input class="cf-input" data-f="help_text" value="${esc(f.help_text)}" maxlength="500" placeholder="Shown under the field">`)
      + (hasOptions ? row('Options', `<textarea class="cf-textarea" rows="5" data-f="options">${esc((f.options || []).join('\n'))}</textarea>`, '<span style="font-weight:400">one per line</span>')
        + (f.type === 'checkbox' ? '<div class="cf-hint" style="margin:-6px 0 10px">Leave empty for a single tick box.</div>' : '') : '')
      + sw('f', 'is_required', f.is_required, 'Required', 'Clients must answer this');
  }

  const widthSeg = seg('s', 'width', s.width, [[3, '¼'], [4, '⅓'], [6, '½'], [8, '⅔'], [9, '¾'], [12, 'Full']]);
  let layout = row('Width', widthSeg) + row('', slider('s', 'width', s.width, 1, 12, 1, '/12'));
  if (!isLayout(f) && !isSingleTick(f)) layout += row('Label position', seg('s', 'label_position', s.label_position, [['', 'Theme'], ['top', 'Top'], ['left', 'Left']]));
  if ((f.type === 'radio' || f.type === 'checkbox') && !isSingleTick(f)) layout += row('Option columns', seg('s', 'option_columns', s.option_columns, [[1, '1'], [2, '2'], [3, '3'], [4, '4']]));
  if (f.type === 'long_text') layout += row('Height', slider('s', 'rows', s.rows, 1, 20, 1, ' rows'));

  let text = '';
  if (!isLayout(f)) {
    text = sw('s', 'label_bold', s.label_bold, 'Bold label')
      + (isSingleTick(f) ? '' : row('Label size', seg('s', 'label_size', s.label_size, [['sm', 'Small'], ['md', 'Medium'], ['lg', 'Large']])));
  } else if (f.type === 'section') {
    text = row('Style', seg('s', 'section_style', s.section_style, [['bar', 'Bar'], ['underline', 'Underline'], ['plain', 'Plain']]))
      + row('Align', seg('s', 'align', s.align, [['left', '<i class="fas fa-align-left"></i>'], ['center', '<i class="fas fa-align-center"></i>'], ['right', '<i class="fas fa-align-right"></i>']]));
  } else if (f.type === 'paragraph') {
    text = sw('s', 'label_bold', s.label_bold, 'Bold text')
      + row('Align', seg('s', 'align', s.align, [['left', '<i class="fas fa-align-left"></i>'], ['center', '<i class="fas fa-align-center"></i>'], ['right', '<i class="fas fa-align-right"></i>']]));
  }

  return `<div class="fd-field-head"><span class="fd-field-icon"><i class="fas ${FD_ICONS[f.type]}"></i></span>
      <div style="flex:1"><strong>${esc(FD_TYPES[f.type])}</strong><small>${isLayout(f) ? 'Layout block' : 'Question'}</small></div>
      <button type="button" class="cf-btn cf-btn-ghost cf-btn-icon" data-act="dup" title="Duplicate"><i class="fas fa-clone"></i></button>
      <button type="button" class="cf-btn cf-btn-danger-ghost cf-btn-icon" data-act="del" title="Delete"><i class="fas fa-trash"></i></button></div>`
    + (content ? group('Content', content) : '')
    + group('Layout', layout)
    + (text ? group('Text style', text) : '');
}

function inspectorDesign() {
  const d = F.design;
  const themes = Object.entries(FD_PRESETS).map(([key, p]) => `
    <button type="button" class="fd-theme ${d.theme === key ? 'is-on' : ''}" data-theme="${key}">
      <div class="fd-theme-sw" style="background:${p.colors.page_bg}"><div class="bar" style="background:${p.colors.header_bg}"></div>
        <div class="l1" style="background:${p.colors.field_border}"></div><div class="l2" style="background:${p.colors.section_bg}"></div>
        <div class="dot" style="background:${p.colors.primary}"></div></div>
      <div class="fd-theme-name">${esc(p.name)}</div></button>`).join('');
  return group('Theme', `<div class="fd-themes">${themes}</div><div class="cf-hint" style="margin:8px 0 6px">A theme sets colours, font and styles. Fine-tune anything below.</div>`)
    + group('Colours',
      color('primary', 'Accent / button') + color('header_bg', 'Title & frame') + color('header_text', 'Banner text')
      + color('section_bg', 'Section bar') + color('section_text', 'Section text') + color('page_bg', 'Page background')
      + color('card_bg', 'Form background') + color('field_bg', 'Field fill') + color('field_border', 'Field border')
      + color('label', 'Labels') + color('text', 'Text'))
    + group('Typography',
      row('Text font', `<select class="cf-select" data-d="font">${Object.keys(FD_FONTS).map(n => `<option ${n === d.font ? 'selected' : ''}>${esc(n)}</option>`).join('')}</select>`)
      + row('Heading font', `<select class="cf-select" data-d="heading_font"><option value="" ${!d.heading_font ? 'selected' : ''}>Same as text</option>${Object.keys(FD_FONTS).map(n => `<option ${n === d.heading_font ? 'selected' : ''}>${esc(n)}</option>`).join('')}</select>`, '<span style="font-weight:400">title & sections</span>')
      + row('Title size', slider('d', 'title_size', d.title_size, 18, 64, 1, 'px'))
      + sw('d', 'title_uppercase', d.title_uppercase, 'Uppercase titles', 'Form title and section headings'))
    + group('Header', inspectorHeader(d), '', 'header')
    + group('Fields',
      row('Field style', seg('d', 'field_style', d.field_style, [['outline', 'Outline'], ['filled', 'Filled'], ['underline', 'Underline']]))
      + row('Labels', seg('d', 'label_position', d.label_position, [['top', 'Above field'], ['left', 'Beside field']]))
      + row('Corner radius', slider('d', 'radius', d.radius, 0, 24, 1, 'px'))
      + row('Spacing', slider('d', 'spacing', d.spacing, 6, 40, 1, 'px'))
      + row('Form width', slider('d', 'max_width', d.max_width, 480, 1100, 10, 'px')))
    + group('Button',
      row('Text', `<input class="cf-input" data-d="submit_label" value="${esc(d.submit_label)}" maxlength="40">`)
      + row('Width', seg('d', 'button_style', d.button_style, [['full', 'Full width'], ['auto', 'Fit text']]))
      + row('Align', seg('d', 'button_align', d.button_align, [['left', '<i class="fas fa-align-left"></i>'], ['center', '<i class="fas fa-align-center"></i>'], ['right', '<i class="fas fa-align-right"></i>']])))
    + group('Footer',
      row('Left (e.g. website)', `<input class="cf-input" data-d="footer_left" value="${esc(d.footer_left)}" maxlength="160" placeholder="www.engageclinic.ae">`)
      + row('Right (e.g. address)', `<input class="cf-input" data-d="footer_right" value="${esc(d.footer_right)}" maxlength="160" placeholder="Khalifa City, Abu Dhabi">`));
}

function inspectorHeader(d) {
  const alignSeg = (key, value) => seg('d', key, value, [['left', '<i class="fas fa-align-left"></i>', 'Left'], ['center', '<i class="fas fa-align-center"></i>', 'Centre'], ['right', '<i class="fas fa-align-right"></i>', 'Right']]);
  const split = d.header_style === 'split', banner = d.header_style === 'banner';
  const hasLogo = d.show_logo && d.header_style !== 'minimal';

  let html = row('Style', seg('d', 'header_style', d.header_style, [['split', 'Logo + title'], ['banner', 'Banner'], ['centered', 'Stacked'], ['minimal', 'Title']]))
    + (split ? '' : row('Title align', alignSeg('title_align', d.title_align)))
    + row('Text above title', `<input class="cf-input" data-d="header_kicker" value="${esc(d.header_kicker)}" maxlength="80" placeholder="e.g. You're invited">`)
    + row('Text below title', `<textarea class="cf-textarea" rows="2" data-d="header_subtitle" maxlength="300" placeholder="e.g. Saturday 5 April · 10 AM – 1 PM · Khalifa City">${esc(d.header_subtitle)}</textarea>`)
    + row('Text below title - size', slider('d', 'subtitle_size', d.subtitle_size, 11, 36, 1, 'px'))
    + (banner ? row('Banner height', slider('d', 'header_padding', d.header_padding, 8, 80, 1, 'px')) : '');

  html += `<div class="fd-sub-title">Logo</div>` + sw('d', 'show_logo', d.show_logo, 'Show logo');
  if (d.header_style === 'minimal') {
    html += '<div class="cf-hint" style="margin:-4px 0 12px">The “Title” style has no logo - pick another style to show it.</div>';
  } else if (hasLogo) {
    html += row('Size', slider('d', 'logo_size', d.logo_size, 24, 160, 1, 'px'), '<span style="font-weight:400">or drag its corner</span>')
      + row('Position', split
        ? seg('d', 'logo_align', d.logo_align === 'right' ? 'right' : 'left', [['left', 'Logo left'], ['right', 'Logo right']])
        : seg('d', 'logo_align', d.logo_align, [['auto', banner ? 'With title' : 'Auto'], ['left', '<i class="fas fa-align-left"></i>', 'Left'], ['center', '<i class="fas fa-align-center"></i>', 'Centre'], ['right', '<i class="fas fa-align-right"></i>', 'Right']]))
      + (banner ? sw('d', 'logo_plate', d.logo_plate, 'White box behind logo', 'Keeps a dark logo readable on the banner') : '');
  }
  return html + `<div class="fd-sub-title">Page</div>` + sw('d', 'frame', d.frame, 'Frame stripes', 'Coloured bars at the top and bottom of the page');
}

function inspectorSettings() {
  const live = F.status === 'published';
  const share = live
    ? row('Public link', `<div class="fd-share-url"><input class="cf-input cf-mono" value="${esc(F.public_url)}" readonly onclick="this.select()">
        <button type="button" class="cf-btn cf-btn-outline cf-btn-icon" title="Copy" onclick="cfCopy(${esc(JSON.stringify(F.public_url))}, this)"><i class="fas fa-copy"></i></button>
        <a class="cf-btn cf-btn-outline cf-btn-icon" href="${esc(F.public_url)}" target="_blank" rel="noopener" title="Open"><i class="fas fa-arrow-up-right-from-square"></i></a></div>`)
      + row('Embed on a website', `<textarea class="cf-textarea cf-mono" rows="3" readonly onclick="this.select()">${esc(F.embed_code)}</textarea>
        <button type="button" class="cf-btn cf-btn-outline cf-btn-sm" style="margin-top:6px" onclick="cfCopy(${esc(JSON.stringify(F.embed_code))}, this)"><i class="fas fa-code"></i> Copy embed code</button>`)
    : '<div class="cf-hint" style="margin-bottom:10px">Publish this form to get a public link and embed code.</div>';
  return group('Form',
      row('Form name', `<input class="cf-input" data-form="title" value="${esc(F.title)}" maxlength="255">`)
      + row('Public URL', `<input class="cf-input cf-mono" data-form="slug" value="${esc(F.slug)}" maxlength="120" ${live ? 'readonly' : ''}>`,
        `<span style="font-weight:400">${live ? 'locked while published' : '/forms/…'}</span>`)
      + row('Intro text', `<textarea class="cf-textarea" rows="3" data-form="description" placeholder="Date, time, venue, what to expect…">${esc(F.description)}</textarea>`)
      + row('Success message', `<textarea class="cf-textarea" rows="2" data-form="success_message" placeholder="{{ \App\Models\Form::DEFAULT_SUCCESS_MESSAGE }}">${esc(F.success_message)}</textarea>`))
    + group('Leads', sw('form', 'auto_create_lead', F.auto_create_lead, 'Create a lead automatically', 'Off: staff review each response and convert it themselves'))
    + group('Share', share);
}

function renderInspector() {
  document.querySelectorAll('.fd-tab').forEach(t => t.classList.toggle('is-on', t.dataset.tab === fdTab));
  $('#fdInspector').innerHTML = fdTab === 'field' ? inspectorField() : fdTab === 'design' ? inspectorDesign() : inspectorSettings();
}
function renderStatus() {
  const live = F.status === 'published';
  $('#fdStatus').innerHTML = live
    ? '<span class="cf-badge" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0"><span class="cf-dot" style="background:#22c55e"></span>Published</span>'
    : '<span class="cf-badge" style="background:#f4f4f5;color:#52525b;border-color:#e4e4e7"><span class="cf-dot" style="background:#a1a1aa"></span>Draft</span>';
  $('#fdSaveBtn span').textContent = live ? 'Save changes' : 'Save Draft';
  $('#fdPublishBtn').style.display = live ? 'none' : '';
  $('#fdUnpublishForm').style.display = live ? '' : 'none';
  $('#fdHeading').textContent = F.title || 'Untitled form';
  $('#fdCrumb').textContent = F.title || 'Untitled form';
}
function select(key, tab = 'field') {
  fdSel = key;
  fdTab = key ? tab : (fdTab === 'field' ? 'design' : fdTab);
  renderCanvas(); renderInspector();
}

// ── Inspector → state ──────────────────────────────────────────────────
function applyValue(scope, key, value) {
  const f = field(fdSel);
  if (scope === 'f' && f) {
    if (key === 'options') value = String(value).split('\n');
    f[key] = value;
    if (key === 'label' && f._autoName && !isLayout(f)) {
      f.name = uniqueName(toName(value), f._key);
      const nameInput = $('#fdInspector [data-f="name"]'); if (nameInput) nameInput.value = f.name;
    }
    if (key === 'name') f._autoName = false;
    f._error = false;
  } else if (scope === 's' && f) {
    f.settings[key] = value;
  } else if (scope === 'd') {
    F.design[key] = value;
    if (key === 'font' || key === 'heading_font') loadFont();
    if (!['submit_label', 'footer_left', 'footer_right', 'title_size', 'title_align', 'max_width', 'spacing', 'button_style', 'button_align', 'show_logo',
      'logo_size', 'logo_align', 'logo_plate', 'header_padding', 'header_kicker', 'header_subtitle', 'subtitle_size'].includes(key)) F.design.theme = 'custom';
  } else if (scope === 'form') {
    F[key] = key === 'slug' ? String(value).toLowerCase().replace(/[^a-z0-9-]/g, '-') : value;
    if (key === 'title') renderStatus();
  }
  markDirty();
  renderCanvas();
}
const insp = $('#fdInspector');
insp.addEventListener('input', (e) => {
  const el = e.target;
  if (el.dataset.c !== undefined) {
    let v = el.value.trim();
    if (!v.startsWith('#')) v = '#' + v;
    if (!/^#[0-9a-fA-F]{6}$/.test(v)) return;
    F.design.colors[el.dataset.c] = v.toUpperCase();
    F.design.theme = 'custom';
    el.closest('.fd-color').querySelectorAll('input').forEach(i => { if (i !== el) i.value = i.type === 'color' ? v.toLowerCase() : v.toUpperCase(); });
    document.querySelectorAll('.fd-theme.is-on').forEach(t => t.classList.remove('is-on'));
    markDirty(); renderCanvas();
    return;
  }
  for (const scope of ['f', 's', 'd', 'form']) {
    if (el.dataset[scope] === undefined) continue;
    let v = el.value;
    if (el.type === 'range') {
      v = Number(v);
      el.nextElementSibling.textContent = v + (el.dataset.unit || '');
      // keep the matching width segment in sync with the slider
      el.closest('.fd-group')?.querySelectorAll(`[data-seg="${scope}"][data-key="${el.dataset[scope]}"]`).forEach(b => b.classList.toggle('is-on', b.dataset.value === String(v)));
    }
    applyValue(scope, el.dataset[scope], v);
    if (scope === 'f' && el.dataset.f === 'options') renderInspectorKeepFocus(el);
    return;
  }
});
// Options changing between "none" and "some" flips a checkbox between tick box and group - refresh the panel without losing the cursor.
function renderInspectorKeepFocus(el) {
  const f = field(fdSel); if (!f || f.type !== 'checkbox') return;
  const pos = el.selectionStart;
  renderInspector();
  const again = $('#fdInspector [data-f="options"]');
  if (again) { again.focus(); again.setSelectionRange(pos, pos); }
}
insp.addEventListener('click', (e) => {
  const segBtn = e.target.closest('[data-seg]');
  if (segBtn) {
    const { seg: scope, key, value } = segBtn.dataset;
    const num = /^\d+$/.test(value) ? Number(value) : value;
    applyValue(scope, key, num);
    renderInspector();
    return;
  }
  const swBtn = e.target.closest('[data-switch]') || e.target.closest('[data-switch-row]')?.querySelector('[data-switch]');
  if (swBtn && e.target.closest('[data-switch-row]')) {
    e.preventDefault();
    const on = swBtn.getAttribute('aria-checked') !== 'true';
    applyValue(swBtn.dataset.switch, swBtn.dataset.key, on);
    renderInspector();
    return;
  }
  const theme = e.target.closest('[data-theme]');
  if (theme) {
    const p = FD_PRESETS[theme.dataset.theme];
    const { name, colors, ...rest } = p;
    Object.assign(F.design, rest, { colors: { ...colors }, theme: theme.dataset.theme });
    loadFont(); markDirty(); renderCanvas(); renderInspector();
    return;
  }
  const act = e.target.closest('[data-act]');
  if (act) doAction(act.dataset.act, fdSel);
});
document.querySelectorAll('.fd-tab').forEach(t => t.addEventListener('click', () => { fdTab = t.dataset.tab; renderInspector(); }));

// ── Field actions ──────────────────────────────────────────────────────
function newField(type) {
  const d = FD_DEFAULTS[type] || {};
  const key = 'k' + (++fdSeq);
  const label = d.label || FD_TYPES[type];
  return {
    _key: key, _autoName: true, id: null, type, label,
    name: uniqueName(toName(type === 'section' || type === 'paragraph' || type === 'divider' ? type : label), key),
    placeholder: d.placeholder || '', help_text: '', is_required: !!d.is_required,
    options: d.options ? [...d.options] : [],
    settings: { ...FD_FIELD_DEFAULTS, ...(d.settings || {}) },
  };
}
function insertAt(f, index) {
  F.fields.splice(index, 0, f);
  markDirty(); select(f._key);
  requestAnimationFrame(() => document.querySelector(`.fd-item[data-key="${f._key}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
}
function addField(type) {
  const at = fdSel ? F.fields.findIndex(f => f._key === fdSel) + 1 : F.fields.length;
  insertAt(newField(type), at);
}
function doAction(act, key) {
  const i = F.fields.findIndex(f => f._key === key);
  if (i < 0) return;
  const f = F.fields[i];
  if (act === 'del') {
    if (f.id && !isLayout(f) && !confirm(`Delete "${f.label}"? Answers clients already gave to it stay in their responses.`)) return;
    F.fields.splice(i, 1);
    markDirty(); select(F.fields[i]?._key || F.fields[i - 1]?._key || null);
  } else if (act === 'dup') {
    const copy = JSON.parse(JSON.stringify(f));
    Object.assign(copy, { _key: 'k' + (++fdSeq), id: null, _autoName: false, _error: false });
    copy.name = uniqueName(f.name, copy._key);
    insertAt(copy, i + 1);
  } else if (act === 'up' || act === 'down') {
    const j = act === 'up' ? i - 1 : i + 1;
    if (j < 0 || j >= F.fields.length) return;
    [F.fields[i], F.fields[j]] = [F.fields[j], F.fields[i]];
    markDirty(); renderCanvas();
  }
}
document.querySelectorAll('[data-add]').forEach(b => b.addEventListener('click', () => addField(b.dataset.add)));

// ── Canvas interactions: select, drag to move, drag to resize ─────────
const canvas = $('#fdCanvas');
canvas.addEventListener('click', (e) => {
  if (fdPreviewing) { if (e.target.closest('.pf-submit')) fdPreviewSubmit(); return; }
  const act = e.target.closest('[data-act]');
  const item = e.target.closest('.fd-item');
  if (act && item) { doAction(act.dataset.act, item.dataset.key); return; }
  if (item) { if (item.dataset.key !== fdSel || fdTab !== 'field') select(item.dataset.key); return; }
  // Clicking the intro text opens Settings; the header, button, footer or background open Design.
  const zone = e.target.closest('[data-zone]')?.dataset.zone;
  fdSel = null;
  fdTab = zone === 'settings' ? 'settings' : 'design';
  renderCanvas(); renderInspector();
  if (zone === 'header') $('#fdInspector [data-group="header"]')?.scrollIntoView({ block: 'start', behavior: 'smooth' });
});

let fdDrag = null; // { key } for moving, { type } for a new block from the palette
document.querySelectorAll('.fd-pal-item').forEach(b => {
  b.addEventListener('dragstart', (e) => { fdDrag = { type: b.dataset.add }; e.dataTransfer.effectAllowed = 'copy'; e.dataTransfer.setData('text/plain', b.dataset.add); });
  b.addEventListener('dragend', clearDrop);
});
canvas.addEventListener('pointerdown', (e) => {
  const handle = e.target.closest('[data-drag]');
  if (handle) handle.closest('.fd-item').setAttribute('draggable', 'true');
});
canvas.addEventListener('dragstart', (e) => {
  const item = e.target.closest?.('.fd-item');
  if (!item) return;
  fdDrag = { key: item.dataset.key };
  item.classList.add('is-dragging');
  e.dataTransfer.effectAllowed = 'move';
  e.dataTransfer.setData('text/plain', item.dataset.key);
});
function dropTarget(e) {
  const grid = $('#fdGrid');
  const item = e.target.closest?.('.fd-item');
  if (item && item.dataset.key !== fdDrag?.key) {
    const r = item.getBoundingClientRect();
    const span = Number(field(item.dataset.key).settings.width);
    const vertical = span >= 12 || fdDevice === 'mobile';
    const before = vertical ? e.clientY < r.top + r.height / 2 : e.clientX < r.left + r.width / 2;
    return { item, before, cls: vertical ? (before ? 'drop-t' : 'drop-b') : (before ? 'drop-l' : 'drop-r') };
  }
  if (!item && grid && (e.target === grid || grid.contains(e.target) || e.target.closest('.pf-card'))) return { end: true };
  return null;
}
function clearDrop() {
  document.querySelectorAll('.drop-l, .drop-r, .drop-t, .drop-b').forEach(el => el.classList.remove('drop-l', 'drop-r', 'drop-t', 'drop-b'));
  $('#fdGrid')?.classList.remove('drop-end');
}
canvas.addEventListener('dragover', (e) => {
  if (!fdDrag || fdPreviewing) return;
  const t = dropTarget(e);
  clearDrop();
  if (!t) return;
  e.preventDefault();
  e.dataTransfer.dropEffect = fdDrag.type ? 'copy' : 'move';
  if (t.end) $('#fdGrid').classList.add('drop-end'); else t.item.classList.add(t.cls);
});
canvas.addEventListener('drop', (e) => {
  if (!fdDrag) return;
  const t = dropTarget(e);
  clearDrop();
  if (!t) return;
  e.preventDefault();
  let moving = null;
  if (fdDrag.key) {
    const from = F.fields.findIndex(f => f._key === fdDrag.key);
    [moving] = F.fields.splice(from, 1);
  }
  let index = t.end ? F.fields.length : F.fields.findIndex(f => f._key === t.item.dataset.key) + (t.before ? 0 : 1);
  if (moving) { F.fields.splice(index, 0, moving); markDirty(); select(moving._key); }
  else insertAt(newField(fdDrag.type), index);
  fdDrag = null;
});
canvas.addEventListener('dragend', () => { fdDrag = null; clearDrop(); renderCanvas(); });

// Resize: drag the right edge for width (snaps to the 12-column grid), the bottom edge for textarea height.
canvas.addEventListener('pointerdown', (e) => {
  const handle = e.target.closest('[data-resize]');
  if (!handle || fdPreviewing) return;
  e.preventDefault(); e.stopPropagation();
  if (handle.dataset.resize === 'logo') { resizeLogo(e, handle); return; }
  const item = handle.closest('.fd-item');
  const f = field(item.dataset.key);
  const grid = $('#fdGrid');
  const gr = grid.getBoundingClientRect();
  const colGap = parseFloat(getComputedStyle(grid).columnGap) || 20;
  const colW = (gr.width - colGap * 11) / 12;
  const startLeft = item.getBoundingClientRect().left;
  const startY = e.clientY, startRows = f.settings.rows;
  const tip = $('#fdSizeTip');
  const axis = handle.dataset.resize;
  handle.setPointerCapture(e.pointerId);
  const move = (ev) => {
    if (axis === 'x') {
      const span = Math.max(1, Math.min(12, Math.round((ev.clientX - startLeft + colGap) / (colW + colGap))));
      if (span !== f.settings.width) {
        f.settings.width = span;
        item.style.setProperty('--span', span);
        item.querySelector('.fd-badge').textContent = `${FD_TYPES[f.type]} · ${span}/12`;
      }
      tip.textContent = `${span} / 12 columns`;
    } else {
      const rows = Math.max(1, Math.min(20, startRows + Math.round((ev.clientY - startY) / 22)));
      f.settings.rows = rows;
      item.querySelector('textarea').rows = rows;
      tip.textContent = `${rows} rows`;
    }
    tip.style.display = 'block'; tip.style.left = (ev.clientX + 14) + 'px'; tip.style.top = (ev.clientY + 14) + 'px';
  };
  const up = () => {
    handle.removeEventListener('pointermove', move);
    handle.removeEventListener('pointerup', up);
    tip.style.display = 'none';
    markDirty(); renderCanvas(); if (fdTab === 'field') renderInspector();
  };
  handle.addEventListener('pointermove', move);
  handle.addEventListener('pointerup', up);
});

// Logo corner handle: drag down/right to grow, up/left to shrink.
function resizeLogo(e, handle) {
  const page = $('#fdCanvas .pf-page');
  const tip = $('#fdSizeTip');
  const start = F.design.logo_size, x0 = e.clientX, y0 = e.clientY;
  let size = start;
  handle.setPointerCapture(e.pointerId);
  const move = (ev) => {
    size = Math.max(24, Math.min(160, Math.round(start + ((ev.clientX - x0) + (ev.clientY - y0)) / 2)));
    page.style.setProperty('--pf-logo-size', size + 'px');
    tip.textContent = `Logo ${size}px`;
    tip.style.display = 'block'; tip.style.left = (ev.clientX + 14) + 'px'; tip.style.top = (ev.clientY + 14) + 'px';
  };
  const up = () => {
    handle.removeEventListener('pointermove', move);
    handle.removeEventListener('pointerup', up);
    tip.style.display = 'none';
    if (size !== start) { F.design.logo_size = size; markDirty(); }
    fdSel = null; fdTab = 'design';
    renderCanvas(); renderInspector();
    $('#fdInspector [data-group="header"]')?.scrollIntoView({ block: 'start' });
  };
  handle.addEventListener('pointermove', move);
  handle.addEventListener('pointerup', up);
}

// ── Toolbar: device + preview ──────────────────────────────────────────
document.querySelectorAll('[data-device]').forEach(b => b.addEventListener('click', () => {
  fdDevice = b.dataset.device;
  document.querySelectorAll('[data-device]').forEach(x => x.classList.toggle('is-on', x === b));
  renderCanvas();
}));
// Preview hides the panels and shows the form full width, exactly as clients
// see it - fields can be filled in and Submit checks required answers.
function setPreview(on) {
  fdPreviewing = on;
  document.querySelectorAll('[data-mode]').forEach(x => x.classList.toggle('is-on', (x.dataset.mode === 'preview') === on));
  $('.fd-layout').classList.toggle('is-previewing', on);
  $('#fdPreviewBar').hidden = !on;
  if (on) fdSel = null;
  renderCanvas(); renderInspector();
  $('#fdCanvasWrap').scrollTop = 0;
}
document.querySelectorAll('[data-mode]').forEach(b => b.addEventListener('click', () => setPreview(b.dataset.mode === 'preview')));

function fdPreviewSubmit() {
  let missing = 0, first = null;
  canvas.querySelectorAll('.fd-item[data-key]').forEach(item => {
    const f = field(item.dataset.key);
    if (!f || isLayout(f)) return;
    const inputs = [...item.querySelectorAll('input, textarea, select')];
    const answered = inputs.some(i => (i.type === 'checkbox' || i.type === 'radio') ? i.checked : i.type === 'file' ? i.files.length > 0 : i.value.trim() !== '');
    const bad = f.is_required && !answered;
    item.classList.toggle('has-error', bad);
    const err = item.querySelector('.pf-error');
    if (err) err.textContent = bad ? 'This field is required.' : '';
    if (bad) { missing++; first ??= item; }
  });
  if (missing) {
    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    cfToast(`${missing} required ${missing === 1 ? 'question needs' : 'questions need'} an answer - clients will see the same message.`, { type: 'error' });
  } else {
    cfToast('Looks good! On the live form this would be sent. Nothing is saved from the preview.', { type: 'info' });
  }
}

// ── Keyboard shortcuts ─────────────────────────────────────────────────
document.addEventListener('keydown', (e) => {
  const typing = e.target.closest('input, textarea, select, [contenteditable]');
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); fdSave(false); return; }
  if (fdPreviewing && e.key === 'Escape' && !document.querySelector('.cf-dialog-overlay.open')) { setPreview(false); return; }
  if (typing || !fdSel || fdPreviewing) return;
  if (e.key === 'Delete' || e.key === 'Backspace') { e.preventDefault(); doAction('del', fdSel); }
  else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'd') { e.preventDefault(); doAction('dup', fdSel); }
  else if (e.key === 'Escape') select(null);
  else if (e.key === 'ArrowUp' && e.altKey) { e.preventDefault(); doAction('up', fdSel); }
  else if (e.key === 'ArrowDown' && e.altKey) { e.preventDefault(); doAction('down', fdSel); }
});

// ── Save / publish ─────────────────────────────────────────────────────
async function fdSave(publish) {
  const btn = $(publish ? '#fdPublishBtn' : '#fdSaveBtn');
  if (btn.disabled) return;
  btn.disabled = true;
  clearTimeout(fdAutoTimer);
  const errBox = $('#fdErrors');
  errBox.style.display = 'none';
  const version = fdVersion;
  try {
    const res = await fetch(FD_UPDATE_URL, { method: 'PUT', headers: fdHeaders(), body: JSON.stringify(fdBody(publish)) });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const errors = data.errors || { message: [data.message || 'Something went wrong. Please try again.'] };
      const bad = new Set();
      Object.keys(errors).forEach(k => { const m = k.match(/^fields\.(\d+)\./); if (m) bad.add(+m[1]); });
      F.fields.forEach((f, i) => { f._error = bad.has(i); });
      if (bad.size) { fdSel = F.fields[[...bad][0]]._key; fdTab = 'field'; }
      else if (errors.slug || errors.title) fdTab = 'settings';
      renderCanvas(); renderInspector();
      errBox.querySelector('ul').innerHTML = [...new Set(Object.values(errors).flat())].map(m => `<li>${esc(m)}</li>`).join('');
      errBox.style.display = 'flex';
      window.scrollTo({ top: 0, behavior: 'smooth' });
      // Keep the work as a draft snapshot while the issues are fixed.
      if (fdDirty || fdHasSnapshot) { fdDirty = true; fdAutoTimer = setTimeout(fdAutosave, 300); }
      return;
    }
    const keys = F.fields.map(f => f._key);
    if (version === fdVersion) {
      F = data.form;
      F.fields.forEach((f, i) => { f._key = keys[i] || ('k' + (++fdSeq)); });
      fdDirty = false;
      setSaved('saved', fdTime(Date.now()));
    } else {
      // Edited while saving: keep those edits, just learn the new ids; autosave picks up the rest.
      data.form.fields.forEach((saved, i) => { const f = field(keys[i]); if (f && !f.id) f.id = saved.id; });
      F.status = data.form.status; F.public_url = data.form.public_url; F.embed_code = data.form.embed_code;
      fdAutoTimer = setTimeout(fdAutosave, 300);
    }
    fdHasSnapshot = false; hideRestored();
    renderStatus(); renderCanvas(); renderInspector();
    cfToast(publish ? 'Published! Your form is live - find the link and embed code under Settings.' : data.message,
      publish && F.public_url ? { action: { label: 'Open form', href: F.public_url }, duration: 7000 } : {});
  } finally {
    btn.disabled = false;
  }
}

// Reopening the designer brings back autosaved work that isn't on the form yet.
if (FD_RESTORE) {
  const r = FD_RESTORE.data;
  const live = F.status === 'published';
  Object.assign(F, {
    title: r.title, slug: live ? F.slug : r.slug, description: r.description, success_message: r.success_message,
    auto_create_lead: r.auto_create_lead, design: r.design, fields: r.fields,
  });
  F.fields.forEach(f => { f._key = 'k' + (++fdSeq); });
  loadFont();
  fdHasSnapshot = true;
  const when = new Date(FD_RESTORE.saved_at).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
  const n = $('#fdRestored');
  n.querySelector('span').innerHTML = `Restored your unsaved changes from <strong>${esc(when)}</strong>${FD_RESTORE.by ? ` (by ${esc(FD_RESTORE.by)})` : ''}.`
    + (live ? ' They are <strong>not live yet</strong> - press <strong>Save changes</strong> to publish them.' : '');
  n.style.display = 'flex';
  if (live) {
    setSaved('snapshot', fdTime(FD_RESTORE.saved_at));
  } else {
    // A draft is saved for real straight away (or kept as a snapshot if it still can't be).
    fdDirty = true; fdVersion++;
    fdAutoTimer = setTimeout(fdAutosave, 400);
  }
}

renderStatus();
renderCanvas();
renderInspector();
</script>
@endsection
