@extends('layouts.admin-sidebar')

@section('title', 'Roles & access · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
<style>
    .ra-page { padding: 6px 28px 40px; }
    .ra-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .ra-title { font: 600 22px/1.2 'Baloo 2'; color: #16436E; }
    .ra-sub { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 3px; }
    .ra-btn { background: #fff; color: #16436E; border: 1px solid #E2DACE; border-radius: 10px; padding: 10px 16px; font: 800 12.5px 'Nunito Sans'; cursor: pointer; white-space: nowrap; }
    .ra-btn:hover { border-color: #C8355F; }
    .ra-btn-primary { background: #16436E; color: #fff; border-color: #16436E; }
    .ra-btn-primary:hover { background: #0F3358; }
    .ra-btn-sm { padding: 6px 12px; font-size: 11.5px; border-radius: 8px; }
    .ra-btn-danger { color: #B3261E; border-color: #EFC7C2; }
    #u-password-mode .ra-btn.on { background: #16436E; color: #fff; border-color: #16436E; }
    .ra-btn-danger:hover { background: #FBE1E1; border-color: #B3261E; }

    .ra-card { background: #fff; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; }
    .ra-card-title { font: 700 16px 'Baloo 2'; color: #16436E; }
    .ra-card-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px; }

    .ra-table { width: 100%; border-collapse: collapse; margin-top: 14px; min-width: 860px; }
    .ra-table th { text-align: left; font: 800 10.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #98897A; padding: 10px 12px; background: #F6F3EE; border-bottom: 1px solid #EBE4DA; }
    .ra-table td { padding: 10px 12px; border-bottom: 1px solid #F3EDE3; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; vertical-align: middle; }
    .ra-table tr:last-child td { border-bottom: none; }
    .ra-user { display: flex; align-items: center; gap: 10px; cursor: pointer; }
    .ra-user:hover .ra-user-name { color: #C8355F; text-decoration: underline; }
    .ra-avatar { width: 32px; height: 32px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font: 800 11px 'Nunito Sans'; flex-shrink: 0; }
    .ra-user-name { font: 800 13px 'Nunito Sans'; color: #16436E; }
    .ra-email { font: 600 12px 'Nunito Sans'; color: #8A7D6C; }
    .ra-select { width: 100%; max-width: 250px; padding: 8px 10px; border: 1px solid #E2DACE; border-radius: 8px; background: #FFFDFA; font: 700 12.5px 'Nunito Sans'; color: #16436E; outline: none; }
    .ra-access { display: inline-block; border: 1px solid #C9D3DE; border-radius: 6px; padding: 2px 7px; font: 800 11px 'Nunito Sans'; color: #16436E; background: #F3F6F9; }
    .ra-status { display: inline-block; border-radius: 7px; padding: 3px 10px; font: 800 11px 'Nunito Sans'; }
    .ra-status.active { background: #E4F6EB; color: #1E8A4C; }
    .ra-status.invited { background: #FDF3B0; color: #7A5C00; }
    .ra-status.suspended { background: #FBE1E1; color: #B3261E; }
    .ra-actions { display: flex; gap: 6px; justify-content: flex-end; }

    .ra-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 16px; margin-top: 18px; }
    .ra-tpl { background: #fff; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
    .ra-tpl-top { display: flex; justify-content: space-between; gap: 10px; }
    .ra-tpl-name { font: 700 15px 'Baloo 2'; color: #16436E; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .ra-tag { font: 800 10px 'Nunito Sans'; border-radius: 6px; padding: 2px 8px; background: #EEF0F2; color: #5A6B7E; }
    .ra-tag.custom { background: #F9E7EC; color: #C8355F; }
    .ra-tpl-count { text-align: right; font: 800 12.5px 'Nunito Sans'; color: #16436E; white-space: nowrap; }
    .ra-tpl-count small { display: block; font: 600 11px 'Nunito Sans'; color: #98897A; }
    .ra-tpl-desc { font: 600 12px 'Nunito Sans'; color: #5A6B7E; line-height: 1.4; min-height: 34px; }
    .ra-section { font: 800 10.5px 'Nunito Sans'; letter-spacing: .05em; text-transform: uppercase; color: #98897A; display: flex; justify-content: space-between; }
    .ra-section span { font-weight: 700; text-transform: none; letter-spacing: 0; }
    .ra-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .ra-chip { border-radius: 7px; padding: 5px 11px; font: 800 11.5px 'Nunito Sans'; border: 1px solid #EBE4DA; background: #F6F3EE; color: #B0A493; cursor: pointer; transition: all .12s ease; }
    .ra-chip.on { background: #E4F6EB; border-color: #BFE9CE; color: #1E7A46; }
    .ra-chip.act.on { background: #F9E7EC; border-color: #F0C2CF; color: #C8355F; }
    .ra-chip.static { cursor: default; }
    .ra-chip:not(.static):hover { border-color: #16436E; }
    .ra-note { font: 600 11px 'Nunito Sans'; color: #98897A; }
    .ra-lock { font: 700 11px 'Nunito Sans'; color: #8A7D6C; }
    .ra-tpl .ra-btn-danger { width: 100%; margin-top: auto; }

    .ra-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(22,67,110,0.25); z-index: 60; align-items: center; justify-content: center; padding: 20px; }
    .ra-modal-overlay.open { display: flex; }
    .ra-modal { background: #FFFDFA; border-radius: 16px; padding: 24px; width: 100%; max-width: 440px; box-shadow: 0 24px 60px rgba(22,67,110,.25); display: flex; flex-direction: column; gap: 14px; max-height: 92vh; overflow: auto; }
    .ra-modal.wide { max-width: 560px; }
    .ra-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .ra-modal-title { font: 700 20px 'Baloo 2'; color: #16436E; }
    .ra-modal-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
    .ra-x { background: #fff; border: 1px solid #E2DACE; border-radius: 8px; width: 30px; height: 30px; cursor: pointer; font: 700 13px 'Nunito Sans'; color: #5A6B7E; }
    .f-label { display: block; font: 800 11px 'Nunito Sans'; letter-spacing: .04em; color: #8A7D6C; text-transform: uppercase; margin-bottom: 6px; }
    .f-input, .f-select { width: 100%; padding: 11px 10px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFDFA; font: 700 13.5px 'Nunito Sans'; color: #16436E; outline: none; box-sizing: border-box; }
    .f-row { display: flex; gap: 10px; }
    .f-row > div { flex: 1; }
    .f-error { display: none; background: #F9E7EC; color: #C8355F; font: 700 12px 'Nunito Sans'; padding: 8px 10px; border-radius: 8px; }
    .ra-toggles { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .ra-toggle { border: 1px solid #E2DACE; border-radius: 9px; padding: 10px 12px; font: 700 12.5px 'Nunito Sans'; color: #16436E; background: #FFFDFA; cursor: pointer; text-align: left; }
    .ra-toggle.on { background: #E4F6EB; border-color: #BFE9CE; color: #1E7A46; }
    .ra-toggle.act.on { background: #F9E7EC; border-color: #F0C2CF; color: #C8355F; }
    .ra-mod-cell { display: flex; gap: 6px; align-items: stretch; min-width: 0; }
    .ra-mod-cell .ra-toggle { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ra-level-select {
        flex: 0 0 86px; border: 1px solid #E2DACE; border-radius: 9px; background: #FFFDFA;
        color: #5A6B7E; font: 700 10.5px 'Nunito Sans'; padding: 0 4px; outline: none; cursor: pointer;
    }
    .ra-level-select:disabled { opacity: .45; cursor: not-allowed; }
    .ra-chip-row { display: flex; align-items: center; gap: 4px; }
    .ra-chip-row .ra-level-select { flex: 0 0 78px; font-size: 10px; padding: 1px 3px; }
    .btn-save { background: #C8355F; border: none; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #fff; cursor: pointer; width: 100%; }
    .btn-cancel { background: #fff; border: 1px solid #E2DACE; border-radius: 10px; padding: 13px 0; font: 800 13.5px 'Nunito Sans'; color: #5A6B7E; cursor: pointer; width: 100%; }
    .toast { position: fixed; bottom: 22px; left: 50%; transform: translateX(-50%); background: #16436E; color: #fff; padding: 11px 18px; border-radius: 10px; font: 800 12.5px 'Nunito Sans'; z-index: 100; display: none; box-shadow: 0 10px 30px rgba(22,67,110,.3); max-width: 90vw; }
</style>

<div class="ra-page" id="ra-root"
     data-users='@json($usersForJs)'
     data-templates='@json($templatesForJs)'
     data-modules='@json($modules)'
     data-levels='@json($levels)'
     data-actions='@json($actions)'
     data-base-roles='@json($baseRoles)'
     data-departments='@json($departments)'
     data-managers='@json($managersForJs)'
     data-department-for-role='@json($departmentForRole)'
     data-can-manage="{{ $canManage ? '1' : '0' }}"
     data-base-url="{{ url('roles-access') }}">

    <div class="ra-head">
        <div>
            <div class="ra-title">Roles &amp; access</div>
            <div class="ra-sub">Eight access levels per the CRM Account Creation Request Form · users hold the permissions, roles are the templates you start from</div>
        </div>
        @if ($canManage)
            <button type="button" class="ra-btn" id="btn-new-template">+ New role template</button>
        @endif
    </div>

    <div class="ra-card">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
            <div>
                <div class="ra-card-title">Users</div>
                <div class="ra-card-sub" id="users-sub"></div>
            </div>
            @if ($canManage)
                <button type="button" class="ra-btn ra-btn-primary" id="btn-add-user">+ Add user</button>
            @endif
        </div>
        <div style="overflow-x:auto;">
            <table class="ra-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role template</th><th>Access</th><th>Status</th><th></th></tr></thead>
                <tbody id="users-body"></tbody>
            </table>
        </div>
    </div>

    <div class="ra-grid" id="templates-grid"></div>
</div>

<!-- New role template -->
<div id="tpl-modal" class="ra-modal-overlay">
    <div class="ra-modal wide">
        <div class="ra-modal-head">
            <div>
                <div class="ra-modal-title">New role template</div>
                <div class="ra-modal-sub">A starting set of modules — per-user access can go beyond it</div>
            </div>
            <button type="button" class="ra-x" data-close="tpl-modal">✕</button>
        </div>
        <div class="f-error" id="tpl-error"></div>
        <div>
            <label class="f-label" for="tpl-name">Role name *</label>
            <input type="text" id="tpl-name" class="f-input" placeholder="e.g. Sales, Insurance Officer, BCBA Supervisor" maxlength="100">
        </div>
        <div>
            <label class="f-label" for="tpl-desc">Description</label>
            <input type="text" id="tpl-desc" class="f-input" placeholder="e.g. Owns enquiries and follow-ups" maxlength="255">
        </div>
        <div>
            <label class="f-label" for="tpl-base">Based on (dashboard &amp; data scope)</label>
            <select id="tpl-base" class="f-select"></select>
        </div>
        <div>
            <label class="f-label">Module access</label>
            <div class="ra-toggles" id="tpl-modules"></div>
        </div>
        <div>
            <label class="f-label">Actions</label>
            <div class="ra-toggles" id="tpl-actions"></div>
        </div>
        <div class="f-row">
            <button type="button" class="btn-save" id="tpl-save">Create role</button>
            <button type="button" class="btn-cancel" data-close="tpl-modal">Cancel</button>
        </div>
    </div>
</div>

<!-- Add user -->
<div id="user-modal" class="ra-modal-overlay">
    <div class="ra-modal wide">
        <div class="ra-modal-head">
            <div>
                <div class="ra-modal-title" id="user-modal-title">Add user</div>
                <div class="ra-modal-sub" id="user-modal-sub">Same details as User Management — the role template sets their access</div>
            </div>
            <button type="button" class="ra-x" data-close="user-modal">✕</button>
        </div>
        <div class="f-error" id="user-error"></div>

        <div class="f-row">
            <div><label class="f-label" for="u-first">First name *</label><input type="text" id="u-first" class="f-input" placeholder="Juan"></div>
            <div><label class="f-label" for="u-middle">Middle name</label><input type="text" id="u-middle" class="f-input" placeholder="Optional"></div>
            <div><label class="f-label" for="u-last">Last name *</label><input type="text" id="u-last" class="f-input" placeholder="Dela Cruz"></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="u-email">Email *</label><input type="email" id="u-email" class="f-input" placeholder="juan@engageclinic.com"></div>
            <div><label class="f-label" for="u-phone">Phone number</label><input type="text" id="u-phone" class="f-input" placeholder="+971 50 000 0000"></div>
        </div>

        <div>
            <label class="f-label" for="u-password" id="u-password-label">Generated password *</label>
            <label id="u-reset-wrap" style="display:none; align-items:center; gap:8px; font:700 12.5px 'Nunito Sans'; color:#2B3A4C; cursor:pointer; margin-bottom:8px;"><input type="checkbox" id="u-reset" style="accent-color:#C8355F;"> Set a new password for this user</label>
            <div id="u-password-row">
                <div style="display:flex; gap:6px; margin-bottom:8px;" id="u-password-mode">
                    <button type="button" class="ra-btn ra-btn-sm" data-mode="auto">Auto-generate</button>
                    <button type="button" class="ra-btn ra-btn-sm" data-mode="custom">Custom</button>
                </div>
                <div style="display:flex; gap:8px;">
                    <input type="text" id="u-password" class="f-input" style="font-family: ui-monospace, Consolas, monospace;" readonly autocomplete="new-password">
                    <button type="button" class="ra-btn" id="u-regen" title="Regenerate">↻</button>
                    <button type="button" class="ra-btn" id="u-copy" title="Copy">Copy</button>
                </div>
            </div>
            <div class="ra-note" style="margin-top:5px;" id="u-password-note">Share this with the user — it isn’t shown again after saving.</div>
        </div>

        <div class="f-row">
            <div><label class="f-label" for="u-template">Role template *</label><select id="u-template" class="f-select"></select></div>
            <div><label class="f-label" for="u-department">Department *</label><select id="u-department" class="f-select"></select></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="u-title">Job title</label><input type="text" id="u-title" class="f-input" placeholder="e.g. RBT, Speech-Language Pathologist"></div>
            <div><label class="f-label" for="u-manager">Manager</label><select id="u-manager" class="f-select"><option value="">No manager</option></select></div>
        </div>
        <div class="f-row">
            <div><label class="f-label" for="u-start">Start date</label><input type="date" id="u-start" class="f-input"></div>
            <div style="display:flex; flex-direction:column; justify-content:flex-end; gap:8px; padding-bottom:4px;">
                <label style="display:flex; align-items:center; gap:8px; font:700 12.5px 'Nunito Sans'; color:#2B3A4C; cursor:pointer;"><input type="checkbox" id="u-active" checked style="accent-color:#C8355F;"> Active account</label>
                <label id="u-invite-wrap" style="display:flex; align-items:center; gap:8px; font:700 12.5px 'Nunito Sans'; color:#2B3A4C; cursor:pointer;"><input type="checkbox" id="u-invite" checked style="accent-color:#C8355F;"> Email a set-your-password link</label>
            </div>
        </div>
        <div><label class="f-label" for="u-notes">Notes</label><textarea id="u-notes" rows="2" class="f-input" style="font-weight:600; resize:vertical;" placeholder="Optional notes about this user"></textarea></div>

        <div class="f-row">
            <button type="button" class="btn-save" id="user-save">Create user</button>
            <button type="button" class="btn-cancel" data-close="user-modal">Cancel</button>
        </div>
    </div>
</div>

<!-- Per-user access -->
<div id="access-modal" class="ra-modal-overlay">
    <div class="ra-modal wide">
        <div class="ra-modal-head">
            <div>
                <div class="ra-modal-title" id="access-title">Access</div>
                <div class="ra-modal-sub" id="access-sub">Grant permissions beyond this user’s role template</div>
            </div>
            <button type="button" class="ra-x" data-close="access-modal">✕</button>
        </div>
        <div class="f-error" id="access-error"></div>
        <div>
            <label class="f-label">Modules</label>
            <div class="ra-toggles" id="access-modules"></div>
        </div>
        <div>
            <label class="f-label">Actions</label>
            <div class="ra-toggles" id="access-actions"></div>
        </div>
        <div class="f-row">
            <button type="button" class="btn-save" id="access-save">Save access</button>
            <button type="button" class="btn-cancel" data-close="access-modal">Cancel</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
(function () {
    const root = document.getElementById('ra-root');
    let USERS = JSON.parse(root.dataset.users);
    let TEMPLATES = JSON.parse(root.dataset.templates);
    const MODULES = JSON.parse(root.dataset.modules);
    const LEVELS = JSON.parse(root.dataset.levels);
    const ACTIONS = JSON.parse(root.dataset.actions);
    const BASE_ROLES = JSON.parse(root.dataset.baseRoles);
    const DEPARTMENTS = JSON.parse(root.dataset.departments);
    const MANAGERS = JSON.parse(root.dataset.managers);
    const DEPARTMENT_FOR_ROLE = JSON.parse(root.dataset.departmentForRole);
    const CAN_MANAGE = root.dataset.canManage === '1';
    const BASE = root.dataset.baseUrl;
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const MODULE_KEYS = Object.keys(MODULES);
    const LEVEL_KEYS = Object.keys(LEVELS);
    const ACTION_KEYS = Object.keys(ACTIONS);
    function levelOptions(current) {
        return LEVEL_KEYS.map(k => `<option value="${k}" ${k === current ? 'selected' : ''}>${esc(LEVELS[k])}</option>`).join('');
    }
    const PALETTE = ['#16436E', '#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];

    function esc(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function color(seed) { let h = 0; for (const ch of String(seed)) h = (h * 31 + ch.charCodeAt(0)) >>> 0; return PALETTE[h % PALETTE.length]; }
    function titleRole(r) { return r.toLowerCase().replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()); }
    let toastTimer;
    function toast(msg) { const el = document.getElementById('toast'); el.textContent = msg; el.style.display = 'block'; clearTimeout(toastTimer); toastTimer = setTimeout(() => el.style.display = 'none', 3500); }
    async function api(url, options = {}) {
        const res = await fetch(url, { ...options, headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}) } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) { const e = new Error(data.message || 'Request failed'); e.errors = data.errors || {}; throw e; }
        return data;
    }
    function showError(id, e) { const box = document.getElementById(id); box.textContent = Object.values(e.errors || {})[0]?.[0] || e.message; box.style.display = 'block'; }
    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => closeModal(b.dataset.close)));
    document.querySelectorAll('.ra-modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }));

    // ---- Users ----
    function renderUsers() {
        const s = { active: 0, invited: 0, suspended: 0 };
        USERS.forEach(u => s[u.status]++);
        document.getElementById('users-sub').textContent =
            `${USERS.length} user${USERS.length === 1 ? '' : 's'} · ${s.active} active · ${s.invited} invited · ${s.suspended} suspended · click a name to grant permissions beyond their role`;

        const body = document.getElementById('users-body');
        body.innerHTML = USERS.map(u => `
            <tr data-id="${esc(u.id)}">
                <td><div class="ra-user" data-act="access"><div class="ra-avatar" style="background:${color(u.id)};">${esc(u.initials)}</div><div><div class="ra-user-name">${esc(u.name)}</div>${u.job_title ? `<div class="ra-email">${esc(u.job_title)}</div>` : ''}</div></div></td>
                <td class="ra-email">${esc(u.email)}</td>
                <td><select class="ra-select" data-act="template" ${CAN_MANAGE ? '' : 'disabled'}>${TEMPLATES.map(t => `<option value="${t.id}" ${t.id === u.template_id ? 'selected' : ''}>${esc(t.name)}</option>`).join('')}</select></td>
                <td><span class="ra-access" title="${esc(u.modules.map(m => MODULES[m]).join(', '))}">${u.access}/${MODULE_KEYS.length}</span></td>
                <td><span class="ra-status ${u.status}">${u.status[0].toUpperCase() + u.status.slice(1)}</span></td>
                <td><div class="ra-actions">
                    <button type="button" class="ra-btn ra-btn-sm" data-act="edit">Edit</button>
                    ${CAN_MANAGE && !u.is_me ? `<button type="button" class="ra-btn ra-btn-sm" data-act="suspend">${u.status === 'suspended' ? 'Reactivate' : 'Suspend'}</button>
                    <button type="button" class="ra-btn ra-btn-sm ra-btn-danger" data-act="remove">Remove</button>` : ''}
                </div></td>
            </tr>`).join('');

        body.querySelectorAll('tr').forEach(tr => {
            const user = USERS.find(u => u.id === tr.dataset.id);
            tr.querySelector('[data-act="access"]').addEventListener('click', () => openAccess(user));
            tr.querySelector('[data-act="edit"]').addEventListener('click', () => openUserModal(user));
            tr.querySelector('[data-act="template"]').addEventListener('change', async e => {
                try {
                    const r = await api(`${BASE}/users/${user.id}/template`, { method: 'PUT', body: JSON.stringify({ role_template_id: e.target.value }) });
                    replaceUser(r.user); toast(r.message); refreshTemplateCounts();
                } catch (err) { toast(err.message); e.target.value = user.template_id; }
            });
            tr.querySelector('[data-act="suspend"]')?.addEventListener('click', async () => {
                try { const r = await api(`${BASE}/users/${user.id}/suspend`, { method: 'PUT' }); replaceUser(r.user); toast(r.message); }
                catch (err) { toast(err.message); }
            });
            tr.querySelector('[data-act="remove"]')?.addEventListener('click', async () => {
                if (!confirm(`Remove ${user.name}? They will no longer be able to sign in.`)) return;
                try { const r = await api(`${BASE}/users/${user.id}`, { method: 'DELETE' }); USERS = USERS.filter(u => u.id !== user.id); renderUsers(); toast(r.message); refreshTemplateCounts(); }
                catch (err) { toast(err.message); }
            });
        });
    }
    function replaceUser(u) { const i = USERS.findIndex(x => x.id === u.id); if (i >= 0) USERS[i] = u; else USERS.push(u); renderUsers(); }
    function refreshTemplateCounts() { TEMPLATES.forEach(t => { t.users_count = USERS.filter(u => u.template_id === t.id).length; }); renderTemplates(); }

    // ---- Templates ----
    function chip(key, label, on, cls, editable, level) {
        const btn = `<button type="button" class="ra-chip ${cls} ${on ? 'on' : ''} ${editable ? '' : 'static'}" data-key="${esc(key)}" ${editable ? '' : 'tabindex="-1"'}>${esc(label)}</button>`;
        if (cls !== 'mod') return btn;
        // The level only matters (and is only shown) once the module itself is on.
        const select = on
            ? `<select class="ra-level-select" data-key="${esc(key)}" ${editable ? '' : 'disabled'}>${levelOptions(level || 'full')}</select>`
            : '';
        return `<div class="ra-chip-row">${btn}${select}</div>`;
    }
    function renderTemplates() {
        const grid = document.getElementById('templates-grid');
        grid.innerHTML = TEMPLATES.map(t => {
            const editable = CAN_MANAGE && !t.locked;
            return `
            <div class="ra-tpl" data-id="${t.id}">
                <div class="ra-tpl-top">
                    <div class="ra-tpl-name">${esc(t.name)} <span class="ra-tag ${t.is_system ? '' : 'custom'}">${t.is_system ? 'System template' : 'Custom template'}</span></div>
                    <div class="ra-tpl-count">${t.users_count} user${t.users_count === 1 ? '' : 's'}<small>${t.modules.length} of ${MODULE_KEYS.length} modules</small></div>
                </div>
                <div class="ra-tpl-desc">${esc(t.description || '')}</div>
                <div class="ra-chips" data-group="modules">${MODULE_KEYS.map(k => chip(k, MODULES[k], t.modules.includes(k), 'mod', editable, (t.module_levels || {})[k])).join('')}</div>
                <div class="ra-section">Actions <span>${t.actions.length} of ${ACTION_KEYS.length} actions</span></div>
                <div class="ra-chips" data-group="actions">${ACTION_KEYS.map(k => chip(k, ACTIONS[k], t.actions.includes(k), 'act', editable)).join('')}</div>
                ${t.locked ? '<div class="ra-lock">🔒 Locked — Super Admin always has every module and action</div>' : ''}
                <div class="ra-note">Template only — editing it does not change people already assigned.</div>
                ${CAN_MANAGE && !t.is_system ? '<button type="button" class="ra-btn ra-btn-danger" data-act="delete">Delete role</button>' : ''}
            </div>`;
        }).join('');

        grid.querySelectorAll('.ra-tpl').forEach(card => {
            const t = TEMPLATES.find(x => String(x.id) === card.dataset.id);
            card.querySelectorAll('.ra-chip:not(.static)').forEach(btn => btn.addEventListener('click', async () => {
                const group = btn.closest('.ra-chips').dataset.group;
                const list = [...t[group]];
                const i = list.indexOf(btn.dataset.key);
                if (i >= 0) list.splice(i, 1); else list.push(btn.dataset.key);
                if (group === 'modules' && !list.includes('dashboard')) { toast('Dashboard stays on for every template.'); return; }
                try {
                    const r = await api(`${BASE}/templates/${t.id}`, { method: 'PUT', body: JSON.stringify({ [group]: list }) });
                    Object.assign(t, r.template); renderTemplates(); toast(r.message);
                } catch (err) { toast(err.message); }
            }));
            card.querySelectorAll('.ra-level-select:not(:disabled)').forEach(sel => sel.addEventListener('change', async () => {
                const levels = { ...(t.module_levels || {}), [sel.dataset.key]: sel.value };
                try {
                    const r = await api(`${BASE}/templates/${t.id}`, { method: 'PUT', body: JSON.stringify({ module_levels: levels }) });
                    Object.assign(t, r.template); renderTemplates(); toast(r.message);
                } catch (err) { toast(err.message); }
            }));
            card.querySelector('[data-act="delete"]')?.addEventListener('click', async () => {
                if (!confirm(`Delete “${t.name}”? Anyone on it moves back to the system template for their base role, keeping the access they already hold.`)) return;
                try {
                    const r = await api(`${BASE}/templates/${t.id}`, { method: 'DELETE' });
                    TEMPLATES = TEMPLATES.filter(x => x.id !== t.id);
                    USERS.forEach(u => { if (u.template_id === t.id) { const fb = TEMPLATES.find(x => x.is_system && x.base_role === t.base_role) || TEMPLATES.find(x => x.key === 'basic'); u.template_id = fb?.id ?? null; } });
                    refreshTemplateCounts(); renderUsers(); toast(r.message);
                } catch (err) { toast(err.message); }
            });
        });
    }

    // ---- Toggle groups (modals) ----
    // Modules ('mod') get a level select alongside the toggle - disabled and
    // reset to "Full access" while the module itself is off, since a level
    // means nothing for a module that isn't granted at all. Actions ('act')
    // are still a plain on/off toggle with no level concept.
    function renderToggles(hostId, dict, selected, cls, levels = {}) {
        const host = document.getElementById(hostId);
        if (cls === 'mod') {
            host.innerHTML = Object.entries(dict).map(([k, label]) => {
                const on = selected.includes(k);
                return `<div class="ra-mod-cell">
                    <button type="button" class="ra-toggle mod ${on ? 'on' : ''}" data-key="${esc(k)}">${esc(label)}</button>
                    <select class="ra-level-select" data-key="${esc(k)}" ${on ? '' : 'disabled'}>${levelOptions(levels[k] || 'full')}</select>
                </div>`;
            }).join('');
            host.querySelectorAll('.ra-toggle').forEach(b => b.addEventListener('click', () => {
                const on = b.classList.toggle('on');
                const select = b.closest('.ra-mod-cell').querySelector('.ra-level-select');
                select.disabled = !on;
                if (!on) select.value = 'full';
            }));
        } else {
            host.innerHTML = Object.entries(dict).map(([k, label]) => `<button type="button" class="ra-toggle ${cls} ${selected.includes(k) ? 'on' : ''}" data-key="${esc(k)}">${esc(label)}</button>`).join('');
            host.querySelectorAll('.ra-toggle').forEach(b => b.addEventListener('click', () => b.classList.toggle('on')));
        }
    }
    function selectedKeys(hostId) { return [...document.querySelectorAll(`#${hostId} .ra-toggle.on`)].map(b => b.dataset.key); }
    function selectedLevels(hostId) {
        const levels = {};
        document.querySelectorAll(`#${hostId} .ra-toggle.on`).forEach(b => {
            const select = b.closest('.ra-mod-cell')?.querySelector('.ra-level-select');
            if (select) levels[b.dataset.key] = select.value;
        });
        return levels;
    }

    // New template
    document.getElementById('btn-new-template')?.addEventListener('click', () => {
        document.getElementById('tpl-error').style.display = 'none';
        document.getElementById('tpl-name').value = '';
        document.getElementById('tpl-desc').value = '';
        document.getElementById('tpl-base').innerHTML = BASE_ROLES.map(r => `<option value="${r}" ${r === 'OTHER_STAFF' ? 'selected' : ''}>${esc(titleRole(r))}</option>`).join('');
        renderToggles('tpl-modules', MODULES, ['dashboard'], 'mod');
        renderToggles('tpl-actions', ACTIONS, [], 'act');
        openModal('tpl-modal');
        document.getElementById('tpl-name').focus();
    });
    document.getElementById('tpl-save')?.addEventListener('click', async () => {
        document.getElementById('tpl-error').style.display = 'none';
        try {
            const r = await api(`${BASE}/templates`, { method: 'POST', body: JSON.stringify({
                name: document.getElementById('tpl-name').value.trim(),
                description: document.getElementById('tpl-desc').value.trim() || null,
                base_role: document.getElementById('tpl-base').value,
                modules: selectedKeys('tpl-modules'),
                module_levels: selectedLevels('tpl-modules'),
                actions: selectedKeys('tpl-actions'),
            }) });
            TEMPLATES.push(r.template); renderTemplates(); renderUsers(); closeModal('tpl-modal'); toast(r.message);
        } catch (e) { showError('tpl-error', e); }
    });

    // Add user (mirrors User Management's create form)
    // Letters and numbers only (upper + lower case, at least one of each), no
    // symbols - easier to read out or type from a note.
    function generatePassword() {
        const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789'];
        const all = sets.join('');
        const rand = n => { const a = new Uint32Array(1); crypto.getRandomValues(a); return a[0] % n; };
        const pick = s => s[rand(s.length)];
        const chars = sets.map(pick);
        while (chars.length < 12) chars.push(pick(all));
        for (let i = chars.length - 1; i > 0; i--) { const j = rand(i + 1); [chars[i], chars[j]] = [chars[j], chars[i]]; }
        return chars.join('');
    }
    const tplSelect = document.getElementById('u-template');
    let editingUser = null; // null = Add user, otherwise the user being edited
    let departmentTouched = false;

    // One dialog for Add and Edit - Edit pre-fills every field from the user's
    // record and only changes the password if "Set a new password" is ticked.
    function openUserModal(user = null) {
        editingUser = user;
        // Department is pre-filled from the record when editing, but still
        // follows a template change (same as Add) until it's picked by hand.
        departmentTouched = false;
        const editing = !!user;
        document.getElementById('user-error').style.display = 'none';
        document.getElementById('user-modal-title').textContent = editing ? `Edit ${user.name}` : 'Add user';
        document.getElementById('user-modal-sub').textContent = editing ? 'Update their details — the role template sets their access' : 'Same details as User Management — the role template sets their access';
        document.getElementById('user-save').textContent = editing ? 'Save changes' : 'Create user';

        tplSelect.innerHTML = TEMPLATES.map(t => `<option value="${t.id}" data-role="${esc(t.base_role)}">${esc(t.name)}</option>`).join('');
        document.getElementById('u-department').innerHTML = DEPARTMENTS.map(d => `<option value="${d}">${esc(titleRole(d))}</option>`).join('');
        document.getElementById('u-manager').innerHTML = '<option value="">No manager</option>' + MANAGERS.filter(m => !editing || String(m.id) !== String(user.uid)).map(m => `<option value="${m.id}">${esc(m.name)}</option>`).join('');

        document.getElementById('u-first').value = user?.first_name ?? '';
        document.getElementById('u-middle').value = user?.middle_name ?? '';
        document.getElementById('u-last').value = user?.last_name ?? '';
        document.getElementById('u-email').value = user?.email ?? '';
        document.getElementById('u-phone').value = user?.phone_number ?? '';
        document.getElementById('u-title').value = user?.job_title ?? '';
        document.getElementById('u-notes').value = user?.notes ?? '';
        document.getElementById('u-start').value = user?.start_date ?? new Date().toISOString().slice(0, 10);
        document.getElementById('u-active').checked = editing ? !!user.is_active : true;
        tplSelect.value = editing && user.template_id ? String(user.template_id) : String((TEMPLATES.find(t => t.key === 'basic') || TEMPLATES[0]).id);
        if (editing && user.department) document.getElementById('u-department').value = user.department;
        else syncDepartmentToTemplate();
        document.getElementById('u-manager').value = editing && user.manager_id ? String(user.manager_id) : '';

        // Password block: always generated for Add; opt-in for Edit.
        document.getElementById('u-reset-wrap').style.display = editing ? 'flex' : 'none';
        document.getElementById('u-reset').checked = false;
        document.getElementById('u-password-label').textContent = editing ? 'Password' : 'Password *';
        document.getElementById('u-password-row').style.display = editing ? 'none' : '';
        document.getElementById('u-password-note').style.display = editing ? 'none' : '';
        setPasswordMode('auto');
        document.getElementById('u-invite-wrap').style.display = editing ? 'none' : 'flex';
        document.getElementById('u-invite').checked = !editing;

        openModal('user-modal');
        document.getElementById('u-first').focus();
    }
    // Auto-generate (letters + numbers, shown read-only) or type a custom one.
    function setPasswordMode(mode) {
        const input = document.getElementById('u-password');
        document.querySelectorAll('#u-password-mode .ra-btn').forEach(b => b.classList.toggle('on', b.dataset.mode === mode));
        document.getElementById('u-regen').style.display = mode === 'auto' ? '' : 'none';
        document.getElementById('u-copy').style.display = mode === 'auto' ? '' : 'none';
        input.readOnly = mode === 'auto';
        input.placeholder = mode === 'auto' ? '' : 'Type a password (at least 8 characters)';
        input.value = mode === 'auto' ? generatePassword() : '';
        document.getElementById('u-password-note').textContent = mode === 'auto'
            ? 'Share this with the user — it isn’t shown again after saving.'
            : 'At least 8 characters. Share it with the user — it isn’t shown again after saving.';
        if (mode === 'custom') input.focus();
    }
    document.querySelectorAll('#u-password-mode .ra-btn').forEach(b => b.addEventListener('click', () => setPasswordMode(b.dataset.mode)));
    document.getElementById('u-reset').addEventListener('change', e => {
        document.getElementById('u-password-row').style.display = e.target.checked ? '' : 'none';
        document.getElementById('u-password-note').style.display = e.target.checked ? '' : 'none';
        if (e.target.checked) setPasswordMode('auto');
    });
    document.getElementById('btn-add-user')?.addEventListener('click', () => openUserModal(null));
    // Department follows the template's base role until the admin picks one by hand.
    function syncDepartmentToTemplate() {
        if (departmentTouched) return;
        const role = tplSelect.selectedOptions[0]?.dataset.role;
        if (role && DEPARTMENT_FOR_ROLE[role]) document.getElementById('u-department').value = DEPARTMENT_FOR_ROLE[role];
    }
    tplSelect.addEventListener('change', syncDepartmentToTemplate);
    document.getElementById('u-department').addEventListener('change', () => { departmentTouched = true; });
    document.getElementById('u-regen').addEventListener('click', () => { document.getElementById('u-password').value = generatePassword(); });
    document.getElementById('u-copy').addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(document.getElementById('u-password').value); toast('Password copied.'); }
        catch { document.getElementById('u-password').select(); document.execCommand('copy'); toast('Password copied.'); }
    });
    document.getElementById('user-save')?.addEventListener('click', async () => {
        document.getElementById('user-error').style.display = 'none';
        const editing = !!editingUser;
        const payload = {
            first_name: document.getElementById('u-first').value.trim(),
            middle_name: document.getElementById('u-middle').value.trim() || null,
            last_name: document.getElementById('u-last').value.trim(),
            email: document.getElementById('u-email').value.trim(),
            phone_number: document.getElementById('u-phone').value.trim() || null,
            job_title: document.getElementById('u-title').value.trim() || null,
            role_template_id: tplSelect.value,
            department: document.getElementById('u-department').value,
            manager_id: document.getElementById('u-manager').value || null,
            start_date: document.getElementById('u-start').value || null,
            notes: document.getElementById('u-notes').value.trim() || null,
            is_active: document.getElementById('u-active').checked,
        };
        const pw = document.getElementById('u-password').value;
        const wantsPassword = !editing || document.getElementById('u-reset').checked;
        if (wantsPassword && pw.length < 8) {
            const box = document.getElementById('user-error');
            box.textContent = 'Password must be at least 8 characters.';
            box.style.display = 'block';
            return;
        }
        if (editing) {
            payload.password = wantsPassword ? pw : null;
        } else {
            payload.password = pw;
            payload.send_invite = document.getElementById('u-invite').checked;
        }
        try {
            const r = editing
                ? await api(`${BASE}/users/${editingUser.id}`, { method: 'PUT', body: JSON.stringify(payload) })
                : await api(`${BASE}/users`, { method: 'POST', body: JSON.stringify(payload) });
            replaceUser(r.user); refreshTemplateCounts(); closeModal('user-modal'); toast(r.message);
        } catch (e) { showError('user-error', e); }
    });

    // Per-user access
    let accessUser = null;
    function openAccess(u) {
        accessUser = u;
        document.getElementById('access-error').style.display = 'none';
        document.getElementById('access-title').textContent = `${u.name} — access`;
        const t = TEMPLATES.find(x => x.id === u.template_id);
        document.getElementById('access-sub').textContent = CAN_MANAGE
            ? `Starts from ${t ? t.name : 'their role'} · toggle anything to grant beyond (or trim below) the template`
            : `On ${t ? t.name : 'their role'} · view only`;
        renderToggles('access-modules', MODULES, u.modules, 'mod', u.module_levels || {});
        renderToggles('access-actions', ACTIONS, u.actions, 'act');
        document.getElementById('access-save').style.display = CAN_MANAGE ? '' : 'none';
        if (!CAN_MANAGE) {
            document.querySelectorAll('#access-modal .ra-toggle').forEach(b => b.disabled = true);
            document.querySelectorAll('#access-modal .ra-level-select').forEach(s => s.disabled = true);
        }
        openModal('access-modal');
    }
    document.getElementById('access-save').addEventListener('click', async () => {
        document.getElementById('access-error').style.display = 'none';
        try {
            const r = await api(`${BASE}/users/${accessUser.id}/access`, { method: 'PUT', body: JSON.stringify({ modules: selectedKeys('access-modules'), module_levels: selectedLevels('access-modules'), actions: selectedKeys('access-actions') }) });
            replaceUser(r.user); closeModal('access-modal'); toast(r.message);
        } catch (e) { showError('access-error', e); }
    });

    renderUsers();
    renderTemplates();
})();
</script>
@endsection
