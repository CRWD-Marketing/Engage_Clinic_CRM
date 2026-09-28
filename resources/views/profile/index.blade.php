@extends('layouts.admin-sidebar')

@section('title', 'My Profile · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    <style>
        .pr-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px; }
        .pr-field-label .pr-required { color: #C8355F; margin-left: 2px; }
        .pr-field-wrap { position: relative; }
        .pr-field-input, .pr-field-select {
            width: 100%; padding: 11px 13px; border: 1px solid #E2DACE; border-radius: 10px;
            background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;
            transition: border-color .12s ease, box-shadow .12s ease, background .12s ease;
        }
        .pr-field-input:focus, .pr-field-select:focus { border-color: #C8355F; background: #FFFDFA; box-shadow: 0 0 0 3px rgba(200,53,95,0.12); }
        .pr-field-input:disabled { opacity: .65; cursor: not-allowed; }
        .pr-field-input.pr-has-icon, .pr-field-select.pr-has-icon { padding-left: 36px; }
        .pr-field-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; display: flex; }
        .pr-field-hint { font: 600 11px 'Nunito Sans'; color: #A79C8E; margin-top: 5px; }
        .pr-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .pr-btn-save { display: inline-flex; align-items: center; gap: 7px; background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 11px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer; transition: background .12s ease; }
        .pr-btn-save:hover { background: #A82348; }
        .pr-btn-save:disabled { opacity: .6; cursor: not-allowed; }
        .pr-btn-cancel { background: #FFFFFF; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 11px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer; transition: background .12s ease; }
        .pr-btn-cancel:hover { background: #F6F3EE; }
        .pr-saved { display: inline-flex; align-items: center; gap: 5px; font: 800 12.5px 'Nunito Sans'; color: #2E7D5B; opacity: 0; transition: opacity .2s ease; }
        .pr-saved.show { opacity: 1; }
        .pr-chip { display: inline-flex; align-items: center; gap: 5px; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
        .pr-meta-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; flex-wrap: wrap; }
        .pr-contact-row { display: flex; align-items: center; gap: 14px; margin-top: 9px; flex-wrap: wrap; }
        .pr-contact-item { display: inline-flex; align-items: center; gap: 6px; font: 700 12px 'Nunito Sans'; color: #5A6B7E; }
        .pr-avatar-wrap { position: relative; flex-shrink: 0; }
        .pr-avatar-dot { position: absolute; right: -2px; bottom: -2px; width: 13px; height: 13px; border-radius: 50%; border: 2.5px solid #FFFFFF; }
        @media (max-width: 640px) {
            .pr-grid-2col { grid-template-columns: 1fr; }
        }
    </style>

    <div style="flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px;">

        <!-- Top Bar -->
        <div style="padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA;">
            <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">My profile</div>
            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">Your details — role and permissions are managed by an admin</div>
        </div>

        <!-- Main Content -->
        <div style="flex: 1; overflow-y: auto; padding: 26px 28px; display: flex; justify-content: center;">
            <div style="width: 100%; max-width: 760px; display: flex; flex-direction: column; gap: 16px;">

                <!-- Summary Card -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 20px 22px; display: flex; align-items: center; gap: 18px;">
                    <div class="pr-avatar-wrap">
                        <div style="width: 62px; height: 62px; border-radius: 50%; background: #16436E; color: #fff; display: flex; align-items: center; justify-content: center; font: 600 22px 'Baloo 2'; box-shadow: 0 0 0 3px #F1EBE1;">
                            {{ $user->first_name ? strtoupper(substr($user->first_name, 0, 1)) : 'U' }}
                        </div>
                        <span class="pr-avatar-dot" style="background: {{ $user->is_active ? '#2E9E5B' : '#B0A493' }};" title="{{ $user->is_active ? 'Active' : 'Inactive' }}"></span>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <div style="font: 600 19px 'Baloo 2'; color: #16436E;">{{ $user->full_name }}</div>
                            <span class="pr-chip" style="background: {{ $user->is_active ? '#E3F1E9' : '#F1EDE5' }}; color: {{ $user->is_active ? '#2E7D5B' : '#8A7D6C' }};">
                                <svg width="6" height="6" viewBox="0 0 6 6"><circle cx="3" cy="3" r="3" fill="currentColor"/></svg>
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="pr-meta-row">
                            <span class="pr-chip" style="background: #E9EEF3; color: #16436E;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M12 3l7 3v5.5c0 4.7-3 7.6-7 9-4-1.4-7-4.3-7-9V6l7-3z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ $user->roleLabel() }}
                            </span>
                            <span class="pr-chip" style="background: #F3EDE3; color: #5A6B7E;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="10" height="18" stroke="currentColor" stroke-width="1.8"/><rect x="14" y="8" width="6" height="13" stroke="currentColor" stroke-width="1.8"/></svg>
                                {{ str_replace('_', ' ', ucwords(strtolower($user->department))) }}
                            </span>
                            <span class="pr-chip" style="background: #F3EDE3; color: #5A6B7E;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><rect x="3.5" y="3.5" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/></svg>
                                {{ $userModuleCount }} of {{ $totalModules }} modules
                            </span>
                        </div>
                        <div class="pr-contact-row">
                            <span class="pr-contact-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M3 6.5h18v11a1 1 0 01-1 1H4a1 1 0 01-1-1v-11z" stroke="#B0A493" stroke-width="1.6"/><path d="M3.5 7l8 6.5 8-6.5" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ $user->email }}
                            </span>
                            @if ($user->phone_number)
                                <span class="pr-contact-item">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M6.6 10.8a15.9 15.9 0 006.6 6.6l2.2-2.2a1.4 1.4 0 011.4-.3 9 9 0 002.8.5 1.4 1.4 0 011.4 1.4V20a1.4 1.4 0 01-1.4 1.4A17.4 17.4 0 013 4.4 1.4 1.4 0 014.4 3h3.2A1.4 1.4 0 019 4.4a9 9 0 00.5 2.8 1.4 1.4 0 01-.3 1.4z" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    {{ $user->phone_number }}
                                </span>
                            @endif
                            @if ($user->last_login_at)
                                <span class="pr-contact-item">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="#B0A493" stroke-width="1.6"/><path d="M12 7.5V12l3.2 2" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    Active {{ $user->last_login_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #A79C8E; margin-top: 8px;">Role, department and module access are managed by your admin.</div>
                    </div>
                </div>

                <!-- Personal Details -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 22px 24px; display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Personal details</div>
                        <div style="font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 2px;">These appear across the app under your name.</div>
                    </div>

                    <form id="pr-form">
                        <div class="pr-grid-2col">
                            <div>
                                <div class="pr-field-label">First name<span class="pr-required">*</span></div>
                                <input id="pr-first-name" type="text" class="pr-field-input" value="{{ $user->first_name }}" required>
                            </div>
                            <div>
                                <div class="pr-field-label">Last name<span class="pr-required">*</span></div>
                                <input id="pr-last-name" type="text" class="pr-field-input" value="{{ $user->last_name }}" required>
                            </div>
                        </div>
                        <div class="pr-grid-2col" style="margin-top: 16px;">
                            <div>
                                <div class="pr-field-label">Job title</div>
                                <div class="pr-field-wrap">
                                    <span class="pr-field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="7.5" width="18" height="12" rx="2" stroke="#B0A493" stroke-width="1.6"/><path d="M8.5 7.5V6a2 2 0 012-2h3a2 2 0 012 2v1.5" stroke="#B0A493" stroke-width="1.6"/><path d="M3 13h18" stroke="#B0A493" stroke-width="1.6"/></svg></span>
                                    <input id="pr-job-title" type="text" placeholder="e.g. Clinic Manager" class="pr-field-input pr-has-icon" value="{{ $user->job_title }}">
                                </div>
                            </div>
                            <div>
                                <div class="pr-field-label">Work email<span class="pr-required">*</span></div>
                                <div class="pr-field-wrap">
                                    <span class="pr-field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M3 6.5h18v11a1 1 0 01-1 1H4a1 1 0 01-1-1v-11z" stroke="#B0A493" stroke-width="1.6"/><path d="M3.5 7l8 6.5 8-6.5" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <input id="pr-email" type="email" class="pr-field-input pr-has-icon" value="{{ $user->email }}" required>
                                </div>
                            </div>
                        </div>
                        <div class="pr-grid-2col" style="margin-top: 16px;">
                            <div>
                                <div class="pr-field-label">Mobile</div>
                                <div class="pr-field-wrap">
                                    <span class="pr-field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6.6 10.8a15.9 15.9 0 006.6 6.6l2.2-2.2a1.4 1.4 0 011.4-.3 9 9 0 002.8.5 1.4 1.4 0 011.4 1.4V20a1.4 1.4 0 01-1.4 1.4A17.4 17.4 0 013 4.4 1.4 1.4 0 014.4 3h3.2A1.4 1.4 0 019 4.4a9 9 0 00.5 2.8 1.4 1.4 0 01-.3 1.4z" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                    <input id="pr-phone" type="text" class="pr-field-input pr-has-icon" value="{{ $user->phone_number }}">
                                </div>
                            </div>
                            <div>
                                <div class="pr-field-label">Timezone</div>
                                <div class="pr-field-wrap">
                                    <span class="pr-field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="#B0A493" stroke-width="1.6"/><path d="M12 3.5c2.4 2.4 3.6 5.4 3.6 8.5s-1.2 6.1-3.6 8.5M12 3.5c-2.4 2.4-3.6 5.4-3.6 8.5s1.2 6.1 3.6 8.5M3.8 12h16.4" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                                    <select id="pr-timezone" class="pr-field-select pr-has-icon">
                                        @foreach ($timezones as $tz)
                                            <option value="{{ $tz }}" @selected($user->timezone === $tz)>{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div style="margin-top: 16px;">
                            <div class="pr-field-label">Message signature</div>
                            <div class="pr-field-wrap">
                                <span class="pr-field-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M4 16.5V20h3.5L18 9.5l-3.5-3.5L4 16.5z" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M13 7.5L16.5 11" stroke="#B0A493" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                                <input id="pr-signature" type="text" placeholder="e.g. Engage Clinic · Al Wasl Road, Dubai" class="pr-field-input pr-has-icon" value="{{ $user->message_signature }}">
                            </div>
                            <div class="pr-field-hint">Optional line with your name, title, and clinic contact info.</div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px; border-top: 1px solid #F3EDE3; padding-top: 16px; margin-top: 18px;">
                            <div style="flex: 1; font: 600 11.5px 'Nunito Sans'; color: #A79C8E;">Role, status and module access can only be changed by an admin.</div>
                            <div id="pr-saved" class="pr-saved">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M4 12.5l4.5 4.5L20 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Saved
                            </div>
                            <button type="button" id="pr-cancel" class="pr-btn-cancel">Cancel</button>
                            <button type="submit" id="pr-save" class="pr-btn-save">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M4 12.5l4.5 4.5L20 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span id="pr-save-label">Save changes</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const form = document.getElementById('pr-form');
            const saveBtn = document.getElementById('pr-save');
            const saveBtnLabel = document.getElementById('pr-save-label');
            const cancelBtn = document.getElementById('pr-cancel');
            const savedLabel = document.getElementById('pr-saved');
            const updateUrl = @json(route('profile.update'));

            const fields = {
                first_name: document.getElementById('pr-first-name'),
                last_name: document.getElementById('pr-last-name'),
                job_title: document.getElementById('pr-job-title'),
                email: document.getElementById('pr-email'),
                phone_number: document.getElementById('pr-phone'),
                timezone: document.getElementById('pr-timezone'),
                message_signature: document.getElementById('pr-signature'),
            };

            const initialValues = {};
            Object.keys(fields).forEach(key => { initialValues[key] = fields[key].value; });

            function csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content;
            }

            cancelBtn.addEventListener('click', function () {
                Object.keys(fields).forEach(key => { fields[key].value = initialValues[key]; });
                savedLabel.classList.remove('show');
            });

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const payload = {};
                Object.keys(fields).forEach(key => { payload[key] = fields[key].value; });

                saveBtn.disabled = true;
                saveBtnLabel.textContent = 'Saving…';
                savedLabel.classList.remove('show');

                fetch(updateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json())
                .then(data => {
                    saveBtn.disabled = false;
                    saveBtnLabel.textContent = 'Save changes';

                    if (data.success) {
                        Object.keys(fields).forEach(key => { initialValues[key] = fields[key].value; });
                        savedLabel.classList.add('show');
                        setTimeout(() => savedLabel.classList.remove('show'), 2500);
                    } else {
                        alert('Could not save: ' + (data.errors ? Object.values(data.errors).flat().join(', ') : 'unknown error'));
                    }
                })
                .catch(() => {
                    saveBtn.disabled = false;
                    saveBtnLabel.textContent = 'Save changes';
                    alert('Network error. Please try again.');
                });
            });
        })();
    </script>
    @endpush
@endsection
