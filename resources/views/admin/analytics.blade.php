@extends('layouts.dashboard')
@section('title', 'Analytics')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/analytics.css') }}">@endsection
@section('js')
<script>window.adminAnalytics = @json($chartsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('assets/js/admin/analytics.js') }}"></script>
@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a><a href="{{ route('admin.analytics') }}" class="sidebar-link active"><i class="fa-solid fa-chart-line"></i><span>Analytics</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">COMMUNICATION</div><a href="{{ route('admin.notifications') }}" class="sidebar-link"><i class="fa-solid fa-bell"></i><span>Notifications</span></a><a href="{{ route('admin.broadcast') }}" class="sidebar-link"><i class="fa-solid fa-paper-plane"></i><span>Send Broadcast</span></a><a href="{{ route('admin.messages') }}" class="sidebar-link"><i class="fa-solid fa-envelope"></i><span>Messages</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">ACCOUNT</div><a href="{{ route('admin.profile') }}" class="sidebar-link"><i class="fa-solid fa-user"></i><span>My Profile</span></a></div>
@endsection
@section('content')
<div class="admin-analytics-page">
    <div class="admin-page-header">
        <div><span class="admin-page-label">INSIGHTS</span><h1>Platform Analytics</h1><p>Track growth, engagement and hiring activity across the portal.</p></div>
        <div class="period-switcher" role="tablist" aria-label="Date range">
            @foreach(['7d' => 'Last 7 days', '30d' => 'Last 30 days', '90d' => 'Last 90 days', '365d' => 'Last 12 months', 'all' => 'All time'] as $slug => $label)
                <a href="{{ route('admin.analytics', ['period' => $slug]) }}" class="period-btn {{ $period === $slug ? 'active' : '' }}" role="tab" aria-selected="{{ $period === $slug ? 'true' : 'false' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="analytics-kpi-grid">
        <div class="analytics-kpi-card"><div class="kpi-icon kpi-blue"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></div><div class="kpi-body"><span>New Users</span><strong>{{ number_format($kpis['new_users']) }}</strong><small>{{ number_format($kpis['new_employers']) }} employers · {{ number_format($kpis['new_jobseekers']) }} seekers</small></div></div>
        <div class="analytics-kpi-card"><div class="kpi-icon kpi-purple"><i class="fa-solid fa-building" aria-hidden="true"></i></div><div class="kpi-body"><span>New Companies</span><strong>{{ number_format($kpis['new_companies']) }}</strong><small>Registered in this period</small></div></div>
        <div class="analytics-kpi-card"><div class="kpi-icon kpi-cyan"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></div><div class="kpi-body"><span>Jobs Posted</span><strong>{{ number_format($kpis['new_jobs']) }}</strong><small>Across all categories</small></div></div>
        <div class="analytics-kpi-card"><div class="kpi-icon kpi-orange"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div><div class="kpi-body"><span>Applications</span><strong>{{ number_format($kpis['new_applications']) }}</strong><small>Submitted in this period</small></div></div>
        <div class="analytics-kpi-card"><div class="kpi-icon kpi-green"><i class="fa-solid fa-trophy" aria-hidden="true"></i></div><div class="kpi-body"><span>Hired</span><strong>{{ number_format($kpis['selected_count']) }}</strong><small>{{ number_format($kpis['hiring_rate'], 1) }}% hiring rate</small></div></div>
        <div class="analytics-kpi-card"><div class="kpi-icon kpi-emerald"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></div><div class="kpi-body"><span>Awaiting Review</span><strong>{{ number_format($kpis['pending_users'] + $kpis['pending_companies']) }}</strong><small>{{ number_format($kpis['pending_users']) }} users · {{ number_format($kpis['pending_companies']) }} companies</small></div></div>
    </div>

    <div class="analytics-chart-grid two-col">
        <section class="chart-card"><div class="chart-card-header"><div><h2>Signups Over Time</h2><p>New user registrations per bucket</p></div><i class="fa-solid fa-user-plus" aria-hidden="true"></i></div><div class="chart-canvas-wrap"><canvas id="signupsChart" height="220"></canvas></div></section>
        <section class="chart-card"><div class="chart-card-header"><div><h2>Jobs &amp; Applications</h2><p>Posting vs. applying activity</p></div><i class="fa-solid fa-chart-line" aria-hidden="true"></i></div><div class="chart-canvas-wrap"><canvas id="jobsAppsChart" height="220"></canvas></div></section>
    </div>
    <div class="analytics-chart-grid two-col">
        <section class="chart-card"><div class="chart-card-header"><div><h2>User Roles</h2><p>Distribution by role</p></div><i class="fa-solid fa-users" aria-hidden="true"></i></div><div class="chart-canvas-wrap chart-doughnut"><canvas id="roleChart" height="220"></canvas></div></section>
        <section class="chart-card"><div class="chart-card-header"><div><h2>Application Pipeline</h2><p>All-time status breakdown</p></div><i class="fa-solid fa-chart-pie" aria-hidden="true"></i></div><div class="chart-canvas-wrap chart-doughnut"><canvas id="statusChart" height="220"></canvas></div></section>
    </div>
    <div class="analytics-chart-grid two-col">
        <section class="chart-card"><div class="chart-card-header"><div><h2>Jobs by Category</h2><p>Top categories by posting volume</p></div><i class="fa-solid fa-layer-group" aria-hidden="true"></i></div><div class="chart-canvas-wrap chart-tall"><canvas id="categoryChart" height="260"></canvas></div></section>
        <section class="chart-card"><div class="chart-card-header"><div><h2>Skills in Demand</h2><p>Most requested skills in open jobs</p></div><i class="fa-solid fa-code" aria-hidden="true"></i></div><div class="chart-canvas-wrap chart-tall"><canvas id="skillsChart" height="260"></canvas></div></section>
    </div>
    <div class="analytics-chart-grid two-col analytics-bottom-grid">
        <section class="analytics-panel">
            <div class="analytics-panel-header"><div><h2>Top Companies</h2><p>By applications received</p></div><a href="{{ route('admin.companies', ['status' => 'Approved']) }}" class="panel-action">View All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
            @if($topCompanies->isEmpty())
                <div class="empty-inline"><i class="fa-solid fa-building" aria-hidden="true"></i><p>No approved companies yet.</p></div>
            @else
                <div class="admin-table-wrapper"><table class="admin-table companies-mini-table"><thead><tr><th scope="col">Company</th><th scope="col">Jobs</th><th scope="col">Applications</th></tr></thead><tbody>
                    @foreach($topCompanies as $company)
                        <tr><td><a href="{{ route('admin.company-details', ['id' => $company->company_id]) }}" class="table-user-link"><strong>{{ $company->company_name }}</strong></a></td><td>{{ number_format((int) $company->job_count) }}</td><td><strong class="metric-highlight">{{ number_format((int) $company->application_count) }}</strong></td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </section>
        <section class="analytics-panel">
            <div class="analytics-panel-header"><div><h2>Interview Activity</h2><p>All-time interview outcomes</p></div><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></div>
            <div class="interview-stats">
                <div class="interview-stat"><strong>{{ number_format($interviewSummary['total']) }}</strong><span>Total</span></div>
                <div class="interview-stat"><strong>{{ number_format($interviewSummary['scheduled']) }}</strong><span>Scheduled</span></div>
                <div class="interview-stat"><strong>{{ number_format($interviewSummary['completed']) }}</strong><span>Completed</span></div>
                <div class="interview-stat"><strong>{{ number_format($interviewSummary['cancelled']) }}</strong><span>Cancelled</span></div>
                <div class="interview-stat"><strong>{{ number_format($interviewSummary['rescheduled']) }}</strong><span>Rescheduled</span></div>
            </div>
            <div class="completion-bar"><div class="completion-bar-header"><span>Completion Rate</span><strong>{{ number_format($interviewSummary['completion_rate'], 1) }}%</strong></div><div class="completion-bar-track"><span style="width: {{ min(100, max(0, $interviewSummary['completion_rate'])) }}%;"></span></div></div>
        </section>
    </div>
</div>
@endsection
