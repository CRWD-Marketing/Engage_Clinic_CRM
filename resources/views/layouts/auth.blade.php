<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Engage Clinic')</title>
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
            --success:#178A45;
            --success-tint:rgba(23,138,69,0.08);
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

        .brand-mark-centered{ margin-bottom:24px; }
        .brand-mark-centered img{ margin:0 auto; }

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

        .form-logo{ display:none; }

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
        .field input[type="password"],
        .field input[type="text"]{
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

        /* Password fields reserve extra right padding for the eye-toggle button
        so typed text never runs underneath it. */
        .field input.has-toggle{ padding-right:40px; }

        /* Hide every browser's own built-in password-reveal/autofill icon so it
        can't overlap or fight with our custom toggle button. */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear{ display:none; }
        input[type="password"]::-webkit-credentials-auto-fill-button,
        input[type="password"]::-webkit-strong-password-auto-fill-button{
            visibility:hidden;
            display:none !important;
            pointer-events:none;
            position:absolute;
            right:0;
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
            top:75%;
            transform:translateY(-50%);
            width:24px;
            height:24px;
            margin:0;
            background:none;
            border:none;
            color:var(--text-muted);
            cursor:pointer;
            padding:0;
            display:flex;
            align-items:center;
            justify-content:center;
            line-height:0;
            border-radius:6px;
            transition:color 150ms ease;
        }

        .toggle-visibility:hover{ color:var(--text-secondary); }
        .toggle-visibility svg{ position:static; display:block; width:16px; height:16px; }

        .error-text{
            font-size:12px;
            color:var(--danger);
            margin-top:6px;
        }

        .status-banner{
            margin-bottom:20px;
            padding:12px 14px;
            border:1px solid var(--success);
            background:var(--success-tint);
            border-radius:10px;
            display:flex;
            gap:10px;
            align-items:flex-start;
            font-size:13px;
            line-height:1.5;
            color:#0F5C31;
        }

        .status-banner svg{ width:16px; height:16px; flex-shrink:0; margin-top:1px; color:var(--success); }

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

        .link-back{
            display:inline-flex;
            align-items:center;
            gap:6px;
            font-size:13px;
            font-weight:500;
            color:var(--text-secondary);
            text-decoration:none;
            margin-top:24px;
            transition:color 150ms ease;
        }

        .link-back:hover{ color:var(--accent); }
        .link-back svg{ width:14px; height:14px; }

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
        @media (max-width: 1024px){
            .shell{ grid-template-columns:1fr 380px; }
            .panel{ padding:36px; }
            .panel-mid{ margin-top:auto; }
            .panel-mid h1{ font-size:30px; }
        }

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
            .form-logo{ display:none; }
        }

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

        @stack('styles')
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
                <h1>@yield('panel-heading')</h1>
                <p>@yield('panel-text')</p>
            </div>

            <div class="panel-bottom">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                All activity on this portal is logged for security and compliance.
            </div>
        </div>

        <!-- Right: form -->
        <div class="form-panel">
            <div class="form-inner">
                <div class="form-logo">
                    <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic">
                </div>

                @yield('form-content')
            </div>
        </div>

    </div>

    <script>
        const EYE_OPEN = '<path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/>';
        const EYE_OFF = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/>';

        // Supports any number of password fields on the page - each toggle button
        // controls the password input inside its own .input-wrap.
        document.querySelectorAll('.toggle-visibility').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const wrap = btn.closest('.input-wrap');
                const input = wrap.querySelector('input');
                const icon = btn.querySelector('svg');
                const isHidden = input.type === 'password';

                input.type = isHidden ? 'text' : 'password';
                btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                icon.innerHTML = isHidden ? EYE_OFF : EYE_OPEN;
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
