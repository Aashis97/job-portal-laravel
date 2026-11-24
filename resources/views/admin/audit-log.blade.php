@extends('layouts.dashboard')
@section('title', 'Audit Log')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/audit_log.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/audit_log.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link active"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a></div>
@endsection
@section('content')
@php($hasFilters = $userId > 0 || $module !== '' || $dateFrom !== '' || $dateTo !== '' || $search !== '')
<div class="admin-audit-page">
    <div class="admin-page-header"><div><span class="admin-page-label">SYSTEM</span><h1>Audit Log</h1><p>Every logged activity across the platform.</p></div><div class="page-header-actions"><a href="{{ route('admin.settings') }}" class="btn btn-light"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Settings</a></div></div>
    <div class="audit-kpi-grid">
        @foreach([['kpi-blue', 'fa-clipboard-list', 'total', 'Total events'], ['kpi-orange', 'fa-clock-rotate-left', 'last_24h', 'Last 24 hours'], ['kpi-purple', 'fa-calendar-week', 'last_7d', 'Last 7 days'], ['kpi-green', 'fa-users', 'unique_users', 'Unique actors']] as [$style, $icon, $key, $label])
            <div class="kpi-card"><div class="kpi-icon {{ $style }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div><div><strong>{{ number_format($stats[$key]) }}</strong><span>{{ $label }}</span></div></div>
        @endforeach
    </div>
    <form action="{{ route('admin.audit-log') }}" method="GET" class="admin-filter-form audit-filter-form">
        <div class="filter-field filter-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search activity, IP, browser, user..." maxlength="100" autocomplete="off"></div>
        <div class="filter-field"><select name="module"><option value="">All Modules</option>@foreach($modules as $item)<option value="{{ $item }}" @selected($module === $item)>{{ $item }}</option>@endforeach</select></div>
        <div class="filter-field"><input type="number" name="user_id" value="{{ $userId > 0 ? $userId : '' }}" placeholder="User ID" min="1" inputmode="numeric"></div>
        <div class="filter-field"><input type="date" name="date_from" value="{{ $dateFrom }}" title="From date"></div>
        <div class="filter-field"><input type="date" name="date_to" value="{{ $dateTo }}" title="To date"></div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>@if($hasFilters)<a href="{{ route('admin.audit-log') }}" class="btn btn-light">Clear</a>@endif</div>
    </form>
    <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Log Entries</h2><p>{{ number_format($logs->total()) }} {{ $logs->total() === 1 ? 'entry' : 'entries' }}@if($logs->currentPage() > 1) &middot; page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}@endif</p></div></div>
        <div class="admin-table-wrapper"><table class="admin-table audit-table"><thead><tr><th scope="col">When</th><th scope="col">Actor</th><th scope="col">Module</th><th scope="col">Activity</th><th scope="col">IP</th><th scope="col">Client</th></tr></thead><tbody>
            @forelse($logs as $log)
                @php($roleLabel = [1 => 'Admin', 2 => 'Employer', 3 => 'Job Seeker'][(int) ($log->user_role ?? 0)] ?? '')
                <tr>
                    <td><div class="audit-time"><strong>{{ $log->created_at ? \Illuminate\Support\Carbon::parse($log->created_at)->diffForHumans() : '—' }}</strong><small>{{ $log->created_at ? \Illuminate\Support\Carbon::parse($log->created_at)->format('M j, Y · H:i') : '' }}</small></div></td>
                    <td>@if((int) $log->user_id > 0)<a href="{{ route('admin.user-details', ['id' => $log->user_id]) }}" class="table-user-link"><strong>{{ trim((string) $log->user_name) !== '' ? $log->user_name : 'User #'.$log->user_id }}</strong></a>@if($roleLabel)<small class="table-subtext">{{ $roleLabel }}</small>@endif @else<span class="muted-value">System</span>@endif</td>
                    <td><span class="module-badge">{{ $log->module ?: 'System' }}</span></td>
                    <td><div class="audit-activity">{{ $log->activity }}</div></td>
                    <td>@if($log->ip_address)<code class="ip-cell">{{ $log->ip_address }}</code>@else<span class="muted-value">—</span>@endif</td>
                    <td><div class="client-cell"><span>{{ $log->browser ?: '—' }}</span><small>{{ $log->operating_system ?: '' }}</small></div></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-table">No log entries match your filters.</td></tr>
            @endforelse
        </tbody></table></div>
        @if($logs->lastPage() > 1)<nav class="admin-pagination" aria-label="Pagination">@if($logs->previousPageUrl())<a href="{{ $logs->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>@endif<div class="pagination-pages">@php($start = max(1, $logs->currentPage() - 2))@php($end = min($logs->lastPage(), $logs->currentPage() + 2))@if($start > 1)<a href="{{ $logs->url(1) }}" class="pagination-number">1</a>@if($start > 2)<span class="pagination-ellipsis">…</span>@endif @endif @foreach($logs->getUrlRange($start, $end) as $page => $url)<a href="{{ $url }}" class="pagination-number {{ $page === $logs->currentPage() ? 'active' : '' }}">{{ $page }}</a>@endforeach @if($end < $logs->lastPage())@if($end < $logs->lastPage() - 1)<span class="pagination-ellipsis">…</span>@endif<a href="{{ $logs->url($logs->lastPage()) }}" class="pagination-number">{{ $logs->lastPage() }}</a>@endif</div>@if($logs->nextPageUrl())<a href="{{ $logs->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>@endif</nav>@endif
    </section>
</div>
@endsection
