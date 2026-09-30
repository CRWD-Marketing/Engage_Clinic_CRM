<style>
/* ── CMS Forms: same token scale as Contacts (--ct-*) and Job Applications
   (--jb-*), namespaced --cf-* so it can't collide ─────────────────────── */
:root {
  --cf-background: #ffffff;
  --cf-foreground: #09090b;
  --cf-card: #ffffff;
  --cf-muted: #f4f4f5;
  --cf-muted-foreground: #71717a;
  --cf-subtle-foreground: #a1a1aa;
  --cf-border: #e4e4e7;
  --cf-input: #e4e4e7;
  --cf-accent: #f4f4f5;
  --cf-primary: #C8355F;
  --cf-primary-hover: #A82348;
  --cf-primary-soft: #FCEAF0;
  --cf-navy: #16436E;
  --cf-destructive: #dc2626;
  --cf-destructive-subtle: #fef2f2;
  --cf-success: #15803d;
  --cf-success-subtle: #f0fdf4;
  --cf-success-border: #bbf7d0;
  --cf-ring: rgba(200, 53, 95, .35);
  --cf-radius: 8px;
  --cf-radius-sm: 6px;
  --cf-font: 'Nunito Sans', ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --cf-font-display: 'Baloo 2', 'Nunito Sans', ui-sans-serif, system-ui, sans-serif;
  --cf-shadow-sm: 0 1px 2px 0 rgba(9, 9, 11, .05);
  --cf-shadow-lg: 0 10px 15px -3px rgba(9, 9, 11, .1), 0 4px 6px -4px rgba(9, 9, 11, .1);
  --cf-shadow-xl: 0 20px 25px -5px rgba(9, 9, 11, .12), 0 8px 10px -6px rgba(9, 9, 11, .1);
}

.main-content-inner.pt-tight-padding { padding-left: 24px; padding-right: 24px; }

.cf-root, .cf-dialog-overlay { font-family: var(--cf-font); -webkit-font-smoothing: antialiased; color: var(--cf-foreground); }
.cf-root *, .cf-dialog-overlay * { box-sizing: border-box; }

/* Header */
.cf-crumbs { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px; font-weight: 600; color: var(--cf-muted-foreground); flex-wrap: wrap; }
.cf-crumbs a { color: var(--cf-muted-foreground); text-decoration: none; }
.cf-crumbs a:hover { color: var(--cf-primary); }
.cf-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
.cf-heading { font-family: var(--cf-font-display); font-size: 25px; font-weight: 600; line-height: 1.2; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.cf-subheading { margin-top: 5px; font-size: 13.5px; color: var(--cf-muted-foreground); }
.cf-header-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* Buttons */
.cf-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 7px;
  height: 36px; padding: 0 14px; border-radius: var(--cf-radius-sm);
  font-family: var(--cf-font); font-size: 13.5px; font-weight: 600; line-height: 1;
  border: 1px solid transparent; background: none; cursor: pointer; text-decoration: none; white-space: nowrap;
  transition: background-color .15s, border-color .15s, color .15s;
}
.cf-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--cf-ring); }
.cf-btn-primary { background: var(--cf-primary); color: #fff; box-shadow: var(--cf-shadow-sm); }
.cf-btn-primary:hover { background: var(--cf-primary-hover); color: #fff; }
.cf-btn-outline { background: var(--cf-background); color: var(--cf-foreground); border-color: var(--cf-input); box-shadow: var(--cf-shadow-sm); }
.cf-btn-outline:hover { background: var(--cf-accent); color: var(--cf-foreground); }
.cf-btn-success { background: #16a34a; color: #fff; box-shadow: var(--cf-shadow-sm); }
.cf-btn-success:hover { background: #15803d; color: #fff; }
.cf-btn-ghost { background: transparent; color: var(--cf-foreground); }
.cf-btn-ghost:hover { background: var(--cf-accent); }
.cf-btn-danger-ghost { background: transparent; color: var(--cf-destructive); }
.cf-btn-danger-ghost:hover { background: var(--cf-destructive-subtle); }
.cf-btn-lg { height: 42px; padding: 0 20px; font-size: 14.5px; }
.cf-btn-sm { height: 30px; padding: 0 10px; font-size: 12.5px; }
.cf-btn-icon { width: 30px; height: 30px; padding: 0; }
.cf-btn[disabled], .cf-btn.is-disabled { opacity: .5; pointer-events: none; cursor: not-allowed; }

/* Alerts */
.cf-alert { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 18px; padding: 12px 14px; border: 1px solid var(--cf-success-border); border-radius: var(--cf-radius); background: var(--cf-success-subtle); color: var(--cf-success); font-size: 13.5px; font-weight: 600; }
.cf-alert.is-error { border-color: #fecaca; background: var(--cf-destructive-subtle); color: #b91c1c; }
.cf-alert svg { flex-shrink: 0; margin-top: 1px; }

/* Cards, tables */
.cf-card { border: 1px solid var(--cf-border); border-radius: var(--cf-radius); background: var(--cf-card); box-shadow: var(--cf-shadow-sm); }
.cf-card-table { overflow-x: auto; }
.cf-table { width: 100%; min-width: 760px; border-collapse: collapse; }
.cf-table th { padding: 11px 16px; border-bottom: 1px solid var(--cf-border); text-align: left; white-space: nowrap; font-size: 12.5px; font-weight: 600; color: var(--cf-muted-foreground); }
.cf-table th.num, .cf-table td.num { text-align: right; }
.cf-table td { padding: 13px 16px; border-bottom: 1px solid var(--cf-border); vertical-align: middle; font-size: 13.5px; }
.cf-table tbody tr:last-child td { border-bottom: 0; }
.cf-table tbody tr:hover td { background: #fafafa; }
.cf-name { font-weight: 700; color: var(--cf-foreground); text-decoration: none; }
a.cf-name:hover { color: var(--cf-primary); }
.cf-secondary { margin-top: 2px; font-size: 12.5px; color: var(--cf-muted-foreground); }
.cf-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; }

.cf-panel { border: 1px solid var(--cf-border); border-radius: var(--cf-radius); background: var(--cf-card); padding: 18px 20px; box-shadow: var(--cf-shadow-sm); }
.cf-panel-title { font-family: var(--cf-font-display); font-size: 16px; font-weight: 600; margin-bottom: 12px; }
.cf-eyebrow { font-size: 11.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--cf-muted-foreground); }

/* Badges */
.cf-badge { display: inline-flex; align-items: center; gap: 6px; padding: 3px 9px; border-radius: 999px; border: 1px solid transparent; font-size: 12px; font-weight: 600; white-space: nowrap; font-family: var(--cf-font); }
.cf-dot { width: 7px; height: 7px; border-radius: 999px; flex-shrink: 0; }
.cf-count-pill { display: inline-block; margin-left: 6px; padding: 1px 7px; border-radius: 999px; background: var(--cf-primary-soft); color: var(--cf-primary); font-size: 11px; font-weight: 800; }

/* Inputs */
.cf-input, .cf-textarea, .cf-select {
  width: 100%; padding: 8px 11px; border: 1px solid var(--cf-input); border-radius: var(--cf-radius-sm);
  background: var(--cf-background); color: var(--cf-foreground); font-family: var(--cf-font); font-size: 13.5px; line-height: 1.5;
  transition: border-color .12s, box-shadow .12s;
}
.cf-input:focus, .cf-textarea:focus, .cf-select:focus { outline: none; border-color: var(--cf-primary); box-shadow: 0 0 0 3px var(--cf-ring); }
.cf-input.is-invalid, .cf-textarea.is-invalid { border-color: var(--cf-destructive); }
.cf-textarea { resize: vertical; min-height: 72px; }
.cf-field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
.cf-label { font-size: 12.5px; font-weight: 600; color: var(--cf-muted-foreground); }
.cf-hint { font-size: 12px; color: var(--cf-muted-foreground); line-height: 1.45; }
.cf-error { font-size: 12.5px; font-weight: 600; color: var(--cf-destructive); }
.cf-check { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer; }
.cf-check input { width: 16px; height: 16px; accent-color: var(--cf-primary); }

/* Dropdown menu (shadcn DropdownMenu). Opened menus are moved to <body> by
   cfToggleMenu(), so every rule here is global rather than under .cf-root. */
.cf-menu-wrap { position: relative; display: inline-block; }
.cf-menu-wrap.is-open > button { background: var(--cf-accent); color: var(--cf-foreground); }
.cf-menu { display: none; position: fixed; z-index: 10000; width: 236px; padding: 4px; overflow-y: auto; overscroll-behavior: contain; background: #fff; border: 1px solid var(--cf-border); border-radius: 10px; box-shadow: 0 10px 38px -10px rgba(9, 9, 11, .28), 0 10px 20px -15px rgba(9, 9, 11, .2); font-family: var(--cf-font); color: var(--cf-foreground); transform-origin: top right; }
.cf-menu.is-open { display: block; animation: cfMenuIn .13s cubic-bezier(.16, 1, .3, 1); }
.cf-menu, .cf-menu * { box-sizing: border-box; -webkit-font-smoothing: antialiased; }
.cf-menu.is-up { transform-origin: bottom right; }
@keyframes cfMenuIn { from { opacity: 0; transform: scale(.96) translateY(-3px); } to { opacity: 1; transform: none; } }
.cf-menu.is-up.is-open { animation-name: cfMenuInUp; }
@keyframes cfMenuInUp { from { opacity: 0; transform: scale(.96) translateY(3px); } to { opacity: 1; transform: none; } }
.cf-menu-label { display: flex; align-items: center; gap: 10px; padding: 6px 8px 7px; }
.cf-menu-label-swatch { width: 30px; height: 30px; border-radius: 7px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .06); }
.cf-menu-label-text { min-width: 0; }
.cf-menu-label-title { font-size: 13px; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cf-menu-label-sub { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--cf-muted-foreground); margin-top: 1px; }
.cf-menu-label-sub .cf-dot { width: 6px; height: 6px; }
.cf-menu-group-label { padding: 4px 8px 2px; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--cf-subtle-foreground); }
.cf-menu-item { display: flex; width: 100%; align-items: center; gap: 10px; height: 31px; padding: 0 8px; border: 0; background: none; border-radius: 6px; font: 500 13.5px var(--cf-font); color: var(--cf-foreground); text-decoration: none; cursor: pointer; text-align: left; outline: none; }
.cf-menu-item:hover, .cf-menu-item:focus-visible, .cf-menu-item:focus { background: var(--cf-accent); color: var(--cf-foreground); }
.cf-menu-item i { width: 16px; text-align: center; font-size: 13px; color: var(--cf-muted-foreground); }
.cf-menu-item .cf-menu-hint { margin-left: auto; font-size: 11.5px; color: var(--cf-subtle-foreground); font-weight: 500; }
.cf-menu-item i.cf-menu-hint { font-size: 10.5px; font-weight: 900; width: auto; }
.cf-menu-item.danger { color: var(--cf-destructive); }
.cf-menu-item.danger:hover, .cf-menu-item.danger:focus { background: var(--cf-destructive-subtle); color: #b91c1c; }
.cf-menu-item.danger i { color: inherit; }
.cf-menu-item[aria-disabled=true] { opacity: .5; cursor: not-allowed; }
.cf-menu-item[aria-disabled=true]:hover { background: none; }
.cf-menu-sep { height: 1px; margin: 4px -4px; background: var(--cf-border); }
.cf-menu form { margin: 0; }

/* Alert dialog + destructive button */
.cf-btn-danger { background: var(--cf-destructive); color: #fff; box-shadow: var(--cf-shadow-sm); }
.cf-btn-danger:hover { background: #b91c1c; color: #fff; }
.cf-alert-dialog { width: 440px; }
.cf-alert-icon { width: 40px; height: 40px; border-radius: 999px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; background: var(--cf-accent); color: var(--cf-foreground); }
.cf-alert-icon.is-danger { background: var(--cf-destructive-subtle); color: var(--cf-destructive); }
.cf-alert-dialog { width: 480px; }
.cf-delete-warning { border: 1px solid #fecaca; background: var(--cf-destructive-subtle); border-radius: 8px; padding: 11px 13px; color: #991b1b; font-size: 13px; }
.cf-delete-warning-title { display: flex; align-items: center; gap: 7px; font-weight: 700; margin-bottom: 5px; }
.cf-delete-warning ul { margin: 0; padding-left: 20px; line-height: 1.55; }
.cf-btn-danger[disabled] { opacity: .45; }
.cf-ack { margin-top: 14px; }
.cf-ack-row { display: flex; align-items: flex-start; gap: 10px; padding: 11px 13px; border: 1px solid var(--cf-border); border-radius: 8px; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: border-color .12s, background-color .12s; }
.cf-ack-row:hover { background: #fafafa; }
.cf-ack-row input { width: 17px; height: 17px; margin: 1px 0 0; accent-color: var(--cf-destructive); flex-shrink: 0; cursor: pointer; }
.cf-ack-row:has(input:checked) { border-color: #fca5a5; background: var(--cf-destructive-subtle); }
.cf-ack-error { display: none; margin-top: 6px; font-size: 12.5px; font-weight: 600; color: var(--cf-destructive); }
.cf-ack.has-error .cf-ack-row { border-color: var(--cf-destructive); box-shadow: 0 0 0 3px rgba(220, 38, 38, .15); animation: cfShake .3s; }
.cf-ack.has-error .cf-ack-error { display: block; }
@keyframes cfShake { 0%, 100% { transform: none; } 25% { transform: translateX(-4px); } 75% { transform: translateX(4px); } }

/* Dialog */
.cf-dialog-overlay { display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; padding: 18px; background: rgba(9, 9, 11, .55); backdrop-filter: blur(2px); }
.cf-dialog-overlay.open { display: flex; }
.cf-dialog { width: 560px; max-width: 100%; max-height: 90vh; overflow-y: auto; background: var(--cf-background); border: 1px solid var(--cf-border); border-radius: 12px; box-shadow: var(--cf-shadow-xl); padding: 24px; }
.cf-dialog h2 { font-family: var(--cf-font-display); font-size: 20px; font-weight: 600; margin: 0; }
.cf-dialog-desc { margin: 4px 0 18px; font-size: 13px; color: var(--cf-muted-foreground); line-height: 1.5; }
.cf-dialog-foot { display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; }

/* Stat cards (Forms list, Responses list) */
.cf-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
.cf-stat { display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1px solid var(--cf-border); border-radius: 12px; background: #fff; box-shadow: var(--cf-shadow-sm); min-width: 0; }
.cf-stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 15px; }
.cf-stat-label { font-size: 12.5px; font-weight: 600; color: var(--cf-muted-foreground); }
.cf-stat-value { font-family: var(--cf-font-display); font-size: 26px; font-weight: 600; line-height: 1.15; margin-top: 2px; }
.cf-stat-value.is-text { font-size: 19px; line-height: 1.5; }
.cf-stat-sub { font-size: 12px; color: var(--cf-muted-foreground); margin-top: 1px; }
.cf-stat-sub strong { color: var(--cf-primary); }
.cf-stat.is-attention { border-color: #f3b6c8; background: linear-gradient(180deg, #fff 0%, #fff7fa 100%); }
@media (max-width: 1100px) { .cf-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) {
  .cf-stats { gap: 8px; }
  .cf-stat { padding: 12px; }
  .cf-stat-icon { display: none; }
}

/* Avatar (partials/avatar) */
.cf-avatar { flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; font-weight: 800; letter-spacing: .02em; line-height: 1; user-select: none; }

/* Status tabs (links) */
.cf-tabs { display: inline-flex; flex-wrap: wrap; padding: 3px; background: var(--cf-muted); border-radius: 8px; gap: 2px; }
.cf-tab { height: 30px; padding: 0 12px; border-radius: 6px; font: 600 13px var(--cf-font); color: var(--cf-muted-foreground); text-decoration: none; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
.cf-tab:hover { color: var(--cf-foreground); }
.cf-tab.is-on { background: #fff; color: var(--cf-foreground); box-shadow: var(--cf-shadow-sm); }
.cf-tab .n { font-size: 11px; font-weight: 700; padding: 0 6px; border-radius: 999px; background: rgba(9, 9, 11, .06); line-height: 18px; }
.cf-tab.is-attention .n { background: var(--cf-primary-soft); color: var(--cf-primary); }

kbd.cf-kbd { display: inline-block; min-width: 18px; padding: 1px 5px; border: 1px solid var(--cf-border); border-bottom-width: 2px; border-radius: 4px; background: #fff; font: 700 11px var(--cf-font); color: var(--cf-muted-foreground); text-align: center; }

/* Toasts (cfToast in partials/scripts) - appended to <body>, so global. */
.cf-toasts { position: fixed; right: 20px; bottom: 20px; z-index: 10050; display: flex; flex-direction: column; gap: 10px; width: 380px; max-width: calc(100vw - 32px); pointer-events: none; }
.cf-toast { pointer-events: auto; display: flex; align-items: flex-start; gap: 11px; padding: 13px 12px 13px 14px; background: #fff; border: 1px solid #e4e4e7; border-radius: 10px; box-shadow: 0 10px 30px -8px rgba(9, 9, 11, .22), 0 4px 10px -6px rgba(9, 9, 11, .12); font: 600 13.5px/1.45 'Nunito Sans', ui-sans-serif, system-ui, sans-serif; color: #09090b; animation: cfToastIn .22s cubic-bezier(.16, 1, .3, 1); -webkit-font-smoothing: antialiased; }
.cf-toast.is-leaving { animation: cfToastOut .18s ease-in forwards; }
.cf-toast-icon { width: 22px; height: 22px; border-radius: 999px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #fff; background: #16a34a; margin-top: -1px; }
.cf-toast.is-error .cf-toast-icon { background: #dc2626; }
.cf-toast.is-info .cf-toast-icon { background: #16436E; }
.cf-toast-body { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.cf-toast-action { flex-shrink: 0; align-self: center; height: 28px; padding: 0 10px; border-radius: 6px; background: #09090b; color: #fff; font: 700 12.5px 'Nunito Sans', system-ui, sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
.cf-toast-action:hover { background: #27272a; color: #fff; }
.cf-toast-close { flex-shrink: 0; width: 24px; height: 24px; margin: -3px -3px 0 0; border: 0; border-radius: 6px; background: none; color: #a1a1aa; cursor: pointer; font-size: 12px; }
.cf-toast-close:hover { background: #f4f4f5; color: #09090b; }
@keyframes cfToastIn { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
@keyframes cfToastOut { to { opacity: 0; transform: translateX(24px); } }
@media (max-width: 640px) { .cf-toasts { right: 16px; left: 16px; bottom: 16px; width: auto; } }
@media (prefers-reduced-motion: reduce) { .cf-toast, .cf-toast.is-leaving { animation: none; } }

/* Empty state */
.cf-empty { padding: 64px 20px; text-align: center; }
.cf-empty-icon { width: 46px; height: 46px; margin: 0 auto 16px; border-radius: var(--cf-radius); border: 1px solid var(--cf-border); background: var(--cf-muted); color: var(--cf-muted-foreground); display: flex; align-items: center; justify-content: center; font-size: 18px; }
.cf-empty-title { font-size: 15px; font-weight: 700; margin-bottom: 5px; }
.cf-empty-sub { max-width: 400px; margin: 0 auto; font-size: 13.5px; color: var(--cf-muted-foreground); line-height: 1.55; }
.cf-empty .cf-btn { margin-top: 18px; }

@media (max-width: 768px) {
  .main-content-inner.pt-tight-padding { padding-left: 14px; padding-right: 14px; }
  .cf-heading { font-size: 21px; }
}
</style>
