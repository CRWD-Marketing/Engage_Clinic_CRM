@extends('layouts.admin-sidebar')

@section('title', $user->full_name . ' · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')

<style>
    .user-show {
        --border: #E4E4E7;
        --border-strong: #D4D4D8;
        --muted: #71717A;
        --muted-fg: #A1A1AA;
        --ring: #18181B;
        --radius: 8px;

        font-feature-settings: "tnum" 1, "cv11" 1;
        -webkit-font-smoothing: antialiased;
    }

    .user-show h1 {
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .user-show-back {
        color: var(--muted);
        font-weight: 600;
        transition: color 0.12s ease, gap 0.12s ease;
    }

    .user-show-back:hover {
        color: #18181B;
    }

    .user-show-back:hover i {
        transform: translateX(-2px);
    }

    .user-show-back i {
        transition: transform 0.12s ease;
    }

    /* Signature: hairline rule with a short accent tick, echoing the index page */
    .user-show-rule {
        height: 1px;
        background: var(--border);
        position: relative;
    }

    .user-show-rule::after {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        height: 2px;
        width: 32px;
        background: #18181B;
    }

    .user-show-avatar {
        background: #F4F4F5;
        color: #52525B;
        border: 1px solid var(--border);
        font-weight: 800;
    }

    .user-show-card {
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #fff;
    }

    .user-show-label {
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.07em;
        color: var(--muted-fg);
    }

    .user-show-value {
        font-weight: 600;
        color: #18181B;
    }

    .user-show-value.is-muted {
        font-weight: 500;
        color: var(--muted);
    }

    .user-show-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: #F4F4F5;
        color: var(--muted);
        flex-shrink: 0;
    }

    .user-show-section-title {
        font-weight: 700;
        letter-spacing: -0.01em;
        color: #18181B;
    }

    .user-show-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .user-show-badge.is-active {
        background: #F0FDF4;
        border-color: #DCFCE7;
        color: #15803D;
    }

    .user-show-badge.is-inactive {
        background: #FAFAFA;
        border-color: var(--border);
        color: var(--muted);
    }

    .user-show-dot {
        width: 6px;
        height: 6px;
        border-radius: 9999px;
        flex-shrink: 0;
    }

    .user-show-dot.is-active { background: #22C55E; }
    .user-show-dot.is-inactive { background: #A1A1AA; }

    .user-show-btn-primary {
        background: #18181B;
        color: #fff;
        font-weight: 700;
        border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: background-color 0.12s ease;
    }

    .user-show-btn-primary:hover {
        background: #27272A;
    }

    .user-show-btn-ghost {
        color: var(--muted);
        border: 1px solid var(--border);
        border-radius: 6px;
        font-weight: 600;
        transition: background-color 0.12s ease, color 0.12s ease, border-color 0.12s ease;
    }

    .user-show-btn-ghost:hover {
        background: #F4F4F5;
        color: #18181B;
        border-color: var(--border-strong);
    }

    .user-show-btn-danger {
        color: #DC2626;
        border: 1px solid #FECACA;
        border-radius: 6px;
        font-weight: 600;
        background: #fff;
        transition: background-color 0.12s ease, border-color 0.12s ease;
    }

    .user-show-btn-danger:hover {
        background: #FEF2F2;
        border-color: #FCA5A5;
    }

    .user-show-copy {
        color: var(--muted-fg);
        border-radius: 4px;
        transition: background-color 0.12s ease, color 0.12s ease;
    }

    .user-show-copy:hover {
        background: #F4F4F5;
        color: #18181B;
    }

    .user-show-divider {
        border-top: 1px solid #F4F4F5;
    }

    .user-show-subordinate {
        border: 1px solid var(--border);
        border-radius: 6px;
        transition: border-color 0.12s ease, background-color 0.12s ease;
    }

    .user-show-subordinate:hover {
        border-color: var(--border-strong);
        background: #FAFAFA;
    }

    .user-show-empty {
        color: var(--muted-fg);
        font-size: 0.8125rem;
    }

    .user-show-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    @media (prefers-reduced-motion: reduce) {
        .user-show-back, .user-show-back i, .user-show-btn-primary, .user-show-btn-ghost,
        .user-show-btn-danger, .user-show-subordinate, .user-show-copy {
            transition: none;
        }
    }
</style>

<div class="user-show" x-data="{ copied: false }">

    <a href="{{ route('users.index') }}" class="user-show-back inline-flex items-center gap-2 text-sm mb-5">
        <i class="fas fa-arrow-left text-[10px]"></i>
        Back to Users
    </a>

    <div class="flex items-start justify-between gap-4 mb-5 flex-wrap">

        <div class="flex items-center gap-4">
            <div class="user-show-avatar w-14 h-14 rounded-full text-lg flex items-center justify-center shrink-0">
                {{ strtoupper(substr($user->full_name, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl text-zinc-900">
                    {{ $user->full_name }}
                </h1>
                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                    @if($user->is_active)
                        <span class="user-show-badge is-active">
                            <span class="user-show-dot is-active"></span>
                            Active
                        </span>
                    @else
                        <span class="user-show-badge is-inactive">
                            <span class="user-show-dot is-inactive"></span>
                            Inactive
                        </span>
                    @endif
                    <span class="text-sm" style="color: var(--muted);">
                        {{ str_replace('_', ' ', ucwords(strtolower($user->role))) }}
                    </span>
                    <span class="text-sm" style="color: var(--border-strong);">·</span>
                    <span class="text-sm" style="color: var(--muted);">
                        {{ str_replace('_', ' ', ucwords(strtolower($user->department))) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('users.edit', $user) }}" class="user-show-btn-ghost inline-flex items-center gap-2 h-9 px-4 text-sm">
                <i class="fas fa-pen text-[10px]"></i>
                Edit
            </a>

            <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this user?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="user-show-btn-danger inline-flex items-center gap-2 h-9 px-4 text-sm">
                    <i class="fas fa-trash text-[10px]"></i>
                    Delete
                </button>
            </form>
        </div>

    </div>

    <div class="user-show-rule mb-6"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Contact & employment --}}
        <div class="lg:col-span-2 flex flex-col gap-4">

            <div class="user-show-card px-6 py-5">
                <h2 class="user-show-section-title text-sm mb-4 flex items-center gap-2">
                    <i class="fas fa-id-card text-xs" style="color: var(--muted-fg);"></i>
                    Contact Information
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    <div class="flex items-start gap-3">
                        <div class="user-show-icon flex items-center justify-center">
                            <i class="fas fa-envelope text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="user-show-label uppercase mb-1">Email</p>
                            <div class="flex items-center gap-2">
                                <p class="user-show-value text-sm truncate">{{ $user->email }}</p>
                                <button
                                    type="button"
                                    title="Copy email"
                                    onclick="navigator.clipboard.writeText('{{ $user->email }}')"
                                    class="user-show-copy w-6 h-6 flex items-center justify-center shrink-0">
                                    <i class="fas fa-copy text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="user-show-icon flex items-center justify-center">
                            <i class="fas fa-phone text-xs"></i>
                        </div>
                        <div>
                            <p class="user-show-label uppercase mb-1">Phone</p>
                            <p class="user-show-value text-sm {{ $user->phone_number ? '' : 'is-muted' }}">
                                {{ $user->phone_number ?: 'Not provided' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="user-show-icon flex items-center justify-center">
                            <i class="fas fa-user text-xs"></i>
                        </div>
                        <div>
                            <p class="user-show-label uppercase mb-1">Full Legal Name</p>
                            <p class="user-show-value text-sm">
                                {{ $user->first_name }}
                                @if($user->middle_name) {{ $user->middle_name }} @endif
                                {{ $user->last_name }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="user-show-card px-6 py-5">
                <h2 class="user-show-section-title text-sm mb-4 flex items-center gap-2">
                    <i class="fas fa-briefcase text-xs" style="color: var(--muted-fg);"></i>
                    Employment
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                    <div>
                        <p class="user-show-label uppercase mb-1">Department</p>
                        <p class="user-show-value text-sm">{{ str_replace('_', ' ', ucwords(strtolower($user->department))) }}</p>
                    </div>

                    <div>
                        <p class="user-show-label uppercase mb-1">Role</p>
                        <p class="user-show-value text-sm">{{ str_replace('_', ' ', ucwords(strtolower($user->role))) }}</p>
                    </div>

                    <div>
                        <p class="user-show-label uppercase mb-1">Manager</p>
                        @if($user->manager)
                            <a href="{{ route('users.show', $user->manager) }}" class="user-show-value text-sm hover:underline">
                                {{ $user->manager->full_name }}
                            </a>
                        @else
                            <p class="user-show-value text-sm is-muted">No manager assigned</p>
                        @endif
                    </div>

                    <div>
                        <p class="user-show-label uppercase mb-1">Start Date</p>
                        <p class="user-show-value text-sm {{ $user->start_date ? '' : 'is-muted' }}">
                            {{ $user->start_date ? \Carbon\Carbon::parse($user->start_date)->format('M d, Y') : 'Not set' }}
                        </p>
                    </div>

                </div>

                @if($user->notes)
                    <div class="user-show-divider mt-5 pt-5">
                        <p class="user-show-label uppercase mb-1.5">Notes</p>
                        <p class="text-sm text-zinc-700 leading-relaxed">{{ $user->notes }}</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- Sidebar: subordinates / meta --}}
        <div class="flex flex-col gap-4">

            <div class="user-show-card px-6 py-5">
                <h2 class="user-show-section-title text-sm mb-4 flex items-center gap-2">
                    <i class="fas fa-sitemap text-xs" style="color: var(--muted-fg);"></i>
                    Direct Reports
                    @if($user->subordinates->count())
                        <span class="text-xs font-medium" style="color: var(--muted-fg);">({{ $user->subordinates->count() }})</span>
                    @endif
                </h2>

                @forelse($user->subordinates as $subordinate)
                    <a href="{{ route('users.show', $subordinate) }}"
                       class="user-show-subordinate flex items-center gap-3 px-3 py-2.5 mb-2 last:mb-0">
                        <div class="user-show-avatar w-8 h-8 rounded-full text-xs flex items-center justify-center shrink-0">
                            {{ strtoupper(substr($subordinate->full_name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-800 truncate">{{ $subordinate->full_name }}</p>
                            <p class="text-xs truncate" style="color: var(--muted);">
                                {{ str_replace('_', ' ', ucwords(strtolower($subordinate->role))) }}
                            </p>
                        </div>
                    </a>
                @empty
                    <p class="user-show-empty">No direct reports.</p>
                @endforelse
            </div>

            <div class="user-show-card px-6 py-5">
                <h2 class="user-show-section-title text-sm mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-xs" style="color: var(--muted-fg);"></i>
                    Record
                </h2>

                <div class="flex flex-col gap-3">
                    <div class="user-show-meta-row">
                        <p class="user-show-label uppercase">Created</p>
                        <p class="user-show-value text-sm is-muted">{{ $user->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="user-show-meta-row">
                        <p class="user-show-label uppercase">Updated</p>
                        <p class="user-show-value text-sm is-muted">{{ $user->updated_at->diffForHumans() }}</p>
                    </div>
                    <div class="user-show-meta-row">
                        <p class="user-show-label uppercase">Last Login</p>
                        <p class="user-show-value text-sm is-muted">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
                        </p>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

@endsection