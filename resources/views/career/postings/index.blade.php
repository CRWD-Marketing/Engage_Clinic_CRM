@extends('layouts.admin-sidebar')

@section('title', 'Job Postings · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')

@include('career.postings._form-styles')

<style>
    .jp-page { margin: -22px -28px; padding: 26px 32px 40px; background: #FFFDFA; min-height: 100%; box-sizing: border-box; }

    .jp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 12px; }
    .jp-header-title { font: 600 26px 'Baloo 2'; color: #16436E; line-height: 1.2; }
    .jp-header-sub { font: 600 13px 'Nunito Sans'; color: #98897A; margin-top: 4px; }

    .jp-btn-primary {
        background: #C8355F; color: #fff; font: 800 13px 'Nunito Sans'; border-radius: 8px;
        padding: 10px 18px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
        box-shadow: 0 4px 14px rgba(200,53,95,0.28); border: none; cursor: pointer;
    }
    .jp-btn-primary:hover { background: #A82348; }

    .jp-btn-ghost {
        color: #5A6B7E; border-radius: 8px; font: 700 13px 'Nunito Sans'; padding: 10px 18px;
        text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid #E2DACE; background: #FFFFFF; cursor: pointer;
    }
    .jp-btn-ghost:hover { background: #F6F3EE; color: #16436E; }

    .jp-table-card { border: 1px solid #EBE4DA; border-radius: 16px; background: #fff; overflow: hidden; box-shadow: 0 2px 10px rgba(22,42,60,0.04); }
    .jp-table { width: 100%; border-collapse: collapse; }
    .jp-table th {
        text-align: left; font: 800 11px 'Nunito Sans'; text-transform: uppercase; letter-spacing: 0.06em;
        color: #98897A; padding: 13px 20px; border-bottom: 1px solid #EBE4DA; background: #FFFDFA;
    }
    .jp-table td { padding: 15px 20px; border-bottom: 1px solid #F3EDE3; font-size: 0.875rem; vertical-align: top; }
    .jp-table tr:last-child td { border-bottom: none; }
    .jp-table tr:hover td { background: #FFFDFA; }
    .jp-title { font: 800 13.5px 'Nunito Sans'; color: #2B3A4C; }
    .jp-type { color: #98897A; font: 600 11.5px 'Nunito Sans'; margin-top: 3px; }

    .jp-badge { border-radius: 999px; padding: 4px 11px; font: 800 11px 'Nunito Sans'; white-space: nowrap; }
    .jp-badge.active { background: #E4F6EB; color: #1E8A4C; }
    .jp-badge.inactive { background: #EFEBE3; color: #7A6E60; }

    .jp-actions { display: flex; gap: 16px; align-items: center; }
    .jp-actions a, .jp-actions button {
        background: none; border: none; padding: 0; font: 800 12.5px 'Nunito Sans'; cursor: pointer; text-decoration: none;
    }
    .jp-actions a { color: #24619C; }
    .jp-actions a:hover { text-decoration: underline; }
    .jp-actions button.delete { color: #A82348; }
    .jp-actions button.delete:hover { text-decoration: underline; }

    .jp-empty { padding: 56px 20px; text-align: center; color: #98897A; font: 700 12.5px 'Nunito Sans'; }

    .jp-success-alert {
        background: #E4F6EB; border: 1px solid #BFE9CE; color: #1E8A4C; border-radius: 10px;
        padding: 12px 16px; font: 700 13px 'Nunito Sans'; margin-bottom: 18px;
    }

    /* New Posting modal */
    .jp-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(22, 42, 60, 0.45);
        align-items: center; justify-content: center; z-index: 9999; padding: 16px;
    }
    .jp-modal-overlay.open { display: flex; }
    .jp-modal-box {
        width: 640px; max-width: 100%; max-height: 88vh; overflow-y: auto; background: #FFFDFA;
        border-radius: 18px; padding: 26px 28px; box-shadow: 0 20px 60px rgba(22,42,60,0.3);
    }
    .jp-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
    .jp-modal-header-title { font: 600 20px 'Baloo 2'; color: #16436E; }
    .jp-modal-close {
        width: 32px; height: 32px; border-radius: 9px; border: 1px solid #E2DACE; background: #FFFFFF;
        color: #5A6B7E; font: 800 15px/1 'Nunito Sans'; cursor: pointer; display: flex;
        align-items: center; justify-content: center; flex-shrink: 0;
    }
    .jp-modal-close:hover { background: #F6F3EE; }
    .jp-modal-actions { display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #F3EDE3; padding-top: 16px; margin-top: 4px; }
</style>

<div class="jp-page">

    <div class="jp-header">
        <div>
            <div class="jp-header-title">Job Postings</div>
            <div class="jp-header-sub">Manage what appears on the public Careers page.</div>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('job-applications.index') }}" class="jp-btn-ghost">
                <i class="fas fa-inbox text-[10px]"></i> View Applicants
            </a>
            <button type="button" id="jp-new-posting-btn" class="jp-btn-primary">
                <i class="fas fa-plus text-[10px]"></i> New Posting
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="jp-success-alert">{{ session('success') }}</div>
    @endif

    <div class="jp-table-card">
        @if ($postings->isEmpty())
            <div class="jp-empty">No job postings yet. Create one to have it appear on the public Careers page.</div>
        @else
            <table class="jp-table">
                <thead>
                    <tr>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Applicants</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($postings as $posting)
                        <tr>
                            <td>
                                <div class="jp-title">{{ $posting->title }}</div>
                                @php
                                    $typeLocation = collect([$posting->employment_type, $posting->location])->filter()->implode(' · ');
                                @endphp
                                @if ($typeLocation)
                                    <div class="jp-type">{{ $typeLocation }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="jp-badge {{ $posting->status }}">{{ ucfirst($posting->status) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('job-applications.index') }}" style="color:#24619C; font-weight:800; text-decoration:none;">
                                    {{ $posting->applications()->count() }}
                                </a>
                            </td>
                            <td>
                                <div class="jp-actions">
                                    <a href="{{ route('job-postings.edit', $posting) }}">Edit</a>
                                    <form action="{{ route('job-postings.destroy', $posting) }}" method="POST" onsubmit="return confirm('Delete this posting? It will disappear from the public Careers page immediately.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="delete">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>

<!-- New Posting modal -->
<div class="jp-modal-overlay {{ $errors->any() ? 'open' : '' }}" id="jp-modal-overlay">
    <div class="jp-modal-box">
        <div class="jp-modal-header">
            <div class="jp-modal-header-title">New Job Posting</div>
            <button type="button" class="jp-modal-close" id="jp-modal-close" aria-label="Close">✕</button>
        </div>

        @if ($errors->any())
            <div class="jp-form-alert is-error is-visible" style="margin-bottom: 16px; align-items: center; gap: 8px;">
                <i class="fas fa-circle-exclamation text-xs"></i>
                <span>Please fix the errors below.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('job-postings.store') }}">
            @csrf
            @include('career.postings._fields', ['posting' => null])

            <div class="jp-modal-actions">
                <button type="button" class="jp-form-btn-ghost inline-flex items-center h-9 px-4 text-sm" id="jp-modal-cancel">Cancel</button>
                <button type="submit" class="jp-form-btn-primary inline-flex items-center gap-2 h-9 px-5 text-sm">
                    <i class="fas fa-plus text-[10px]"></i> Create Posting
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const overlay = document.getElementById('jp-modal-overlay');

        function openModal() {
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('jp-new-posting-btn').addEventListener('click', openModal);
        document.getElementById('jp-modal-close').addEventListener('click', closeModal);
        document.getElementById('jp-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
        });
    })();
</script>

@endsection
