@extends('layouts.admin-sidebar')

@section('title', 'Billing · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    @php
        $statusColors = [
            'draft' => ['bg' => '#F1EDE5', 'color' => '#8A7D6C'],
            'submitted' => ['bg' => '#E7EFF7', 'color' => '#24619C'],
            'pending_info' => ['bg' => '#F7EEDD', 'color' => '#B97F24'],
            'paid' => ['bg' => '#E3F1E9', 'color' => '#2E7D5B'],
            'rejected' => ['bg' => '#F9E4E2', 'color' => '#B3261E'],
        ];
        $statusLabels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'pending_info' => 'Pending info',
            'paid' => 'Paid',
            'rejected' => 'Rejected',
        ];
        $payerOptions = ['Daman', 'Daman Enhanced', 'Thiqa', 'ADNIC', 'AXA/GIG', 'Self-pay'];
        $activePayers = $recentClaims->pluck('payer')->unique()->filter(fn ($p) => $p !== 'Self-pay')->values();
    @endphp

    <style>
        .inv-modal-overlay {
            display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45);
            align-items: center; justify-content: center; z-index: 9999; padding: 16px;
        }
        .inv-modal-box {
            width: 700px; max-width: 100%; max-height: 92vh; overflow-y: auto; background: #FFFDFA;
            border-radius: 18px; padding: 26px 28px; display: flex; flex-direction: column; gap: 16px;
            box-shadow: 0 20px 60px rgba(22,42,60,0.3);
        }
        .inv-modal-header { display: flex; align-items: center; gap: 12px; }
        .inv-modal-title { font: 600 20px 'Baloo 2'; color: #16436E; }
        .inv-modal-sub { font: 600 12px 'Nunito Sans'; color: #98897A; }
        .inv-modal-close {
            width: 32px; height: 32px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF;
            color: #5A6B7E; font: 800 15px/1 'Nunito Sans'; cursor: pointer; display: flex;
            align-items: center; justify-content: center; flex-shrink: 0;
        }
        .inv-modal-close:hover { background: #F6F3EE; }
        .inv-field-label { font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 4px; }
        .inv-field-input, .inv-field-select {
            width: 100%; padding: 9px 12px; border: 1px solid #E2DACE; border-radius: 8px;
            background: #F6F3EE; font: 700 13.5px 'Nunito Sans'; color: #2B3A4C; outline: none; box-sizing: border-box;
        }
        .inv-field-input:focus, .inv-field-select:focus { border-color: #C8355F; box-shadow: 0 0 0 3px rgba(200,53,95,0.12); }
        .inv-field-input:disabled, .inv-field-select:disabled { opacity: .6; cursor: not-allowed; }
        .inv-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .inv-grid-3col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        .inv-modal-actions { display: flex; gap: 10px; border-top: 1px solid #F3EDE3; padding-top: 14px; margin-top: 2px; }
        .inv-btn-save { flex: 1; background: #C8355F; color: white; border: none; border-radius: 8px; padding: 11px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer; }
        .inv-btn-save:hover { background: #A82348; }
        .inv-btn-save:disabled { opacity: .6; cursor: not-allowed; }
        .inv-btn-cancel { background: #FFFFFF; color: #5A6B7E; border: 1px solid #E2DACE; border-radius: 8px; padding: 11px 20px; font: 800 13px 'Nunito Sans'; cursor: pointer; }
        .inv-btn-cancel:hover { background: #F6F3EE; }
        .inv-btn-print { background: none; border: none; color: #C8355F; font: 800 13px 'Nunito Sans'; cursor: pointer; padding: 11px 4px; white-space: nowrap; }
        .inv-btn-print:hover { color: #A82348; }
        .inv-btn-print:disabled { opacity: .6; cursor: not-allowed; }
        .inv-lines-scroll { overflow-x: auto; }
        .inv-line-header {
            display: grid; grid-template-columns: 1.8fr 1.3fr 64px 84px 92px 26px; gap: 8px; min-width: 560px;
            padding: 0 4px 4px; font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px;
        }
        .inv-line-row {
            display: grid; grid-template-columns: 1.8fr 1.3fr 64px 84px 92px 26px; gap: 8px; align-items: center; min-width: 560px;
            padding: 4px; margin-bottom: 6px;
        }
        .inv-line-amount { font: 800 12.5px 'Nunito Sans'; color: #16436E; text-align: right; padding-right: 4px; }
        .inv-line-remove {
            width: 26px; height: 26px; border-radius: 7px; border: 1px solid #E8CFCC; background: #FFFFFF;
            color: #B3261E; font: 800 13px/1 'Nunito Sans'; cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
        .inv-line-remove:hover { background: #F9E4E2; }
        .inv-add-line {
            background: #FFFFFF; border: 1px dashed #D9CDBD; border-radius: 9px; padding: 9px; text-align: center;
            font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer;
        }
        .inv-add-line:hover { background: #FBF3E4; }
        .inv-totals { background: #F6F3EE; border-radius: 10px; padding: 12px 16px; display: flex; flex-direction: column; gap: 6px; }
        .inv-totals-row { display: flex; justify-content: space-between; font: 700 12.5px 'Nunito Sans'; color: #5A6B7E; }
        .inv-totals-row.final { border-top: 1px solid #E2DACE; padding-top: 8px; margin-top: 2px; font: 800 15px 'Baloo 2'; color: #16436E; }
        .claim-status-btn {
            border: none; border-radius: 7px; padding: 5px 9px 5px 10px; font: 800 11px 'Nunito Sans';
            cursor: pointer; display: inline-flex; align-items: center; gap: 5px; outline: none;
        }
        .claim-status-btn:hover { filter: brightness(.96); }
        .claim-status-btn svg { flex-shrink: 0; opacity: .7; }
        .claim-status-menu {
            position: fixed; display: none; background: #FFFFFF; border: 1px solid #E2DACE; border-radius: 12px;
            box-shadow: 0 14px 34px rgba(22,42,60,0.18); padding: 6px; z-index: 10000; min-width: 152px;
        }
        .claim-status-menu.open { display: block; }
        .claim-status-option {
            display: flex; align-items: center; gap: 9px; padding: 9px 11px; border-radius: 8px;
            font: 700 12.5px 'Nunito Sans'; color: #2B3A4C; cursor: pointer;
        }
        .claim-status-option:hover { background: #F6F3EE; }
        .claim-status-option.active { background: #FBF3F6; color: #A82348; font-weight: 800; }
        .claim-status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .claim-row { transition: background-color .12s ease; }
        .claim-row:hover { background: #FBF9F5; }

        /* ===== Responsive layout ===== */
        .bl-wrap { flex: 1; display: flex; flex-direction: column; min-height: 0; margin: -22px -28px 0 -28px; }
        .bl-topbar { display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; }
        .bl-topbar-new-btn { background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; white-space: nowrap; }
        .bl-main { flex: 1; overflow-y: auto; padding: 26px 32px; display: flex; flex-direction: column; gap: 24px; }
        .bl-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .bl-stat-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 18px 20px; min-width: 0; }
        .bl-main-grid { display: grid; grid-template-columns: 1.7fr 1fr; gap: 22px; align-items: start; }
        .bl-side-col { display: flex; flex-direction: column; gap: 22px; min-width: 0; }
        .bl-claims-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden; min-width: 0; }
        .bl-claims-scroll { overflow-x: auto; }
        .bl-claim-cols { display: grid; grid-template-columns: 1fr 90px 90px 70px 140px; gap: 10px; min-width: 600px; align-items: center; text-align: center; }

        @media (max-width: 1080px) {
            .bl-main-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .bl-topbar { padding: 14px 16px; flex-direction: column; align-items: stretch; gap: 10px; }
            .bl-topbar-new-btn { text-align: center; }
            .bl-main { padding: 16px; gap: 18px; }
            .bl-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .bl-stat-card { padding: 14px 16px; }
            .bl-main-grid { gap: 16px; }
            .inv-modal-box { padding: 18px 16px; border-radius: 14px; }
            .inv-grid-2col, .inv-grid-3col { grid-template-columns: 1fr; }
            .inv-modal-actions { flex-wrap: wrap; }
            .inv-btn-print { flex: 1 1 100%; text-align: center; }
        }
        @media (max-width: 380px) {
            .bl-stats-grid { grid-template-columns: 1fr; }
        }
    </style>

    <!-- Billing & Insurance -->
    <div class="bl-wrap">

        <!-- Top Bar -->
        <div class="bl-topbar">
            <div style="flex: 1;">
                <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Billing &amp; insurance</div>
                <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">
                    {{ now()->format('F Y') }}{{ $activePayers->isNotEmpty() ? ' · ' . $activePayers->implode(', ') . ' claims' : ' · no claims recorded yet' }}
                </div>
            </div>
            <button type="button" id="inv-open-btn" class="bl-topbar-new-btn">+ New invoice</button>
        </div>

        <!-- Main Content -->
        <div class="bl-main">

            <!-- Stats Cards -->
            <div class="bl-stats-grid">
                <div class="bl-stat-card">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">Invoiced · MTD</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #16436E;">AED {{ number_format($invoicedMtd) }}</div>
                </div>
                <div class="bl-stat-card">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">Collected</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #2E7D5B;">AED {{ number_format($collectedMtd) }}</div>
                </div>
                <div class="bl-stat-card">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">Outstanding claims</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #B97F24;">AED {{ number_format($outstandingClaims) }}</div>
                </div>
                <div class="bl-stat-card">
                    <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;">Avg. claim cycle</div>
                    <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $avgClaimCycleDays !== null ? $avgClaimCycleDays . ' days' : '—' }}</div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="bl-main-grid">

                <!-- Recent Claims Table -->
                <div class="bl-claims-card">
                    <div style="font: 600 16px 'Baloo 2'; color: #16436E; padding: 20px 24px 12px;">Recent claims</div>
                    <div class="bl-claims-scroll">
                        <div class="bl-claim-cols" style="padding: 10px 24px; font: 700 10px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.4px; border-bottom: 1px solid #F3EDE3;">
                            <div style="text-align: left;">Patient</div>
                            <div>Insurer</div>
                            <div>Amount</div>
                            <div>Sessions</div>
                            <div>Status</div>
                        </div>

                        @forelse ($recentClaims as $claim)
                            @php
                                $colors = $statusColors[$claim->status] ?? $statusColors['submitted'];
                                $childName = $claim->patient?->lead?->child_name ?? 'Unknown patient';
                                $printUrl = route('billing.invoices.show', $claim);
                            @endphp
                            <div class="claim-row bl-claim-cols" style="padding: 15px 24px; {{ !$loop->last ? 'border-bottom: 1px solid #F3EDE3;' : '' }}">
                                <div style="min-width: 0; text-align: left;">
                                    <a href="{{ $printUrl }}" target="_blank" style="font: 800 13px 'Nunito Sans'; color: #2B3A4C; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none; display: block;">{{ $childName }}</a>
                                    <div style="font: 700 11px 'Nunito Sans'; color: #98897A; margin-top: 2px;">{{ $claim->invoice_number }}{{ $claim->claim_reference ? ' · ' . $claim->claim_reference : '' }} · {{ $claim->period->format('M Y') }}</div>
                                </div>
                                <div style="font: 700 11.5px 'Nunito Sans'; color: #5A6B7E;">{{ $claim->payer }}</div>
                                <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">AED {{ number_format($claim->subtotal) }}</div>
                                <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">{{ (int) $claim->sessions_count }}</div>
                                <div>
                                    <button type="button" class="claim-status-btn" data-invoice-id="{{ $claim->id }}" data-status="{{ $claim->status }}" style="background: {{ $colors['bg'] }}; color: {{ $colors['color'] }};">
                                        {{ $statusLabels[$claim->status] ?? ucfirst($claim->status) }}
                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"></path></svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div style="padding: 32px 24px; text-align: center; font: 700 12.5px 'Nunito Sans'; color: #98897A;">No invoices yet — create the first one with "+ New invoice".</div>
                        @endforelse
                    </div>
                </div>

                <!-- Shared status dropdown, positioned next to whichever pill is open -->
                <div id="claim-status-menu" class="claim-status-menu">
                    @foreach ($statusLabels as $value => $label)
                        <div class="claim-status-option" data-value="{{ $value }}">
                            <span class="claim-status-dot" style="background: {{ $statusColors[$value]['color'] }};"></span>
                            {{ $label }}
                        </div>
                    @endforeach
                </div>

                <div class="bl-side-col">
                    <!-- Outstanding Claim Aging -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 22px 24px;">
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E;">Outstanding claim aging</div>
                        <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A; margin-bottom: 18px;">Open claims by days since submission</div>
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            @foreach ($agingBuckets as $label => $bucket)
                                <div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                        <div style="font: 700 12.5px 'Nunito Sans'; color: #2B3A4C;">{{ $label }}</div>
                                        <div style="font: 800 12px 'Nunito Sans'; color: #16436E;">AED {{ number_format($bucket['amount']) }}</div>
                                    </div>
                                    <div style="height: 9px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                        <div style="width: {{ $bucket['amount'] > 0 ? max(4, round($bucket['amount'] / $agingMax * 100)) : 0 }}%; height: 100%; background: {{ $label === '60+ days' ? '#B3261E' : '#B97F24' }}; border-radius: 5px;"></div>
                                    </div>
                                    <div style="font: 600 11px 'Nunito Sans'; color: #98897A; margin-top: 3px;">{{ $bucket['count'] }} {{ Str::plural('claim', $bucket['count']) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Revenue by Payer -->
                    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 22px 24px;">
                        <div style="font: 600 16px 'Baloo 2'; color: #16436E; margin-bottom: 18px;">Revenue by payer · MTD</div>
                        @if ($revenueByPayer->isEmpty())
                            <div style="font: 700 12.5px 'Nunito Sans'; color: #98897A;">No invoices issued this month yet.</div>
                        @else
                            <div style="display: flex; flex-direction: column; gap: 16px;">
                                @foreach ($revenueByPayer as $row)
                                    <div>
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                            <div style="font: 700 13px 'Nunito Sans'; color: #2B3A4C;">{{ $row['payer'] }}</div>
                                            <div style="font: 800 12.5px 'Nunito Sans'; color: #16436E;">{{ $row['percent'] }}%</div>
                                        </div>
                                        <div style="height: 10px; background: #F3EDE3; border-radius: 5px; overflow: hidden;">
                                            <div style="width: {{ $row['percent'] }}%; height: 100%; background: {{ $row['color'] }}; border-radius: 5px;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if ($rejectedAlert)
                            <div style="background: #FBF3E4; border: 1px solid #EBDCBB; border-radius: 10px; padding: 10px 14px; margin-top: 16px; font: 600 12px/1.5 'Nunito Sans'; color: #8A5A10;">
                                {{ $rejectedAlert->patient?->lead?->child_name ?? 'A patient' }}'s {{ $rejectedAlert->payer }} claim ({{ $rejectedAlert->claim_reference ?? $rejectedAlert->invoice_number }}) was rejected — resubmit with updated CPT codes.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Invoice Modal -->
    <div id="inv-modal" class="inv-modal-overlay">
        <div class="inv-modal-box">
            <div class="inv-modal-header">
                <div style="flex: 1;">
                    <div class="inv-modal-title">New invoice — manual entry</div>
                    <div class="inv-modal-sub">Log a claim against a patient's insurance, or a self-pay invoice</div>
                </div>
                <button type="button" id="inv-modal-close" class="inv-modal-close">✕</button>
            </div>

            <form id="inv-form">
                <div class="inv-grid-2col">
                    <div>
                        <div class="inv-field-label">Patient (child) *</div>
                        <select id="inv-patient" class="inv-field-select" required>
                            <option value="">Select a patient…</option>
                            @foreach ($patients as $patient)
                                <option value="{{ $patient->id }}"
                                    data-guardian="{{ $patient->lead->parent_guardian_name }}"
                                    data-insurance="{{ $patient->primaryAuthorization()->payer_name ?? '' }}">
                                    {{ $patient->lead->child_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="inv-field-label">Bill to (parent / guardian) *</div>
                        <input id="inv-bill-to" type="text" placeholder="e.g. Mr. Saif Al Mansoori" class="inv-field-input" required>
                    </div>
                </div>

                <div class="inv-grid-3col" style="margin-top: 12px;">
                    <div>
                        <div class="inv-field-label">Payer</div>
                        <select id="inv-payer" class="inv-field-select">
                            @foreach ($payerOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="inv-field-label">Coverage</div>
                        <select id="inv-coverage" class="inv-field-select">
                            <option value="0">0%</option>
                            <option value="50">50%</option>
                            <option value="70">70%</option>
                            <option value="80" selected>80%</option>
                            <option value="100">100%</option>
                        </select>
                    </div>
                    <div>
                        <div class="inv-field-label">Period</div>
                        <input id="inv-period" type="month" class="inv-field-input" value="{{ now()->format('Y-m') }}" required>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <div class="inv-lines-scroll">
                        <div class="inv-line-header">
                            <div>Service</div>
                            <div>Therapist</div>
                            <div>Sessions</div>
                            <div>Rate (AED)</div>
                            <div style="text-align: right;">Amount</div>
                            <div></div>
                        </div>
                        <div id="inv-line-items"></div>
                    </div>
                    <div id="inv-add-line" class="inv-add-line">+ Add line item</div>
                </div>

                <div class="inv-totals" style="margin-top: 14px;">
                    <div class="inv-totals-row">
                        <div>Subtotal</div>
                        <div id="inv-subtotal">AED 0</div>
                    </div>
                    <div class="inv-totals-row">
                        <div id="inv-coverage-label">Insurance coverage</div>
                        <div id="inv-coverage-amount">– AED 0</div>
                    </div>
                    <div class="inv-totals-row final">
                        <div>Patient responsibility</div>
                        <div id="inv-patient-resp">AED 0</div>
                    </div>
                </div>

                <div class="inv-modal-actions">
                    <button type="submit" id="inv-save-btn" class="inv-btn-save">Save invoice</button>
                    <button type="button" id="inv-modal-cancel" class="inv-btn-cancel">Cancel</button>
                    <button type="button" id="inv-print-btn" class="inv-btn-print">Printable template →</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Line item row template -->
    <template id="inv-line-template">
        <div class="inv-line-row">
            <select class="inv-field-select inv-line-service" required>
                <option value="">Select service…</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" data-rate="{{ $service->default_rate }}" data-cpt="{{ $service->cpt_code }}">{{ $service->name }}</option>
                @endforeach
            </select>
            <select class="inv-field-select inv-line-therapist" required>
                <option value="">Select…</option>
                @foreach ($therapists as $therapist)
                    <option value="{{ $therapist->id }}">{{ $therapist->first_name }} {{ $therapist->last_name }}</option>
                @endforeach
            </select>
            <input type="number" min="1" value="1" class="inv-field-input inv-line-sessions" required>
            <input type="number" min="0.01" step="0.01" value="0" class="inv-field-input inv-line-rate" required>
            <div class="inv-line-amount">0</div>
            <button type="button" class="inv-line-remove" title="Remove line">✕</button>
        </div>
    </template>

    @push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('inv-modal');
            const form = document.getElementById('inv-form');
            const lineItemsEl = document.getElementById('inv-line-items');
            const lineTemplate = document.getElementById('inv-line-template');
            const patientSelect = document.getElementById('inv-patient');
            const billToInput = document.getElementById('inv-bill-to');
            const payerSelect = document.getElementById('inv-payer');
            const coverageSelect = document.getElementById('inv-coverage');
            const saveBtn = document.getElementById('inv-save-btn');

            const storeUrl = @json(route('billing.invoices.store'));
            const updateUrlTemplate = @json(route('billing.invoices.update', ['invoice' => '__ID__']));

            function csrf() {
                return document.querySelector('meta[name="csrf-token"]')?.content;
            }

            function fmt(n) {
                return 'AED ' + Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
            }

            function addLineItem() {
                const node = lineTemplate.content.firstElementChild.cloneNode(true);
                lineItemsEl.appendChild(node);
                updateRemoveButtons();
                recalcTotals();
            }

            function updateRemoveButtons() {
                const rows = lineItemsEl.querySelectorAll('.inv-line-row');
                rows.forEach(row => {
                    row.querySelector('.inv-line-remove').style.visibility = rows.length > 1 ? 'visible' : 'hidden';
                });
            }

            function recalcTotals() {
                let subtotal = 0;
                lineItemsEl.querySelectorAll('.inv-line-row').forEach(row => {
                    const sessions = parseFloat(row.querySelector('.inv-line-sessions').value) || 0;
                    const rate = parseFloat(row.querySelector('.inv-line-rate').value) || 0;
                    const amount = sessions * rate;
                    row.querySelector('.inv-line-amount').textContent = amount.toLocaleString('en-US', { maximumFractionDigits: 0 });
                    subtotal += amount;
                });

                const isSelfPay = payerSelect.value === 'Self-pay';
                coverageSelect.disabled = isSelfPay;
                const coveragePct = isSelfPay ? 0 : (parseFloat(coverageSelect.value) || 0);
                const coverageAmount = subtotal * coveragePct / 100;
                const patientResp = subtotal - coverageAmount;

                document.getElementById('inv-subtotal').textContent = fmt(subtotal);
                document.getElementById('inv-coverage-label').textContent = isSelfPay ? 'Insurance coverage' : `Insurance coverage — ${payerSelect.value} (${coveragePct}%)`;
                document.getElementById('inv-coverage-amount').textContent = '– ' + fmt(coverageAmount);
                document.getElementById('inv-patient-resp').textContent = fmt(patientResp);
            }

            function openModal() {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (!lineItemsEl.children.length) addLineItem();
                recalcTotals();
            }

            function closeModal() {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                form.reset();
                lineItemsEl.innerHTML = '';
            }

            document.getElementById('inv-open-btn').addEventListener('click', openModal);
            document.getElementById('inv-modal-close').addEventListener('click', closeModal);
            document.getElementById('inv-modal-cancel').addEventListener('click', closeModal);
            modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.style.display === 'flex') closeModal(); });

            document.getElementById('inv-add-line').addEventListener('click', addLineItem);

            patientSelect.addEventListener('change', () => {
                const opt = patientSelect.selectedOptions[0];
                billToInput.value = opt?.dataset.guardian || '';
                const insurance = opt?.dataset.insurance;
                if (insurance && [...payerSelect.options].some(o => o.value === insurance)) {
                    payerSelect.value = insurance;
                }
                recalcTotals();
            });

            payerSelect.addEventListener('change', recalcTotals);
            coverageSelect.addEventListener('change', recalcTotals);

            lineItemsEl.addEventListener('input', e => {
                if (e.target.matches('.inv-line-sessions, .inv-line-rate')) recalcTotals();
            });

            lineItemsEl.addEventListener('change', e => {
                if (e.target.matches('.inv-line-service')) {
                    const opt = e.target.selectedOptions[0];
                    const row = e.target.closest('.inv-line-row');
                    if (opt?.dataset.rate) row.querySelector('.inv-line-rate').value = opt.dataset.rate;
                    recalcTotals();
                }
            });

            lineItemsEl.addEventListener('click', e => {
                if (e.target.matches('.inv-line-remove')) {
                    if (lineItemsEl.querySelectorAll('.inv-line-row').length > 1) {
                        e.target.closest('.inv-line-row').remove();
                        updateRemoveButtons();
                        recalcTotals();
                    }
                }
            });

            const printBtn = document.getElementById('inv-print-btn');

            function resetButtons() {
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save invoice';
                printBtn.disabled = false;
                printBtn.textContent = 'Printable template →';
            }

            function saveInvoice(openPrint) {
                const lineItems = [...lineItemsEl.querySelectorAll('.inv-line-row')].map(row => ({
                    service_id: row.querySelector('.inv-line-service').value,
                    therapist_id: row.querySelector('.inv-line-therapist').value,
                    sessions: row.querySelector('.inv-line-sessions').value,
                    rate: row.querySelector('.inv-line-rate').value,
                }));

                const payload = {
                    patient_id: patientSelect.value,
                    bill_to: billToInput.value,
                    payer: payerSelect.value,
                    coverage_percent: coverageSelect.value,
                    period: document.getElementById('inv-period').value,
                    line_items: lineItems,
                };

                saveBtn.disabled = true;
                printBtn.disabled = true;
                if (openPrint) {
                    printBtn.textContent = 'Saving…';
                } else {
                    saveBtn.textContent = 'Saving…';
                }

                fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        if (openPrint) {
                            window.open(data.print_url, '_blank');
                        }
                        location.reload();
                    } else {
                        alert('Could not save invoice: ' + (data.errors ? Object.values(data.errors).flat().join(', ') : 'unknown error'));
                        resetButtons();
                    }
                })
                .catch(() => {
                    alert('Network error. Please try again.');
                    resetButtons();
                });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                saveInvoice(false);
            });

            printBtn.addEventListener('click', function () {
                if (!form.reportValidity()) return;
                saveInvoice(true);
            });

            const statusMenu = document.getElementById('claim-status-menu');
            let activeStatusBtn = null;

            function closeStatusMenu() {
                statusMenu.classList.remove('open');
                activeStatusBtn = null;
            }

            document.querySelectorAll('.claim-status-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();

                    if (activeStatusBtn === this) {
                        closeStatusMenu();
                        return;
                    }

                    activeStatusBtn = this;
                    const rect = this.getBoundingClientRect();
                    statusMenu.style.top = (rect.bottom + 6) + 'px';
                    statusMenu.style.left = 'auto';
                    statusMenu.style.right = (window.innerWidth - rect.right) + 'px';

                    statusMenu.querySelectorAll('.claim-status-option').forEach(opt => {
                        opt.classList.toggle('active', opt.dataset.value === this.dataset.status);
                    });

                    statusMenu.classList.add('open');
                });
            });

            statusMenu.querySelectorAll('.claim-status-option').forEach(opt => {
                opt.addEventListener('click', function () {
                    if (!activeStatusBtn) return;

                    const url = updateUrlTemplate.replace('__ID__', activeStatusBtn.dataset.invoiceId);
                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf(),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ status: this.dataset.value }),
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Could not update status.');
                            closeStatusMenu();
                        }
                    })
                    .catch(() => {
                        alert('Network error. Please try again.');
                        closeStatusMenu();
                    });
                });
            });

            document.addEventListener('click', e => {
                if (statusMenu.classList.contains('open') && !statusMenu.contains(e.target)) closeStatusMenu();
            });
            document.addEventListener('keydown', e => { if (e.key === 'Escape') closeStatusMenu(); });
        })();
    </script>
    @endpush
@endsection
