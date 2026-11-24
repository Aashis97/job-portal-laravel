@extends('layouts.dashboard')
@section('title', 'Admin Dashboard')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/dashboard.css') }}">@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.analytics') }}" class="sidebar-link"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a><a href="{{ url('/admin/users.php') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ url('/admin/companies.php') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ url('/admin/jobs.php') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div><div class="sidebar-section"><div class="sidebar-section-title">COMMUNICATION</div><a href="{{ route('admin.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('admin.broadcast') }}" class="sidebar-link"><i class="fa-solid fa-paper-plane"></i><span>Send Broadcast</span></a><a href="{{ route('admin.messages') }}" class="sidebar-link"><i class="fa-solid fa-envelope"></i><span>Messages</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">ACCOUNT</div><a href="{{ route('admin.profile') }}" class="sidebar-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a></div>
@endsection
@section('content')
<div class="admin-dashboard">
    <div class="admin-page-header">
        <div><span class="admin-page-label">ADMIN PANEL</span><h1>Dashboard</h1><p>Welcome back, {{ auth()->user()->full_name }}. Here's what's happening across the job portal.</p></div>
        <div class="admin-page-date"><i class="fa-regular fa-calendar" aria-hidden="true"></i>{{ now()->format('F d, Y') }}</div>
    </div>

    @if($stats['pending_companies'] > 0 || $stats['pending_users'] > 0 || $stats['blocked_users'] > 0)
        <div class="admin-alert-grid">
            @if($stats['pending_companies'] > 0)
                <a href="{{ url('/admin/companies.php?status=Pending') }}" class="admin-alert admin-alert-warning"><i class="fa-solid fa-building-circle-exclamation" aria-hidden="true"></i><div><strong>{{ number_format($stats['pending_companies']) }}</strong><span>pending company verification{{ $stats['pending_companies'] === 1 ? '' : 's' }}</span></div><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            @endif
            @if($stats['pending_users'] > 0)
                <a href="{{ url('/admin/users.php?status=Pending') }}" class="admin-alert admin-alert-info"><i class="fa-solid fa-user-clock" aria-hidden="true"></i><div><strong>{{ number_format($stats['pending_users']) }}</strong><span>user{{ $stats['pending_users'] === 1 ? '' : 's' }} awaiting activation</span></div><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            @endif
            @if($stats['blocked_users'] > 0)
                <a href="{{ url('/admin/users.php?status=Blocked') }}" class="admin-alert admin-alert-danger"><i class="fa-solid fa-user-lock" aria-hidden="true"></i><div><strong>{{ number_format($stats['blocked_users']) }}</strong><span>blocked account{{ $stats['blocked_users'] === 1 ? '' : 's' }}</span></div><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            @endif
        </div>
    @endif

    <div class="admin-stat-grid">
        @foreach([
            ['blue', 'fa-users', 'Total Users', 'total_users', 'Registered accounts'],
            ['purple', 'fa-user-tie', 'Employers', 'total_employers', 'Recruiter accounts'],
            ['green', 'fa-user-graduate', 'Job Seekers', 'total_job_seekers', 'Candidate accounts'],
            ['orange', 'fa-building', 'Companies', 'total_companies', 'Registered companies'],
            ['cyan', 'fa-briefcase', 'Total Jobs', 'total_jobs', 'All job postings'],
            ['emerald', 'fa-circle-check', 'Open Jobs', 'open_jobs', 'Accepting applications'],
            ['red', 'fa-file-lines', 'Applications', 'total_applications', 'All submissions'],
            ['yellow', 'fa-user-clock', 'Pending Users', 'pending_users', 'Awaiting activation'],
        ] as [$color, $icon, $label, $key, $description])
            <div class="admin-stat-card"><div class="stat-icon {{ $color }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div><div class="stat-content"><span class="stat-label">{{ $label }}</span><strong class="stat-value">{{ number_format((int) $stats[$key]) }}</strong><span class="stat-description">{{ $description }}</span></div></div>
        @endforeach
    </div>

    <div class="admin-content-grid">
        <section class="admin-panel">
            <div class="admin-panel-header"><div><h2>Recent Users</h2><p>Latest registered accounts</p></div><a href="{{ url('/admin/users.php') }}" class="panel-action">View All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
            <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th scope="col">User</th><th scope="col">Email</th><th scope="col">Status</th></tr></thead><tbody>
                @forelse($recentUsers as $user)
                    @php($role = [1 => 'Administrator', 2 => 'Employer', 3 => 'Job Seeker'][(int) $user->role_id] ?? 'User')
                    @php($statusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $user->account_status) ?? '')) ?: 'unknown'))
                    <tr><td><div class="table-user"><div class="table-avatar">{{ mb_strtoupper(mb_substr(trim((string) $user->full_name) ?: 'U', 0, 1)) }}</div><div><strong>{{ $user->full_name }}</strong><small class="table-subtext">{{ $role }}</small></div></div></td><td class="table-email">{{ $user->email }}</td><td><span class="status-badge {{ $statusClass }}">{{ $user->account_status }}</span></td></tr>
                @empty
                    <tr><td colspan="3" class="empty-table">No users found.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-header"><div><h2>Recent Jobs</h2><p>Latest job postings</p></div><a href="{{ url('/admin/jobs.php') }}" class="panel-action">View All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
            <div class="admin-table-wrapper"><table class="admin-table"><thead><tr><th scope="col">Job</th><th scope="col">Company</th><th scope="col">Status</th></tr></thead><tbody>
                @forelse($recentJobs as $job)
                    @php($statusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $job->job_status) ?? '')) ?: 'unknown'))
                    <tr><td><div class="job-table-info"><strong>{{ $job->job_title }}</strong><small>{{ $job->employment_type }} &middot; {{ $job->workplace }}</small></div></td><td>{{ $job->company_name ?? 'N/A' }}</td><td><span class="status-badge {{ $statusClass }}">{{ $job->job_status }}</span></td></tr>
                @empty
                    <tr><td colspan="3" class="empty-table">No jobs found.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    </div>

    <section class="admin-panel applications-panel">
        <div class="admin-panel-header"><div><h2>Recent Applications</h2><p>Latest submissions across the platform</p></div><a href="{{ url('/admin/applications.php') }}" class="panel-action">View All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
        <div class="admin-table-wrapper"><table class="admin-table applications-table"><thead><tr><th scope="col">Applicant</th><th scope="col">Job</th><th scope="col">Match Score</th><th scope="col">Status</th></tr></thead><tbody>
            @forelse($recentApplications as $application)
                @php($statusClass = 'status-'.(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $application->application_status) ?? '')) ?: 'unknown'))
                <tr><td><div class="table-user"><div class="table-avatar">{{ mb_strtoupper(mb_substr(trim((string) $application->applicant_name) ?: 'U', 0, 1)) }}</div><strong>{{ $application->applicant_name }}</strong></div></td><td>{{ $application->job_title }}</td><td>@if($application->skill_match_score !== null)<span class="match-score">{{ number_format((float) $application->skill_match_score, 1) }}%</span>@else<span class="muted-value">Not scored</span>@endif</td><td><span class="status-badge {{ $statusClass }}">{{ $application->application_status }}</span></td></tr>
            @empty
                <tr><td colspan="4" class="empty-table">No applications found.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
