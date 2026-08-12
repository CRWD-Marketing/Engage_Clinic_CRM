@extends('layouts.admin-sidebar')

@section('title', 'Add User · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')

<style>
    .user-form-page {
        --border: #E4E4E7;
        --border-strong: #D4D4D8;
        --muted: #71717A;
        --muted-fg: #A1A1AA;
        --ring: #18181B;
        --radius: 8px;

        font-feature-settings: "tnum" 1, "cv11" 1;
        -webkit-font-smoothing: antialiased;
    }

    .user-form-page h1 {
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .user-form-rule {
        height: 1px;
        background: var(--border);
        position: relative;
    }

    .user-form-rule::after {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        height: 2px;
        width: 32px;
        background: #18181B;
    }

    .user-form-card {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #fff;
    }

    .user-form-section-title {
        font-weight: 700;
        letter-spacing: -0.01em;
        font-size: 0.8125rem;
        text-transform: uppercase;
        color: var(--muted);
        letter-spacing: 0.06em;
    }

    .user-form-label {
        font-weight: 600;
        font-size: 0.8125rem;
        color: #27272A;
        margin-bottom: 6px;
        display: block;
    }

    .user-form-label .required {
        color: #DC2626;
        margin-left: 2px;
    }

    .user-input,
    .user-select {
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #fff;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
        width: 100%;
    }

    .user-input:focus,
    .user-select:focus {
        outline: none;
        border-color: var(--ring);
        box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.08);
    }

    .user-input::placeholder {
        color: var(--muted-fg);
    }

    .user-input.is-invalid,
    .user-select.is-invalid {
        border-color: #DC2626;
    }

    .user-field-error {
        color: #DC2626;
        font-size: 0.75rem;
        margin-top: 4px;
        display: none;
    }

    .user-field-error.is-visible {
        display: block;
    }

    .user-form-btn-primary {
        background: #18181B;
        color: #fff;
        font-weight: 700;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: background-color 0.12s ease, opacity 0.12s ease;
    }

    .user-form-btn-primary:hover {
        background: #27272A;
    }

    .user-form-btn-primary:active {
        transform: scale(0.98);
    }

    .user-form-btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .user-form-btn-ghost {
        color: var(--muted);
        border-radius: 6px;
        font-weight: 600;
        transition: background-color 0.12s ease, color 0.12s ease;
    }

    .user-form-btn-ghost:hover {
        background: #F4F4F5;
        color: #18181B;
    }

    .user-form-alert {
        border-radius: 6px;
        padding: 12px 14px;
        font-size: 0.8125rem;
        font-weight: 600;
        display: none;
    }

    .user-form-alert.is-visible {
        display: flex;
    }

    .user-form-alert.is-error {
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: #B91C1C;
    }

    .user-form-alert.is-success {
        background: #F0FDF4;
        border: 1px solid #BBF7D0;
        color: #15803D;
    }

    .user-checkbox-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-checkbox-row input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #18181B;
    }
</style>

<div class="user-form-page">

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl text-zinc-900">Add User</h1>
            <p class="text-sm mt-1" style="color: var(--muted);">Create a new staff account.</p>
        </div>

        <a href="{{ route('users.index') }}"
           class="user-form-btn-ghost inline-flex items-center gap-2 h-9 px-4 text-sm">
            <i class="fas fa-arrow-left text-[10px]"></i>
            Back to Users
        </a>
    </div>

    <div class="user-form-rule mb-6"></div>

    <div id="formAlert" class="user-form-alert is-error mb-5 items-center gap-2">
        <i class="fas fa-circle-exclamation text-xs"></i>
        <span id="formAlertText">Something went wrong.</span>
    </div>

    <form id="createUserForm" class="user-form-card p-6">
        @csrf

        {{-- Personal Info --}}
        <div class="user-form-section-title mb-4">Personal Information</div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

            <div>
                <label class="user-form-label">First Name<span class="required">*</span></label>
                <input type="text" name="first_name" class="user-input h-9 px-3 text-sm" placeholder="Juan">
                <p class="user-field-error" data-error-for="first_name"></p>
            </div>

            <div>
                <label class="user-form-label">Middle Name</label>
                <input type="text" name="middle_name" class="user-input h-9 px-3 text-sm" placeholder="Optional">
                <p class="user-field-error" data-error-for="middle_name"></p>
            </div>

            <div>
                <label class="user-form-label">Last Name<span class="required">*</span></label>
                <input type="text" name="last_name" class="user-input h-9 px-3 text-sm" placeholder="Dela Cruz">
                <p class="user-field-error" data-error-for="last_name"></p>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            <div>
                <label class="user-form-label">Email<span class="required">*</span></label>
                <input type="email" name="email" class="user-input h-9 px-3 text-sm" placeholder="juan@engageclinic.com">
                <p class="user-field-error" data-error-for="email"></p>
            </div>

            <div>
                <label class="user-form-label">Phone Number</label>
                <input type="text" name="phone_number" class="user-input h-9 px-3 text-sm" placeholder="+63 900 000 0000">
                <p class="user-field-error" data-error-for="phone_number"></p>
            </div>

        </div>

        {{-- Security --}}
        <div class="user-form-section-title mb-4">Security</div>

        <div class="mb-6">
            <label class="user-form-label">Generated Password<span class="required">*</span></label>

            <div class="flex items-center gap-2">
                <input type="text" id="generatedPassword" class="user-input h-9 px-3 text-sm font-mono" readonly>

                <button type="button" id="regenBtn" title="Regenerate"
                        class="user-form-btn-ghost inline-flex items-center justify-center h-9 w-9 flex-shrink-0" style="border: 1px solid var(--border);">
                    <i class="fas fa-rotate text-xs"></i>
                </button>

                <button type="button" id="copyBtn" title="Copy"
                        class="user-form-btn-ghost inline-flex items-center justify-center h-9 w-9 flex-shrink-0" style="border: 1px solid var(--border);">
                    <i class="fas fa-copy text-xs"></i>
                </button>
            </div>

            <p class="text-xs mt-2" style="color: var(--muted);">
                Auto-generated. Copy and share this with the user securely — it won't be shown again after creation.
            </p>

            <p class="user-field-error" data-error-for="password"></p>

            {{-- Submitted alongside the visible field --}}
            <input type="hidden" name="password" id="passwordInput">
            <input type="hidden" name="password_confirmation" id="passwordConfirmInput">
        </div>

        {{-- Role & Assignment --}}
        <div class="user-form-section-title mb-4">Role &amp; Assignment</div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            <div>
                <label class="user-form-label">Department<span class="required">*</span></label>
                <select name="department" class="user-select h-9 px-3 text-sm">
                    <option value="">Select department</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}">{{ str_replace('_', ' ', ucwords(strtolower($dept))) }}</option>
                    @endforeach
                </select>
                <p class="user-field-error" data-error-for="department"></p>
            </div>

            <div>
                <label class="user-form-label">Role<span class="required">*</span></label>
                <select name="role" class="user-select h-9 px-3 text-sm">
                    <option value="">Select role</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}">{{ str_replace('_', ' ', ucwords(strtolower($r))) }}</option>
                    @endforeach
                </select>
                <p class="user-field-error" data-error-for="role"></p>
            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            <div>
                <label class="user-form-label">Manager</label>
                <select name="manager_id" class="user-select h-9 px-3 text-sm">
                    <option value="">No manager</option>
                    @foreach($managers as $manager)
                        <option value="{{ $manager->id }}">{{ $manager->first_name }} {{ $manager->last_name }}</option>
                    @endforeach
                </select>
                <p class="user-field-error" data-error-for="manager_id"></p>
            </div>

            <div>
                <label class="user-form-label">Start Date</label>
                <input type="date" name="start_date" class="user-input h-9 px-3 text-sm">
                <p class="user-field-error" data-error-for="start_date"></p>
            </div>

        </div>

        <div class="mb-6">
            <label class="user-form-label">Notes</label>
            <textarea name="notes" rows="3" class="user-input px-3 py-2 text-sm" placeholder="Optional notes about this user"></textarea>
            <p class="user-field-error" data-error-for="notes"></p>
        </div>

        <div class="user-checkbox-row mb-6">
            <input type="checkbox" name="is_active" id="is_active" checked>
            <label for="is_active" class="text-sm text-zinc-700 font-medium">Active account</label>
        </div>

        <div class="user-form-rule mb-6"></div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('users.index') }}" class="user-form-btn-ghost inline-flex items-center h-9 px-4 text-sm">
                Cancel
            </a>
            <button type="submit" id="submitBtn" class="user-form-btn-primary inline-flex items-center gap-2 h-9 px-5 text-sm">
                <i class="fas fa-plus text-[10px]"></i>
                <span id="submitBtnText">Create User</span>
            </button>
        </div>

    </form>

</div>

<script>
function generatePassword(length = 12) {
    const lower = 'abcdefghijkmnopqrstuvwxyz';
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const digits = '23456789';
    const symbols = '!@#$%^&*';
    const all = lower + upper + digits + symbols;

    const randomChar = (charset) => charset[crypto.getRandomValues(new Uint32Array(1))[0] % charset.length];

    // Guarantee at least one of each required character type
    let chars = [randomChar(lower), randomChar(upper), randomChar(digits), randomChar(symbols)];

    for (let i = chars.length; i < length; i++) {
        chars.push(randomChar(all));
    }

    // Shuffle
    for (let i = chars.length - 1; i > 0; i--) {
        const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }

    return chars.join('');
}

function setGeneratedPassword() {
    const pwd = generatePassword();
    document.getElementById('generatedPassword').value = pwd;
    document.getElementById('passwordInput').value = pwd;
    document.getElementById('passwordConfirmInput').value = pwd;
}

document.addEventListener('DOMContentLoaded', setGeneratedPassword);

document.getElementById('regenBtn').addEventListener('click', setGeneratedPassword);

document.getElementById('copyBtn').addEventListener('click', async function () {
    const field = document.getElementById('generatedPassword');
    try {
        await navigator.clipboard.writeText(field.value);
        const icon = this.querySelector('i');
        icon.classList.remove('fa-copy');
        icon.classList.add('fa-check');
        setTimeout(() => {
            icon.classList.remove('fa-check');
            icon.classList.add('fa-copy');
        }, 1200);
    } catch (err) {
        field.select();
        document.execCommand('copy');
    }
});

document.getElementById('createUserForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const formAlert = document.getElementById('formAlert');
    const formAlertText = document.getElementById('formAlertText');

    // Reset previous errors
    form.querySelectorAll('.user-field-error').forEach(el => {
        el.classList.remove('is-visible');
        el.textContent = '';
    });
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    formAlert.classList.remove('is-visible');

    submitBtn.disabled = true;
    submitBtnText.textContent = 'Creating...';

    const formData = new FormData(form);
    // Checkbox: send explicit boolean
    formData.set('is_active', document.getElementById('is_active').checked ? '1' : '0');

    try {
        const response = await fetch('{{ route('users.store') }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    const errorEl = form.querySelector(`[data-error-for="${field}"]`);
                    if (input) input.classList.add('is-invalid');
                    if (errorEl) {
                        errorEl.textContent = messages[0];
                        errorEl.classList.add('is-visible');
                    }
                });
                formAlertText.textContent = 'Please fix the errors below.';
            } else {
                formAlertText.textContent = data.message || 'Failed to create user.';
            }
            formAlert.classList.add('is-visible');
            submitBtn.disabled = false;
            submitBtnText.textContent = 'Create User';
            return;
        }

        // Success
        window.location.href = '{{ route('users.index') }}';

    } catch (err) {
        formAlertText.textContent = 'Network error. Please try again.';
        formAlert.classList.add('is-visible');
        submitBtn.disabled = false;
        submitBtnText.textContent = 'Create User';
    }
});
</script>

@endsection