<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
            min-height: 100vh;
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
            z-index: 100;
            display: flex;
            flex-direction: column;
            padding: 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #F6F3EE;
            display: flex;
            flex-direction: column;
        }
        
        .main-content-bg {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #F6F3EE;
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
        
        .sidebar-mobile-overlay.active {
            display: block;
            opacity: 1;
        }
        
        .sidebar-mobile {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            max-width: 86vw;
            height: 100vh;
            background: #FFFFFF;
            z-index: 1000;
            transition: left 0.3s ease;
            padding: 1.5rem;
            overflow-y: auto;
            border-right: 1px solid #EBE4DA;
            box-shadow: 0 0 40px rgba(22,42,60,0.15);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-mobile.active {
            left: 0;
        }

        .mobile-menu-btn {
            position: fixed;
            top: 14px;
            right: 16px;
            z-index: 998;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #FFFFFF;
            border: 1px solid #EBE4DA;
            color: #16436E;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(22,42,60,0.12);
            transition: all 0.2s ease;
        }

        .mobile-menu-btn:hover {
            background: #F9E3EA;
            color: #C8355F;
            border-color: #F3C9D5;
        }

        .mobile-menu-btn:active {
            transform: scale(0.94);
        }

        .mobile-menu-btn .badge-dot {
            position: absolute;
            top: -3px;
            right: -3px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #C8355F;
            border: 2px solid #FFFFFF;
        }
        
        @media (max-width: 768px) {
            .sidebar-desktop {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .main-content-inner {
                padding-top: 66px !important;
            }
        }
        
        @media (min-width: 769px) {
            .sidebar-mobile,
            .sidebar-mobile-overlay,
            .mobile-menu-btn {
                display: none !important;
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
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 28px;
            border-bottom: 1px solid #EBE4DA;
            background: #FFFDFA;
            flex-shrink: 0;
            width: 1440px;
            max-width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
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
            .topbar-fixed {
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
    <!-- Mobile Menu Trigger -->
    <button type="button" class="mobile-menu-btn" onclick="openSidebar()" aria-label="Open navigation menu" aria-controls="sidebarMobile" aria-expanded="false" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
        @php
            $newLeadsCount = \App\Models\Lead::where('status', 'new')->count();
        @endphp
        @if($newLeadsCount > 0)
            <span class="badge-dot"></span>
        @endif
    </button>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="sidebar-mobile-overlay" onclick="closeSidebar()"></div>
    
    <!-- Mobile Sidebar -->
    <div id="sidebarMobile" class="sidebar-mobile">
        <div class="flex items-center justify-between mb-8">
            <div class="logo-container" style="padding: 0; text-align: center; width: 100%;">
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic" class="sidebar-logo-img" style="max-width: 90px; margin: 0 auto;">
                </a>
            </div>
            <button onclick="closeSidebar()" class="text-gray-400 hover:text-[#C8355F] transition" style="position: absolute; right: 16px; top: 16px;" aria-label="Close navigation menu">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="flex items-center gap-3 p-3 rounded-xl bg-[#F8F5F0] mb-6">
            <div class="user-avatar">
                {{ Auth::user()->first_name ? strtoupper(substr(Auth::user()->first_name, 0, 1)) : (Auth::user()->name ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U') }}
            </div>
            <div class="user-info">
                <div class="name">{{ Auth::user()->full_name ?? Auth::user()->name ?? 'Admin' }}</div>
                <div class="role">{{ Auth::user()->role ?? 'Staff' }}</div>
            </div>
        </div>
        
        <nav class="space-y-1">
            @if(Auth::user()->canAccessFeature('dashboard'))
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('leads'))
            <a href="{{ route('leads.index') }}" class="sidebar-link {{ request()->routeIs('leads.index') ? 'active' : '' }}">
                <i class="fas fa-filter"></i> Leads
                @php
                    $leadCount = \App\Models\Lead::count();
                @endphp
                @if($leadCount > 0)
                    <span class="badge badge-pulse">{{ $leadCount }}</span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('whatsapp'))
            <a href="{{ route('whatsapp.index') }}" class="sidebar-link {{ request()->routeIs('whatsapp.index') ? 'active' : '' }}">
                <i class="fab fa-whatsapp"></i> WhatsApp
                @php
                    $whatsappCount = \App\Models\WhatsappContact::sum('unread_count');
                @endphp
                @if($whatsappCount > 0)
                    <span class="badge badge-pulse">{{ $whatsappCount }}</span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('patients'))
            <a href="{{ route('patient.index') }}" class="sidebar-link {{ request()->routeIs('patient.index') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Patients
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('calendar'))
            <a href="{{ route('calendar.index') }}" class="sidebar-link {{ request()->routeIs('calendar.index') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i> Calendar
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('therapists'))
            <a href="{{ route('therapist.index') }}" class="sidebar-link {{ request()->routeIs('therapist.index') ? 'active' : '' }}">
                <i class="fas fa-user-md"></i> Therapists
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('users'))
            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-user-shield"></i> User Management
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('billing'))
            <a href="{{ route('billing.index') }}" class="sidebar-link {{ request()->routeIs('billing.index') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i> Billing
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('reports'))
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            @endif
        </nav>
        
        <div class="waitlist-card mt-6">
            <div class="label">Waitlist</div>
            <div class="number">
                @php
                    $waitlistCount = 0;
                    if (class_exists('App\Models\Waitlist')) {
                        $waitlistCount = \App\Models\Waitlist::count();
                    }
                @endphp
                {{ $waitlistCount }} families
            </div>
            <div class="sub">avg. wait 3.2 weeks</div>
        </div>
        
        <div class="mt-auto pt-6" style="flex-shrink: 0;">
            <div class="profile-dropdown" style="width: 100%;">
                <div onclick="toggleDropdown()" class="dropdown-trigger">
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 11px;">
                        {{ Auth::user()->first_name ? strtoupper(substr(Auth::user()->first_name, 0, 1)) : (Auth::user()->name ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U') }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="color: #2B3A4C; font-weight: 700; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ Auth::user()->full_name ?? Auth::user()->name ?? 'Admin' }}
                        </div>
                        <div style="color: #98897A; font-size: 10px;">
                            {{ Auth::user()->role ?? 'Staff' }}
                        </div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: #98897A; font-size: 11px;"></i>
                </div>
                
                <div id="dropdownMenu" class="dropdown-menu">
                    <a href="{{ route('profile.index') }}" class="dropdown-item">
                        <i class="fas fa-user-circle" style="width: 17px;"></i> My Profile
                    </a>
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-cog" style="width: 17px;"></i> Settings
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
    </div>

    <!-- Desktop Sidebar -->
    <aside class="sidebar sidebar-desktop">
        <div class="logo-container">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('uploads/engage.png') }}" alt="Engage Clinic" class="sidebar-logo-img">
            </a>
        </div>
        
        <div style="padding: 6px 12px; display: flex; flex-direction: column; gap: 2px; flex: 1;">
            @if(Auth::user()->canAccessFeature('dashboard'))
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('leads'))
            <a href="{{ route('leads.index') }}" class="sidebar-link {{ request()->routeIs('leads.index') ? 'active' : '' }}">
                <i class="fas fa-filter"></i> Leads
                @php
                    $leadCount = \App\Models\Lead::count();
                @endphp
                @if($leadCount > 0)
                    <span class="badge badge-pulse">{{ $leadCount }}</span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('whatsapp'))
            <a href="{{ route('whatsapp.index') }}" class="sidebar-link {{ request()->routeIs('whatsapp.index') ? 'active' : '' }}">
                <i class="fab fa-whatsapp"></i> WhatsApp
                @php
                    $whatsappCount = \App\Models\WhatsappContact::sum('unread_count');
                @endphp
                @if($whatsappCount > 0)
                    <span class="badge badge-pulse">{{ $whatsappCount }}</span>
                @else
                    <span class="badge badge-zero">0</span>
                @endif
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('patients'))
            <a href="{{ route('patient.index') }}" class="sidebar-link {{ request()->routeIs('patient.index') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Patients
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('calendar'))
            <a href="{{ route('calendar.index') }}" class="sidebar-link {{ request()->routeIs('calendar.index') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i> Calendar
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('therapists'))
            <a href="{{ route('therapist.index') }}" class="sidebar-link {{ request()->routeIs('therapist.index') ? 'active' : '' }}">
                <i class="fas fa-user-md"></i> Therapists
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('users'))
            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-user-shield"></i> User Management
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('billing'))
            <a href="{{ route('billing.index') }}" class="sidebar-link {{ request()->routeIs('billing.index') ? 'active' : '' }}">
                <i class="fas fa-credit-card"></i> Billing
            </a>
            @endif

            @if(Auth::user()->canAccessFeature('reports'))
            <a href="{{ route('reports.index') }}" class="sidebar-link {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            @endif
        </div>
        
        <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 12px; border-top: 1px solid #EBE4DA;">
            <div class="waitlist-card">
                <div class="label">Waitlist</div>
                <div class="number">
                    @php
                        $waitlistCount = 0;
                        if (class_exists('App\Models\Waitlist')) {
                            $waitlistCount = \App\Models\Waitlist::count();
                        }
                    @endphp
                    {{ $waitlistCount }} families
                </div>
                <div class="sub">avg. wait 3.2 weeks</div>
            </div>
            
            <div class="profile-dropdown" style="width: 100%;">
                <div onclick="toggleDropdown()" class="dropdown-trigger">
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 11px;">
                        {{ Auth::user()->first_name ? strtoupper(substr(Auth::user()->first_name, 0, 1)) : (Auth::user()->name ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'U') }}
                    </div>
                    <div style="flex: 1; min-width: 0;">
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
                    <a href="#" class="dropdown-item">
                        <i class="fas fa-cog" style="width: 17px;"></i> Settings
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
        <div class="main-content-bg">
            <div class="main-content-inner @yield('content-class')">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
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
        
        function openSidebar() {
            document.getElementById('sidebarMobile').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
            const btn = document.getElementById('mobileMenuBtn');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        }
        
        function closeSidebar() {
            document.getElementById('sidebarMobile').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
            document.body.style.overflow = '';
            const btn = document.getElementById('mobileMenuBtn');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSidebar();
                document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
                    menu.classList.remove('active');
                });
            }
        });
        
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebarMobile');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar.classList.contains('active') && !sidebar.contains(e.target) && !e.target.closest('.mobile-menu-btn')) {
                closeSidebar();
            }
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
        }, 30000);
    </script>
    
    @stack('scripts')
</body>
</html>