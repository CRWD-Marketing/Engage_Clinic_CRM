    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Sign In · Engage Clinic</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
        <style>
            :root{
                --bg-primary:#FFFFFF;
                --bg-secondary:#FAFAFA;
                --bg-surface:#F5F5F5;
                --border:#E5E5E5;
                --border-hover:#D4D4D4;
                --text-primary:#0A0A0A;
                --text-secondary:#52525B;
                --text-muted:#A1A1AA;
                --accent:#C8355F;
                --accent-hover:#A82348;
                --accent-tint:rgba(200,53,95,0.08);
                --danger:#DC2626;
            }

            *{ box-sizing:border-box; margin:0; padding:0; }

            body{
                font-family:'Inter', system-ui, -apple-system, sans-serif;
                background:var(--bg-primary);
                color:var(--text-primary);
                -webkit-font-smoothing:antialiased;
            }

            .mono{ font-family:'Geist Mono','SF Mono', monospace; }

            /* ---------- Layout ---------- */
            .shell{
                min-height:100vh;
                display:grid;
                grid-template-columns:1fr 440px;
            }

            /* ---------- Left: identity panel ---------- */
            .panel{
                position:relative;
                background:var(--bg-secondary);
                border-right:1px solid var(--border);
                padding:48px;
                display:flex;
                flex-direction:column;
                justify-content:space-between;
                overflow:hidden;
            }

            .panel::before{
                content:'';
                position:absolute;
                inset:0;
                background-image:
                    linear-gradient(var(--border) 1px, transparent 1px),
                    linear-gradient(90deg, var(--border) 1px, transparent 1px);
                background-size:40px 40px;
                opacity:0.5;
                mask-image:radial-gradient(circle at 30% 30%, black, transparent 70%);
            }

            .panel::after{
                content:'';
                position:absolute;
                right:-120px;
                bottom:-120px;
                width:420px;
                height:420px;
                background:radial-gradient(circle, var(--accent-tint) 0%, transparent 70%);
                pointer-events:none;
            }

            .panel-top, .panel-mid, .panel-bottom{ position:relative; z-index:1; }

            /* Logo + status pill sit in one row so the pill can be pinned right after
            the logo ("at the end of the logo") on mobile without duplicating markup. */
            .brand-row{
                display:flex;
                align-items:center;
                gap:16px;
                flex-wrap:wrap;
            }

            .brand-mark img{
                height:100px;
                width:auto;
                display:block;
                object-fit:contain;
            }

            .brand-mark-centered{
                margin-bottom:24px;
            }

            .brand-mark-centered img{
                margin:0 auto;
            }

            /* Large faded logo watermark inside the left panel */
            .logo-watermark{
                position:absolute;
                right:-40px;
                bottom:-40px;
                width:380px;
                height:380px;
                opacity:0.05;
                object-fit:contain;
                pointer-events:none;
                z-index:0;
                filter:grayscale(1);
            }

            .brand-name{
                font-size:14px;
                font-weight:600;
                letter-spacing:-0.01em;
                color:var(--text-primary);
            }

            .panel-mid{
                max-width:400px;
                margin:auto;
                padding:0;
                text-align:center;
            }

            .panel-mid h1{
                font-size:36px;
                line-height:1.15;
                font-weight:700;
                letter-spacing:-0.02em;
                margin-bottom:16px;
                color:var(--text-primary);
            }

            .panel-mid h1 .accent{ color:var(--accent); }

            .panel-mid p{
                font-size:14px;
                line-height:1.6;
                color:var(--text-secondary);
                max-width:380px;
                margin:0 auto;
            }

            .panel-bottom{
                display:flex;
                align-items:center;
                gap:8px;
                font-size:12px;
                color:var(--text-muted);
            }

            .panel-bottom svg{ width:14px; height:14px; flex-shrink:0; opacity:0.7; }

            /* ---------- Right: form panel ---------- */
            .form-panel{
                display:flex;
                align-items:center;
                justify-content:center;
                padding:48px 40px;
                background:var(--bg-primary);
            }

            .form-inner{
                width:100%;
                max-width:340px;
            }

            .form-logo{
                display:none; /* shown only when the left panel is hidden, via media query */
            }

            .form-logo img{
                height:56px;
                width:auto;
                object-fit:contain;
                display:block;
            }

            .form-header{ margin-bottom:32px; }

            .form-header h2{
                font-size:24px;
                font-weight:700;
                letter-spacing:-0.02em;
                margin-bottom:6px;
                color:var(--text-primary);
            }

            .form-header p{
                font-size:13px;
                color:var(--text-muted);
            }

            .field{ margin-bottom:16px; }

            .field label{
                display:block;
                font-size:12px;
                font-weight:500;
                color:var(--text-secondary);
                margin-bottom:8px;
            }

            .input-wrap{ position:relative; }

            .input-wrap svg{
                position:absolute;
                left:14px;
                top:50%;
                transform:translateY(-50%);
                width:16px;
                height:16px;
                color:var(--text-muted);
                pointer-events:none;
            }

            .field input[type="email"],
            .field input[type="password"]{
                width:100%;
                background:var(--bg-surface);
                border:1px solid var(--border);
                border-radius:10px;
                padding:11px 14px 11px 40px;
                font-size:14px;
                color:var(--text-primary);
                font-family:inherit;
                transition:border-color 150ms ease, background-color 150ms ease, box-shadow 150ms ease;
            }

            .field input::placeholder{ color:var(--text-muted); }

            .field input:hover{ border-color:var(--border-hover); }

            .field input:focus{
                outline:none;
                border-color:var(--accent);
                background:var(--bg-primary);
                box-shadow:0 0 0 3px var(--accent-tint);
            }

            .field input.error{ border-color:var(--danger); }

            .toggle-visibility{
                position:absolute;
                right:12px;
                top:50%;
                transform:translateY(-50%);
                background:none;
                border:none;
                color:var(--text-muted);
                cursor:pointer;
                padding:4px;
                display:flex;
                border-radius:6px;
                transition:color 150ms ease;
            }

            .toggle-visibility:hover{ color:var(--text-secondary); }
            .toggle-visibility svg{ position:static; width:16px; height:16px; }

            .error-text{
                font-size:12px;
                color:var(--danger);
                margin-top:6px;
            }

            .row-between{
                display:flex;
                align-items:center;
                justify-content:space-between;
                margin:20px 0 24px;
                flex-wrap:wrap;
                gap:8px;
            }

            .remember{
                display:flex;
                align-items:center;
                gap:8px;
                font-size:13px;
                color:var(--text-secondary);
                cursor:pointer;
                user-select:none;
            }

            .remember input{
                width:16px;
                height:16px;
                border-radius:4px;
                border:1px solid var(--border);
                background:var(--bg-surface);
                accent-color:var(--accent);
                cursor:pointer;
            }

            .link-muted{
                font-size:13px;
                color:var(--text-secondary);
                text-decoration:none;
                transition:color 150ms ease;
            }

            .link-muted:hover{ color:var(--accent); }

            .btn-primary{
                width:100%;
                padding:11px;
                border-radius:10px;
                background:var(--accent);
                color:#FFFFFF;
                font-weight:600;
                font-size:14px;
                border:none;
                cursor:pointer;
                display:flex;
                align-items:center;
                justify-content:center;
                gap:8px;
                font-family:inherit;
                transition:background-color 150ms ease, box-shadow 150ms ease;
            }

            .btn-primary:hover{
                background:var(--accent-hover);
                box-shadow:0 0 0 4px var(--accent-tint);
            }

            .btn-primary:focus-visible{
                outline:none;
                box-shadow:0 0 0 4px var(--accent-tint);
            }

            .btn-primary svg{ width:16px; height:16px; }

            .notice{
                margin-top:24px;
                padding:12px 14px;
                border:1px solid var(--border);
                border-radius:10px;
                background:var(--bg-surface);
                display:flex;
                gap:10px;
                align-items:flex-start;
            }

            .notice svg{
                width:15px; height:15px;
                color:var(--accent);
                flex-shrink:0;
                margin-top:1px;
            }

            .notice p{
                font-size:12px;
                line-height:1.5;
                color:var(--text-muted);
            }

            .notice p strong{ color:var(--text-secondary); font-weight:600; }

            /* ---------- Responsive ---------- */
            /* Tablet: keep both columns but narrow the identity panel and tighten spacing */
            @media (max-width: 1024px){
                .shell{ grid-template-columns:1fr 380px; }
                .panel{ padding:36px; }
                .panel-mid{ margin-top:auto; }
                .panel-mid h1{ font-size:30px; }
            }

            /* Small tablet / large mobile: stack panels, identity content moves above the form
            instead of disappearing, so mobile shows the same information as desktop. */
            @media (max-width: 860px){
                .shell{
                    grid-template-columns:1fr;
                    grid-template-rows:auto auto;
                }

                .panel{
                    border-right:none;
                    border-bottom:1px solid var(--border);
                    padding:32px 24px;
                    min-height:0;
                }

                /* Logo sized a little smaller than desktop, and the status pill
                moves out of panel-mid to sit right after the logo instead. */
                .brand-mark img{ height:80px; }

                .panel-mid{
                    margin-top:24px;
                    padding:0;
                    max-width:none;
                }

                .panel-mid h1{ font-size:26px; }

                .logo-watermark{
                    width:220px;
                    height:220px;
                    right:-40px;
                    bottom:-40px;
                }

                .form-panel{ padding:32px 24px; }

                .form-logo{ display:none; } /* logo already visible via .panel above */
            }

            /* Phones */
            @media (max-width: 480px){
                .panel{ padding:24px 20px; }
                .brand-row{ gap:12px; }
                .brand-mark img{ height:64px; }
                .panel-mid{ margin-top:20px; }
                .panel-mid h1{ font-size:22px; }
                .panel-mid p{ font-size:13px; }
                .form-panel{ padding:24px 16px; }
                .form-inner{ max-width:100%; }
                .form-header h2{ font-size:21px; }
                .btn-primary{ padding:12px; }
                .logo-watermark{ width:160px; height:160px; opacity:0.04; }
            }

            @media (prefers-reduced-motion: reduce){
                *{ transition:none !important; animation:none !important; }
            }
        </style>
    </head>
    <body>
        <div class="shell">

            <!-- Left: identity / context panel (stacks above the form on mobile, not hidden) -->
            <div class="panel">
                <img src="{{ asset('uploads/engage.png') }}" alt="" class="logo-watermark">

                <div class="panel-mid">
                    <div class="brand-mark brand-mark-centered">
                        <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic logo">
                    </div>
                    <h1>Clinic operations,<br><span class="accent">one login</span> away.</h1>
                    <p>Manage patient records, book and reschedule appointments, track staff shifts, and control who has access to what — all from one dashboard built for your entire care team.</p>
                </div>

                <div class="panel-bottom">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    All activity on this portal is logged for security and compliance.
                </div>
            </div>

            <!-- Right: login form -->
            <div class="form-panel">
                <div class="form-inner">
                    <div class="form-logo">
                        <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic">
                    </div>
                    <div class="form-header">
                        <h2>Sign in</h2>
                        <p>Enter your credentials to reach the dashboard.</p>
                    </div>

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
                                    class="@error('password') error @enderror"
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="toggle-visibility" aria-label="Show password" onclick="togglePassword()">
                                    <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
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
                            <a href="#" class="link-muted" onclick="alert('Please contact your system administrator to reset your password.'); return false;">
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
                </div>
            </div>

        </div>

        <script>
            function togglePassword(){
                const input = document.getElementById('password');
                const btn = document.querySelector('.toggle-visibility');
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            }
        </script>
    </body>
    </html>