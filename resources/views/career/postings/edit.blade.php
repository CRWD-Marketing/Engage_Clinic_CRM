@extends('layouts.admin-sidebar')

@section('title', 'Edit Job Posting · Engage Clinic')
@section('page-title', '')
@section('page-subtitle', '')

@section('content')

@include('career.postings._form-styles')

<div class="jp-form-page">

    <div class="flex items-center justify-between" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <div class="jp-form-title">Edit Job Posting</div>
            <div class="jp-form-subtitle">{{ $posting->title }}</div>
        </div>
        <a href="{{ route('job-postings.index') }}" class="jp-form-back">
            <i class="fas fa-arrow-left text-[10px]"></i> Back to Postings
        </a>
    </div>

    <div class="jp-form-rule"></div>

    @if ($errors->any())
        <div class="jp-form-alert is-error is-visible" style="margin-bottom: 20px; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation text-xs"></i>
            <span>Please fix the errors below.</span>
        </div>
    @endif

    <form method="POST" action="{{ route('job-postings.update', $posting) }}" class="jp-form-card">
        @csrf
        @method('PUT')
        @include('career.postings._fields', ['posting' => $posting])

        <div class="jp-form-rule"></div>

        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('job-postings.index') }}" class="jp-form-btn-ghost inline-flex items-center h-9 px-4 text-sm">Cancel</a>
            <button type="submit" class="jp-form-btn-primary inline-flex items-center gap-2 h-9 px-5 text-sm">
                <i class="fas fa-check text-[10px]"></i> Save Changes
            </button>
        </div>
    </form>

</div>

@endsection
