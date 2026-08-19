@extends('layouts.admin-sidebar')

@section('title', 'My Profile · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    <style>
        .pr-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 5px; }
        .pr-field-input, .pr-field-select {
            width: 100%; padding: 11px 13px; border: 1px solid #E2DACE; border-radius: 10px;
            background: #F6F3EE; font: 700 13px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;
        }
        .pr-field-input:focus, .pr-field-select:focus { border-color: #C8355F; box-shadow: 0 0 0 3px rgba(200,53,95,0.12); }
        .pr-field-input:disabled { opacity: .65; cursor: not-allowed; }
        .pr-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .pr-btn-save { background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 11px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer; }
        .pr-btn-save:hover { background: #A82348; }
        .pr-btn-save:disabled { opacity: .6; cursor: not-allowed; }
        .pr-btn-cancel { background: #FFFFFF; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 11px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer; }
        .pr-btn-cancel:hover { background: #F6F3EE; }
        .pr-saved { font: 800 12.5px 'Nunito Sans'; color: #2E7D5B; opacity: 0; transition: opacity .2s ease; }
        .pr-saved.show { opacity: 1; }
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
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 20px 22px; display: flex; align-items: center; gap: 16px;">
                    <div style="width: 58px; height: 58px; border-radius: 50%; background: #16436E; color: #fff; display: flex; align-items: center; justify-content: center; font: 600 21px 'Baloo 2'; flex-shrink: 0;">
                        {{ $user->first_name ? strtoupper(substr($user->first_name, 0, 1)) : 'U' }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font: 600 19px 'Baloo 2'; color: #16436E;">{{ $user->full_name }}</div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                            <span style="background: {{ $user->is_active ? '#E3F1E9' : '#F1EDE5' }}; color: {{ $user->is_active ? '#2E7D5B' : '#8A7D6C' }}; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans';">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                            <div style="font: 600 12px 'Nunito Sans'; color: #98897A;">{{ $user->roleLabel() }} · {{ $userModuleCount }} of {{ $totalModules }} modules · access set by your admin</div>
                        </div>
                    </div>
                </div>

                <!-- Personal Details -->
                <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 22px 24px; display: flex; flex-direction: column; gap: 16px;">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Personal details</div>

                    <form id="pr-form">
                        <div class="pr-grid-2col">
                            <div>
                                <div class="pr-field-label">First name</div>
                                <input id="pr-first-name" type="text" class="pr-field-input" value="{{ $user->first_name }}" required>
                            </div>
                            <div>
                                <div class="pr-field-label">Last name</div>
                                <input id="pr-last-name" type="text" class="pr-field-input" value="{{ $user->last_name }}" required>
                            </div>
                        </div>
                        <div class="pr-grid-2col" style="margin-top: 16px;">
                            <div>
                                <div class="pr-field-label">Job title</div>
                                <input id="pr-job-title" type="text" placeholder="e.g. Clinic Manager" class="pr-field-input" value="{{ $user->job_title }}">
                            </div>
                            <div>
                                <div class="pr-field-label">Work email</div>
                                <input id="pr-email" type="email" class="pr-field-input" value="{{ $user->email }}" required>
                            </div>
                        </div>
                        <div class="pr-grid-2col" style="margin-top: 16px;">
                            <div>
                                <div class="pr-field-label">Mobile</div>
                                <input id="pr-phone" type="text" class="pr-field-input" value="{{ $user->phone_number }}">
                            </div>
                            <div>
                                <div class="pr-field-label">Timezone</div>
                                <select id="pr-timezone" class="pr-field-select">
                                    @foreach ($timezones as $tz)
                                        <option value="{{ $tz }}" @selected($user->timezone === $tz)>{{ $tz }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div style="margin-top: 16px;">
                            <div class="pr-field-label">Message signature</div>
                            <input id="pr-signature" type="text" placeholder="e.g. Engage Clinic · Al Wasl Road, Dubai" class="pr-field-input" value="{{ $user->message_signature }}">
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px; border-top: 1px solid #F3EDE3; padding-top: 16px; margin-top: 18px;">
                            <div style="flex: 1; font: 600 11.5px 'Nunito Sans'; color: #A79C8E;">Role, status and module access can only be changed by an admin.</div>
                            <div id="pr-saved" class="pr-saved">Saved ✓</div>
                            <button type="button" id="pr-cancel" class="pr-btn-cancel">Cancel</button>
                            <button type="submit" id="pr-save" class="pr-btn-save">Save changes</button>
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
                saveBtn.textContent = 'Saving…';
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
                    saveBtn.textContent = 'Save changes';

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
                    saveBtn.textContent = 'Save changes';
                    alert('Network error. Please try again.');
                });
            });
        })();
    </script>
    @endpush
@endsection
