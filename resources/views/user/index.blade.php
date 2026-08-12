@extends('layouts.admin-sidebar')

@section('title', 'Users Management · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')

<style>
    .users-page {
        --border: #E4E4E7;
        --border-strong: #D4D4D8;
        --muted: #71717A;
        --muted-fg: #A1A1AA;
        --ring: #18181B;
        --radius: 8px;

        font-feature-settings: "tnum" 1, "cv11" 1;
        -webkit-font-smoothing: antialiased;
    }

    .users-page h1 {
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .users-rule {
        height: 1px;
        background: linear-gradient(to right, var(--border) 0%, var(--border) 100%);
        position: relative;
    }

    .users-rule::after {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        height: 2px;
        width: 32px;
        background: #18181B;
    }

    .users-card {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #fff;
    }

    .users-stat {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #fff;
        transition: border-color 0.15s ease;
    }

    .users-stat:hover {
        border-color: var(--border-strong);
    }

    .users-stat-label {
        font-weight: 700;
        letter-spacing: 0.04em;
    }

    .users-stat-value {
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }

    .users-table thead th {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        color: var(--muted);
        border-bottom: 1px solid var(--border);
    }

    .users-table tbody tr {
        border-bottom: 1px solid #F4F4F5;
        transition: background-color 0.12s ease;
    }

    .users-table tbody tr:last-child {
        border-bottom: none;
    }

    .users-table tbody tr:hover {
        background-color: #FAFAFA;
    }

    .users-name {
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    /* ============================================
       ENHANCED ICON ACTIONS
       ============================================ */
    .users-action-group {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 2px;
        padding: 2px 0;
    }

    .users-action-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        color: var(--muted-fg);
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        font-size: 0.8125rem;
        -webkit-tap-highlight-color: transparent;
    }

    .users-action-btn i {
        font-size: 0.8125rem;
        transition: transform 0.15s ease;
    }

    /* View button */
    .users-action-btn.view-btn {
        color: #3B82F6;
    }
    .users-action-btn.view-btn:hover {
        background: #EFF6FF;
        color: #2563EB;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.10);
    }
    .users-action-btn.view-btn:active {
        transform: scale(0.92);
    }

    /* Edit button */
    .users-action-btn.edit-btn {
        color: #8B5CF6;
    }
    .users-action-btn.edit-btn:hover {
        background: #F5F3FF;
        color: #7C3AED;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.10);
    }
    .users-action-btn.edit-btn:active {
        transform: scale(0.92);
    }

    /* Delete button */
    .users-action-btn.delete-btn {
        color: #EF4444;
    }
    .users-action-btn.delete-btn:hover {
        background: #FEF2F2;
        color: #DC2626;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.10);
    }
    .users-action-btn.delete-btn:active {
        transform: scale(0.92);
    }

    /* Tooltip style (optional, clean) */
    .users-action-btn::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%) scale(0.9);
        background: #18181B;
        color: #fff;
        font-size: 0.625rem;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 6px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.12s ease, transform 0.12s ease;
        letter-spacing: 0.03em;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }

    .users-action-btn:hover::after {
        opacity: 1;
        transform: translateX(-50%) scale(1);
    }

    /* Mobile / touch friendly */
    @media (max-width: 640px) {
        .users-action-btn {
            width: 40px;
            height: 40px;
        }
        .users-action-btn i {
            font-size: 1rem;
        }
        .users-action-btn::after {
            display: none;
        }
    }

    .users-input,
    .users-select {
        border: 1px solid var(--border);
        border-radius: 6px;
        background: #fff;
        transition: box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .users-input:focus,
    .users-select:focus {
        outline: none;
        border-color: var(--ring);
        box-shadow: 0 0 0 3px rgba(24, 24, 27, 0.08);
    }

    .users-input::placeholder {
        color: var(--muted-fg);
    }

    .users-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .users-badge.is-active {
        background: #F0FDF4;
        border-color: #DCFCE7;
        color: #15803D;
    }

    .users-badge.is-inactive {
        background: #FAFAFA;
        border-color: var(--border);
        color: var(--muted);
    }

    .users-dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .users-dot.is-active {
        background: #22C55E;
    }

    .users-dot.is-inactive {
        background: #A1A1AA;
    }

    .users-btn-primary {
        background: #18181B;
        color: #fff;
        font-weight: 700;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: background-color 0.12s ease;
    }

    .users-btn-primary:hover {
        background: #27272A;
    }

    .users-btn-primary:active {
        transform: scale(0.98);
    }

    .users-btn-ghost {
        color: var(--muted);
        border-radius: 6px;
        font-weight: 600;
        transition: background-color 0.12s ease, color 0.12s ease;
    }

    .users-btn-ghost:hover {
        background: #F4F4F5;
        color: #18181B;
    }

    .users-count-pill {
        font-weight: 700;
        letter-spacing: 0.05em;
    }

    .users-pagination {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .users-page-link {
        min-width: 32px;
        height: 32px;
        border-radius: 6px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--muted);
        transition: background-color 0.12s ease, color 0.12s ease, border-color 0.12s ease;
        border: 1px solid transparent;
    }

    .users-page-link:hover {
        background: #F4F4F5;
        color: #18181B;
    }

    .users-page-link.is-active {
        background: #18181B;
        color: #fff;
        border-color: #18181B;
    }

    .users-page-link.is-disabled {
        color: #D4D4D8;
        pointer-events: none;
    }

    .users-scroll::-webkit-scrollbar {
        height: 6px;
    }

    .users-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .users-scroll::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 9999px;
    }

    .users-scroll::-webkit-scrollbar-thumb:hover {
        background: var(--border-strong);
    }

    .users-empty-icon {
        color: #E4E4E7;
    }

    .users-empty-title {
        font-weight: 700;
    }

    @media (prefers-reduced-motion: reduce) {
        .users-stat, .users-table tbody tr, .users-action-btn,
        .users-input, .users-select, .users-btn-primary, .users-page-link {
            transition: none;
        }
        .users-action-btn::after {
            transition: none;
        }
    }

    /* ============================================
       CLEAN CENTERED SWEETALERT STYLES
       ============================================ */
    .swal2-popup.swal2-modal {
        border-radius: 16px !important;
        padding: 2rem 2.5rem !important;
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.08), 0 12px 32px rgba(0, 0, 0, 0.04) !important;
        background: #ffffff !important;
        width: 400px !important;
        max-width: 90vw !important;
        text-align: center !important;
    }

    .swal2-popup .swal2-icon {
        margin: 0 auto 1rem auto !important;
        border: none !important;
        width: 64px !important;
        height: 64px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .swal2-popup .swal2-icon-content {
        font-size: 2.5rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .swal2-popup .swal2-icon.swal2-warning {
        color: #F59E0B !important;
        background: #FFFBEB !important;
        border-radius: 50% !important;
        padding: 0 !important;
    }

    .swal2-popup .swal2-icon.swal2-success {
        color: #10B981 !important;
        background: #F0FDF4 !important;
        border-radius: 50% !important;
        padding: 0 !important;
    }

    .swal2-popup .swal2-icon.swal2-success .swal2-success-ring {
        border: none !important;
    }

    .swal2-popup .swal2-icon.swal2-error {
        color: #EF4444 !important;
        background: #FEF2F2 !important;
        border-radius: 50% !important;
        padding: 0 !important;
    }

    .swal2-popup .swal2-icon.swal2-error .swal2-x-mark {
        color: #EF4444 !important;
    }

    .swal2-popup .swal2-icon.swal2-info {
        color: #3B82F6 !important;
        background: #EFF6FF !important;
        border-radius: 50% !important;
        padding: 0 !important;
    }

    .swal2-popup .swal2-title {
        font-size: 1.125rem !important;
        font-weight: 700 !important;
        color: #18181B !important;
        padding: 0 0 0.5rem 0 !important;
        margin: 0 !important;
        letter-spacing: -0.01em !important;
        text-align: center !important;
        line-height: 1.4 !important;
    }

    .swal2-popup .swal2-html-container {
        font-size: 0.875rem !important;
        color: #71717A !important;
        font-weight: 400 !important;
        line-height: 1.6 !important;
        padding: 0 !important;
        margin: 0 !important;
        text-align: center !important;
    }

    .swal2-popup .swal2-actions {
        margin: 1.5rem 0 0 0 !important;
        padding: 0 !important;
        gap: 0.75rem !important;
        justify-content: center !important;
        width: 100% !important;
    }

    .swal2-popup .swal2-confirm {
        border-radius: 10px !important;
        font-weight: 600 !important;
        font-size: 0.8125rem !important;
        padding: 0.625rem 1.75rem !important;
        transition: all 0.15s ease !important;
        border: none !important;
        letter-spacing: 0.01em !important;
        min-width: 100px !important;
        box-shadow: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .swal2-popup .swal2-confirm:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
    }

    .swal2-popup .swal2-confirm:active {
        transform: scale(0.97) !important;
    }

    .swal2-popup .swal2-cancel {
        border-radius: 10px !important;
        font-weight: 500 !important;
        font-size: 0.8125rem !important;
        padding: 0.625rem 1.75rem !important;
        background: #F4F4F5 !important;
        color: #18181B !important;
        border: 1px solid #E4E4E7 !important;
        transition: all 0.15s ease !important;
        letter-spacing: 0.01em !important;
        min-width: 100px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .swal2-popup .swal2-cancel:hover {
        background: #E8E8EA !important;
        border-color: #D4D4D8 !important;
    }

    .swal2-popup .swal2-cancel:active {
        transform: scale(0.97) !important;
    }

    .swal2-toast {
        border-radius: 12px !important;
        padding: 0.75rem 1.25rem !important;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06), 0 2px 8px rgba(0, 0, 0, 0.04) !important;
    }

    .swal2-toast .swal2-title {
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #18181B !important;
    }

    .swal2-toast .swal2-icon {
        margin: 0 0.75rem 0 0 !important;
        width: 24px !important;
        height: 24px !important;
    }

    .swal2-toast .swal2-icon-content {
        font-size: 1rem !important;
    }

    .swal2-toast .swal2-timer-progress-bar {
        background: #10B981 !important;
        opacity: 0.15 !important;
        height: 2px !important;
    }

    .swal2-toast .swal2-icon.swal2-success {
        background: #F0FDF4 !important;
        border-radius: 50% !important;
        padding: 2px !important;
    }

    .swal2-toast .swal2-icon.swal2-error {
        background: #FEF2F2 !important;
        border-radius: 50% !important;
        padding: 2px !important;
    }

    .swal2-toast .swal2-icon.swal2-warning {
        background: #FFFBEB !important;
        border-radius: 50% !important;
        padding: 2px !important;
    }

    .swal2-toast .swal2-icon.swal2-info {
        background: #EFF6FF !important;
        border-radius: 50% !important;
        padding: 2px !important;
    }
</style>

<div class="users-page">

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl text-zinc-900">
            User Management
        </h1>
    </div>

    <a href="{{ route('users.create') }}"
       class="users-btn-primary inline-flex items-center gap-2 h-9 px-4 text-sm">
        <i class="fas fa-plus text-[10px]"></i>
        Add User
    </a>
</div>

<div class="users-rule mb-6"></div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

    <div class="users-stat px-5 py-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="users-stat-label text-xs uppercase" style="color: var(--muted);">Total Users</span>
            <i class="fas fa-users text-[11px]" style="color: var(--muted-fg);"></i>
        </div>
        <p class="users-stat-value text-2xl text-zinc-900">{{ $stats['total'] }}</p>
    </div>

    <div class="users-stat px-5 py-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="users-stat-label text-xs uppercase" style="color: var(--muted);">Active</span>
            <span class="users-dot is-active"></span>
        </div>
        <p class="users-stat-value text-2xl text-zinc-900">{{ $stats['active'] }}</p>
    </div>

    <div class="users-stat px-5 py-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="users-stat-label text-xs uppercase" style="color: var(--muted);">Inactive</span>
            <span class="users-dot is-inactive"></span>
        </div>
        <p class="users-stat-value text-2xl text-zinc-900">{{ $stats['inactive'] }}</p>
    </div>

    <div class="users-stat px-5 py-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="users-stat-label text-xs uppercase" style="color: var(--muted);">Departments</span>
            <i class="fas fa-building text-[11px]" style="color: var(--muted-fg);"></i>
        </div>
        <p class="users-stat-value text-2xl text-zinc-900">{{ $stats['departments'] }}</p>
    </div>

</div>

<div class="users-card">

    <form method="GET" action="{{ route('users.index') }}"
          class="px-5 py-4 flex flex-wrap items-center gap-3" style="border-bottom: 1px solid var(--border);">

        <div class="relative flex-1 min-w-[200px] max-w-xs">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-xs" style="color: var(--muted-fg);"></i>
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search users..."
                class="users-input w-full h-9 pl-9 pr-3 text-sm">
        </div>

        <select name="department" class="users-select h-9 px-3 text-sm" style="color: {{ request('department') ? '#18181B' : 'var(--muted)' }};">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept }}" @selected(request('department') === $dept)>
                    {{ str_replace('_', ' ', ucwords(strtolower($dept))) }}
                </option>
            @endforeach
        </select>

        <select name="role" class="users-select h-9 px-3 text-sm" style="color: {{ request('role') ? '#18181B' : 'var(--muted)' }};">
            <option value="">All Roles</option>
            @foreach($roles as $r)
                <option value="{{ $r }}" @selected(request('role') === $r)>
                    {{ str_replace('_', ' ', ucwords(strtolower($r))) }}
                </option>
            @endforeach
        </select>

        <select name="status" class="users-select h-9 px-3 text-sm" style="color: {{ request('status') ? '#18181B' : 'var(--muted)' }};">
            <option value="">All Statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>

        <button type="submit" class="users-btn-primary inline-flex items-center gap-2 h-9 px-4 text-sm">
            <i class="fas fa-filter text-[10px]"></i>
            Filter
        </button>

        @if(request()->anyFilled(['search', 'department', 'role', 'status']))
            <a href="{{ route('users.index') }}" class="users-btn-ghost inline-flex items-center h-9 px-3 text-sm">
                Clear
            </a>
        @endif

        <span class="users-count-pill text-xs uppercase whitespace-nowrap ml-auto" style="color: var(--muted);">
            {{ $users->total() }} {{ Str::plural('User', $users->total()) }}
        </span>

    </form>

    <div class="users-scroll overflow-x-auto">

        <table class="users-table min-w-full text-sm">

            <thead>

                <tr class="text-left">

                    <th class="px-5 py-3">Name</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Department</th>
                    <th class="px-5 py-3">Role</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-center">Actions</th>

                </tr>

            </thead>

            <tbody>

                @forelse($users as $user)

                <tr>

                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="users-name text-zinc-900">
                                {{ $user->full_name }}
                            </span>
                        </div>
                    </td>

                    <td class="px-5 py-3.5" style="color: var(--muted);">
                        {{ $user->email }}
                    </td>

                    <td class="px-5 py-3.5" style="color: var(--muted);">
                        {{ str_replace('_', ' ', $user->department) }}
                    </td>

                    <td class="px-5 py-3.5" style="color: var(--muted);">
                        {{ str_replace('_', ' ', $user->role) }}
                    </td>

                    <td class="px-5 py-3.5">

                        @if($user->is_active)

                            <span class="users-badge is-active">
                                <span class="users-dot is-active"></span>
                                Active
                            </span>

                        @else

                            <span class="users-badge is-inactive">
                                <span class="users-dot is-inactive"></span>
                                Inactive
                            </span>

                        @endif

                    </td>

                    <td class="px-5 py-3.5 text-center align-middle">

    <div class="users-action-group flex justify-center items-center">

        <a href="{{ route('users.show', $user) }}"
           data-tooltip="View"
           class="users-action-btn view-btn">
            <i class="fas fa-eye"></i>
        </a>

        <a href="{{ route('users.edit', $user) }}"
           data-tooltip="Edit"
           class="users-action-btn edit-btn">
            <i class="fas fa-pen"></i>
        </a>

        <form
            action="{{ route('users.destroy', $user) }}"
            method="POST"
            class="delete-form"
            data-user-name="{{ $user->full_name }}">

            @csrf
            @method('DELETE')

            <button
                type="button"
                data-tooltip="Delete"
                class="users-action-btn delete-btn delete-btn-trigger">
                <i class="fas fa-trash"></i>
            </button>

        </form>

    </div>

</td>

                </tr>

                @empty

                <tr>

                    <td colspan="6" class="px-5 py-16 text-center">
                        <i class="users-empty-icon fas fa-users text-2xl mb-3"></i>
                        <p class="users-empty-title text-sm" style="color: var(--muted-fg);">
                            @if(request()->anyFilled(['search', 'department', 'role', 'status']))
                                No users match your filters.
                            @else
                                No users found.
                            @endif
                        </p>
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    @if($users->hasPages())
        <div class="px-5 py-4 flex items-center justify-between gap-4" style="border-top: 1px solid var(--border);">

            <span class="text-xs" style="color: var(--muted);">
                Showing <span class="font-semibold text-zinc-700">{{ $users->firstItem() }}–{{ $users->lastItem() }}</span>
                of <span class="font-semibold text-zinc-700">{{ $users->total() }}</span>
            </span>

            <div class="users-pagination">

                <a href="{{ $users->previousPageUrl() }}"
                   class="users-page-link flex items-center justify-center px-2 {{ $users->onFirstPage() ? 'is-disabled' : '' }}">
                    <i class="fas fa-chevron-left text-[10px]"></i>
                </a>

                @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                    <a href="{{ $url }}"
                       class="users-page-link flex items-center justify-center {{ $page === $users->currentPage() ? 'is-active' : '' }}">
                        {{ $page }}
                    </a>
                @endforeach

                <a href="{{ $users->nextPageUrl() }}"
                   class="users-page-link flex items-center justify-center px-2 {{ !$users->hasMorePages() ? 'is-disabled' : '' }}">
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </a>

            </div>

        </div>
    @endif

</div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toast notifications
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        // Handle delete button clicks (using the enhanced trigger class)
        document.querySelectorAll('.delete-btn-trigger').forEach(button => {
            button.addEventListener('click', function(e) {
                const form = this.closest('.delete-form');
                const userName = form.dataset.userName || 'this user';

                Swal.fire({
                    title: `Delete "${userName}"?`,
                    html: `This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC2626',
                    cancelButtonColor: '#71717A',
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Flash messages as toast
        @if(session('success'))
            Toast.fire({
                icon: 'success',
                title: '{{ session('success') }}'
            });
        @endif

        @if(session('error'))
            Toast.fire({
                icon: 'error',
                title: '{{ session('error') }}'
            });
        @endif

        @if(session('info'))
            Toast.fire({
                icon: 'info',
                title: '{{ session('info') }}'
            });
        @endif
    });
</script>
@endpush

@endsection