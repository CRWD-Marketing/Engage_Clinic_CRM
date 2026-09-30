<style>
/* Public form renderer - shared by /forms/{slug}, the admin Preview page and
   the designer canvas, so what staff design is exactly what clients see.
   Every colour/size comes from --pf-* variables (App\Support\CmsForms\FormDesign). */
.pf-page {
  --pf-primary: #C8355F; --pf-header-bg: #16436E; --pf-header-text: #fff; --pf-page-bg: #F6F3EE; --pf-card-bg: #FFFDFA;
  --pf-field-bg: #fff; --pf-field-border: #DDD4C8; --pf-text: #2B3A4C; --pf-label: #2B3A4C; --pf-section-bg: #16436E; --pf-section-text: #fff;
  --pf-font: 'Nunito Sans', system-ui, sans-serif; --pf-heading-font: 'Baloo 2', 'Nunito Sans', system-ui, sans-serif; --pf-heading-weight: 600; --pf-radius: 10px; --pf-title-size: 28px; --pf-max: 680px; --pf-gap: 18px; --pf-logo-size: 46px; --pf-header-pad: 26px; --pf-subtitle-size: 16px;
  container-type: inline-size;
  min-height: 100vh; background: var(--pf-page-bg); color: var(--pf-text); font-family: var(--pf-font);
  display: flex; flex-direction: column;
}
.pf-page * { box-sizing: border-box; font-family: inherit; }
.pf-page.is-embed { min-height: 0; background: transparent; }
.pf-frame { height: 14px; background: var(--pf-header-bg); box-shadow: inset 0 -4px 0 color-mix(in srgb, var(--pf-header-bg) 70%, #fff); flex-shrink: 0; }
.pf-frame.is-bottom { box-shadow: inset 0 4px 0 color-mix(in srgb, var(--pf-header-bg) 70%, #fff); }
.pf-body { flex: 1; padding: 36px 16px; }
.pf-page.is-embed .pf-body { padding: 8px; }
.pf-shell { max-width: var(--pf-max); margin: 0 auto; }
.pf-card { background: var(--pf-card-bg); border: 1px solid color-mix(in srgb, var(--pf-field-border) 45%, transparent); border-radius: calc(var(--pf-radius) + 6px); padding: 30px 32px 26px; box-shadow: 0 1px 3px rgba(15, 23, 42, .05); }

/* Header: [logo] + heading (kicker / title / subtitle). data-align places
   the heading text, data-logo the logo ('auto' = the style's own default). */
.pf-header { margin-bottom: 20px; }
.pf-logo-wrap { display: flex; min-width: 0; }
.pf-logo { height: var(--pf-logo-size); width: auto; max-width: 100%; display: block; object-fit: contain; }
.pf-heading { min-width: 0; }
.pf-kicker { margin-bottom: 6px; font-size: 12.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--pf-primary); }
.pf-title { margin: 0; font-family: var(--pf-heading-font); font-weight: var(--pf-heading-weight); font-size: var(--pf-title-size); line-height: 1.1; color: var(--pf-header-bg); letter-spacing: -.01em; word-break: break-word; }
.pf-title.is-upper { text-transform: uppercase; letter-spacing: .01em; }
.pf-subtitle { margin: 8px 0 0; font-size: var(--pf-subtitle-size); line-height: 1.5; opacity: .82; white-space: pre-line; }
.pf-header[data-align=center] .pf-heading { text-align: center; }
.pf-header[data-align=right] .pf-heading { text-align: right; }

/* Stacked: logo above the heading, centred unless placed. */
.pf-header--centered .pf-logo-wrap { justify-content: center; margin-bottom: 16px; }
.pf-header--centered[data-logo=left] .pf-logo-wrap { justify-content: flex-start; }
.pf-header--centered[data-logo=right] .pf-logo-wrap { justify-content: flex-end; }

/* Logo + title: side by side, the heading on the side away from the logo. */
.pf-header--split { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.pf-header--split .pf-heading { flex: 1; text-align: right; }
.pf-header--split[data-logo=right] .pf-logo-wrap { order: 2; }
.pf-header--split[data-logo=right] .pf-heading { text-align: left; }

/* Banner: coloured block across the top of the card. */
.pf-header--banner { margin: -30px -32px 22px; padding: var(--pf-header-pad) 32px; background: var(--pf-header-bg); display: flex; flex-direction: column; gap: 14px; border-radius: calc(var(--pf-radius) + 5px) calc(var(--pf-radius) + 5px) 0 0; }
.pf-header--banner .pf-title, .pf-header--banner .pf-subtitle, .pf-header--banner .pf-kicker { color: var(--pf-header-text); }
.pf-header--banner .pf-kicker { opacity: .8; }
.pf-header--banner .pf-logo-wrap.has-plate .pf-logo { background: #fff; padding: 6px 10px; border-radius: 8px; }
.pf-header--banner[data-align=center] .pf-logo-wrap { justify-content: center; }
.pf-header--banner[data-align=right] .pf-logo-wrap { justify-content: flex-end; }
.pf-header--banner[data-logo=left] .pf-logo-wrap { justify-content: flex-start; }
.pf-header--banner[data-logo=center] .pf-logo-wrap { justify-content: center; }
.pf-header--banner[data-logo=right] .pf-logo-wrap { justify-content: flex-end; }
.pf-desc { font-size: 14.5px; line-height: 1.6; opacity: .85; white-space: pre-line; margin: -6px 0 20px; }

/* 12-column grid - items span var(--span) columns */
.pf-grid { display: grid; grid-template-columns: repeat(12, minmax(0, 1fr)); gap: var(--pf-gap) 20px; align-items: start; }
.pf-item { grid-column: span var(--span, 12); min-width: 0; }

.pf-label { display: block; margin-bottom: 6px; color: var(--pf-label); font-weight: 500; font-size: 13.5px; line-height: 1.35; }
.pf-label.is-bold { font-weight: 800; }
.pf-label.size-sm { font-size: 12.5px; }
.pf-label.size-lg { font-size: 16px; }
.pf-req { color: var(--pf-primary); }
.pf-item.is-label-left { display: grid; grid-template-columns: minmax(96px, 34%) minmax(0, 1fr); column-gap: 14px; align-items: center; }
.pf-item.is-label-left > .pf-label { margin: 0; }
.pf-item.is-label-left.is-tall { align-items: start; }
.pf-item.is-label-left.is-tall > .pf-label { padding-top: 10px; }

/* Inputs - three field styles */
.pf-input { width: 100%; padding: 10px 12px; border: 1px solid var(--pf-field-border); border-radius: var(--pf-radius); background: var(--pf-card-bg); color: var(--pf-text); font-size: 14.5px; line-height: 1.4; transition: border-color .12s, box-shadow .12s; }
.pf-page[data-field-style=filled] .pf-input { background: var(--pf-field-bg); }
.pf-page[data-field-style=underline] .pf-input { background: transparent; border-width: 0 0 1.5px; border-radius: 0; padding-left: 2px; padding-right: 2px; }
.pf-input:focus { outline: none; border-color: var(--pf-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--pf-primary) 20%, transparent); }
.pf-page[data-field-style=underline] .pf-input:focus { box-shadow: 0 1.5px 0 0 var(--pf-primary); }
textarea.pf-input { resize: vertical; display: block; }
select.pf-input { appearance: auto; }
.pf-input.pf-file { padding: 8px; }
.pf-help { margin-top: 5px; font-size: 12.5px; opacity: .7; }
.pf-choices { display: grid; grid-template-columns: repeat(var(--cols, 1), minmax(0, 1fr)); gap: 9px 16px; }
.pf-choice, .pf-consent { display: flex; align-items: flex-start; gap: 10px; font-size: 14.5px; cursor: pointer; line-height: 1.4; }
.pf-choice input, .pf-consent input { width: 17px; height: 17px; margin: 1px 0 0; accent-color: var(--pf-primary); flex-shrink: 0; }
.pf-consent { padding: 12px 14px; border: 1px solid var(--pf-field-border); border-radius: var(--pf-radius); background: var(--pf-field-bg); }
.pf-error { display: none; margin-top: 6px; font-size: 12.5px; font-weight: 700; color: #C0392B; }
.pf-item.has-error .pf-error { display: block; }
.pf-item.has-error .pf-input, .pf-item.has-error .pf-consent { border-color: #C0392B; }

/* Layout blocks */
.pf-section { margin-top: 4px; }
.pf-section h2 { margin: 0; font-family: var(--pf-heading-font); font-size: 17px; font-weight: min(var(--pf-heading-weight), 700); line-height: 1.3; }
.pf-section p { margin: 4px 0 0; font-size: 13px; opacity: .8; white-space: pre-line; }
.pf-section--bar { background: var(--pf-section-bg); color: var(--pf-section-text); padding: 9px 14px; border-radius: min(var(--pf-radius), 6px); }
.pf-section--underline { padding-bottom: 8px; border-bottom: 2px solid var(--pf-primary); color: var(--pf-header-bg); }
.pf-section--plain { color: var(--pf-header-bg); }
.pf-section.is-upper h2 { text-transform: uppercase; letter-spacing: .02em; }
.pf-paragraph { font-size: 14.5px; line-height: 1.6; white-space: pre-line; }
.pf-paragraph.is-bold { font-weight: 800; }
.pf-divider { border: 0; border-top: 1px solid var(--pf-field-border); margin: 4px 0; }
[data-align=center] { text-align: center; }
[data-align=right] { text-align: right; }

/* Submit + footer */
.pf-actions { margin-top: calc(var(--pf-gap) + 6px); display: flex; }
.pf-actions[data-align=center] { justify-content: center; }
.pf-actions[data-align=right] { justify-content: flex-end; }
.pf-submit { padding: 13px 28px; border: 0; border-radius: var(--pf-radius); background: var(--pf-primary); color: #fff; font-size: 15.5px; font-weight: 800; cursor: pointer; transition: filter .15s; }
.pf-actions.is-full .pf-submit { width: 100%; }
.pf-submit:hover { filter: brightness(.92); }
.pf-submit[disabled] { opacity: .6; cursor: not-allowed; }
.pf-footer { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-top: 24px; padding-top: 16px; border-top: 1px solid color-mix(in srgb, var(--pf-field-border) 50%, transparent); font-size: 13px; opacity: .85; }
.pf-footer span { display: inline-flex; align-items: center; gap: 7px; }
.pf-footer svg { flex-shrink: 0; }

.pf-banner { padding: 12px 14px; border-radius: var(--pf-radius); margin-bottom: 18px; font-size: 13.5px; font-weight: 700; }
.pf-banner.is-error { background: #FDECEA; color: #A93226; border: 1px solid #F5C6C0; }
.pf-banner.is-preview { background: #FFF7E6; color: #8A5A00; border: 1px solid #F5DFB0; }
.pf-success { text-align: center; padding: 26px 8px 10px; }
.pf-success-icon { width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 999px; background: color-mix(in srgb, var(--pf-primary) 14%, #fff); color: var(--pf-primary); display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; }
.pf-success p { font-size: 15px; line-height: 1.6; white-space: pre-line; margin: 0; }
.pf-honeypot { position: absolute !important; left: -10000px !important; width: 1px; height: 1px; overflow: hidden; }
/* Narrow screens (and the designer's mobile preview) - last, so it wins over the desktop layout rules above. */
@container (max-width: 620px) {
  .pf-item { grid-column: span 12; }
  .pf-item.is-label-left { display: block; }
  .pf-item.is-label-left > .pf-label { margin-bottom: 6px; padding-top: 0 !important; }
  .pf-card { padding: 22px 18px; }
  .pf-header--banner { margin: -22px -18px 20px; padding: calc(var(--pf-header-pad) * .85) 18px; }
  .pf-header--split { flex-direction: column; align-items: flex-start; }
  .pf-header--split .pf-heading { text-align: left; }
  .pf-header--split[data-logo=right] .pf-logo-wrap { order: 0; }
  .pf-logo { max-height: max(40px, calc(var(--pf-logo-size) * .8)); }
}
</style>
