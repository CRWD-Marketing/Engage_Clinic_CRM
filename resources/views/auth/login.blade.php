@extends('layouts.auth')

@section('title', 'Sign In · Engage Clinic')

@section('panel-heading')
    Clinic operations,<br><span class="accent">one login</span> away.
@endsection

@section('panel-text')
    Manage patient records, book and reschedule appointments, track staff shifts, and control who has access to what — all from one dashboard built for your entire care team.
@endsection

@section('form-content')
    <div class="form-header">
        <h2>Sign in</h2>
        <p>Enter your credentials to reach the dashboard.</p>
    </div>

    @if (session('status'))
        <div class="status-banner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 6 9 17l-5-5"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Rate-limiting (too many rapid submits) flashes under the 'message'
         key rather than 'email'/'password', since it isn't a field-level
         validation error - shown here so a throttled attempt doesn't look
         like the page silently did nothing. --}}
    @error('message')
        <div class="error-banner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <form action="{{ route('login.submit') }}" method="POST" novalidate>
        @csrf

        <!-- Email -->
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

        <!-- Password -->
        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="has-toggle @error('password') error @enderror"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
                <button type="button" class="toggle-visibility" aria-label="Show password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="row-between">
            <label class="remember">
                <input type="checkbox" name="remember">
                Remember me
            </label>
            <a href="{{ route('password.request') }}" class="link-muted">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="btn-primary">
            Sign in
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>

    <div class="notice">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
        <p><strong>Authorized access only.</strong> This portal is restricted to Engage Clinic staff. All activity is logged.</p>
    </div>
@endsection
