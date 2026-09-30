<script>
  function cfOpen(id) {
    const el = document.getElementById(id);
    el.classList.add('open');
    const first = el.querySelector('input:not([type=hidden]), textarea, select');
    if (first) setTimeout(() => first.focus(), 30);
  }
  function cfClose(id) { document.getElementById(id).classList.remove('open'); }

  // ── Dropdown menus ──────────────────────────────────────────────────
  // The open menu is moved to <body> and positioned against the viewport, so
  // no scrolling table, hover transform or overflow on a card can clip or
  // shift it. It goes back into its wrapper when closed.
  let cfMenu = null; // { wrap, menu, btn }

  function cfCloseMenus(refocus = false) {
    if (!cfMenu) return;
    const { wrap, menu, btn } = cfMenu;
    menu.classList.remove('is-open', 'is-up');
    wrap.appendChild(menu);
    wrap.classList.remove('is-open');
    btn.setAttribute('aria-expanded', 'false');
    cfMenu = null;
    if (refocus) btn.focus();
  }

  // Always keeps the whole menu on screen: it opens toward the side with more
  // room, and if it's still taller than that space it scrolls instead of
  // running off the edge (the admin layout locks page scrolling, so anything
  // past the viewport edge would be unreachable).
  function cfPlaceMenu() {
    if (!cfMenu) return;
    const { menu, btn } = cfMenu;
    const gap = 6, margin = 8;
    const r = btn.getBoundingClientRect();
    menu.style.maxHeight = '';
    const full = menu.scrollHeight, w = menu.offsetWidth;
    const below = window.innerHeight - r.bottom - gap - margin;
    const above = r.top - gap - margin;
    const up = full > below && above > below;
    const h = Math.min(full, up ? above : below);
    menu.style.maxHeight = h + 'px';
    menu.classList.toggle('is-up', up);
    menu.style.top = (up ? r.top - gap - h : r.bottom + gap) + 'px';
    menu.style.left = Math.max(margin, Math.min(r.right - w, window.innerWidth - w - margin)) + 'px';
  }

  // One delegated handler for every "delete form" control (menu item, card
  // and row buttons) - the dialog's details travel in data-delete-form.
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-delete-form]');
    if (!trigger) return;
    e.preventDefault();
    let data;
    try { data = JSON.parse(trigger.dataset.deleteForm); } catch (_) { return; }
    cfCloseMenus();
    cfDeleteForm(data);
  });

  function cfToggleMenu(btn) {
    const wrap = btn.closest('[data-cf-menu]');
    const wasOpen = cfMenu && cfMenu.wrap === wrap;
    cfCloseMenus();
    if (wasOpen) return;
    const menu = wrap.querySelector('.cf-menu');
    document.body.appendChild(menu);
    menu.classList.add('is-open');
    wrap.classList.add('is-open');
    btn.setAttribute('aria-expanded', 'true');
    cfMenu = { wrap, menu, btn };
    cfPlaceMenu();
    menu.querySelector('.cf-menu-item:not([aria-disabled=true])')?.focus({ preventScroll: true });
  }

  document.addEventListener('click', (e) => {
    if (!cfMenu) return;
    if (cfMenu.menu.contains(e.target)) {
      // Picking an item closes the menu (after "Copied" has had a moment to show).
      const item = e.target.closest('.cf-menu-item:not([aria-disabled=true])');
      if (item) setTimeout(() => cfCloseMenus(), item.hasAttribute('data-copy') ? 700 : 0);
      return;
    }
    if (!cfMenu.wrap.contains(e.target)) cfCloseMenus();
  });
  window.addEventListener('resize', () => cfCloseMenus());
  document.addEventListener('scroll', (e) => { if (cfMenu && !cfMenu.menu.contains(e.target)) cfCloseMenus(); }, true);

  // Registered before the admin layout's own Escape handler (which collapses
  // the sidebar), so closing a menu or dialog with Escape stops there.
  document.addEventListener('keydown', (e) => {
    if (cfMenu) {
      const items = [...cfMenu.menu.querySelectorAll('.cf-menu-item:not([aria-disabled=true])')];
      const i = items.indexOf(document.activeElement);
      if (e.key === 'Escape') { e.preventDefault(); e.stopImmediatePropagation(); cfCloseMenus(true); return; }
      if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length]?.focus(); return; }
      if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length]?.focus(); return; }
      if (e.key === 'Home') { e.preventDefault(); items[0]?.focus(); return; }
      if (e.key === 'End') { e.preventDefault(); items[items.length - 1]?.focus(); return; }
      if (e.key === 'Tab') { cfCloseMenus(); return; }
    }
    const openDialogs = document.querySelectorAll('.cf-dialog-overlay.open');
    if (e.key === 'Escape' && openDialogs.length) {
      e.stopImmediatePropagation();
      openDialogs.forEach(d => d.classList.remove('open'));
    }
  });

  // ── Confirmation dialog (replaces window.confirm) ───────────────────
  function cfConfirm({ title, message, action, method = 'POST', confirmLabel = 'Confirm', danger = false }) {
    let overlay = document.getElementById('cfConfirmDialog');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'cfConfirmDialog';
      overlay.className = 'cf-dialog-overlay cf-root';
      overlay.innerHTML = `
        <form class="cf-dialog cf-alert-dialog" method="POST" role="alertdialog" aria-modal="true" aria-labelledby="cfConfirmTitle">
          <input type="hidden" name="_token">
          <input type="hidden" name="_method">
          <div class="cf-alert-icon"><i class="fas"></i></div>
          <h2 id="cfConfirmTitle"></h2>
          <p class="cf-dialog-desc"></p>
          <div class="cf-dialog-foot">
            <button type="button" class="cf-btn cf-btn-outline" data-cancel>Cancel</button>
            <button type="submit" class="cf-btn" data-ok></button>
          </div>
        </form>`;
      overlay.addEventListener('click', (e) => { if (e.target === overlay || e.target.closest('[data-cancel]')) overlay.classList.remove('open'); });
      overlay.querySelector('form').addEventListener('submit', (e) => { e.submitter?.setAttribute('disabled', ''); });
      document.body.appendChild(overlay);
    }
    const form = overlay.querySelector('form');
    form.action = action;
    form.querySelector('[name=_token]').value = document.querySelector('meta[name="csrf-token"]').content;
    form.querySelector('[name=_method]').value = method;
    overlay.querySelector('h2').textContent = title;
    overlay.querySelector('.cf-dialog-desc').textContent = message;
    overlay.querySelector('.cf-alert-icon').classList.toggle('is-danger', danger);
    overlay.querySelector('.cf-alert-icon i').className = 'fas ' + (danger ? 'fa-trash' : 'fa-circle-question');
    const ok = overlay.querySelector('[data-ok]');
    ok.textContent = confirmLabel;
    ok.disabled = false;
    ok.className = 'cf-btn ' + (danger ? 'cf-btn-danger' : 'cf-btn-primary');
    overlay.classList.add('open');
    setTimeout(() => overlay.querySelector('[data-cancel]').focus(), 30);
  }

  // ── Delete-form dialog ──────────────────────────────────────────────
  // A form without responses gets a plain confirmation. One with responses
  // spells out what will be lost and asks for the form's name to be typed.
  function cfDeleteForm({ title, action, responses = 0, converted = 0, published = false, unpublishAction = null }) {
    let overlay = document.getElementById('cfDeleteDialog');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'cfDeleteDialog';
      overlay.className = 'cf-dialog-overlay cf-root';
      overlay.innerHTML = `
        <form class="cf-dialog cf-alert-dialog" method="POST" role="alertdialog" aria-modal="true" aria-labelledby="cfDeleteTitle" aria-describedby="cfDeleteDesc">
          <input type="hidden" name="_token">
          <input type="hidden" name="_method" value="DELETE">
          <div class="cf-alert-icon is-danger"><i class="fas fa-trash"></i></div>
          <h2 id="cfDeleteTitle"></h2>
          <p class="cf-dialog-desc" id="cfDeleteDesc"></p>
          <div class="cf-delete-warning" data-warning>
            <div class="cf-delete-warning-title"><i class="fas fa-triangle-exclamation"></i> <span>This also deletes client data</span></div>
            <ul>
              <li data-responses></li>
              <li data-converted></li>
              <li>This can't be undone.</li>
            </ul>
          </div>
          <div class="cf-ack" data-ack-wrap>
            <label class="cf-ack-row">
              <input type="checkbox" id="cfDeleteConfirm" name="confirm_delete_responses" value="1">
              <span data-ack-text></span>
            </label>
            <div class="cf-ack-error" data-ack-error>Tick the box above to confirm, then press Delete form.</div>
          </div>
          <div class="cf-dialog-foot">
            <button type="button" class="cf-btn cf-btn-ghost" data-unpublish style="margin-right:auto"><i class="fas fa-eye-slash"></i> Unpublish instead</button>
            <button type="button" class="cf-btn cf-btn-outline" data-cancel>Cancel</button>
            <button type="submit" class="cf-btn cf-btn-danger" data-ok><i class="fas fa-trash"></i> Delete form</button>
          </div>
        </form>`;
      overlay.addEventListener('click', (e) => { if (e.target === overlay || e.target.closest('[data-cancel]')) overlay.classList.remove('open'); });
      const ack = overlay.querySelector('#cfDeleteConfirm');
      ack.addEventListener('change', () => overlay.querySelector('[data-ack-wrap]').classList.remove('has-error'));
      overlay.querySelector('form').addEventListener('submit', (e) => {
        const ok = overlay.querySelector('[data-ok]');
        // The button always responds: if the acknowledgement is missing, say so instead of doing nothing.
        if (overlay.dataset.needsAck === '1' && !ack.checked) {
          e.preventDefault();
          const wrap = overlay.querySelector('[data-ack-wrap]');
          wrap.classList.remove('has-error'); void wrap.offsetWidth; wrap.classList.add('has-error');
          ack.focus();
          return;
        }
        ok.disabled = true;
        ok.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting…';
      });
      document.body.appendChild(overlay);
    }

    const hasResponses = responses > 0;
    const plural = (n, w) => `${n.toLocaleString()} ${w}${n === 1 ? '' : 's'}`;
    const form = overlay.querySelector('form');
    const ack = overlay.querySelector('#cfDeleteConfirm');
    const ok = overlay.querySelector('[data-ok]');
    overlay.dataset.needsAck = hasResponses ? '1' : '0';
    form.action = action;
    form.querySelector('[name=_token]').value = document.querySelector('meta[name="csrf-token"]').content;
    overlay.querySelector('h2').textContent = `Delete “${title}”?`;
    overlay.querySelector('#cfDeleteDesc').textContent = hasResponses
      ? 'The form, its design and its public link will be permanently removed.'
      : 'The form, its design and its public link will be permanently removed. It has no responses yet.';
    overlay.querySelector('[data-warning]').style.display = hasResponses ? '' : 'none';
    overlay.querySelector('[data-responses]').textContent = `${plural(responses, 'client response')} and any files clients uploaded will be deleted.`;
    const conv = overlay.querySelector('[data-converted]');
    conv.style.display = converted > 0 ? '' : 'none';
    conv.textContent = `${plural(converted, 'lead')} created from these responses will stay in the Leads pipeline.`;
    const ackWrap = overlay.querySelector('[data-ack-wrap]');
    ackWrap.style.display = hasResponses ? '' : 'none';
    ackWrap.classList.remove('has-error');
    overlay.querySelector('[data-ack-text]').textContent = `I understand this permanently deletes ${plural(responses, 'client response')}.`;
    ack.checked = false;
    ok.disabled = false;
    ok.innerHTML = '<i class="fas fa-trash"></i> Delete form';

    const unpub = overlay.querySelector('[data-unpublish]');
    unpub.style.display = hasResponses && published && unpublishAction ? '' : 'none';
    unpub.onclick = () => {
      overlay.classList.remove('open');
      cfConfirm({ title: 'Unpublish this form?', message: `“${title}” will stop accepting responses and its public link will stop working. All ${plural(responses, 'response')} are kept.`, action: unpublishAction, confirmLabel: 'Unpublish' });
    };

    overlay.classList.add('open');
    setTimeout(() => overlay.querySelector('[data-cancel]').focus(), 30);
  }

  // ── Toasts ─────────────────────────────────────────────────────────
  // cfToast('Draft saved.') - bottom-right, auto-dismisses (paused while
  // hovered), with an optional action link: { action: { label, href } }.
  function cfToast(message, { type = 'success', action = null, duration = 4500 } = {}) {
    let host = document.getElementById('cfToasts');
    if (!host) {
      host = document.createElement('div');
      host.id = 'cfToasts';
      host.className = 'cf-toasts';
      host.setAttribute('role', 'region');
      host.setAttribute('aria-label', 'Notifications');
      document.body.appendChild(host);
    }
    const icon = { success: 'fa-check', error: 'fa-exclamation', info: 'fa-info' }[type] || 'fa-check';
    const t = document.createElement('div');
    t.className = 'cf-toast is-' + type;
    t.setAttribute('role', type === 'error' ? 'alert' : 'status');
    t.innerHTML = `<span class="cf-toast-icon"><i class="fas ${icon}"></i></span><div class="cf-toast-body"></div>`
      + (action ? '<a class="cf-toast-action"></a>' : '')
      + '<button type="button" class="cf-toast-close" aria-label="Dismiss"><i class="fas fa-xmark"></i></button>';
    t.querySelector('.cf-toast-body').textContent = message;
    if (action) { const a = t.querySelector('.cf-toast-action'); a.textContent = action.label; a.href = action.href; }
    host.appendChild(t);
    while (host.children.length > 3) host.firstElementChild.remove();

    let timer, left = duration, started;
    const close = () => { clearTimeout(timer); t.classList.add('is-leaving'); setTimeout(() => t.remove(), 180); };
    const run = () => { started = Date.now(); timer = setTimeout(close, left); };
    t.addEventListener('mouseenter', () => { clearTimeout(timer); left -= Date.now() - started; });
    t.addEventListener('mouseleave', run);
    t.querySelector('.cf-toast-close').addEventListener('click', close);
    run();
    return t;
  }
  @if (session('success'))
    document.addEventListener('DOMContentLoaded', () => cfToast(@js(session('success'))));
  @endif

  async function cfCopy(text, btn) {
    try {
      await navigator.clipboard.writeText(text);
    } catch (_) {
      const ta = document.createElement('textarea');
      ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove();
    }
    if (btn) {
      const original = btn.innerHTML;
      btn.innerHTML = btn.classList.contains('cf-btn-icon') ? '<i class="fas fa-check"></i>' : '<i class="fas fa-check"></i> Copied';
      setTimeout(() => { btn.innerHTML = original; }, 1500);
    }
  }
</script>
