@extends('layouts.admin-sidebar')

@section('title', 'Edit ' . $user->full_name . ' · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')

<style>
    .user-edit {
        --border: #E4E4E7;
        --border-strong: #D4D4D8;
        --muted: #71717A;
        --muted-fg: #A1A1AA;
        --ring: #18181B;
        --radius: 8px;

        font-feature-settings: "tnum" 1, "cv11" 1;
        -webkit-font-smoothing: antialiased;
    }

    .user-edit h1 {
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .user-edit-back {
        color: var(--muted);
        font-weight: 600;
        transition: color 0.12s ease;
    }

    .user-edit-back:hover {
        color: #18181B;
    }

    .user-edit-rule {
        height: 1px;
        background: var(--border);
        position: relative;
    }

    .user-edit-rule::after {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        height: 2px;
        width: 32px;
        background: #18181B;
    }

    .user-edit-card {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #fff;
    }

    .user-edit-section-title {
        font-weight: 700;
        letter-spacing: -0.01em;
        color: #18181B;
    }

    .user-edit-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #3F3F46;
    }

    .user-edit-hint {
        font-size: 0.75rem;
        color: var(--muted-fg);
    }

    .user-edit-input,
    .user-edit-select,
    .user-edit-textarea {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #fff;
        font-size: 0.875rem;
        color: #18181B;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .user-edit-input:focus,
    .user-edit-select:focus,
    .user-edit-textarea:focus {
        outline: none;
        border-color: var(--ring);
        box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.08);
    }

    .user-edit-input::placeholder,
    .user-edit-textarea::placeholder {
        color: var(--muted-fg);
    }

    .user-edit-input.has-error,
    .user-edit-select.has-error,
    .user-edit-textarea.has-error {
        border-color: #FCA5A5;
    }

    .user-edit-input.has-error:focus,
    .user-edit-select.has-error:focus,
    .user-edit-textarea.has-error:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.08);
    }

    .user-edit-error {
        font-size: 0.75rem;
        font-weight: 500;
        color: #DC2626;
    }

    .user-edit-toggle-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 10px 14px;
    }

    .user-edit-toggle {
        position: relative;
        width: 36px;
        height: 20px;
        flex-shrink: 0;
    }

    .user-edit-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .user-edit-toggle-track {
        position: absolute;
        inset: 0;
        background: #D4D4D8;
        border-radius: 9999px;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }

    .user-edit-toggle-track::before {
        content: "";
        position: absolute;
        left: 2px;
        top: 2px;
        width: 16px;
        height: 16px;
        background: #fff;
        border-radius: 9999px;
        transition: transform 0.15s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }

    .user-edit-toggle input:checked + .user-edit-toggle-track {
        background: #18181B;
    }

    .user-edit-toggle input:checked + .user-edit-toggle-track::before {
        transform: translateX(16px);
    }

    .user-edit-divider {
        border-top: 1px solid #F4F4F5;
    }

    .user-edit-btn-primary {
        background: #18181B;
        color: #fff;
        font-weight: 700;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: background-color 0.12s ease;
    }

    .user-edit-btn-primary:hover {
        background: #27272A;
    }

    .user-edit-btn-ghost {
        color: var(--muted);
        border: 1px solid var(--border);
        border-radius: 6px;
        font-weight: 600;
        transition: background-color 0.12s ease, color 0.12s ease, border-color 0.12s ease;
    }

    .user-edit-btn-ghost:hover {
        background: #F4F4F5;
        color: #18181B;
        border-color: var(--border-strong);
    }

    .user-edit-sticky-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid var(--border);
    }

    @media (prefers-reduced-motion: reduce) {
        .user-edit-back, .user-edit-input, .user-edit-select, .user-edit-textarea,
        .user-edit-btn-primary, .user-edit-btn-ghost, .user-edit-toggle-track,
        .user-edit-toggle-track::before {
            transition: none;
        }
    }
</style>

<div class="user-edit">

    <a href="{{ route('users.show', $user) }}" class="user-edit-back inline-flex items-center gap-2 text-sm mb-5">
        <i class="fas fa-arrow-left text-[10px]"></i>
        Back to {{ $user->full_name }}
    </a>

    <div class="flex items-center justify-between gap-4 mb-5 flex-wrap">
        <h1 class="text-2xl text-zinc-900">Edit User</h1>
    </div>

    <div class="user-edit-rule mb-6"></div>

    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <div class="lg:col-span-2 flex flex-col gap-4">

                {{-- Personal information --}}
                <div class="user-edit-card px-6 py-5">
                    <h2 class="user-edit-section-title text-sm mb-4 flex items-center gap-2">
                        <i class="fas fa-id-card text-xs" style="color: var(--muted-fg);"></i>
                        Personal Information
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label for="first_name" class="user-edit-label block mb-1.5">First Name</label>
                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                value="{{ old('first_name', $user->first_name) }}"
                                class="user-edit-input h-10 px-3 {{ $errors->has('first_name') ? 'has-error' : '' }}">
                            @error('first_name')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="last_name" class="user-edit-label block mb-1.5">Last Name</label>
                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                value="{{ old('last_name', $user->last_name) }}"
                                class="user-edit-input h-10 px-3 {{ $errors->has('last_name') ? 'has-error' : '' }}">
                            @error('last_name')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="middle_name" class="user-edit-label block mb-1.5">
                                Middle Name <span class="user-edit-hint">(optional)</span>
                            </label>
                            <input
                                type="text"
                                id="middle_name"
                                name="middle_name"
                                value="{{ old('middle_name', $user->middle_name) }}"
                                class="user-edit-input h-10 px-3 {{ $errors->has('middle_name') ? 'has-error' : '' }}">
                            @error('middle_name')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone_number" class="user-edit-label block mb-1.5">
                                Phone Number <span class="user-edit-hint">(optional)</span>
                            </label>
                            <input
                                type="text"
                                id="phone_number"
                                name="phone_number"
                                value="{{ old('phone_number', $user->phone_number) }}"
                                placeholder="(555) 555-0100"
                                class="user-edit-input h-10 px-3 {{ $errors->has('phone_number') ? 'has-error' : '' }}">
                            @error('phone_number')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="user-edit-label block mb-1.5">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                class="user-edit-input h-10 px-3 {{ $errors->has('email') ? 'has-error' : '' }}">
                            @error('email')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <div class="user-edit-divider mt-5 pt-5">
                        <label for="password" class="user-edit-label block mb-1.5">
                            New Password <span class="user-edit-hint">(leave blank to keep current)</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                class="user-edit-input h-10 px-3 {{ $errors->has('password') ? 'has-error' : '' }}">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                placeholder="Confirm new password"
                                class="user-edit-input h-10 px-3">
                        </div>
                        @error('password')
                            <p class="user-edit-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Employment --}}
                <div class="user-edit-card px-6 py-5">
                    <h2 class="user-edit-section-title text-sm mb-4 flex items-center gap-2">
                        <i class="fas fa-briefcase text-xs" style="color: var(--muted-fg);"></i>
                        Employment
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label for="department" class="user-edit-label block mb-1.5">Department</label>
                            <select id="department" name="department" class="user-edit-select h-10 px-3 {{ $errors->has('department') ? 'has-error' : '' }}">
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" @selected(old('department', $user->department) === $dept)>
                                        {{ str_replace('_', ' ', ucwords(strtolower($dept))) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="user-edit-label block mb-1.5">Role</label>
                            <select id="role" name="role" class="user-edit-select h-10 px-3 {{ $errors->has('role') ? 'has-error' : '' }}">
                                @foreach($roles as $r)
                                    <option value="{{ $r }}" @selected(old('role', $user->role) === $r)>
                                        {{ str_replace('_', ' ', ucwords(strtolower($r))) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="manager_id" class="user-edit-label block mb-1.5">
                                Manager <span class="user-edit-hint">(optional)</span>
                            </label>
                            <select id="manager_id" name="manager_id" class="user-edit-select h-10 px-3 {{ $errors->has('manager_id') ? 'has-error' : '' }}">
                                <option value="">No manager</option>
                                @foreach($managers as $manager)
                                    @if($manager->id !== $user->id)
                                        <option value="{{ $manager->id }}" @selected((string) old('manager_id', $user->manager_id) === (string) $manager->id)>
                                            {{ $manager->full_name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('manager_id')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="start_date" class="user-edit-label block mb-1.5">
                                Start Date <span class="user-edit-hint">(optional)</span>
                            </label>
                            <input
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="{{ old('start_date', $user->start_date?->format('Y-m-d')) }}"
                                class="user-edit-input h-10 px-3 {{ $errors->has('start_date') ? 'has-error' : '' }}">
                            @error('start_date')
                                <p class="user-edit-error mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <div class="mt-4">
                        <label for="notes" class="user-edit-label block mb-1.5">
                            Notes <span class="user-edit-hint">(optional)</span>
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="user-edit-textarea px-3 py-2 {{ $errors->has('notes') ? 'has-error' : '' }}">{{ old('notes', $user->notes) }}</textarea>
                        @error('notes')
                            <p class="user-edit-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- Sidebar: account status --}}
            <div class="flex flex-col gap-4">

                <div class="user-edit-card px-6 py-5">
                    <h2 class="user-edit-section-title text-sm mb-4 flex items-center gap-2">
                        <i class="fas fa-toggle-on text-xs" style="color: var(--muted-fg);"></i>
                        Account Status
                    </h2>

                    <label for="is_active" class="user-edit-toggle-wrap cursor-pointer">
                        <span class="user-edit-toggle">
                            <input
                                type="checkbox"
                                id="is_active"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', $user->is_active))>
                            <span class="user-edit-toggle-track"></span>
                        </span>
                        <span class="text-sm font-medium text-zinc-800">Active account</span>
                    </label>

                    <p class="user-edit-hint mt-2">
                        Inactive users can't log in but their record and history are preserved.
                    </p>
                </div>

                <div class="user-edit-card px-6 py-5">
                    <p class="user-edit-hint leading-relaxed">
                        Changes take effect immediately. The user won't be notified automatically —
                        let them know directly if their role or access has changed.
                    </p>
                </div>

            </div>

        </div>

        <div class="user-edit-sticky-footer mt-6 py-4 flex items-center justify-end gap-2">
            <a href="{{ route('users.show', $user) }}" class="user-edit-btn-ghost inline-flex items-center h-9 px-4 text-sm">
                Cancel
            </a>
            <button type="submit" class="user-edit-btn-primary inline-flex items-center gap-2 h-9 px-5 text-sm">
                <i class="fas fa-check text-[10px]"></i>
                Save Changes
            </button>
        </div>

    </form>

</div>

@endsection