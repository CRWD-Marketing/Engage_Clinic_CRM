@extends('layouts.admin-sidebar')

@section('title', 'Contact Us · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width pt-tight-padding')

@section('content')
<style>
/* ── shadcn-style token system, namespaced --ct-* so it can't collide with
   the admin layout's own variables. Same scale as the Job Applications page
   (--jb-*), so the two inbox screens read as one system ──────────────────── */
:root {
  --ct-background: #ffffff;
  --ct-foreground: #09090b;
  --ct-card: #ffffff;
  --ct-muted: #f4f4f5;
  --ct-muted-foreground: #71717a;
  --ct-subtle-foreground: #a1a1aa;
  --ct-border: #e4e4e7;
  --ct-input: #e4e4e7;
  --ct-accent: #f4f4f5;
  --ct-primary: #C8355F;
  --ct-primary-hover: #A82348;
  --ct-primary-foreground: #ffffff;
  --ct-destructive: #dc2626;
  --ct-destructive-subtle: #fef2f2;
  --ct-success: #15803d;
  --ct-success-subtle: #f0fdf4;
  --ct-success-border: #bbf7d0;
  --ct-ring: rgba(200, 53, 95, .35);
  --ct-radius: 8px;
  --ct-radius-sm: 6px;
  --ct-font: 'Nunito Sans', ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --ct-font-display: 'Baloo 2', 'Nunito Sans', ui-sans-serif, system-ui, sans-serif;
  --ct-shadow-sm: 0 1px 2px 0 rgba(9, 9, 11, .05);
  --ct-shadow-lg: 0 10px 15px -3px rgba(9, 9, 11, .1), 0 4px 6px -4px rgba(9, 9, 11, .1);
  --ct-shadow-xl: 0 20px 25px -5px rgba(9, 9, 11, .12), 0 8px 10px -6px rgba(9, 9, 11, .1);
}

.main-content-inner.pt-tight-padding { padding-left: 24px; padding-right: 24px; }

.ct-root, .ct-dialog-overlay {
  font-family: var(--ct-font);
  -webkit-font-smoothing: antialiased;
  color: var(--ct-foreground);
}
.ct-root *, .ct-dialog-overlay * { box-sizing: border-box; }

/* ── Page header ─────────────────────────────────────────────────────── */
.ct-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  gap: 16px; flex-wrap: wrap; margin-bottom: 20px;
}
.ct-heading { font-family: var(--ct-font-display); font-size: 25px; font-weight: 600; line-height: 1.2; }
.ct-subheading { margin-top: 5px; font-size: 13.5px; color: var(--ct-muted-foreground); }
.ct-header-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* ── Buttons ─────────────────────────────────────────────────────────── */
.ct-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  height: 36px; padding: 0 14px; border-radius: var(--ct-radius-sm);
  font-family: var(--ct-font); font-size: 13.5px; font-weight: 600; line-height: 1;
  border: 1px solid transparent; background: none; cursor: pointer;
  text-decoration: none; white-space: nowrap;
  transition: background-color .15s, border-color .15s, color .15s;
}
.ct-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--ct-ring); }
.ct-btn svg { flex-shrink: 0; }

.ct-btn-primary { background: var(--ct-primary); color: var(--ct-primary-foreground); box-shadow: var(--ct-shadow-sm); }
.ct-btn-primary:hover { background: var(--ct-primary-hover); }

.ct-btn-outline { background: var(--ct-background); color: var(--ct-foreground); border-color: var(--ct-input); box-shadow: var(--ct-shadow-sm); }
.ct-btn-outline:hover { background: var(--ct-accent); }

.ct-btn-secondary { background: #18181b; color: #fff; }
.ct-btn-secondary:hover { background: #27272a; }

.ct-btn-ghost { background: transparent; color: var(--ct-foreground); }
.ct-btn-ghost:hover { background: var(--ct-accent); }

.ct-btn-destructive-ghost { background: transparent; color: var(--ct-destructive); }
.ct-btn-destructive-ghost:hover { background: var(--ct-destructive-subtle); }

/* Approve / Reject read as outline buttons until they carry the decision,
   at which point the chosen one fills in - the decision stays swappable, so
   the filled state is a selection, not a dead end. */
.ct-btn-approve { background: var(--ct-background); color: var(--ct-success); border-color: var(--ct-success-border); box-shadow: var(--ct-shadow-sm); }
.ct-btn-approve:hover { background: var(--ct-success-subtle); }
.ct-btn-approve.is-active { background: #16a34a; color: #fff; border-color: #16a34a; }
.ct-btn-approve.is-active:hover { background: #15803d; }

.ct-btn-reject { background: var(--ct-background); color: #b91c1c; border-color: #fecaca; box-shadow: var(--ct-shadow-sm); }
.ct-btn-reject:hover { background: var(--ct-destructive-subtle); }
.ct-btn-reject.is-active { background: #dc2626; color: #fff; border-color: #dc2626; }
.ct-btn-reject.is-active:hover { background: #b91c1c; }

.ct-btn-sm { height: 32px; padding: 0 11px; font-size: 12.5px; }
.ct-btn[disabled], .ct-btn.is-disabled { opacity: .5; pointer-events: none; cursor: not-allowed; }

/* ── Status filter dropdown (replaces the old chip row) ──────────────── */
.ct-dropdown { position: relative; }
.ct-dropdown-trigger { min-width: 210px; justify-content: space-between; padding-right: 11px; }
.ct-dropdown-trigger .ct-trigger-body { display: inline-flex; align-items: center; gap: 8px; min-width: 0; }
.ct-chevron { transition: transform .18s ease; color: var(--ct-subtle-foreground); }
.ct-dropdown.is-open .ct-chevron { transform: rotate(180deg); }

.ct-dropdown-menu {
  display: none; position: absolute; right: 0; top: calc(100% + 6px); z-index: 60;
  min-width: 250px; padding: 4px;
  background: var(--ct-card); border: 1px solid var(--ct-border);
  border-radius: var(--ct-radius); box-shadow: var(--ct-shadow-lg);
  animation: ctPop .14s ease-out;
}
.ct-dropdown.is-open .ct-dropdown-menu { display: block; }
@keyframes ctPop {
  from { opacity: 0; transform: translateY(-4px) scale(.97); }
  to { opacity: 1; transform: none; }
}

.ct-dropdown-label {
  padding: 7px 9px 5px; font-size: 11.5px; font-weight: 700;
  color: var(--ct-muted-foreground); letter-spacing: .02em;
}
.ct-dropdown-separator { height: 1px; margin: 4px -4px; background: var(--ct-border); }

.ct-dropdown-item {
  display: flex; align-items: center; gap: 9px;
  padding: 8px 9px; border-radius: var(--ct-radius-sm);
  font-size: 13.5px; font-weight: 600; color: var(--ct-foreground);
  text-decoration: none; cursor: pointer;
  transition: background-color .12s;
}
.ct-dropdown-item:hover, .ct-dropdown-item.is-active { background: var(--ct-accent); }
.ct-dropdown-item .ct-check { width: 14px; flex-shrink: 0; color: var(--ct-primary); opacity: 0; }
.ct-dropdown-item.is-active .ct-check { opacity: 1; }
.ct-dropdown-item .ct-item-label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.ct-dot { width: 7px; height: 7px; border-radius: 999px; flex-shrink: 0; }
.ct-count {
  min-width: 22px; padding: 2px 7px; border-radius: 999px;
  background: var(--ct-muted); color: var(--ct-muted-foreground);
  font-size: 11.5px; font-weight: 700; text-align: center; flex-shrink: 0;
}
.ct-dropdown-item.is-active .ct-count { background: var(--ct-background); border: 1px solid var(--ct-border); }

/* ── Alert ───────────────────────────────────────────────────────────── */
.ct-alert {
  display: flex; align-items: flex-start; gap: 10px;
  margin-bottom: 18px; padding: 12px 14px;
  border: 1px solid var(--ct-success-border); border-radius: var(--ct-radius);
  background: var(--ct-success-subtle); color: var(--ct-success);
  font-size: 13.5px; font-weight: 600;
}
.ct-alert svg { flex-shrink: 0; margin-top: 1px; }

/* ── Card + table ────────────────────────────────────────────────────── */
.ct-card {
  border: 1px solid var(--ct-border); border-radius: var(--ct-radius);
  background: var(--ct-card); box-shadow: var(--ct-shadow-sm);
  overflow: hidden; overflow-x: auto;
}
.ct-table { width: 100%; min-width: 960px; border-collapse: collapse; }
.ct-table th {
  padding: 11px 16px; border-bottom: 1px solid var(--ct-border);
  text-align: left; white-space: nowrap;
  font-size: 12.5px; font-weight: 600; color: var(--ct-muted-foreground);
}
.ct-table td {
  padding: 13px 16px; border-bottom: 1px solid var(--ct-border);
  vertical-align: middle; font-size: 13.5px; color: var(--ct-foreground);
}
.ct-table tbody tr:last-child td { border-bottom: 0; }
.ct-row { cursor: pointer; transition: background-color .12s; }
.ct-row:hover td { background: #fafafa; }

.ct-person { display: flex; align-items: center; gap: 11px; }
.ct-avatar {
  width: 34px; height: 34px; flex-shrink: 0; border-radius: 999px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-family: var(--ct-font-display); font-size: 13px; font-weight: 600;
}
.ct-name { font-weight: 700; }
.ct-secondary { margin-top: 2px; font-size: 12.5px; color: var(--ct-muted-foreground); }

.ct-badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 3px 9px; border-radius: 999px; border: 1px solid transparent;
  font-size: 12px; font-weight: 600; white-space: nowrap;
}

/* ── Empty state ─────────────────────────────────────────────────────── */
.ct-empty { padding: 64px 20px; text-align: center; }
.ct-empty-icon {
  width: 46px; height: 46px; margin: 0 auto 16px; border-radius: var(--ct-radius);
  border: 1px solid var(--ct-border); background: var(--ct-muted);
  color: var(--ct-muted-foreground);
  display: flex; align-items: center; justify-content: center;
}
.ct-empty-title { font-size: 15px; font-weight: 700; margin-bottom: 5px; }
.ct-empty-sub { max-width: 380px; margin: 0 auto; font-size: 13.5px; color: var(--ct-muted-foreground); line-height: 1.55; }
.ct-empty .ct-btn { margin-top: 18px; }

/* ── Dialog ──────────────────────────────────────────────────────────── */
.ct-dialog-overlay {
  display: none; position: fixed; inset: 0; z-index: 9999;
  align-items: center; justify-content: center; padding: 18px;
  background: rgba(9, 9, 11, .55); backdrop-filter: blur(2px);
}
.ct-dialog-overlay.open { display: flex; }
.ct-dialog {
  width: 840px; max-width: 100%; max-height: 88vh; overflow-y: auto;
  background: var(--ct-background); border: 1px solid var(--ct-border);
  border-radius: 12px; box-shadow: var(--ct-shadow-xl);
  padding: 24px; animation: ctDialogIn .16s ease-out;
}
@keyframes ctDialogIn {
  from { opacity: 0; transform: translateY(6px) scale(.985); }
  to { opacity: 1; transform: none; }
}
.ct-dialog-head { display: flex; align-items: flex-start; gap: 13px; flex-wrap: wrap; }
.ct-dialog-title { flex: 1; min-width: 170px; }
.ct-dialog-title h2 { font-family: var(--ct-font-display); font-size: 20px; font-weight: 600; margin: 0; }
.ct-dialog-desc { margin-top: 3px; font-size: 13px; color: var(--ct-muted-foreground); }
.ct-dialog-contact { text-align: right; font-size: 13px; font-weight: 700; }
.ct-dialog-contact-sub { text-align: right; margin-top: 2px; font-size: 12.5px; color: var(--ct-muted-foreground); }
.ct-dialog-close {
  width: 30px; height: 30px; flex-shrink: 0; border-radius: var(--ct-radius-sm);
  border: 1px solid var(--ct-input); background: var(--ct-background);
  color: var(--ct-muted-foreground); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background-color .12s, color .12s;
}
.ct-dialog-close:hover { background: var(--ct-accent); color: var(--ct-foreground); }

.ct-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 18px 0; }
.ct-grid { display: grid; grid-template-columns: 1.4fr .85fr; gap: 16px; }
.ct-col { display: flex; flex-direction: column; gap: 16px; }

.ct-panel {
  display: flex; flex-direction: column; gap: 12px;
  border: 1px solid var(--ct-border); border-radius: var(--ct-radius);
  background: var(--ct-card); padding: 16px 18px; box-shadow: var(--ct-shadow-sm);
}
.ct-panel-title { font-family: var(--ct-font-display); font-size: 16px; font-weight: 600; }
.ct-prose { font-size: 13.5px; line-height: 1.65; white-space: pre-wrap; word-break: break-word; }
.ct-panel-empty { font-size: 13px; color: var(--ct-muted-foreground); line-height: 1.55; }

.ct-field-row {
  display: flex; align-items: baseline; justify-content: space-between; gap: 12px;
  padding: 9px 0; border-bottom: 1px solid var(--ct-border);
}
.ct-field-row:last-child { border-bottom: 0; padding-bottom: 0; }
.ct-field-label { font-size: 12.5px; color: var(--ct-muted-foreground); flex-shrink: 0; }
.ct-field-value { font-size: 13.5px; font-weight: 700; text-align: right; word-break: break-word; }

/* Requested consultation slot - the one piece of a contact that isn't plain
   text, so it keeps a surface of its own rather than becoming another row. */
.ct-slot {
  display: flex; align-items: center; gap: 12px;
  border: 1px solid var(--ct-border); border-radius: var(--ct-radius-sm);
  background: #fafafa; padding: 11px 13px;
}
.ct-slot-icon {
  display: flex; align-items: center; justify-content: center;
  width: 32px; height: 32px; flex-shrink: 0; border-radius: var(--ct-radius-sm);
  border: 1px solid var(--ct-border); background: var(--ct-background); color: var(--ct-primary);
}
.ct-slot-date { font-size: 13.5px; font-weight: 700; }
.ct-slot-sub { margin-top: 1px; font-size: 12.5px; color: var(--ct-muted-foreground); }
.ct-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }

/* Slot editor: collapsed date field that drops down a month calendar, plus
   the consultation times - same days/times as the website booking. */
.ct-slot-editor { display: flex; flex-direction: column; gap: 12px; padding-top: 4px; }
.ct-slot-editor[hidden] { display: none; }
.ct-slot-editor .ct-error { margin: 0; }
.ct-slot-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.ct-slot-fields .ct-label { display: block; margin-bottom: 5px; }
.ct-slot-fields select.ct-input { height: 38px; padding-top: 0; padding-bottom: 0; }
.ct-slot-fields select.ct-input:disabled { opacity: .55; cursor: not-allowed; }
.ct-date-field { position: relative; }
.ct-date-btn { display: flex; align-items: center; justify-content: space-between; gap: 8px; height: 38px; text-align: left; cursor: pointer; }
.ct-date-btn svg { color: var(--ct-muted-foreground); flex-shrink: 0; }
.ct-date-btn.is-empty span { color: var(--ct-muted-foreground); }
.ct-date-btn[aria-expanded=true] { border-color: var(--ct-primary); box-shadow: 0 0 0 3px var(--ct-ring); }
.ct-cal { position: absolute; z-index: 30; top: calc(100% + 6px); left: 0; width: 280px; padding: 12px; background: var(--ct-background); border: 1px solid var(--ct-border); border-radius: var(--ct-radius); box-shadow: 0 16px 36px -12px rgba(9, 9, 11, .25); }
.ct-cal[hidden] { display: none; }
.ct-cal-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; font-size: 13.5px; font-weight: 700; }
.ct-cal-arrow { width: 28px; height: 28px; border-radius: var(--ct-radius-sm); border: 1px solid var(--ct-border); background: var(--ct-background); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: var(--ct-foreground); }
.ct-cal-arrow:hover { background: var(--ct-accent); }
.ct-cal-arrow:disabled { opacity: .35; pointer-events: none; }
.ct-cal-weekdays, .ct-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
.ct-cal-weekdays span { font-size: 11px; font-weight: 600; color: var(--ct-muted-foreground); padding: 4px 0; }
.ct-cal-day { height: 32px; border: 1px solid transparent; border-radius: var(--ct-radius-sm); background: none; font: 600 12.5px var(--ct-font); color: var(--ct-foreground); cursor: pointer; }
.ct-cal-day:hover:not(:disabled):not(.is-selected) { background: var(--ct-accent); }
.ct-cal-day.is-today { border-color: var(--ct-primary); color: var(--ct-primary); }
.ct-cal-day.is-selected { background: var(--ct-primary); border-color: var(--ct-primary); color: #fff; }
.ct-cal-day:disabled { color: #d4d4d8; text-decoration: line-through; cursor: default; }
.ct-cal-legend { display: flex; gap: 12px; margin-top: 8px; font-size: 11.5px; color: var(--ct-muted-foreground); }
.ct-cal-legend i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 5px; vertical-align: -1px; }
.ct-cal-legend i.is-today { border: 1.5px solid var(--ct-primary); }
.ct-cal-legend i.is-closed { background: #e4e4e7; }
.ct-slot-actions { display: flex; align-items: center; gap: 8px; }
@media (max-width: 560px) { .ct-slot-fields { grid-template-columns: 1fr; } .ct-cal { width: 100%; } }

.ct-actions { display: flex; flex-direction: column; gap: 8px; }
.ct-actions-row { display: flex; gap: 8px; }
.ct-actions-row form { flex: 1; display: flex; }
.ct-actions-row .ct-btn { flex: 1; }
.ct-footnote { font-size: 12.5px; color: var(--ct-muted-foreground); line-height: 1.5; }
.ct-emailed { display: flex; align-items: center; gap: 7px; font-size: 12.5px; color: var(--ct-muted-foreground); }
.ct-link {
  border: 0; background: none; padding: 0; cursor: pointer;
  font-family: var(--ct-font); font-size: 12.5px; font-weight: 600;
  color: var(--ct-muted-foreground); text-decoration: underline; text-align: left;
}
.ct-link:hover { color: var(--ct-foreground); }
.ct-link.danger { color: var(--ct-destructive); }
.ct-link.danger:hover { color: #991b1b; }
.ct-links { display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }

/* ── Inputs (email composer) ─────────────────────────────────────────── */
.ct-input, .ct-textarea {
  width: 100%; box-sizing: border-box; padding: 8px 11px;
  border: 1px solid var(--ct-input); border-radius: var(--ct-radius-sm);
  background: var(--ct-background); color: var(--ct-foreground);
  font-family: var(--ct-font); font-size: 13.5px; line-height: 1.5;
  transition: border-color .12s, box-shadow .12s;
}
.ct-input:focus, .ct-textarea:focus {
  outline: none; border-color: var(--ct-primary); box-shadow: 0 0 0 3px var(--ct-ring);
}
.ct-textarea { resize: vertical; min-height: 72px; }
.ct-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
.ct-label { font-size: 12.5px; font-weight: 600; color: var(--ct-muted-foreground); }
.ct-error { display: none; margin-bottom: 10px; font-size: 13px; font-weight: 600; color: var(--ct-destructive); }

.ct-email-overlay { z-index: 10050; }
.ct-email-dialog { width: 560px; }

/* ── Phone: grouped, collapsible list ────────────────────────────────────
   The table needs 960px before it stops wrapping on itself, so on a phone it
   is replaced by the same shape the leads pipeline uses: one group per status,
   all shut to begin with, one open at a time. Each card carries the same
   .ct-row[data-contact] hook as a table row, so tapping one opens the very
   same detail dialog. */
.ct-groups { display: none; flex-direction: column; gap: 10px; }
.ct-group { border: 1px solid var(--ct-border); border-radius: var(--ct-radius); background: var(--ct-card); overflow: hidden; }
.ct-group-head {
  display: flex; align-items: center; gap: 9px; padding: 13px 14px; cursor: pointer;
  user-select: none; -webkit-tap-highlight-color: transparent;
}
.ct-group-title { flex: 1; font-size: 14px; font-weight: 700; color: var(--ct-foreground); }
.ct-group-count {
  min-width: 24px; padding: 2px 8px; border-radius: 999px;
  background: var(--ct-muted); color: var(--ct-muted-foreground);
  font-size: 12px; font-weight: 700; text-align: center;
}
.ct-group-caret {
  display: flex; color: var(--ct-muted-foreground);
  transition: transform .22s cubic-bezier(.4, 0, .2, 1);
}
.ct-group.is-open .ct-group-caret { transform: rotate(90deg); }

/* Height rather than display, so opening can be animated. The open height is
   written onto the element by the script, which is the only thing that knows
   how tall the cards inside actually are. */
.ct-group-body {
  display: flex; flex-direction: column; gap: 0;
  max-height: 0; opacity: 0; overflow: hidden;
  transition: max-height .3s cubic-bezier(.4, 0, .2, 1), opacity .22s ease;
}
.ct-group.is-open .ct-group-body { opacity: 1; overflow-y: auto; }

.ct-mcard {
  display: flex; flex-direction: column; gap: 7px;
  padding: 13px 14px; border-top: 1px solid var(--ct-border);
}
.ct-mcard-head { display: flex; align-items: center; gap: 10px; }
.ct-mcard-ident { min-width: 0; }
.ct-mcard-ident .ct-name { font-size: 14px; }
.ct-mcard-line { font-size: 13px; color: var(--ct-foreground); }
.ct-mcard-line.ct-secondary { margin-top: 0; }
.ct-mcard-slot {
  align-self: flex-start; padding: 3px 9px; border-radius: 999px;
  background: var(--ct-muted); font-size: 12px; font-weight: 600; color: var(--ct-muted-foreground);
}

@media (prefers-reduced-motion: reduce) {
  .ct-group-body, .ct-group-caret { transition: none; }
}

@media (max-width: 860px) {
  .ct-grid { grid-template-columns: 1fr; }
  .ct-dialog { padding: 20px 16px; }
  .ct-dialog-contact, .ct-dialog-contact-sub { text-align: left; }
  .ct-card-table { display: none; }
  .ct-groups { display: flex; }
}
@media (max-width: 520px) {
  .ct-dropdown-trigger { min-width: 0; width: 100%; }
  .ct-dropdown { flex: 1; }
  .ct-dropdown-menu { left: 0; right: auto; width: 100%; }
}
</style>

@php
    $avatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $avatarColor = fn ($seed) => $avatarPalette[crc32((string) $seed) % count($avatarPalette)];

    // shadcn-ish subtle badge tokens: tinted surface, hairline border, saturated
    // text - same scale the Job Applications inbox uses.
    $statusTokens = [
        'new'       => ['dot' => '#2563eb', 'bg' => '#eff6ff', 'fg' => '#1d4ed8', 'border' => '#bfdbfe'],
        'approved'  => ['dot' => '#16a34a', 'bg' => '#f0fdf4', 'fg' => '#15803d', 'border' => '#bbf7d0'],
        'rejected'  => ['dot' => '#dc2626', 'bg' => '#fef2f2', 'fg' => '#b91c1c', 'border' => '#fecaca'],
        'contacted' => ['dot' => '#ca8a04', 'bg' => '#fefce8', 'fg' => '#a16207', 'border' => '#fef08a'],
        'converted' => ['dot' => '#0d9488', 'bg' => '#f0fdfa', 'fg' => '#0f766e', 'border' => '#99f6e4'],
        'closed'    => ['dot' => '#71717a', 'bg' => '#fafafa', 'fg' => '#52525b', 'border' => '#e4e4e7'],
    ];
    $tokensFor = fn ($status) => $statusTokens[$status] ?? $statusTokens['closed'];

    // Normalise the filter once: an unknown ?status= falls back to "all" rather
    // than rendering a dropdown trigger with a blank label.
    $currentStatus = request('status');
    if (! $currentStatus || ! array_key_exists($currentStatus, $statuses)) {
        $currentStatus = 'all';
    }
    $isAllStatus = $currentStatus === 'all';
    $currentLabel = $isAllStatus ? 'All submissions' : $statuses[$currentStatus];
    $currentCount = $isAllStatus ? $totalCount : ($statusCounts[$currentStatus] ?? 0);

    $contactPayload = fn ($contact) => [
        'id' => $contact->id,
        'name' => $contact->name,
        'child_name' => $contact->child_name,
        'phone' => $contact->phone,
        'email' => $contact->email,
        'child_age' => $contact->child_age,
        'interested_in' => $contact->interested_in,
        'insurance' => $contact->insurance,
        'message' => $contact->message,
        'status' => $contact->status,
        'status_label' => $contact->status_label,
        'booking_date' => optional($contact->booking_date)->format('M j, Y'),
        'booking_date_iso' => optional($contact->booking_date)->format('Y-m-d'),
        'booking_time' => $contact->booking_time,
        'can_edit_slot' => $contact->isSlotEditable(),
        'booking_decision' => $contact->booking_decision,
        'status_email_sent_at' => optional($contact->status_email_sent_at)->format('M j, Y · H:i'),
        'can_send_email' => $contact->canSendStatusEmail(),
        'can_convert' => $contact->canConvertToLead(),
        'received' => $contact->created_at->format('M j, Y · H:i'),
        'received_human' => $contact->created_at->diffForHumans(),
        'initials' => strtoupper(mb_substr($contact->name ?: '?', 0, 1)),
        'avatar' => $avatarColor($contact->id),
    ];
@endphp

<div class="ct-root">
  <div class="ct-header">
    <div>
      <div class="ct-heading">Contacts</div>
      <div class="ct-subheading">{{ $contacts->count() }} {{ Str::plural('submission', $contacts->count()) }} shown &middot; {{ $newCount }} new</div>
    </div>

    <div class="ct-header-actions">
      <!-- Status filter: one dropdown in place of the old chip row -->
      <div class="ct-dropdown" id="ctStatusDropdown">
        <button type="button" class="ct-btn ct-btn-outline ct-dropdown-trigger" id="ctStatusTrigger"
                aria-haspopup="menu" aria-expanded="false" aria-controls="ctStatusMenu">
          <span class="ct-trigger-body">
            @unless ($isAllStatus)
              <span class="ct-dot" style="background: {{ $tokensFor($currentStatus)['dot'] }};"></span>
            @endunless
            <span>{{ $currentLabel }}</span>
            <span class="ct-count">{{ $currentCount }}</span>
          </span>
          <svg class="ct-chevron" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div class="ct-dropdown-menu" id="ctStatusMenu" role="menu" aria-labelledby="ctStatusTrigger">
          <div class="ct-dropdown-label">Filter by status</div>
          <a href="{{ route('contacts.index') }}" role="menuitem" class="ct-dropdown-item {{ $isAllStatus ? 'is-active' : '' }}">
            <svg class="ct-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            <span class="ct-item-label">All submissions</span>
            <span class="ct-count">{{ $totalCount }}</span>
          </a>
          <div class="ct-dropdown-separator"></div>
          @foreach ($statuses as $key => $label)
            <a href="{{ route('contacts.index', ['status' => $key]) }}" role="menuitem" class="ct-dropdown-item {{ $currentStatus === $key ? 'is-active' : '' }}">
              <svg class="ct-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span class="ct-dot" style="background: {{ $tokensFor($key)['dot'] }};"></span>
              <span class="ct-item-label">{{ $label }}</span>
              <span class="ct-count">{{ $statusCounts[$key] ?? 0 }}</span>
            </a>
          @endforeach
        </div>
      </div>

      <a href="{{ route('leads.index') }}" class="ct-btn ct-btn-primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Leads pipeline
      </a>
    </div>
  </div>

  @if (session('success'))
    <div class="ct-alert">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      <span>{{ session('success') }}</span>
    </div>
  @endif

  <div class="ct-card {{ $contacts->isEmpty() ? '' : 'ct-card-table' }}">
    @if ($contacts->isEmpty())
      <div class="ct-empty">
        <div class="ct-empty-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        @if (! $isAllStatus)
          <div class="ct-empty-title">No {{ strtolower($statuses[$currentStatus]) }} submissions</div>
          <div class="ct-empty-sub">Try a different filter, or check back once families move into this stage.</div>
          <a href="{{ route('contacts.index') }}" class="ct-btn ct-btn-outline">Clear filter</a>
        @else
          <div class="ct-empty-title">No submissions yet</div>
          <div class="ct-empty-sub">Enquiries from the website's Contact form will show up here as soon as families get in touch.</div>
        @endif
      </div>
    @else
      <table class="ct-table">
        <thead>
          <tr>
            <th>Contact</th>
            <th>Child &middot; Service enquiry</th>
            <th>Phone &middot; Email</th>
            <th>Consultation slot</th>
            <th>Received</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        @foreach ($contacts as $contact)
          @php
            $tokens = $tokensFor($contact->status);
            $slotLabel = $contact->hasBookingSlot() ? $contact->booking_date->format('M j, Y').' · '.$contact->booking_time : null;
          @endphp
          <tr class="ct-row" data-contact="{{ json_encode($contactPayload($contact), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
            <td>
              <div class="ct-person">
                <div class="ct-avatar" style="background: {{ $avatarColor($contact->id) }};">{{ strtoupper(mb_substr($contact->name ?: '?', 0, 1)) }}</div>
                <div>
                  <div class="ct-name">{{ $contact->name ?: 'Unknown contact' }}</div>
                  <div class="ct-secondary">Website enquiry</div>
                </div>
              </div>
            </td>
            <td>
              <div>{{ $contact->child_name ?: 'Name not provided' }}{{ $contact->child_age ? ' · Age '.$contact->child_age : '' }}</div>
              <div class="ct-secondary">{{ $contact->interested_in ?: 'No service selected' }}@if ($contact->insurance) · {{ $contact->insurance }}@endif</div>
            </td>
            <td>
              <div>{{ $contact->phone ?: '—' }}</div>
              <div class="ct-secondary">{{ $contact->email ?: 'No email provided' }}</div>
            </td>
            <td>
              @if ($slotLabel)
                <div>{{ $slotLabel }}</div>
                <div class="ct-secondary">Free 30-min consultation</div>
              @else
                <div class="ct-secondary">No slot requested</div>
              @endif
            </td>
            <td>
              {{ $contact->created_at->format('M j, Y') }}
              <div class="ct-secondary">{{ $contact->created_at->format('H:i') }}</div>
            </td>
            <td>
              <span class="ct-badge" style="background: {{ $tokens['bg'] }}; color: {{ $tokens['fg'] }}; border-color: {{ $tokens['border'] }};">
                <span class="ct-dot" style="background: {{ $tokens['dot'] }};"></span>
                {{ $contact->status_label }}
              </span>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>

  @if ($contacts->isNotEmpty())
    <div class="ct-groups" id="ctGroups">
      @foreach ($statuses as $statusKey => $statusLabel)
        @php $groupContacts = $contacts->where('status', $statusKey); @endphp
        @continue ($groupContacts->isEmpty())
        @php $groupTokens = $tokensFor($statusKey); @endphp
        <div class="ct-group">
          <div class="ct-group-head" role="button" tabindex="0" aria-expanded="false">
            <span class="ct-dot" style="background: {{ $groupTokens['dot'] }};"></span>
            <span class="ct-group-title">{{ $statusLabel }}</span>
            <span class="ct-group-count">{{ $groupContacts->count() }}</span>
            <span class="ct-group-caret">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </span>
          </div>
          <div class="ct-group-body">
            @foreach ($groupContacts as $contact)
              @php
                $slotLabel = $contact->hasBookingSlot() ? $contact->booking_date->format('M j, Y').' · '.$contact->booking_time : null;
              @endphp
              <div class="ct-row ct-mcard" data-contact="{{ json_encode($contactPayload($contact), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
                <div class="ct-mcard-head">
                  <div class="ct-avatar" style="background: {{ $avatarColor($contact->id) }};">{{ strtoupper(mb_substr($contact->name ?: '?', 0, 1)) }}</div>
                  <div class="ct-mcard-ident">
                    <div class="ct-name">{{ $contact->name ?: 'Unknown contact' }}</div>
                    <div class="ct-secondary">{{ $contact->created_at->diffForHumans() }}</div>
                  </div>
                </div>
                <div class="ct-mcard-line">{{ $contact->child_name ?: 'Name not provided' }}{{ $contact->child_age ? ' · Age '.$contact->child_age : '' }} · {{ $contact->interested_in ?: 'No service selected' }}{{ $contact->insurance ? ' · '.$contact->insurance : '' }}</div>
                <div class="ct-mcard-line ct-secondary">{{ $contact->phone ?: 'No phone' }} · {{ $contact->email ?: 'No email' }}</div>
                @if ($slotLabel)
                  <div class="ct-mcard-slot">{{ $slotLabel }} · free 30-min consultation</div>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- Detail dialog. Unlike the Job Applications dialog this one is filled
     client-side from the clicked row, so opening a submission never costs a
     page load - the markup below is the single shell every contact reuses. --}}
<div id="ctDialogOverlay" class="ct-dialog-overlay" role="dialog" aria-modal="true" aria-labelledby="ctDialogTitle">
  <div class="ct-dialog">
    <div class="ct-dialog-head">
      <div class="ct-avatar" id="ctDialogAvatar" style="width: 44px; height: 44px; font-size: 16px;"></div>
      <div class="ct-dialog-title">
        <h2 id="ctDialogTitle">—</h2>
        <div class="ct-dialog-desc" id="ctDialogDesc">Website enquiry</div>
      </div>
      <div>
        <div class="ct-dialog-contact" id="ctDialogPhone">—</div>
        <div class="ct-dialog-contact-sub" id="ctDialogEmail">—</div>
      </div>
      <button class="ct-dialog-close" type="button" onclick="closeContactDialog()" aria-label="Close contact details">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="ct-toolbar">
      <span class="ct-badge" style="background: var(--ct-muted); color: var(--ct-muted-foreground); border-color: var(--ct-border);">Website</span>
      <span class="ct-badge" id="ctDialogStatus"><span class="ct-dot" id="ctDialogStatusDot"></span><span id="ctDialogStatusLabel"></span></span>
      <div style="flex: 1;"></div>
      <button type="button" id="ctEmailActionBtn" class="ct-btn ct-btn-secondary ct-btn-sm" style="display: none;" onclick="ctOpenEmailFromDialog()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Send email
      </button>
      <form id="ctConvertForm" method="POST" style="display: none;">
        @csrf
        <button type="submit" class="ct-btn ct-btn-primary ct-btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          Convert to lead
        </button>
      </form>
      <a id="ctViewLeadLink" href="{{ route('leads.index') }}" class="ct-btn ct-btn-outline ct-btn-sm" style="display: none;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        View lead
      </a>
    </div>

    <div class="ct-grid">
      <div class="ct-col">
        <div class="ct-panel">
          <div class="ct-panel-title">Message</div>
          <div class="ct-prose" id="ctDialogMessage"></div>
          <div class="ct-panel-empty" id="ctDialogMessageEmpty" style="display: none;">No message provided.</div>
        </div>

        <div class="ct-panel" id="ctDialogSlotPanel">
          <div class="ct-panel-head">
            <div class="ct-panel-title">Requested consultation slot</div>
            <button type="button" class="ct-btn ct-btn-outline ct-btn-sm" id="ctSlotEditBtn">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
              <span>Change</span>
            </button>
          </div>
          <div class="ct-slot" id="ctDialogSlotCard">
            <div class="ct-slot-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            </div>
            <div>
              <div class="ct-slot-date" id="ctDialogSlotDate"></div>
              <div class="ct-slot-sub">Free 30-min consultation</div>
            </div>
          </div>
          <div class="ct-panel-empty" id="ctDialogSlotEmpty" style="display: none;">No consultation slot was requested.</div>
          <div class="ct-footnote" id="ctSlotLockedNote" style="display: none;">The decision has been emailed, so this slot is final.</div>

          {{-- Set / move the slot: a collapsed date field with the month
               calendar as a dropdown, and the consultation times. --}}
          <div class="ct-slot-editor" id="ctSlotEditor" hidden>
            <div class="ct-error" id="ctSlotError"></div>
            <div class="ct-slot-fields">
              <div class="ct-date-field">
                <label class="ct-label" for="ctSlotDateBtn">Date</label>
                <button type="button" class="ct-input ct-date-btn" id="ctSlotDateBtn" aria-haspopup="dialog" aria-expanded="false" aria-controls="ctSlotCal">
                  <span id="ctSlotDateText">Select a date…</span>
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </button>
                <div class="ct-cal" id="ctSlotCal" role="dialog" aria-label="Choose a date" hidden>
                  <div class="ct-cal-nav">
                    <button type="button" class="ct-cal-arrow" id="ctSlotCalPrev" aria-label="Previous month"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg></button>
                    <span id="ctSlotCalLabel"></span>
                    <button type="button" class="ct-cal-arrow" id="ctSlotCalNext" aria-label="Next month"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg></button>
                  </div>
                  <div class="ct-cal-weekdays"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
                  <div class="ct-cal-grid" id="ctSlotCalGrid"></div>
                  <div class="ct-cal-legend"><span><i class="is-today"></i>Today</span><span><i class="is-closed"></i>Closed (Fri–Sat)</span></div>
                </div>
              </div>
              <div>
                <label class="ct-label" for="ctSlotTime">Time</label>
                <select class="ct-input" id="ctSlotTime" disabled><option value="">Pick a date first</option></select>
              </div>
            </div>
            <div class="ct-slot-actions">
              <button type="button" class="ct-link danger" id="ctSlotRemove">Remove slot</button>
              <span style="flex:1"></span>
              <button type="button" class="ct-btn ct-btn-ghost ct-btn-sm" id="ctSlotCancel">Cancel</button>
              <button type="button" class="ct-btn ct-btn-primary ct-btn-sm" id="ctSlotSave">Save slot</button>
            </div>
            <div class="ct-footnote" id="ctSlotApprovedNote" style="display: none;">This slot is approved - after moving it, email the family the new time.</div>
          </div>
        </div>
      </div>

      <div class="ct-col">
        <div class="ct-panel">
          <div class="ct-panel-title">Contact details</div>
          <div>
            <div class="ct-field-row"><span class="ct-field-label">Phone</span><span class="ct-field-value" id="ctFieldPhone">—</span></div>
            <div class="ct-field-row"><span class="ct-field-label">Email</span><span class="ct-field-value" id="ctFieldEmail">—</span></div>
            <div class="ct-field-row"><span class="ct-field-label">Child's name</span><span class="ct-field-value" id="ctFieldChildName">—</span></div>
            <div class="ct-field-row"><span class="ct-field-label">Child's age</span><span class="ct-field-value" id="ctFieldAge">—</span></div>
            <div class="ct-field-row"><span class="ct-field-label">Service enquiry</span><span class="ct-field-value" id="ctFieldInterest">—</span></div>
            <div class="ct-field-row"><span class="ct-field-label">Insurance</span><span class="ct-field-value" id="ctFieldInsurance">—</span></div>
            <div class="ct-field-row"><span class="ct-field-label">Channel</span><span class="ct-field-value">Website</span></div>
            <div class="ct-field-row"><span class="ct-field-label">Received</span><span class="ct-field-value" id="ctFieldReceived">—</span></div>
          </div>
        </div>

        <div class="ct-panel">
          <div class="ct-panel-title">Decision</div>
          <div class="ct-actions">
            <div class="ct-actions-row" id="ctDecisionRow">
              <form id="ctApproveForm" method="POST">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="approved">
                <button type="submit" id="ctApproveBtn" class="ct-btn ct-btn-approve ct-btn-sm">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Approve
                </button>
              </form>
              <form id="ctRejectForm" method="POST">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <button type="submit" id="ctRejectBtn" class="ct-btn ct-btn-reject ct-btn-sm">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                  Reject
                </button>
              </form>
            </div>
            <div class="ct-footnote" id="ctDecisionHint">Approve or reject the requested slot, then email the family.</div>
            <div class="ct-emailed" id="ctEmailedWrap" style="display: none;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              <span id="ctEmailedText"></span>
            </div>
            <div class="ct-links">
              <button type="button" id="ctCloseLink" class="ct-link" style="display: none;"
                      onclick="if (confirm('Close this submission without approving or rejecting?')) document.getElementById('ctCloseForm').submit()">Close without action</button>
              @if (auth()->user()->role !== 'COORDINATOR')
                <button type="button" class="ct-link danger"
                        onclick="if (confirm('Delete this submission? This cannot be undone.')) document.getElementById('ctDeleteForm').submit()">Delete submission</button>
              @endif
            </div>
            <form id="ctCloseForm" method="POST" style="display: none;">@csrf @method('PATCH')<input type="hidden" name="status" value="closed"></form>
            <form id="ctDeleteForm" method="POST" style="display: none;">@csrf @method('DELETE')</form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="ctEmailOverlay" class="ct-dialog-overlay ct-email-overlay" role="dialog" aria-modal="true" aria-labelledby="ctEmailTitle">
  <div class="ct-dialog ct-email-dialog">
    <div class="ct-dialog-head" style="margin-bottom: 18px;">
      <div class="ct-dialog-title">
        <h2 id="ctEmailTitle">Email the family</h2>
        <div class="ct-dialog-desc" id="ctEmailSub"></div>
      </div>
      <button class="ct-dialog-close" type="button" onclick="closeEmailDialog()" aria-label="Close">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="ct-error" id="ctEmailError"></div>
    <div class="ct-field"><label class="ct-label" for="ctEmailTo">To</label><input id="ctEmailTo" type="email" class="ct-input"></div>
    <div class="ct-field"><label class="ct-label" for="ctEmailSubject">Subject</label><input id="ctEmailSubject" type="text" class="ct-input"></div>
    <div class="ct-field"><label class="ct-label" for="ctEmailMessage">Message</label><textarea id="ctEmailMessage" class="ct-textarea" rows="9"></textarea></div>
    <button type="button" id="ctEmailSend" class="ct-btn ct-btn-primary" style="width: 100%;">Send email</button>
  </div>
</div>

@if ($activeContact)
  <script>const CT_ACTIVE_CONTACT = {!! json_encode($contactPayload($activeContact), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};</script>
@endif
@endsection

@push('scripts')
<script>
function closeContactDialog() {
    document.getElementById('ctDialogOverlay')?.classList.remove('open');
    const url = new URL(window.location.href);
    if (url.searchParams.has('contact')) {
        url.searchParams.delete('contact');
        window.history.replaceState({}, '', url.toString());
    }
}
function closeEmailDialog() { document.getElementById('ctEmailOverlay')?.classList.remove('open'); }

const CT_CLINIC_NAME = @json(config('clinic.name'));
const CT_CLINIC_PHONE = '+971 50 884 6801';
const CT_CLINIC_EMAIL = 'info@engagebehavior.com';
const CT_CLINIC_ADDRESS = 'Office No. 1203, ADCP Commercial Tower-C, Electra Street, Abu Dhabi, UAE';
const CT_SEND_EMAIL_TEMPLATE = @json(route('contacts.send-email', ['contact' => '__CONTACT__']));
let ctEmailTargetId = null, ctCurrentContact = null;

// contact is a plain object with at least {id,name,email,status,booking_date,booking_time} -
// `status` doubles as the decision (approved/rejected) since that's the only
// time this is ever callable (see canSendStatusEmail()).
function openEmailModalFor(contact) {
    ctEmailTargetId = contact.id;
    const approved = contact.status === 'approved';
    const slot = (contact.booking_date && contact.booking_time) ? `${contact.booking_date} at ${contact.booking_time}` : 'your requested slot';
    document.getElementById('ctEmailError').style.display = 'none';
    document.getElementById('ctEmailSub').textContent = (contact.name || 'This family') + ' · ' + (approved ? 'approval' : 'rejection') + ' notice';
    document.getElementById('ctEmailTo').value = contact.email || '';
    document.getElementById('ctEmailSubject').value = approved
        ? 'Your free consultation with ' + CT_CLINIC_NAME + ' is confirmed'
        : 'About your consultation request with ' + CT_CLINIC_NAME;
    const ctContactBlock = `Call Us: ${CT_CLINIC_PHONE}\nEmail: ${CT_CLINIC_EMAIL}\nVisit Us: ${CT_CLINIC_ADDRESS}\n\nWarm regards,\n${CT_CLINIC_NAME}`;
    document.getElementById('ctEmailMessage').value = approved
        ? `Dear ${contact.name || 'parent'},\n\nThank you for choosing ${CT_CLINIC_NAME}. Your free 30-minute consultation has been confirmed for ${slot}.\n\nOur team looks forward to meeting with you and learning more about your child's needs, so we can better understand how ${CT_CLINIC_NAME} may support your family.\n\nIf you need to make any changes to your consultation, simply reply to this email and our team will be happy to assist.\n\n${ctContactBlock}`
        : `Dear ${contact.name || 'parent'},\n\nThank you for your interest in ${CT_CLINIC_NAME} and for taking the time to request a free consultation.\n\nUnfortunately, we're unable to confirm the consultation slot you requested (${slot}). This may simply be due to limited availability at that time, but we'd still love the opportunity to support your family.\n\nPlease reply to this email or reach out to us directly, and our team will be happy to help you find another time that works.\n\n${ctContactBlock}`;
    document.getElementById('ctEmailOverlay').classList.add('open');
}

function ctOpenEmailFromDialog() { if (ctCurrentContact) openEmailModalFor(ctCurrentContact); }

document.getElementById('ctEmailSend')?.addEventListener('click', function () {
    const btn = this, error = document.getElementById('ctEmailError'), label = btn.textContent;
    error.style.display = 'none';
    btn.disabled = true; btn.textContent = 'Sending…';
    fetch(CT_SEND_EMAIL_TEMPLATE.replace('__CONTACT__', ctEmailTargetId), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ to: document.getElementById('ctEmailTo').value, subject: document.getElementById('ctEmailSubject').value, message: document.getElementById('ctEmailMessage').value }),
    })
    .then(async response => ({ ok: response.ok, data: await response.json() }))
    .then(result => {
        if (result.ok) { window.location.reload(); return; }
        error.textContent = result.data.errors ? Object.values(result.data.errors).flat().join(', ') : (result.data.message || 'Could not send the email.');
        error.style.display = 'block'; btn.disabled = false; btn.textContent = label;
    })
    .catch(() => { error.textContent = 'Network error. Please try again.'; error.style.display = 'block'; btn.disabled = false; btn.textContent = label; });
});

// Status groups on a phone: all shut to begin with, one open at a time. The
// table takes over above the breakpoint, where nothing needs hiding.
(function () {
    const groups = document.getElementById('ctGroups');
    if (!groups) return;

    const onNarrowScreen = () => window.matchMedia('(max-width: 860px)').matches;

    function setOpen(group, open) {
        group.classList.toggle('is-open', open);

        const head = group.querySelector('.ct-group-head');
        if (head) head.setAttribute('aria-expanded', open ? 'true' : 'false');

        const body = group.querySelector('.ct-group-body');
        if (!body) return;

        // Animating towards a guessed height would run the transition at a
        // different speed for every group, so the target is the real content
        // height - capped, past which the group scrolls inside itself rather
        // than pushing the next one off-screen.
        body.style.maxHeight = open
            ? Math.min(body.scrollHeight, Math.round(window.innerHeight * 0.68)) + 'px'
            : '';
    }

    groups.querySelectorAll('.ct-group-head').forEach(function (head) {
        function toggle() {
            if (!onNarrowScreen()) return;

            const group = head.closest('.ct-group');
            const opening = !group.classList.contains('is-open');

            groups.querySelectorAll('.ct-group').forEach(g => setOpen(g, false));
            if (opening) setOpen(group, true);
        }

        head.addEventListener('click', toggle);
        head.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggle();
            }
        });
    });

    // The height above is written inline, so it would follow the list up to the
    // wide layout and cap a group that is no longer collapsible.
    window.addEventListener('resize', function () {
        if (onNarrowScreen()) return;

        groups.querySelectorAll('.ct-group').forEach(function (group) {
            group.classList.remove('is-open');
            const body = group.querySelector('.ct-group-body');
            if (body) body.style.maxHeight = '';
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const overlay = document.getElementById('ctDialogOverlay'),
        emailOverlay = document.getElementById('ctEmailOverlay'),
        statusTemplate = @json(route('contacts.update-status', ['contact' => '__CONTACT__'])),
        convertTemplate = @json(route('contacts.convert-to-lead', ['contact' => '__CONTACT__'])),
        deleteTemplate = @json(route('contacts.destroy', ['contact' => '__CONTACT__'])),
        updateTemplate = @json(route('contacts.update', ['contact' => '__CONTACT__'])),
        CT_TIMES = @json($consultationTimes),
        tokens = @json($statusTokens),
        urlFor = (template, id) => template.replace('__CONTACT__', id);

    function openContactDialog(contact) {
        ctCurrentContact = contact;
        const token = tokens[contact.status] || tokens.closed;

        const avatar = document.getElementById('ctDialogAvatar');
        avatar.textContent = contact.initials || '?';
        avatar.style.background = contact.avatar;
        document.getElementById('ctDialogTitle').textContent = contact.name || 'Unknown contact';
        document.getElementById('ctDialogDesc').textContent = 'Website enquiry · ' + contact.received_human;
        document.getElementById('ctDialogPhone').textContent = contact.phone || 'No phone provided';
        document.getElementById('ctDialogEmail').textContent = contact.email || 'No email provided';

        const badge = document.getElementById('ctDialogStatus');
        badge.style.background = token.bg; badge.style.color = token.fg; badge.style.borderColor = token.border;
        document.getElementById('ctDialogStatusDot').style.background = token.dot;
        document.getElementById('ctDialogStatusLabel').textContent = contact.status_label;

        const message = document.getElementById('ctDialogMessage'), messageEmpty = document.getElementById('ctDialogMessageEmpty');
        message.textContent = contact.message || '';
        message.style.display = contact.message ? '' : 'none';
        messageEmpty.style.display = contact.message ? 'none' : '';

        const hasSlot = Boolean(contact.booking_date && contact.booking_time);
        document.getElementById('ctDialogSlotCard').style.display = hasSlot ? '' : 'none';
        document.getElementById('ctDialogSlotEmpty').style.display = hasSlot ? 'none' : '';
        if (hasSlot) document.getElementById('ctDialogSlotDate').textContent = contact.booking_date + ' · ' + contact.booking_time;

        document.getElementById('ctFieldPhone').textContent = contact.phone || 'Not provided';
        document.getElementById('ctFieldEmail').textContent = contact.email || 'Not provided';
        document.getElementById('ctFieldChildName').textContent = contact.child_name || 'Not provided';
        document.getElementById('ctFieldAge').textContent = contact.child_age ? ('Age ' + contact.child_age) : 'Not provided';
        document.getElementById('ctFieldInterest').textContent = contact.interested_in || 'Not provided';
        document.getElementById('ctFieldInsurance').textContent = contact.insurance || 'Not provided';
        setupSlotEditor(contact);
        document.getElementById('ctFieldReceived').textContent = contact.received;

        // Approve/Reject stay available (and swappable) right up until the
        // decision is emailed - after that (contacted) or once converted,
        // the decision is locked in and these buttons disappear entirely.
        const decidable = ['new', 'approved', 'rejected'].includes(contact.status);
        const approveForm = document.getElementById('ctApproveForm'), rejectForm = document.getElementById('ctRejectForm'),
            approveBtn = document.getElementById('ctApproveBtn'), rejectBtn = document.getElementById('ctRejectBtn');
        document.getElementById('ctDecisionRow').style.display = decidable ? '' : 'none';
        document.getElementById('ctDecisionHint').style.display = decidable ? '' : 'none';
        approveForm.action = urlFor(statusTemplate, contact.id);
        rejectForm.action = urlFor(statusTemplate, contact.id);
        approveBtn.classList.toggle('is-active', contact.status === 'approved');
        rejectBtn.classList.toggle('is-active', contact.status === 'rejected');

        const emailBtn = document.getElementById('ctEmailActionBtn'), emailedWrap = document.getElementById('ctEmailedWrap');
        emailBtn.style.display = contact.can_send_email ? '' : 'none';
        if (contact.status_email_sent_at) {
            const decisionLabel = contact.booking_decision === 'approved' ? 'Approved' : (contact.booking_decision === 'rejected' ? 'Rejected' : 'Decision');
            document.getElementById('ctEmailedText').textContent = decisionLabel + ' · emailed ' + contact.status_email_sent_at;
            emailedWrap.style.display = '';
        } else {
            emailedWrap.style.display = 'none';
        }

        const convertForm = document.getElementById('ctConvertForm');
        convertForm.style.display = contact.can_convert ? '' : 'none';
        convertForm.action = urlFor(convertTemplate, contact.id);
        document.getElementById('ctViewLeadLink').style.display = contact.status === 'converted' ? '' : 'none';

        document.getElementById('ctCloseLink').style.display = contact.status === 'new' ? '' : 'none';
        document.getElementById('ctCloseForm').action = urlFor(statusTemplate, contact.id);
        const deleteForm = document.getElementById('ctDeleteForm');
        if (deleteForm) deleteForm.action = urlFor(deleteTemplate, contact.id);

        overlay.classList.add('open');
    }

    // ---- Saving slot edits ----
    // PATCH contacts.update; the page reloads onto the same contact afterwards
    // so the table, badges and dialog all show the saved values.
    function saveContact(contact, payload) {
        return fetch(urlFor(updateTemplate, contact.id), {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        }).then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Could not save. Please try again.'));
            return data;
        });
    }
    function reloadOnContact(id) {
        const url = new URL(window.location.href);
        url.searchParams.set('contact', id);
        window.location.href = url.toString();
    }

    // ---- Consultation slot editor ----
    const slotEditor = document.getElementById('ctSlotEditor'), slotEditBtn = document.getElementById('ctSlotEditBtn'),
        slotDateBtn = document.getElementById('ctSlotDateBtn'), slotCal = document.getElementById('ctSlotCal'),
        slotTime = document.getElementById('ctSlotTime'), slotError = document.getElementById('ctSlotError');
    const slotState = { contact: null, date: null, time: null, monthOffset: 0 };
    const isoOf = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

    function setupSlotEditor(contact) {
        slotState.contact = contact;
        const hasSlot = Boolean(contact.booking_date && contact.booking_time);
        slotEditBtn.style.display = contact.can_edit_slot ? '' : 'none';
        slotEditBtn.querySelector('span').textContent = hasSlot ? 'Change' : 'Set a slot';
        document.getElementById('ctSlotLockedNote').style.display = (!contact.can_edit_slot && hasSlot && contact.status_email_sent_at) ? '' : 'none';
        closeSlotEditor();
    }
    function openSlotEditor() {
        const c = slotState.contact;
        slotState.date = c.booking_date_iso ? new Date(c.booking_date_iso + 'T00:00:00') : null;
        slotState.time = c.booking_time || null;
        slotError.style.display = 'none';
        document.getElementById('ctSlotRemove').style.display = c.booking_date_iso ? '' : 'none';
        document.getElementById('ctSlotApprovedNote').style.display = c.status === 'approved' ? '' : 'none';
        slotEditor.hidden = false;
        slotEditBtn.style.display = 'none';
        renderSlotFields();
        slotDateBtn.focus();
    }
    function closeSlotEditor() {
        slotEditor.hidden = true;
        setSlotCalOpen(false);
        if (slotState.contact?.can_edit_slot) slotEditBtn.style.display = '';
    }
    function setSlotCalOpen(open) {
        slotCal.hidden = !open;
        slotDateBtn.setAttribute('aria-expanded', open);
        if (open) {
            const now = new Date();
            slotState.monthOffset = slotState.date ? Math.max(0, (slotState.date.getFullYear() - now.getFullYear()) * 12 + slotState.date.getMonth() - now.getMonth()) : 0;
            renderSlotCalendar();
        }
    }
    function renderSlotCalendar() {
        const base = new Date(); base.setDate(1); base.setMonth(base.getMonth() + slotState.monthOffset);
        document.getElementById('ctSlotCalLabel').textContent = base.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
        document.getElementById('ctSlotCalPrev').disabled = slotState.monthOffset <= 0;
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const year = base.getFullYear(), month = base.getMonth();
        const grid = document.getElementById('ctSlotCalGrid');
        grid.innerHTML = '';
        for (let i = 0; i < new Date(year, month, 1).getDay(); i++) grid.appendChild(document.createElement('span'));
        for (let d = 1; d <= new Date(year, month + 1, 0).getDate(); d++) {
            const day = new Date(year, month, d);
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'ct-cal-day';
            cell.textContent = d;
            cell.disabled = day < today || day.getDay() === 5 || day.getDay() === 6;
            if (day.getTime() === today.getTime()) cell.classList.add('is-today');
            if (slotState.date && day.getTime() === slotState.date.getTime()) cell.classList.add('is-selected');
            cell.setAttribute('aria-label', day.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
            cell.addEventListener('click', () => { slotState.date = day; setSlotCalOpen(false); renderSlotFields(); slotTime.focus(); });
            grid.appendChild(cell);
        }
    }
    function renderSlotFields() {
        const text = document.getElementById('ctSlotDateText');
        text.textContent = slotState.date ? slotState.date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) : 'Select a date…';
        slotDateBtn.classList.toggle('is-empty', !slotState.date);
        slotTime.disabled = !slotState.date;
        slotTime.innerHTML = '';
        slotTime.add(new Option(slotState.date ? 'Select a time…' : 'Pick a date first', ''));
        if (slotState.date) CT_TIMES.forEach(t => slotTime.add(new Option(t, t, false, t === slotState.time)));
    }
    function saveSlot(payload, btn) {
        const label = btn.textContent;
        slotError.style.display = 'none';
        btn.disabled = true; btn.textContent = 'Saving…';
        saveContact(slotState.contact, payload)
            .then(() => reloadOnContact(slotState.contact.id))
            .catch(err => { slotError.textContent = err.message; slotError.style.display = 'block'; btn.disabled = false; btn.textContent = label; });
    }

    slotEditBtn.addEventListener('click', openSlotEditor);
    document.getElementById('ctSlotCancel').addEventListener('click', closeSlotEditor);
    slotDateBtn.addEventListener('click', () => setSlotCalOpen(slotCal.hidden));
    document.getElementById('ctSlotCalPrev').addEventListener('click', () => { slotState.monthOffset = Math.max(0, slotState.monthOffset - 1); renderSlotCalendar(); });
    document.getElementById('ctSlotCalNext').addEventListener('click', () => { slotState.monthOffset++; renderSlotCalendar(); });
    slotTime.addEventListener('change', () => { slotState.time = slotTime.value || null; });
    document.addEventListener('click', e => { if (!slotCal.hidden && !e.target.closest('.ct-date-field')) setSlotCalOpen(false); });
    document.getElementById('ctSlotSave').addEventListener('click', function () {
        if (!slotState.date || !slotState.time) {
            slotError.textContent = !slotState.date ? 'Pick a date.' : 'Choose a time.';
            slotError.style.display = 'block';
            return;
        }
        saveSlot({ booking_date: isoOf(slotState.date), booking_time: slotState.time }, this);
    });
    document.getElementById('ctSlotRemove').addEventListener('click', function () {
        if (confirm('Remove the requested consultation slot from this contact?')) saveSlot({ booking_date: null, booking_time: null }, this);
    });

    document.querySelectorAll('.ct-row[data-contact]').forEach(row => row.addEventListener('click', () => {
        openContactDialog(JSON.parse(row.dataset.contact));
    }));

    if (typeof CT_ACTIVE_CONTACT !== 'undefined') openContactDialog(CT_ACTIVE_CONTACT);

    // ---- Status dropdown ----
    const dropdown = document.getElementById('ctStatusDropdown');
    const trigger = document.getElementById('ctStatusTrigger');

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
            const items = Array.from(dropdown.querySelectorAll('.ct-dropdown-item'));
            if (!items.length) return;
            const index = items.indexOf(document.activeElement);
            const next = e.key === 'ArrowDown'
                ? (index + 1) % items.length
                : (index <= 0 ? items.length - 1 : index - 1);
            items[next].focus();
        });
    }

    if (overlay) overlay.addEventListener('click', e => { if (e.target === overlay) closeContactDialog(); });
    if (emailOverlay) emailOverlay.addEventListener('click', e => { if (e.target === emailOverlay) closeEmailDialog(); });

    // Escape closes the innermost thing first, so one press never does two
    // things at once.
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if (dropdown && dropdown.classList.contains('is-open')) { closeDropdown(); trigger.focus(); return; }
        if (!slotCal.hidden) { setSlotCalOpen(false); slotDateBtn.focus(); return; }
        if (emailOverlay?.classList.contains('open')) { closeEmailDialog(); return; }
        if (overlay?.classList.contains('open')) closeContactDialog();
    });
});
</script>
@endpush
