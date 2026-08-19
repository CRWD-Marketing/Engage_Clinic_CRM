@extends('layouts.auth')

@section('title', 'Forgot Password · Engage Clinic')

@section('panel-heading')
    Forgot your<br><span class="accent">password?</span>
@endsection

@section('panel-text')
    Enter the work email tied to your Engage Clinic account and we'll send you a link to choose a new password.
@endsection

@section('form-content')
    <div class="form-header">
        <h2>Reset your password</h2>
        <p>We'll email you a link to set a new one.</p>
    </div>

    @if (session('status'))
        <div class="status-banner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6 9 17l-5-5"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST" novalidate>
        @csrf

        <div class="field">
            <label for="email">Work email</label>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 6 8 7 8-7"/></svg>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="@error('email') error @enderror"
                    placeholder="you@engageclinic.ae"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
            @error('email')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 8px;">
            Send reset link
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>

    <a href="{{ route('login') }}" class="link-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to sign in
    </a>
@endsection
