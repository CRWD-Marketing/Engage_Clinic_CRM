<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="{{ asset('uploads/engage.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard · Engage Clinic')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 230px;
        }

        * { 
            font-family: 'Nunito Sans', system-ui, -apple-system, sans-serif; 
        }
        
        .admin-body {
            background: #F6F3EE;
            height: 100vh;
            overflow: hidden;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #FFFFFF;
            border-right: 1px solid #EBE4DA;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            display: flex;
            flex-direction: column;
            padding: 0;
            transition: width 0.18s ease;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            height: 100vh;
            background: #F6F3EE;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.18s ease;
        }

        /* Collapsed desktop sidebar - icon rail instead of the mobile hamburger/drawer */
        body.sidebar-collapsed {
            --sidebar-width: 76px;
        }

        .sidebar-toggle-row {
            display: flex;
            justify-content: flex-end;
            padding: 0 14px 10px;
        }

        .sidebar-toggle-btn {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            border: 1px solid #E2DACE;
            background: #FFFFFF;
            color: #5A6B7E;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 11px;
            flex-shrink: 0;
            transition: background 0.15s ease;
        }

        .sidebar-toggle-btn:hover {
            background: #F5EFE7;
        }

        .sidebar-toggle-btn i {
            transition: transform 0.18s ease;
        }

        body.sidebar-collapsed .sidebar-toggle-row {
            justify-content: center;
            padding: 0 0 10px;
        }

        body.sidebar-collapsed .sidebar-toggle-btn i {
            transform: rotate(180deg);
        }

        .sidebar-logo-icon {
            display: none;
            width: 32px;
            height: auto;
            margin: 0 auto;
        }

        body.sidebar-collapsed .sidebar-logo-full {
            display: none;
        }

        body.sidebar-collapsed .sidebar-logo-icon {
            display: block;
        }

        body.sidebar-collapsed .logo-container {
            padding: 16px 8px 0;
        }

        body.sidebar-collapsed .sidebar-link {
            justify-content: center;
            padding: 12px;
            position: relative;
        }

        body.sidebar-collapsed .link-label,
        body.sidebar-collapsed .sidebar-link .badge,
        body.sidebar-collapsed .waitlist-card,
        body.sidebar-collapsed .user-info,
        body.sidebar-collapsed .dropdown-trigger .fa-chevron-down {
            display: none;
        }

        body.sidebar-collapsed .profile-dropdown .dropdown-trigger {
            justify-content: center;
            padding: 10px;
        }

        .collapsed-dot {
            display: none;
        }

        body.sidebar-collapsed .collapsed-dot {
            display: block;
            position: absolute;
            top: 9px;
            right: 12px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #C8355F;
            border: 2px solid #FFFFFF;
        }
        
        .main-content-bg {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #F6F3EE;
        }

        /* Fill-height pages (WhatsApp, User Management's table, ...) own a single,
           correctly-bounded internal scroll region (flex:1; min-height:0; overflow:auto
           on exactly one descendant, with every element between it and here being a
           pure flex:1;min-height:0;overflow:hidden pass-through or a fixed flex-shrink:0
           item - never a second independent overflow:auto). This outer container must
           not ALSO be independently scrollable in that case: two auto-overflow
           containers stacked let browser-zoom's sub-pixel rounding make this outer one
           falsely detect its own fractional overflow and grab a scrollbar instead of
           deferring to the real inner one, trapping the page above the fold. Scoped to
           only content-fill-height pages via :has() - normal pages that just grow
           taller than the viewport still need this to stay auto. */
        .main-content-bg:has(.content-fill-height) {
            overflow-y: hidden;
        }

        .main-content-inner {
            width: 100%;
            max-width: 1440px;
            flex: 0 0 auto;
            margin: 0 auto;
            padding: 22px 28px;
            display: flex;
            flex-direction: column;
            gap: 18px;
            box-sizing: border-box;
        }

        /* Opt-in modifier for pages (e.g. WhatsApp inbox) that need to fill the full viewport height
           instead of sizing to their content. */
        .main-content-inner.content-fill-height {
            flex: 1 1 auto;
            min-height: 0;
        }

        /* Opt-in modifier for pages (e.g. Billing) whose layout is built to use the full
           browser width instead of being capped and centered at 1440px. */
        .main-content-inner.content-full-width {
            max-width: none;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #40536A;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            font-family: 'Nunito Sans', sans-serif;
            position: relative;
        }
        
        .sidebar-link:hover {
            background: rgba(200, 53, 95, 0.08);
            color: #C8355F;
        }
        
        .sidebar-link.active {
            background: #F9E3EA;
            color: #C8355F;
        }
        
        .sidebar-link i {
            width: 17px;
            text-align: center;
            font-size: 1rem;
            color: inherit;
        }
        
        .sidebar-link .badge {
            background: #C8355F;
            color: white;
            font-size: 10.5px;
            padding: 1px 7px;
            border-radius: 9px;
            margin-left: auto;
            font-weight: 800;
            min-width: 20px;
            text-align: center;
        }
        
        .sidebar-link .badge.badge-zero {
            background: #D1C7BB;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #16436E;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 13px;
            font-family: 'Baloo 2', cursive;
            flex-shrink: 0;
        }
        
        .user-info {
            flex: 1;
            min-width: 0;
        }
        
        .user-info .name {
            color: #2B3A4C;
            font-weight: 800;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-info .role {
            color: #98897A;
            font-size: 11.5px;
            font-weight: 600;
        }
        
        .waitlist-card {
            background: #F3EDE3;
            border-radius: 12px;
            padding: 12px 14px;
        }
        
        .waitlist-card .label {
            font-size: 11px;
            font-weight: 700;
            color: #8A7D6C;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        
        .waitlist-card .number {
            font-size: 20px;
            font-weight: 600;
            color: #16436E;
            font-family: 'Baloo 2', cursive;
            line-height: 1.3;
        }
        
        .waitlist-card .sub {
            font-size: 11.5px;
            font-weight: 600;
            color: #8A7D6C;
        }
        
        .profile-dropdown {
            position: relative;
            cursor: pointer;
        }
        
        .profile-dropdown .dropdown-menu {
            display: none;
            position: absolute;
            bottom: calc(100% + 10px);
            left: 0;
            right: 0;
            background: #FFFFFF;
            border: 1px solid #EBE4DA;
            border-radius: 10px;
            padding: 8px 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            min-width: 180px;
        }
        
        .profile-dropdown .dropdown-menu.active {
            display: block;
        }
        
        .profile-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 16px;
            color: #40536A;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            transition: all 0.2s;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            font-family: 'Nunito Sans', sans-serif;
        }
        
        .profile-dropdown .dropdown-item:hover {
            background: rgba(200, 53, 95, 0.08);
            color: #C8355F;
        }
        
        .profile-dropdown .dropdown-item.danger:hover {
            background: rgba(239, 68, 68, 0.08);
            color: #ef4444;
        }
        
        .profile-dropdown .dropdown-divider {
            height: 1px;
            background: #EBE4DA;
            margin: 4px 12px;
        }
        
        .profile-dropdown .dropdown-trigger {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            background: #F8F5F0;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .profile-dropdown .dropdown-trigger:hover {
            background: #F0EBE5;
        }
        
        .sidebar-logo {
            color: #16436E;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #DDD4C8;
            border-radius: 5px;
        }
        
        .sidebar-mobile-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(22, 42, 60, 0.5);
            backdrop-filter: blur(2px);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.25s ease;
        }

        /* The old mobile pattern (hamburger button + full-screen slide-in drawer) has
           been replaced by the same icon-rail sidebar used on desktop - it just
           defaults to collapsed on narrow screens and expands as an overlay. */
        @media (max-width: 768px) {
            .sidebar-desktop {
                display: flex !important;
                z-index: 1000;
            }
            .main-content {
                margin-left: 76px !important;
            }
            body.sidebar-collapsed .sidebar-desktop {
                width: 76px;
            }
            body:not(.sidebar-collapsed) .sidebar-desktop {
                width: 230px;
                box-shadow: 0 8px 40px rgba(22, 42, 60, 0.25);
            }
            body:not(.sidebar-collapsed) .sidebar-mobile-overlay {
                display: block !important;
                opacity: 1;
                z-index: 999;
            }
        }
        
        .sidebar-logo-img {
            max-width: 100px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        
        .logo-container {
            padding: 16px 18px 12px;
            text-align: center;
        }

        .topbar-full {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 28px;
            border-bottom: 1px solid #EBE4DA;
            background: #FFFDFA;
            flex-shrink: 0;
            width: 100%;
            box-sizing: border-box;
        }
        
        .topbar-fixed {
            border-bottom: 1px solid #EBE4DA;
            background: #FFFDFA;
            flex-shrink: 0;
            width: 100%;
            box-sizing: border-box;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .topbar-fixed-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 28px;
            width: 100%;
            box-sizing: border-box;
        }

        .topbar-search-wrap { position: relative; width: 100%; max-width: 420px; }
        .topbar-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #B0A493; font-size: 13px; pointer-events: none; }
        .topbar-search-input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 14px 10px 36px;
            border: 1px solid #E2DACE;
            border-radius: 10px;
            background: #F6F3EE;
            font: 600 13px 'Nunito Sans';
            color: #2B3A4C;
            outline: none;
            transition: border-color 0.15s ease, background 0.15s ease;
        }
        .topbar-search-input:focus { border-color: #C8355F; background: #FFFDFA; }

        .topbar-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .topbar-activity-btn {
            background: #FFFFFF;
            color: #16436E;
            border: 1px solid #E2DACE;
            border-radius: 9px;
            padding: 9px 16px;
            font: 800 12.5px 'Nunito Sans';
            cursor: pointer;
            white-space: nowrap;
            transition: background 0.15s ease;
        }
        .topbar-activity-btn:hover { background: #F5EFE7; }
        .topbar-bell-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 9px;
            border: 1px solid #E2DACE;
            background: #FFFFFF;
            color: #5A6B7E;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            flex-shrink: 0;
            transition: background 0.15s ease;
        }
        .topbar-bell-btn:hover { background: #F5EFE7; }
        .topbar-bell-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #C8355F;
            color: #fff;
            font: 800 10px 'Nunito Sans';
            min-width: 17px;
            height: 17px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid #FFFDFA;
        }

        @media (max-width: 640px) {
            .topbar-search-wrap { max-width: none; }
        }
        
        .topbar-dashboard {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 28px;
            border-bottom: 1px solid #EBE4DA;
            background: #FFFDFA;
            margin: -22px -28px 18px -28px;
        }
        
        @media (max-width: 768px) {
            .topbar-fixed-inner {
                max-width: 100%;
                padding: 12px 16px;
                flex-wrap: wrap;
            }

            .topbar-dashboard {
                margin: -16px -16px 12px -16px;
                padding: 12px 16px;
                flex-wrap: wrap;
            }
            
            .topbar-full {
                padding: 12px 16px;
                flex-wrap: wrap;
            }
        }

        /* Notification badge pulse animation */
        .badge-pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
    
    @stack('styles')
</head>
<body class="admin-body">
    <script>
        (function () {
            const stored = localStorage.getItem('sidebarCollapsed');
            // No saved preference yet: default to collapsed on narrow screens, expanded on desktop.
            const collapsed = stored !== null ? stored === '1' : window.matchMedia('(max-width: 768px)').matches;
            if (collapsed) document.body.classList.add('sidebar-collapsed');
        })();
    </script>

    <!-- Overlay behind the sidebar when it's expanded-over-content on a narrow screen -->
    <div id="sidebarOverlay" class="sidebar-mobile-overlay" onclick="collapseSidebar()"></div>

    <!-- Desktop Sidebar -->
    <aside class="sidebar sidebar-desktop">
        <div class="logo-container">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic" class="sidebar-logo-img sidebar-logo-full">
                <img src="{{ asset('uploads/engage-icon.png') }}" alt="Engage Clinic" class="sidebar-logo-icon">
            </a>
        </div>

        <div class="sidebar-toggle-row">
            <button type="button" class="sidebar-toggle-btn" id="sidebarCollapseBtn" onclick="toggleSidebarCollapse()" title="Collapse sidebar" aria-label="Collapse sidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>

        <div style="padding: 6px 12px; display: flex; flex-direction: column; gap: 2px; flex: 1;">
            @if(Auth::user()->canAccessFeature('dashboard'))
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                <i class="fas fa-th-large"></i> <span class="link-label">Dashboard</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('leads'))
            <a href="{{ route('leads.index') }}" class="sidebar-link {{ request()->routeIs('leads.index') ? 'active' : '' }}" title="Leads">
                <i class="fas fa-filter"></i> <span class="link-label">Leads</span>
                @php
                    $leadCount = \App\Models\Lead::where('status', 'new')->count();
                @endphp
                @if($leadCount > 0)
                    <span class="badge badge-pulse">{{ $leadCount }}</span>
                    <span class="collapsed-dot"></span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('contacts'))
            <a href="{{ route('contacts.index') }}" class="sidebar-link {{ request()->routeIs('contacts.index') ? 'active' : '' }}" title="Contact Us">
                <i class="fas fa-envelope"></i> <span class="link-label">Contacts</span>
                @php
                    $contactCount = \App\Models\Contact::where('status', 'new')->count();
                @endphp
                @if($contactCount > 0)
                    <span class="badge badge-pulse">{{ $contactCount }}</span>
                    <span class="collapsed-dot"></span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('whatsapp'))
            <a href="{{ route('whatsapp.index') }}" class="sidebar-link {{ request()->routeIs('whatsapp.index') ? 'active' : '' }}" title="WhatsApp">
                <i class="fab fa-whatsapp"></i> <span class="link-label">WhatsApp</span>
                @php
                    $whatsappCount = \App\Models\WhatsappContact::sum('unread_count');
                @endphp
                @if($whatsappCount > 0)
                    <span class="badge badge-pulse">{{ $whatsappCount }}</span>
                    <span class="collapsed-dot"></span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('voice'))
            <a href="{{ route('voice_calls.index') }}" class="sidebar-link {{ request()->routeIs('voice_calls.*') ? 'active' : '' }}" title="Voice Calls">
                <i class="fas fa-phone-volume"></i> <span class="link-label">Voice Calls</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('knowledge_base'))
            <a href="{{ route('knowledge_base.index') }}" class="sidebar-link {{ request()->routeIs('knowledge_base.*') ? 'active' : '' }}" title="Knowledge Base">
                <i class="fas fa-robot"></i> <span class="link-label">AI Employee</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('patients'))
            <a href="{{ route('patient.index') }}" class="sidebar-link {{ request()->routeIs('patient.index') ? 'active' : '' }}" title="Patients">
                <i class="fas fa-users"></i> <span class="link-label">Patients</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('calendar'))
            <a href="{{ route('calendar.index') }}" class="sidebar-link {{ request()->routeIs('calendar.index') ? 'active' : '' }}" title="Calendar">
                <i class="fas fa-calendar-alt"></i> <span class="link-label">Calendar</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('therapists'))
            <a href="{{ route('therapist.index') }}" class="sidebar-link {{ request()->routeIs('therapist.index') ? 'active' : '' }}" title="Therapists">
                <i class="fas fa-user-md"></i> <span class="link-label">Therapists</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('careers'))
            <a href="{{ route('job-applications.index') }}" class="sidebar-link {{ request()->routeIs('job-applications.*') || request()->routeIs('job-postings.*') ? 'active' : '' }}" title="Job Application">
                <i class="fas fa-briefcase"></i> <span class="link-label">Job Application</span>
                @php
                    $applicationCount = \App\Models\JobApplication::where('status', 'new')->count();
                @endphp
                @if($applicationCount > 0)
                    <span class="badge badge-pulse">{{ $applicationCount }}</span>
                    <span class="collapsed-dot"></span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('users'))
            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}" title="User Management">
                <i class="fas fa-user-shield"></i> <span class="link-label">User Management</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('billing'))
            <a href="{{ route('billing.index') }}" class="sidebar-link {{ request()->routeIs('billing.index') ? 'active' : '' }}" title="Billing">
                <i class="fas fa-credit-card"></i> <span class="link-label">Billing</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('reports'))
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" title="Reports">
                <i class="fas fa-chart-bar"></i> <span class="link-label">Reports</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('packages'))
            <a href="{{ route('packages.index') }}" class="sidebar-link {{ request()->routeIs('packages.*') ? 'active' : '' }}" title="Packages">
                <i class="fas fa-box-open"></i> <span class="link-label">Packages</span>
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('settings'))
            <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" title="Settings">
                <i class="fas fa-cog"></i> <span class="link-label">Settings</span>
            </a>
            @endif
        </div>

        <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 12px; border-top: 1px solid #EBE4DA;">
            @if(Auth::user()->canAccessFeature('patients'))
            <div class="waitlist-card">
                <div class="label">Waitlist</div>
                <div class="number">
                    @php
                        $waitlistEntries = \App\Models\Waitlist::waiting()->get();
                        $waitlistCount = $waitlistEntries->count();
                        $avgWaitWeeks = $waitlistCount > 0
                            ? round($waitlistEntries->avg(fn ($w) => $w->joined_at->diffInWeeks(now())), 1)
                            : null;
                    @endphp
                    {{ $waitlistCount }} families
                </div>
                <div class="sub">{{ $avgWaitWeeks !== null ? 'avg. wait ' . $avgWaitWeeks . ' weeks' : 'No one waiting' }}</div>
            </div>
            @endif

            <div class="profile-dropdown" style="width: 100%;">
                <div onclick="toggleDropdown()" class="dropdown-trigger" title="{{ Auth::user()->full_name ?? Auth::user()->name ?? 'Admin' }}">
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 11px;">
                        {{ Auth::user()->first_name ? strtoupper(substr(Auth::user()->first_name, 0, 1)) : (Auth::user()->name ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U') }}
                    </div>
                    <div class="user-info" style="flex: 1; min-width: 0;">
                        <div style="color: #2B3A4C; font-weight: 700; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ Auth::user()->full_name ?? Auth::user()->name ?? 'Admin' }}
                        </div>
                        <div style="color: #98897A; font-size: 10px;">
                            {{ Auth::user()->role ?? 'Staff' }}
                        </div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: #98897A; font-size: 11px;"></i>
                </div>

                <div id="desktopDropdownMenu" class="dropdown-menu">
                    <a href="{{ route('profile.index') }}" class="dropdown-item">
                        <i class="fas fa-user-circle" style="width: 17px;"></i> My Profile
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="dropdown-item danger">
                            <i class="fas fa-sign-out-alt" style="width: 17px;"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar-fixed">
            <div class="topbar-fixed-inner">
                <div class="topbar-search-wrap">
                    <i class="fas fa-search topbar-search-icon"></i>
                    <input type="text" class="topbar-search-input" placeholder="Search patients, parents, leads, phone…">
                </div>
                <div class="topbar-actions">
                    <button type="button" class="topbar-activity-btn">Activity</button>
                    <button type="button" class="topbar-bell-btn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        @php
                            $topbarNotifCount = \App\Models\Lead::where('status', 'new')->count()
                                + \App\Models\Contact::where('status', 'new')->count()
                                + \App\Models\WhatsappContact::sum('unread_count');
                        @endphp
                        @if($topbarNotifCount > 0)
                            <span class="topbar-bell-badge">{{ $topbarNotifCount > 99 ? '99+' : $topbarNotifCount }}</span>
                        @endif
                    </button>
                </div>
            </div>
        </div>
        <div class="main-content-bg">
            <div class="main-content-inner @yield('content-class')">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        function toggleSidebarCollapse() {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');

            const btn = document.getElementById('sidebarCollapseBtn');
            if (btn) btn.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }

        function toggleDropdown() {
            const mobileDropdown = document.getElementById('dropdownMenu');
            if (mobileDropdown) {
                mobileDropdown.classList.toggle('active');
            }
            
            const desktopDropdown = document.getElementById('desktopDropdownMenu');
            if (desktopDropdown) {
                desktopDropdown.classList.toggle('active');
            }
        }
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.profile-dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                    menu.classList.remove('active');
                });
            }
        });
        
        function collapseSidebar() {
            document.body.classList.add('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', '1');

            const btn = document.getElementById('sidebarCollapseBtn');
            if (btn) btn.setAttribute('title', 'Expand sidebar');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                collapseSidebar();
                document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                    menu.classList.remove('active');
                });
            }
        });

        // On a narrow screen the sidebar expands as a full overlay - once a link is picked,
        // collapse it back to the rail so the next page doesn't load with the overlay open.
        document.querySelectorAll('.sidebar-desktop .sidebar-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 768px)').matches) {
                    localStorage.setItem('sidebarCollapsed', '1');
                }
            });
        });

        // Auto-refresh badge counts every 30 seconds
        setInterval(function() {
            const leadBadges = document.querySelectorAll('.sidebar-link a[href*="leads"] .badge');
            if (leadBadges.length > 0) {
                fetch('{{ route("leads.count") }}')
                    .then(response => response.json())
                    .then(data => {
                        leadBadges.forEach(badge => {
                            badge.textContent = data.count || 0;
                            if (data.count > 0) {
                                badge.classList.remove('badge-zero');
                                badge.classList.add('badge-pulse');
                            } else {
                                badge.classList.add('badge-zero');
                                badge.classList.remove('badge-pulse');
                            }
                        });
                    })
                    .catch(error => console.error('Error updating lead count:', error));
            }

            const contactBadges = document.querySelectorAll('.sidebar-link[href*="contacts"] .badge');
            if (contactBadges.length > 0) {
                fetch('{{ route("contacts.count") }}')
                    .then(response => response.json())
                    .then(data => {
                        contactBadges.forEach(badge => {
                            badge.textContent = data.count || 0;
                            if (data.count > 0) {
                                badge.classList.remove('badge-zero');
                                badge.classList.add('badge-pulse');
                            } else {
                                badge.classList.add('badge-zero');
                                badge.classList.remove('badge-pulse');
                            }
                        });
                    })
                    .catch(error => console.error('Error updating contact count:', error));
            }

            const applicationBadges = document.querySelectorAll('.sidebar-link[href*="job-applications"] .badge');
            if (applicationBadges.length > 0) {
                fetch('{{ route("job-applications.count") }}')
                    .then(response => response.json())
                    .then(data => {
                        applicationBadges.forEach(badge => {
                            badge.textContent = data.count || 0;
                            if (data.count > 0) {
                                badge.classList.remove('badge-zero');
                                badge.classList.add('badge-pulse');
                            } else {
                                badge.classList.add('badge-zero');
                                badge.classList.remove('badge-pulse');
                            }
                        });
                    })
                    .catch(error => console.error('Error updating application count:', error));
            }
        }, 30000);
    </script>
    
    @stack('scripts')
</body>
</html>