@extends('layouts.admin-sidebar')

@section('title', 'Users Management · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-fill-height content-full-width')

@section('content')
<style>
    /* Fixed dashboard-style layout: topbar, stat cards, filters, and pagination are
       ALWAYS visible - only the table rows scroll, to fill whatever space remains.
       Critically, there is exactly ONE overflow:auto element in this whole chain
       (.um-table-scroll, far below) - every ancestor between the viewport and it is
       either a fixed-size flex item (flex-shrink:0) or a pure sizing pass-through
       (flex:1; min-height:0; overflow:hidden - no scrolling of its own). Two
       independently-auto-overflowing containers stacked on top of each other is what
       caused the original bug (sub-pixel rounding under browser zoom made the outer
       one falsely detect its own overflow and grab a scrollbar instead of deferring
       to the inner one) - keeping only one real scroll container avoids that class
       of bug entirely, regardless of zoom level. */
    .um-wrap { flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px; background: #F6F3EE; }

    .um-topbar {
        display: flex; align-items: center; gap: 16px; padding: 16px 28px;
        border-bottom: 1px solid #EBE4DA; background: #FFFDFA; flex-shrink: 0; flex-wrap: wrap;
    }
    .um-title { font: 600 21px/1.2 'Baloo 2'; color: #16436E; }
    .um-subtitle { font: 600 12.5px 'Nunito Sans'; color: #98897A; margin-top: 2px; }
    .um-add-btn {
        background: #C8355F; color: #fff; border: none; border-radius: 10px; text-decoration: none;
        padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; margin-left: auto; flex-shrink: 0;
        display: inline-flex; align-items: center; gap: 7px; transition: background-color .15s ease;
    }
    .um-add-btn:hover { background: #A82348; }

    .um-body {
        flex: 1; min-height: 0; overflow: hidden;
        padding: 20px 28px 28px; display: flex; flex-direction: column; gap: 18px;
    }

    .um-stats { flex-shrink: 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    .um-stat {
        background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 16px 18px;
        transition: border-color .15s ease;
    }
    .um-stat:hover { border-color: #D8CDBC; }
    .um-stat-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .um-stat-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.06em; }
    .um-stat-icon { color: #B0A493; font-size: 12px; }
    .um-stat-value { font: 600 26px 'Baloo 2'; color: #16436E; }
    .um-stat-dot { width: 8px; height: 8px; border-radius: 50%; }
    .um-stat-dot.is-active { background: #1FA855; }
    .um-stat-dot.is-inactive { background: #B0A493; }

    .um-card {
        flex: 1; min-height: 0; overflow: hidden;
        background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px;
        display: flex; flex-direction: column;
    }

    .um-filters { flex-shrink: 0; padding: 16px 20px; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; border-bottom: 1px solid #EBE4DA; }
    .um-search { position: relative; flex: 1; min-width: 200px; max-width: 300px; }
    .um-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #B0A493; font-size: 12px; }
    .um-input, .um-select {
        border: 1px solid #E2DACE; border-radius: 9px; background: #F6F3EE; font: 700 12.5px 'Nunito Sans';
        color: #2B3A4C; outline: none; height: 36px; transition: border-color .12s ease, box-shadow .12s ease;
    }
    .um-input { width: 100%; padding: 0 12px 0 32px; }
    .um-input::placeholder { color: #B0A493; font-weight: 600; }
    .um-select { padding: 0 10px; }
    .um-input:focus, .um-select:focus { border-color: #C8355F; box-shadow: 0 0 0 3px rgba(200, 53, 95, 0.12); background: #FFFDFA; }
    .um-filter-btn {
        background: #C8355F; color: #fff; border: none; border-radius: 9px; height: 36px; padding: 0 16px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
        transition: background-color .15s ease;
    }
    .um-filter-btn:hover { background: #A82348; }
    .um-clear-link {
        color: #98897A; font: 700 12.5px 'Nunito Sans'; text-decoration: none; height: 36px; display: inline-flex;
        align-items: center; padding: 0 10px; border-radius: 9px; transition: background-color .12s ease, color .12s ease;
    }
    .um-clear-link:hover { background: #F6F3EE; color: #2B3A4C; }
    .um-count-pill { font: 800 11px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.05em; margin-left: auto; white-space: nowrap; }

    /* Only this row area scrolls - a fixed, bounded height (not a viewport-fill flex
       calculation) so it stays robust under browser zoom. The topbar, stat cards,
       filters, and pagination bar all stay in normal page flow around it. */
    /* The single scrollable element in this whole page - see the note on .um-wrap above. */
    .um-table-scroll { flex: 1; min-height: 0; overflow-x: auto; overflow-y: auto; }
    .um-table { width: 100%; border-collapse: collapse; font: 600 13px 'Nunito Sans'; }
    .um-table thead th {
        position: sticky; top: 0; z-index: 1;
        text-align: left; padding: 11px 20px; font: 700 10.5px 'Nunito Sans'; color: #98897A;
        text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #EBE4DA; white-space: nowrap;
        background: #FFFFFF;
    }
    .um-table tbody tr { border-bottom: 1px solid #F3EDE3; transition: background-color .12s ease; }
    .um-table tbody tr:last-child { border-bottom: none; }
    .um-table tbody tr:hover { background: #FAF5EC; }
    .um-table td { padding: 12px 20px; vertical-align: middle; }
    .um-user-cell { display: flex; align-items: center; gap: 11px; }
    .um-avatar {
        width: 34px; height: 34px; border-radius: 50%; color: #fff; display: flex; align-items: center;
        justify-content: center; font: 600 12.5px 'Baloo 2'; flex-shrink: 0;
    }
    .um-name { font: 800 13px 'Nunito Sans'; color: #2B3A4C; }
    .um-muted { color: #98897A; }
    .um-tag {
        display: inline-flex; background: #F3EDE3; color: #8A7D6C; border-radius: 7px; padding: 3px 10px;
        font: 800 11px 'Nunito Sans'; white-space: nowrap;
    }
    .um-badge {
        display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 999px;
        font: 800 11px 'Nunito Sans'; border: 1px solid transparent;
    }
    .um-badge.is-active { background: #E3F4E9; border-color: #CFEBD8; color: #178A45; }
    .um-badge.is-inactive { background: #F3EDE3; border-color: #EBE4DA; color: #8A7D6C; }
    .um-badge-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .um-badge.is-active .um-badge-dot { background: #22C55E; }
    .um-badge.is-inactive .um-badge-dot { background: #A1A1AA; }

    .um-actions { display: flex; align-items: center; justify-content: center; gap: 6px; }
    .um-action-btn {
        width: 30px; height: 30px; border-radius: 8px; border: 1px solid #E2DACE; background: #FFFFFF;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;
        font-size: 11.5px; transition: all .15s ease;
    }
    .um-action-btn.view-btn { color: #24619C; }
    .um-action-btn.view-btn:hover { background: #E7EFF7; border-color: #C7DCF0; }
    .um-action-btn.edit-btn { color: #B97F24; }
    .um-action-btn.edit-btn:hover { background: #FBF2E3; border-color: #EAD6AE; }
    .um-action-btn.delete-btn { color: #C8355F; }
    .um-action-btn.delete-btn:hover { background: #F9E7EC; border-color: #F0C3D2; }

    .um-empty { padding: 56px 20px; text-align: center; }
    .um-empty-icon {
        width: 48px; height: 48px; border-radius: 50%; background: #F3EDE3; color: #B0A493;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 18px;
    }
    .um-empty-text { font: 700 12.5px 'Nunito Sans'; color: #98897A; }

    .um-pagination-bar {
        flex-shrink: 0;
        padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 14px;
        border-top: 1px solid #EBE4DA; flex-wrap: wrap;
    }
    .um-pagination-info { font: 600 11.5px 'Nunito Sans'; color: #98897A; }
    .um-pagination-info b { color: #2B3A4C; }
    .um-pagination { display: flex; align-items: center; gap: 4px; }
    .um-page-link {
        min-width: 30px; height: 30px; border-radius: 8px; font: 700 12px 'Nunito Sans'; color: #98897A;
        display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        transition: all .12s ease; border: 1px solid transparent; padding: 0 4px;
    }
    .um-page-link:hover { background: #F6F3EE; color: #2B3A4C; }
    .um-page-link.is-active { background: #C8355F; color: #fff; }
    .um-page-link.is-disabled { color: #D8CDBC; pointer-events: none; }

    @media (max-width: 900px) {
        .um-stats { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 640px) {
        .um-topbar { padding: 12px 16px; flex-direction: column; align-items: stretch; gap: 10px; }
        .um-add-btn { margin-left: 0; justify-content: center; }
        .um-body { padding: 14px 16px 20px; }
        .um-filters { padding: 14px 16px; }
        .um-count-pill { margin-left: 0; }
        .um-table td, .um-table th { padding: 10px 14px; }
    }

    /* ===== SweetAlert2, re-skinned to match the app palette ===== */
    .swal2-popup.swal2-modal {
        border-radius: 16px !important; padding: 2rem 2.5rem !important;
        box-shadow: 0 20px 60px rgba(22,42,60,0.16) !important; background: #FFFDFA !important;
        width: 400px !important; max-width: 90vw !important; text-align: center !important;
        font-family: 'Nunito Sans', sans-serif !important;
    }
    .swal2-popup .swal2-icon {
        margin: 0 auto 1rem auto !important; border: none !important; width: 64px !important; height: 64px !important;
        display: flex !important; align-items: center !important; justify-content: center !important;
    }
    .swal2-popup .swal2-icon-content { font-size: 2.5rem !important; display: flex !important; align-items: center !important; justify-content: center !important; }
    .swal2-popup .swal2-icon.swal2-warning { color: #B97F24 !important; background: #F7EEDD !important; border-radius: 50% !important; padding: 0 !important; }
    .swal2-popup .swal2-icon.swal2-success { color: #178A45 !important; background: #E3F4E9 !important; border-radius: 50% !important; padding: 0 !important; }
    .swal2-popup .swal2-icon.swal2-success .swal2-success-ring { border: none !important; }
    .swal2-popup .swal2-icon.swal2-error { color: #C8355F !important; background: #F9E7EC !important; border-radius: 50% !important; padding: 0 !important; }
    .swal2-popup .swal2-icon.swal2-error .swal2-x-mark { color: #C8355F !important; }
    .swal2-popup .swal2-icon.swal2-info { color: #24619C !important; background: #E7EFF7 !important; border-radius: 50% !important; padding: 0 !important; }
    .swal2-popup .swal2-title {
        font: 600 1.125rem 'Baloo 2' !important; color: #16436E !important; padding: 0 0 0.5rem 0 !important;
        margin: 0 !important; text-align: center !important; line-height: 1.4 !important;
    }
    .swal2-popup .swal2-html-container {
        font: 600 0.875rem 'Nunito Sans' !important; color: #98897A !important; line-height: 1.6 !important;
        padding: 0 !important; margin: 0 !important; text-align: center !important;
    }
    .swal2-popup .swal2-actions { margin: 1.5rem 0 0 0 !important; padding: 0 !important; gap: 0.75rem !important; justify-content: center !important; width: 100% !important; }
    .swal2-popup .swal2-confirm {
        border-radius: 10px !important; font: 800 0.8125rem 'Nunito Sans' !important; padding: 0.625rem 1.75rem !important;
        transition: all .15s ease !important; border: none !important; min-width: 100px !important; box-shadow: none !important;
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
    }
    .swal2-popup .swal2-confirm:hover { transform: translateY(-1px) !important; box-shadow: 0 4px 12px rgba(22,42,60,0.15) !important; }
    .swal2-popup .swal2-confirm:active { transform: scale(0.97) !important; }
    .swal2-popup .swal2-cancel {
        border-radius: 10px !important; font: 700 0.8125rem 'Nunito Sans' !important; padding: 0.625rem 1.75rem !important;
        background: #F6F3EE !important; color: #2B3A4C !important; border: 1px solid #E2DACE !important;
        transition: all .15s ease !important; min-width: 100px !important;
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
    }
    .swal2-popup .swal2-cancel:hover { background: #F0E9DD !important; border-color: #D8CDBC !important; }
    .swal2-popup .swal2-cancel:active { transform: scale(0.97) !important; }
    .swal2-toast { border-radius: 12px !important; padding: 0.75rem 1.25rem !important; box-shadow: 0 8px 32px rgba(22,42,60,0.14) !important; background: #FFFDFA !important; }
    .swal2-toast .swal2-title { font: 700 0.875rem 'Nunito Sans' !important; padding: 0 !important; margin: 0 !important; color: #2B3A4C !important; }
    .swal2-toast .swal2-icon { margin: 0 0.75rem 0 0 !important; width: 24px !important; height: 24px !important; }
    .swal2-toast .swal2-icon-content { font-size: 1rem !important; }
    .swal2-toast .swal2-timer-progress-bar { background: #178A45 !important; opacity: 0.2 !important; height: 2px !important; }
    .swal2-toast .swal2-icon.swal2-success { background: #E3F4E9 !important; border-radius: 50% !important; padding: 2px !important; }
    .swal2-toast .swal2-icon.swal2-error { background: #F9E7EC !important; border-radius: 50% !important; padding: 2px !important; }
    .swal2-toast .swal2-icon.swal2-warning { background: #F7EEDD !important; border-radius: 50% !important; padding: 2px !important; }
    .swal2-toast .swal2-icon.swal2-info { background: #E7EFF7 !important; border-radius: 50% !important; padding: 2px !important; }
</style>

@php
    $umAvatarPalette = ['#C8355F', '#24619C', '#B97F24', '#6E4FA8', '#1F8FA8', '#A8461F', '#2E7D5B'];
    $umAvatarColor = fn ($seed) => $umAvatarPalette[crc32($seed) % count($umAvatarPalette)];
@endphp

<div class="um-wrap">

    <!-- Top Bar -->
    <div class="um-topbar">
        <div>
            <div class="um-title">User Management</div>
            <div class="um-subtitle">{{ $stats['total'] }} staff {{ Str::plural('account', $stats['total']) }} · manage roles, departments and access</div>
        </div>
        <a href="{{ route('users.create') }}" class="um-add-btn">
            <i class="fas fa-plus" style="font-size: 10px;"></i>
            Add User
        </a>
    </div>

    <div class="um-body">

        <!-- Stats -->
        <div class="um-stats">
            <div class="um-stat">
                <div class="um-stat-top">
                    <span class="um-stat-label">Total Users</span>
                    <i class="fas fa-users um-stat-icon"></i>
                </div>
                <div class="um-stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="um-stat">
                <div class="um-stat-top">
                    <span class="um-stat-label">Active</span>
                    <span class="um-stat-dot is-active"></span>
                </div>
                <div class="um-stat-value">{{ $stats['active'] }}</div>
            </div>
            <div class="um-stat">
                <div class="um-stat-top">
                    <span class="um-stat-label">Inactive</span>
                    <span class="um-stat-dot is-inactive"></span>
                </div>
                <div class="um-stat-value">{{ $stats['inactive'] }}</div>
            </div>
            <div class="um-stat">
                <div class="um-stat-top">
                    <span class="um-stat-label">Departments</span>
                    <i class="fas fa-building um-stat-icon"></i>
                </div>
                <div class="um-stat-value">{{ $stats['departments'] }}</div>
            </div>
        </div>

        <!-- Table card -->
        <div class="um-card">

            <form method="GET" action="{{ route('users.index') }}" class="um-filters">
                <div class="um-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users…" class="um-input">
                </div>

                <select name="department" class="um-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" @selected(request('department') === $dept)>
                            {{ str_replace('_', ' ', ucwords(strtolower($dept))) }}
                        </option>
                    @endforeach
                </select>

                <select name="role" class="um-select">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                        <option value="{{ $r }}" @selected(request('role') === $r)>
                            {{ str_replace('_', ' ', ucwords(strtolower($r))) }}
                        </option>
                    @endforeach
                </select>

                <select name="status" class="um-select">
                    <option value="">All Statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>

                <button type="submit" class="um-filter-btn">
                    <i class="fas fa-filter" style="font-size: 10px;"></i>
                    Filter
                </button>

                @if(request()->anyFilled(['search', 'department', 'role', 'status']))
                    <a href="{{ route('users.index') }}" class="um-clear-link">Clear</a>
                @endif

                <span class="um-count-pill">{{ $users->total() }} {{ Str::plural('User', $users->total()) }}</span>
            </form>

            <div class="um-table-scroll">
                <table class="um-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="um-user-cell">
                                        <div class="um-avatar" style="background: {{ $umAvatarColor($user->email) }};">
                                            {{ strtoupper(substr($user->first_name ?? $user->full_name, 0, 1) . substr($user->last_name ?? '', 0, 1)) }}
                                        </div>
                                        <span class="um-name">{{ $user->full_name }}</span>
                                    </div>
                                </td>
                                <td class="um-muted">{{ $user->email }}</td>
                                <td><span class="um-tag">{{ str_replace('_', ' ', $user->department) }}</span></td>
                                <td class="um-muted">{{ str_replace('_', ' ', $user->role) }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="um-badge is-active"><span class="um-badge-dot"></span>Active</span>
                                    @else
                                        <span class="um-badge is-inactive"><span class="um-badge-dot"></span>Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="um-actions">
                                        <a href="{{ route('users.show', $user) }}" class="um-action-btn view-btn" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('users.edit', $user) }}" class="um-action-btn edit-btn" title="Edit">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="delete-form" data-user-name="{{ $user->full_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="um-action-btn delete-btn delete-btn-trigger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="um-empty">
                                        <div class="um-empty-icon"><i class="fas fa-users"></i></div>
                                        <div class="um-empty-text">
                                            @if(request()->anyFilled(['search', 'department', 'role', 'status']))
                                                No users match your filters.
                                            @else
                                                No users found.
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="um-pagination-bar">
                    <span class="um-pagination-info">
                        Showing <b>{{ $users->firstItem() }}–{{ $users->lastItem() }}</b> of <b>{{ $users->total() }}</b>
                    </span>

                    <div class="um-pagination">
                        <a href="{{ $users->previousPageUrl() }}" class="um-page-link {{ $users->onFirstPage() ? 'is-disabled' : '' }}">
                            <i class="fas fa-chevron-left" style="font-size: 10px;"></i>
                        </a>

                        @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                            <a href="{{ $url }}" class="um-page-link {{ $page === $users->currentPage() ? 'is-active' : '' }}">{{ $page }}</a>
                        @endforeach

                        <a href="{{ $users->nextPageUrl() }}" class="um-page-link {{ !$users->hasMorePages() ? 'is-disabled' : '' }}">
                            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        document.querySelectorAll('.delete-btn-trigger').forEach(button => {
            button.addEventListener('click', function(e) {
                const form = this.closest('.delete-form');
                const userName = form.dataset.userName || 'this user';

                Swal.fire({
                    title: `Delete "${userName}"?`,
                    html: `This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#C8355F',
                    cancelButtonColor: '#8A7D6C',
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

        @if(session('success'))
            Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
        @endif

        @if(session('error'))
            Toast.fire({ icon: 'error', title: '{{ session('error') }}' });
        @endif

        @if(session('info'))
            Toast.fire({ icon: 'info', title: '{{ session('info') }}' });
        @endif
    });
</script>
@endpush

@endsection
