@extends('layouts.auth')

@section('title', 'Reset Password · Engage Clinic')

@section('panel-heading')
    Choose a<br><span class="accent">new password</span>
@endsection

@section('panel-text')
    Pick something you haven't used before. It should be at least 8 characters.
@endsection

@section('form-content')
    <div class="form-header">
        <h2>New password</h2>
        <p>Enter and confirm your new password below.</p>
    </div>

    <form action="{{ route('password.update') }}" method="POST" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

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
                    value="{{ old('email', $email) }}"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
            @error('email')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password">New password</label>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="has-toggle @error('password') error @enderror"
                    placeholder="At least 8 characters"
                    required
                    autocomplete="new-password"
                >
                <button type="button" class="toggle-visibility" aria-label="Show password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="has-toggle"
                    placeholder="Re-enter your new password"
                    required
                    autocomplete="new-password"
                >
                <button type="button" class="toggle-visibility" aria-label="Show password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="margin-top: 8px;">
            Reset password
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>

    <a href="{{ route('login') }}" class="link-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back to sign in
    </a>
@endsection
