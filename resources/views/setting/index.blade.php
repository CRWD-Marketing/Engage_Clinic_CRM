@extends('layouts.admin-sidebar')

@section('title', 'Settings · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')

<style>
    .st-header-title { font: 600 26px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .st-header-sub { font: 600 13px 'Nunito Sans'; color: #98897A; margin-top: 4px; }

    .st-success-banner {
        background: #E4F6EB; border: 1px solid #BFE9CE; color: #1E8A4C; border-radius: 12px; padding: 12px 16px;
        font: 700 12.5px 'Nunito Sans';
    }

    .st-card { background: #fff; border: 1px solid #EBE4DA; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(22,42,60,0.04); }
    .st-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 20px 22px; }
    .st-card-title { font: 600 17px 'Baloo 2'; color: #16436E; }
    .st-card-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 3px; }
    .st-add-btn {
        background: #16436E; color: #fff; border: none; border-radius: 10px; padding: 11px 20px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; flex-shrink: 0; white-space: nowrap; box-sizing: border-box;
    }
    .st-add-btn:hover { background: #123655; }

    .st-table { width: 100%; border-collapse: collapse; }
    .st-table th {
        text-align: left; font: 800 10.5px 'Nunito Sans'; text-transform: uppercase; letter-spacing: 0.05em;
        color: #98897A; padding: 10px 22px; border-top: 1px solid #EBE4DA; border-bottom: 1px solid #EBE4DA; background: #FBF8F3;
    }
    .st-table td { padding: 14px 22px; border-bottom: 1px solid #F3EDE3; font: 700 13px 'Nunito Sans'; color: #2B3A4C; vertical-align: middle; }
    .st-table tr:last-child td { border-bottom: none; }
    .st-status-cell { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }

    .st-badge { border-radius: 999px; padding: 4px 12px; font: 800 11px 'Nunito Sans'; white-space: nowrap; border: none; cursor: pointer; }
    .st-badge.active { background: #E4F6EB; color: #1E8A4C; }
    .st-badge.paused { background: #FDF6E9; color: #8A5A10; }

    .st-remove-btn {
        width: 26px; height: 26px; border-radius: 8px; border: 1px solid #EFC7C2; background: #FBEAE8; color: #B3261E;
        font: 800 12px/1 'Nunito Sans'; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .st-remove-btn:hover { background: #F6D5D1; }

    .st-edit-btn {
        width: 26px; height: 26px; border-radius: 8px; border: 1px solid #E2DACE; background: #fff; color: #16436E;
        font: 700 11.5px/1 'Nunito Sans'; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .st-edit-btn:hover { background: #F6F3EE; }

    .st-empty { padding: 22px; font: 600 12.5px 'Nunito Sans'; color: #98897A; }

    .st-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45);
        align-items: center; justify-content: center; z-index: 9999; padding: 16px;
    }
    .st-modal-box {
        width: 400px; max-width: 100%; background: #FFFDFA; border-radius: 18px; padding: 26px 28px;
        display: flex; flex-direction: column; gap: 16px; box-shadow: 0 20px 60px rgba(22,42,60,0.3);
    }
    .st-modal-title { font: 600 19px 'Baloo 2'; color: #16436E; }
    .st-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
    .st-field-input {
        width: 100%; padding: 10px 12px; border: 1px solid #E2DACE; border-radius: 8px;
        background: #F6F3EE; font: 700 13.5px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;
    }
    .st-field-input:focus { border-color: #16436E; }
    .st-modal-actions { display: flex; gap: 10px; margin-top: 16px; }
    .st-btn-save {
        flex: 1; background: #16436E; color: #fff; border: none; border-radius: 10px; padding: 12px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer;
    }
    .st-btn-cancel {
        background: #fff; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 12px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer;
    }
</style>

<div>
    <div class="st-header-title">Settings</div>
    <div class="st-header-sub">Clinic setup — the service names available when building a client package</div>
</div>

@if (session('success'))
    <div class="st-success-banner">{{ session('success') }}</div>
@endif

<div class="st-card">
    <div class="st-card-header">
        <div>
            <div class="st-card-title">Services</div>
            <div class="st-card-sub">{{ $services->where('is_active', true)->count() }} active · {{ $services->count() }} total · service names used when building a client package</div>
        </div>
        <button type="button" class="st-add-btn" onclick="stOpenModal('service')">+ Add service</button>
    </div>
    <table class="st-table">
        <thead><tr><th>Service</th><th style="text-align:right;">Status</th></tr></thead>
        <tbody>
            @forelse ($services as $service)
                <tr>
                    <td>{{ $service->name }}</td>
                    <td>
                        <div class="st-status-cell">
                            <form action="{{ route('settings.services.toggle', $service) }}" method="POST" style="display:inline;">
                                @csrf @method('PATCH')
                                <button type="submit" class="st-badge {{ $service->is_active ? 'active' : 'paused' }}">{{ $service->is_active ? 'Active' : 'Paused' }}</button>
                            </form>
                            <button type="button" class="st-edit-btn" title="Edit" onclick="stOpenEditModal('service', {{ Js::from($service->name) }}, {{ Js::from(route('settings.services.update', $service)) }})">✎</button>
                            <form action="{{ route('settings.services.destroy', $service) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this service?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="st-remove-btn">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="2" class="st-empty">No services yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="st-card">
    <div class="st-card-header">
        <div>
            <div class="st-card-title">Locations</div>
            <div class="st-card-sub">{{ $locations->where('is_active', true)->count() }} active · {{ $locations->count() }} total · service zones used when building a client package</div>
        </div>
        <button type="button" class="st-add-btn" onclick="stOpenModal('location')">+ Add location</button>
    </div>
    <table class="st-table">
        <thead><tr><th>Location</th><th style="text-align:right;">Status</th></tr></thead>
        <tbody>
            @forelse ($locations as $location)
                <tr>
                    <td>{{ $location->name }}</td>
                    <td>
                        <div class="st-status-cell">
                            <form action="{{ route('settings.locations.toggle', $location) }}" method="POST" style="display:inline;">
                                @csrf @method('PATCH')
                                <button type="submit" class="st-badge {{ $location->is_active ? 'active' : 'paused' }}">{{ $location->is_active ? 'Active' : 'Paused' }}</button>
                            </form>
                            <button type="button" class="st-edit-btn" title="Edit" onclick="stOpenEditModal('location', {{ Js::from($location->name) }}, {{ Js::from(route('settings.locations.update', $location)) }})">✎</button>
                            <form action="{{ route('settings.locations.destroy', $location) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this location?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="st-remove-btn">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="2" class="st-empty">No locations yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="stServiceModal" class="st-modal-overlay">
    <div class="st-modal-box">
        <div class="st-modal-title">Add service</div>
        <form action="{{ route('settings.services.store') }}" method="POST">
            @csrf
            <div class="st-field-label">Name</div>
            <input type="text" name="name" class="st-field-input" placeholder="e.g. ABA therapy session" required>
            <div class="st-modal-actions">
                <button type="submit" class="st-btn-save">Add service</button>
                <button type="button" class="st-btn-cancel" onclick="stCloseModal('service')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="stLocationModal" class="st-modal-overlay">
    <div class="st-modal-box">
        <div class="st-modal-title">Add location</div>
        <form action="{{ route('settings.locations.store') }}" method="POST">
            @csrf
            <div class="st-field-label">Name</div>
            <input type="text" name="name" class="st-field-input" placeholder="e.g. Inside Abu Dhabi" required>
            <div class="st-modal-actions">
                <button type="submit" class="st-btn-save">Add location</button>
                <button type="button" class="st-btn-cancel" onclick="stCloseModal('location')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="stEditModal" class="st-modal-overlay">
    <div class="st-modal-box">
        <div class="st-modal-title" id="stEditModalTitle">Edit</div>
        <form id="stEditForm" method="POST">
            @csrf
            @method('PUT')
            <div class="st-field-label">Name</div>
            <input type="text" name="name" id="stEditNameInput" class="st-field-input" required>
            <div class="st-modal-actions">
                <button type="submit" class="st-btn-save">Save</button>
                <button type="button" class="st-btn-cancel" onclick="stCloseModal('edit')">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function stOpenEditModal(kind, name, url) {
        document.getElementById('stEditModalTitle').textContent = kind === 'service' ? 'Edit service' : 'Edit location';
        document.getElementById('stEditForm').action = url;
        document.getElementById('stEditNameInput').value = name;
        document.getElementById('stEditModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function stOpenModal(kind) {
        document.getElementById(kind === 'service' ? 'stServiceModal' : 'stLocationModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function stCloseModal(kind) {
        const id = kind === 'service' ? 'stServiceModal' : kind === 'location' ? 'stLocationModal' : 'stEditModal';
        document.getElementById(id).style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.st-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function (e) {
                if (e.target === this) {
                    this.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.st-modal-overlay').forEach(el => el.style.display = 'none');
                document.body.style.overflow = '';
            }
        });
    });
</script>
@endpush

@endsection
