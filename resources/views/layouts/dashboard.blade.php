<!DOCTYPE html>
<html lang="{{ session('language', 'en') }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Online Job Portal System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard-premium.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/dashboard-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/language.css') }}">
    @yield('css')
</head>
<body>
<div class="dashboard-wrapper">
    <aside class="dashboard-sidebar" id="dashboardSidebar">
        <div class="sidebar-logo">
            <a href="{{ url('/') }}" class="sidebar-logo-link">
                <div class="sidebar-logo-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="sidebar-logo-text"><strong>Job Portal</strong><span>@yield('role')</span></div>
            </a>
        </div>
        <nav class="sidebar-navigation">
            @hasSection('sidebar')
                @yield('sidebar')
            @else
                @if((int) auth()->user()->role_id === 3)
                    <div class="sidebar-section"><div class="sidebar-section-title">CAREER</div><a href="{{ route('jobs.index') }}" class="sidebar-link"><i class="fa-solid fa-magnifying-glass"></i><span>Find Jobs</span></a><a href="{{ route('applications.index') }}" class="sidebar-link"><i class="fa-solid fa-file-circle-check"></i><span>Applications</span></a><a href="{{ route('saved_jobs.index') }}" class="sidebar-link"><i class="fa-solid fa-bookmark"></i><span>Saved Jobs</span></a><a href="{{ route('jobseeker.recommendations') }}" class="sidebar-link"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Recommended Jobs</span></a><a href="{{ route('jobseeker.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('jobseeker.messages') }}" class="sidebar-link"><i class="fa-solid fa-message"></i><span>Messages</span></a><a href="{{ route('jobseeker.profile') }}" class="sidebar-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a><a href="{{ route('jobseeker.resume') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>My Resume</span></a></div>
                    <div class="sidebar-section"><div class="sidebar-section-title">ACCOUNT</div><a href="{{ route('jobseeker.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a><a href="{{ route('jobseeker.account') }}" class="sidebar-link"><i class="fa-solid fa-user-shield"></i><span>Account</span></a></div>
                @elseif((int) auth()->user()->role_id === 2)
                    <div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('employer.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
                    <div class="sidebar-section"><div class="sidebar-section-title">RECRUITMENT</div><a href="{{ route('employer.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Manage Jobs</span></a><a href="{{ route('employer.applications') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Applicants</span></a><a href="{{ route('employer.interviews') }}" class="sidebar-link"><i class="fa-solid fa-calendar-check"></i><span>Interviews</span></a><a href="{{ route('employer.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a><a href="{{ route('employer.candidate-ranking') }}" class="sidebar-link"><i class="fa-solid fa-ranking-star"></i><span>Candidate Ranking</span></a><a href="{{ route('employer.recommended-candidates') }}" class="sidebar-link"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Recommended Candidates</span></a><a href="{{ route('employer.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('employer.messages') }}" class="sidebar-link"><i class="fa-solid fa-message"></i><span>Messages</span></a></div>
                    <div class="sidebar-section"><div class="sidebar-section-title">COMPANY</div><a href="{{ route('employer.company-profile') }}" class="sidebar-link"><i class="fa-solid fa-building-user"></i><span>Company Profile</span></a></div>
                    <div class="sidebar-section"><div class="sidebar-section-title">ACCOUNT</div><a href="{{ route('employer.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a><a href="{{ route('employer.account') }}" class="sidebar-link"><i class="fa-solid fa-user-shield"></i><span>Account</span></a><a href="{{ route('employer.profile-image.form') }}" class="sidebar-link"><i class="fa-solid fa-image"></i><span>Profile Image</span></a></div>
                @endif
            @endif
        </nav>
    </aside>
    <div class="dashboard-main">
        <header class="dashboard-header">
            <div class="dashboard-header-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu"><i class="fa-solid fa-bars"></i></button>
                <div class="dashboard-page-title"><h1>@yield('title')</h1></div>
            </div>
            <div class="dashboard-header-right">
                <a href="{{ route('logout') }}" class="header-icon-btn" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </header>
        <main class="dashboard-content">@yield('content')</main>
    </div>
</div>
<script src="{{ asset('assets/js/dashboard.js') }}"></script>
<script src="{{ asset('assets/js/language.js') }}"></script>
@yield('js')
</body>
</html>
