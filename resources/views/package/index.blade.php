@extends('layouts.admin-sidebar')

@section('title', 'Packages · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')

<style>
    .pk-header-title { font: 600 26px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .pk-header-sub { font: 600 13px 'Nunito Sans'; color: #98897A; margin-top: 4px; }

    .pk-success-banner {
        background: #E4F6EB; border: 1px solid #BFE9CE; color: #1E8A4C; border-radius: 12px; padding: 12px 16px;
        font: 700 12.5px 'Nunito Sans';
    }

    .pk-card { background: #fff; border: 1px solid #EBE4DA; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 10px rgba(22,42,60,0.04); }
    .pk-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 20px 22px; }
    .pk-card-title { font: 600 17px 'Baloo 2'; color: #16436E; }
    .pk-card-sub { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 3px; }
    .pk-add-btn {
        background: #C8355F; color: #fff; border: none; border-radius: 10px; padding: 11px 20px;
        font: 800 12.5px 'Nunito Sans'; cursor: pointer; flex-shrink: 0; white-space: nowrap; box-sizing: border-box;
    }
    .pk-add-btn:hover { background: #A82348; }

    .pk-table { width: 100%; border-collapse: collapse; }
    .pk-table th {
        text-align: left; font: 800 10.5px 'Nunito Sans'; text-transform: uppercase; letter-spacing: 0.05em;
        color: #98897A; padding: 10px 18px; border-top: 1px solid #EBE4DA; border-bottom: 1px solid #EBE4DA; background: #FBF8F3; white-space: nowrap;
    }
    .pk-table td { padding: 13px 18px; border-bottom: 1px solid #F3EDE3; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; vertical-align: middle; white-space: nowrap; }
    .pk-table tr:last-child td { border-bottom: none; }
    .pk-table-scroll { overflow-x: auto; }

    .pk-badge { border-radius: 999px; padding: 4px 11px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
    .pk-badge.insurance { background: #E7EFF7; color: #24619C; }
    .pk-badge.self-pay { background: #FDF6E9; color: #8A5A10; }

    .pk-actions-cell { display: flex; align-items: center; gap: 8px; }
    .pk-edit-btn {
        background: #fff; color: #16436E; border: 1px solid #E2DACE; border-radius: 8px; padding: 7px 13px;
        font: 800 11.5px 'Nunito Sans'; cursor: pointer;
    }
    .pk-edit-btn:hover { background: #F6F3EE; }
    .pk-remove-btn {
        width: 30px; height: 30px; border-radius: 8px; border: 1px solid #EFC7C2; background: #FBEAE8; color: #B3261E;
        font: 800 12px/1 'Nunito Sans'; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .pk-remove-btn:hover { background: #F6D5D1; }

    .pk-empty { padding: 22px; font: 600 12.5px 'Nunito Sans'; color: #98897A; }

    .pk-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45);
        align-items: center; justify-content: center; z-index: 9999; padding: 16px;
    }
    .pk-modal-box {
        width: 560px; max-width: 100%; background: #FFFDFA; border-radius: 18px; padding: 26px 28px;
        display: flex; flex-direction: column; gap: 16px; box-shadow: 0 20px 60px rgba(22,42,60,0.3);
    }
    .pk-modal-header { display: flex; align-items: flex-start; gap: 12px; }
    .pk-modal-header > div:first-child { flex: 1; }
    .pk-modal-title { font: 600 19px 'Baloo 2'; color: #16436E; }
    .pk-modal-subtitle { font: 600 12px 'Nunito Sans'; color: #98897A; margin-top: 3px; }
    .pk-modal-close {
        width: 30px; height: 30px; border-radius: 9px; border: 1px solid #E2DACE; background: #fff;
        color: #5A6B7E; font: 800 14px/1 'Nunito Sans'; cursor: pointer; flex-shrink: 0;
    }
    .pk-modal-close:hover { background: #F6F3EE; }
    .pk-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .pk-field-input {
        width: 100%; padding: 10px 12px; border: 1px solid #E2DACE; border-radius: 8px;
        background: #F6F3EE; font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;
    }
    .pk-field-input:focus { border-color: #C8355F; }
    .pk-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .pk-modal-divider { border-top: 1px solid #F3EDE3; padding-top: 14px; display: flex; align-items: center; gap: 12px; }
    .pk-total-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.5px; }
    .pk-total-value { font: 700 20px 'Baloo 2'; color: #16436E; margin-top: 2px; }
    .pk-modal-actions { display: flex; gap: 10px; margin-left: auto; }
    .pk-btn-save {
        background: #16436E; color: #fff; border: none; border-radius: 10px; padding: 12px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer;
    }
    .pk-btn-save:hover { background: #0F3255; }
    .pk-btn-cancel {
        background: #fff; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 10px; padding: 12px 22px;
        font: 800 13px 'Nunito Sans'; cursor: pointer;
    }

    @media (max-width: 600px) {
        .pk-grid-2col { grid-template-columns: 1fr; }
        .pk-modal-divider { flex-direction: column; align-items: flex-start; }
        .pk-modal-actions { margin-left: 0; width: 100%; }
    }
</style>

<div>
    <div class="pk-header-title">Packages</div>
    <div class="pk-header-sub">Custom client packages — all prices exclude VAT 5%</div>
</div>

@if (session('success'))
    <div class="pk-success-banner">{{ session('success') }}</div>
@endif

<div class="pk-card">
    <div class="pk-card-header">
        <div>
            <div class="pk-card-title">All packages</div>
            <div class="pk-card-sub">{{ $packages->count() }} package{{ $packages->count() === 1 ? '' : 's' }} · service, location and funding come from Settings</div>
        </div>
        <button type="button" class="pk-add-btn" onclick="pkOpenCreate()">+ New package</button>
    </div>
    <div class="pk-table-scroll">
        <table class="pk-table">
            <thead>
                <tr>
                    <th>Package</th>
                    <th>Service</th>
                    <th>Location</th>
                    <th>Funding</th>
                    <th>Setting</th>
                    <th>Hours</th>
                    <th>Rate / hr</th>
                    <th>Total excl. VAT</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($packages as $package)
                    <tr>
                        <td>{{ $package->name }}</td>
                        <td>{{ $package->service->name ?? '—' }}</td>
                        <td>{{ $package->location->name ?? '—' }}</td>
                        <td>
                            @if ($package->funding_type)
                                <span class="pk-badge {{ $package->funding_type === 'Insurance' ? 'insurance' : 'self-pay' }}">{{ $package->funding_type }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $package->delivery_mode ?? '—' }}</td>
                        <td>{{ rtrim(rtrim($package->hours_per_week, '0'), '.') }} h</td>
                        <td>AED {{ number_format($package->rate, 0) }}</td>
                        <td>AED {{ number_format($package->total_excl_vat, 0) }}</td>
                        <td>
                            <div class="pk-actions-cell">
                                <button type="button" class="pk-edit-btn" onclick="pkOpenEdit({{ $package->id }})">Edit</button>
                                <form action="{{ route('packages.destroy', $package) }}" method="POST" onsubmit="return confirm('Remove this package?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="pk-remove-btn">✕</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="pk-empty">No packages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="pkModal" class="pk-modal-overlay">
    <div class="pk-modal-box">
        <div class="pk-modal-header">
            <div>
                <div class="pk-modal-title" id="pkModalTitle">New package</div>
                <div class="pk-modal-subtitle">Service and location options come from Settings · price excludes VAT 5%</div>
            </div>
            <button type="button" class="pk-modal-close" onclick="pkCloseModal()">✕</button>
        </div>
        <form id="pkForm" method="POST" onsubmit="return pkSubmit(event)">
            @csrf
            <input type="hidden" id="pkMethod" name="_method" value="">

            <div>
                <div class="pk-field-label">Package name</div>
                <input type="text" name="name" id="pkName" class="pk-field-input" placeholder="e.g. P-20 hrs ABA and 10 hr speech" required>
            </div>

            <div class="pk-grid-2col" style="margin-top:12px;">
                <div>
                    <div class="pk-field-label">Service</div>
                    <select name="service_id" id="pkService" class="pk-field-input">
                        <option value="">Select service…</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="pk-field-label">Location</div>
                    <select name="location_id" id="pkLocation" class="pk-field-input">
                        <option value="">Select location…</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="pk-field-label">Funding</div>
                    <select name="funding_type" id="pkFunding" class="pk-field-input">
                        <option value="Insurance">Insurance</option>
                        <option value="Self pay">Self pay</option>
                    </select>
                </div>
                <div>
                    <div class="pk-field-label">Setting</div>
                    <select name="delivery_mode" id="pkDeliveryMode" class="pk-field-input">
                        <option value="Home base">Home base</option>
                        <option value="Clinic">Clinic</option>
                    </select>
                </div>
                <div>
                    <div class="pk-field-label">Hours</div>
                    <input type="number" min="0" step="0.5" name="hours_per_week" id="pkHours" class="pk-field-input" placeholder="20" oninput="pkRecalcTotal()">
                </div>
                <div>
                    <div class="pk-field-label">Rate / hr (AED)</div>
                    <input type="number" min="0" step="0.01" name="rate" id="pkRate" class="pk-field-input" placeholder="450" oninput="pkRecalcTotal()">
                </div>
            </div>

            <div class="pk-modal-divider" style="margin-top:14px;">
                <div>
                    <div class="pk-total-label">Total excl. VAT</div>
                    <div class="pk-total-value" id="pkTotalPreview">AED 0</div>
                </div>
                <div class="pk-modal-actions">
                    <button type="button" class="pk-btn-cancel" onclick="pkCloseModal()">Cancel</button>
                    <button type="submit" class="pk-btn-save" id="pkSaveBtn">Create package</button>
                </div>
            </div>
        </form>
    </div>
</div>

@php
    $pkPackagesData = $packages->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->name,
        'service_id' => $p->service_id,
        'location_id' => $p->location_id,
        'funding_type' => $p->funding_type,
        'delivery_mode' => $p->delivery_mode,
        'hours_per_week' => $p->hours_per_week,
        'rate' => $p->rate,
    ])->values();
@endphp

@push('scripts')
<script>
    const pkPackages = @json($pkPackagesData);
    const pkStoreUrl = '{{ route('packages.store') }}';
    const pkUpdateUrlTemplate = '{{ route('packages.update', ['package' => '__ID__']) }}';

    function pkRecalcTotal() {
        const hours = parseFloat(document.getElementById('pkHours').value) || 0;
        const rate = parseFloat(document.getElementById('pkRate').value) || 0;
        document.getElementById('pkTotalPreview').textContent = 'AED ' + Math.round(hours * rate).toLocaleString();
    }

    function pkOpenCreate() {
        document.getElementById('pkModalTitle').textContent = 'New package';
        document.getElementById('pkSaveBtn').textContent = 'Create package';
        document.getElementById('pkForm').action = pkStoreUrl;
        document.getElementById('pkMethod').value = '';
        document.getElementById('pkForm').reset();
        document.getElementById('pkFunding').value = 'Self pay';
        document.getElementById('pkDeliveryMode').value = 'Home base';
        pkRecalcTotal();
        document.getElementById('pkModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function pkOpenEdit(id) {
        const pkg = pkPackages.find(p => p.id === id);
        if (!pkg) return;

        document.getElementById('pkModalTitle').textContent = 'Edit package';
        document.getElementById('pkSaveBtn').textContent = 'Save changes';
        document.getElementById('pkForm').action = pkUpdateUrlTemplate.replace('__ID__', id);
        document.getElementById('pkMethod').value = 'PUT';

        document.getElementById('pkName').value = pkg.name || '';
        document.getElementById('pkService').value = pkg.service_id || '';
        document.getElementById('pkLocation').value = pkg.location_id || '';
        document.getElementById('pkFunding').value = pkg.funding_type || 'Self pay';
        document.getElementById('pkDeliveryMode').value = pkg.delivery_mode || 'Home base';
        document.getElementById('pkHours').value = pkg.hours_per_week || '';
        document.getElementById('pkRate').value = pkg.rate || '';
        pkRecalcTotal();

        document.getElementById('pkModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function pkCloseModal() {
        document.getElementById('pkModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    function pkSubmit(event) {
        // Plain form POST (with _method spoofing for PUT) - simplest path,
        // matches the rest of this page's forms (delete), full page reload
        // on save is fine for an admin settings-style table like this.
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('pkModal').addEventListener('click', function (e) {
            if (e.target === this) pkCloseModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') pkCloseModal();
        });
    });
</script>
@endpush

@endsection
