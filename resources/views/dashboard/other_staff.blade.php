@extends('layouts.admin-sidebar')

@section('title', 'Dashboard · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')
@section('content-class', 'content-full-width')

@section('content')
    @php
        $statusColors = [
            'draft' => ['bg' => '#EEF0F2', 'color' => '#6B7A8C', 'label' => 'Draft'],
            'submitted' => ['bg' => '#F7EEDD', 'color' => '#B97F24', 'label' => 'Submitted'],
            'pending_info' => ['bg' => '#F7EEDD', 'color' => '#B97F24', 'label' => 'Pending info'],
            'paid' => ['bg' => '#E3F1E9', 'color' => '#2E7D5B', 'label' => 'Paid'],
            'rejected' => ['bg' => '#F9E4E2', 'color' => '#B3261E', 'label' => 'Rejected'],
        ];
    @endphp

    <style>
        .db-topbar { display: flex; align-items: center; gap: 16px; padding: 16px 28px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA; margin: -22px -28px 18px -28px; }
        .db-topbar-btn { background: #C8355F; color: white; border: none; border-radius: 10px; padding: 11px 18px; font: 800 13px 'Nunito Sans'; cursor: pointer; text-decoration: none; display: inline-block; white-space: nowrap; }
        .db-stats-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .db-stat-card { background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; padding: 14px 16px; min-width: 0; }
        .db-row { display: flex; align-items: center; gap: 14px; padding: 11px 20px; border-top: 1px solid #F3EDE3; flex-wrap: wrap; }
        .db-card-head { flex-wrap: wrap; row-gap: 4px; }

        @media (max-width: 640px) {
            .db-topbar { padding: 14px 16px; flex-direction: column; align-items: stretch; gap: 10px; margin: -22px -28px 14px -28px; }
            .db-topbar-btn { text-align: center; }
            .db-stats-grid { grid-template-columns: repeat(1, 1fr); gap: 8px; margin-bottom: 14px; }
            .db-row { padding: 9px 14px; gap: 8px; }
            .db-card-title { font-size: 14px !important; }
        }
    </style>

    <!-- Top Bar -->
    <div class="db-topbar">
        <div style="flex: 1; min-width: 0;">
            <div style="font: 600 21px/1.2 'Baloo 2'; color: #16436E;">Good morning, {{ $user->full_name }}</div>
            <div style="font: 600 12.5px 'Nunito Sans'; color: #98897A;">{{ date('l, j F Y') }} · Khalifa City, Abu Dhabi</div>
        </div>
        <a href="{{ route('billing.index') }}" class="db-topbar-btn">Open billing</a>
    </div>

    <!-- Stats Cards -->
    <div class="db-stats-grid">
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Draft quotations</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $draftQuotationsCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #8A7D6C;">not yet submitted</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Awaiting payment</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $awaitingPaymentCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #B97F24;">submitted or pending info</div>
        </div>
        <div class="db-stat-card">
            <div style="font: 700 10.5px 'Nunito Sans'; color: #98897A; text-transform: uppercase; letter-spacing: 0.6px;">Paid · this month</div>
            <div style="font: 600 26px 'Baloo 2'; color: #16436E;">{{ $paidThisMonthCount }}</div>
            <div style="font: 700 11.5px 'Nunito Sans'; color: #2E7D5B;">invoices settled</div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div style="background: #FFFFFF; border: 1px solid #EBE4DA; border-radius: 14px; overflow: hidden;">
        <div class="db-card-head" style="display: flex; align-items: baseline; gap: 10px; padding: 16px 20px 10px;">
            <div class="db-card-title" style="font: 600 16px 'Baloo 2'; color: #16436E; flex: 1;">Recent invoices &amp; quotations</div>
            <a href="{{ route('billing.index') }}" style="background: none; border: none; font: 800 12px 'Nunito Sans'; color: #C8355F; cursor: pointer; padding: 0; text-decoration: none;">Open billing →</a>
        </div>
        @forelse ($recentInvoices as $invoice)
            @php $status = $statusColors[$invoice->status] ?? ['bg' => '#EEF0F2', 'color' => '#6B7A8C', 'label' => ucfirst($invoice->status)]; @endphp
            <a href="{{ route('billing.invoices.show', $invoice) }}" class="db-row" style="text-decoration: none; cursor: pointer;">
                <div style="flex: 1; min-width: 0;">
                    <div style="font: 800 13.5px 'Nunito Sans'; color: #2B3A4C;">{{ $invoice->invoice_number }} · {{ $invoice->patient->lead->child_name ?? $invoice->bill_to ?? 'Unknown' }}</div>
                    <div style="font: 600 11.5px 'Nunito Sans'; color: #98897A;">{{ $invoice->payer }} · issued {{ $invoice->issue_date?->format('d M Y') }}</div>
                </div>
                <div style="font: 800 13.5px 'Nunito Sans'; color: #16436E; white-space: nowrap;">AED {{ number_format($invoice->subtotal, 0) }}</div>
                <span style="background: {{ $status['bg'] }}; color: {{ $status['color'] }}; border-radius: 7px; padding: 3px 9px; font: 800 11px 'Nunito Sans'; white-space: nowrap;">{{ $status['label'] }}</span>
            </a>
        @empty
            <div style="padding: 24px 20px; text-align: center; font: 700 12.5px 'Nunito Sans'; color: #98897A;">No invoices or quotations yet.</div>
        @endforelse
    </div>
@endsection
