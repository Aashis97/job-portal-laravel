@extends('layouts.dashboard')
@section('title', 'Reports')
@section('role', 'Administrator')
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admin/reports.css') }}">@endsection
@section('js')<script src="{{ asset('assets/js/admin/reports.js') }}"></script>@endsection
@section('sidebar')
<div class="sidebar-section"><div class="sidebar-section-title">MAIN</div><a href="{{ route('admin.dashboard') }}" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a></div>
<div class="sidebar-section"><div class="sidebar-section-title">MANAGEMENT</div><a href="{{ route('admin.users') }}" class="sidebar-link"><i class="fa-solid fa-users"></i><span>Users</span></a><a href="{{ route('admin.companies') }}" class="sidebar-link"><i class="fa-solid fa-building"></i><span>Companies</span></a><a href="{{ route('admin.jobs') }}" class="sidebar-link"><i class="fa-solid fa-briefcase"></i><span>Jobs</span></a><a href="{{ route('admin.applications') }}" class="sidebar-link"><i class="fa-solid fa-file-lines"></i><span>Applications</span></a><a href="{{ route('admin.reports') }}" class="sidebar-link active"><i class="fa-solid fa-flag"></i><span>Reports</span></a><a href="{{ route('admin.audit-log') }}" class="sidebar-link"><i class="fa-solid fa-clipboard-list"></i><span>Audit Log</span></a><a href="{{ route('admin.taxonomy') }}" class="sidebar-link"><i class="fa-solid fa-tags"></i><span>Categories &amp; Skills</span></a><a href="{{ route('admin.settings') }}" class="sidebar-link"><i class="fa-solid fa-gear"></i><span>Settings</span></a></div>
@endsection
@section('content')
@php
    $reasonLabels = ['spam' => 'Spam', 'inappropriate' => 'Inappropriate content', 'fraud' => 'Fraud / Scam', 'fake' => 'Fake / Misleading', 'harassment' => 'Harassment', 'copyright' => 'Copyright', 'other' => 'Other'];
    $statusClasses = ['open' => 'status-open', 'in_review' => 'status-review', 'resolved' => 'status-resolved', 'dismissed' => 'status-dismissed'];
    $filters = array_filter(['status' => $status, 'target_type' => $targetType, 'reason' => $reason, 'q' => $search], fn ($value) => $value !== '');
@endphp
<div class="admin-reports-page">
    <div class="admin-page-header"><div><span class="admin-page-label">MODERATION</span><h1>Reports Queue</h1><p>Review user-submitted reports about jobs, companies, users and content.</p></div><div class="admin-page-date"><i class="fa-solid fa-flag" aria-hidden="true"></i> {{ number_format($stats['open']) }} open</div></div>
    @foreach(['success' => 'admin-alert--success', 'danger' => 'admin-alert--error', 'warning' => 'admin-alert-warning', 'info' => 'admin-alert--info'] as $key => $class)
        @if(session($key))<div class="admin-alert {{ $class }}" role="status">{{ session($key) }}</div>@endif
    @endforeach
    <div class="reports-kpi-grid">
        @foreach([['kpi-red', 'fa-circle-exclamation', 'open', 'Open'], ['kpi-orange', 'fa-eye', 'in_review', 'In review'], ['kpi-green', 'fa-circle-check', 'resolved', 'Resolved'], ['kpi-slate', 'fa-circle-xmark', 'dismissed', 'Dismissed'], ['kpi-blue', 'fa-calendar-week', 'last_7d', 'Last 7 days'], ['kpi-purple', 'fa-layer-group', 'total', 'All-time']] as [$style, $icon, $key, $label])
            <div class="kpi-card"><div class="kpi-icon {{ $style }}"><i class="fa-solid {{ $icon }}" aria-hidden="true"></i></div><div><strong>{{ number_format($stats[$key]) }}</strong><span>{{ $label }}</span></div></div>
        @endforeach
    </div>
    <div class="admin-tabs">
        <a href="{{ route('admin.reports', array_merge($filters, ['status' => null, 'page' => null])) }}" class="admin-tab {{ $status === '' ? 'active' : '' }}">All <span class="tab-count">{{ number_format($stats['total']) }}</span></a>
        <a href="{{ route('admin.reports', array_merge($filters, ['status' => 'open', 'page' => null])) }}" class="admin-tab {{ $status === 'open' ? 'active' : '' }}"><i class="fa-solid fa-circle" aria-hidden="true"></i> Open <span class="tab-count tab-count-danger">{{ number_format($stats['open']) }}</span></a>
        <a href="{{ route('admin.reports', array_merge($filters, ['status' => 'in_review', 'page' => null])) }}" class="admin-tab {{ $status === 'in_review' ? 'active' : '' }}"><i class="fa-solid fa-eye" aria-hidden="true"></i> In Review <span class="tab-count tab-count-warning">{{ number_format($stats['in_review']) }}</span></a>
        <a href="{{ route('admin.reports', array_merge($filters, ['status' => 'resolved', 'page' => null])) }}" class="admin-tab {{ $status === 'resolved' ? 'active' : '' }}"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Resolved <span class="tab-count">{{ number_format($stats['resolved']) }}</span></a>
        <a href="{{ route('admin.reports', array_merge($filters, ['status' => 'dismissed', 'page' => null])) }}" class="admin-tab {{ $status === 'dismissed' ? 'active' : '' }}"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Dismissed <span class="tab-count">{{ number_format($stats['dismissed']) }}</span></a>
    </div>
    <form action="{{ route('admin.reports') }}" method="GET" class="admin-filter-form">
        @if($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
        <div class="filter-field filter-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" name="q" value="{{ $search }}" placeholder="Search details, reporter name or email..." maxlength="100" autocomplete="off"></div>
        <div class="filter-field"><select name="target_type"><option value="">All Targets</option>@foreach(['job', 'company', 'user', 'application', 'message'] as $target)<option value="{{ $target }}" @selected($targetType === $target)>{{ ucfirst($target) }}</option>@endforeach</select></div>
        <div class="filter-field"><select name="reason"><option value="">All Reasons</option>@foreach(array_keys($reasonLabels) as $key)<option value="{{ $key }}" @selected($reason === $key)>{{ $reasonLabels[$key] }}</option>@endforeach</select></div>
        <div class="filter-actions"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>@if($search !== '' || $targetType !== '' || $reason !== '')<a href="{{ route('admin.reports', $status !== '' ? ['status' => $status] : []) }}" class="btn btn-light">Clear</a>@endif</div>
    </form>
    <section class="admin-panel">
        <div class="admin-panel-header"><div><h2>Results</h2><p>{{ number_format($reports->total()) }} report{{ $reports->total() === 1 ? '' : 's' }}@if($reports->currentPage() > 1) &middot; page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}@endif</p></div></div>
        <div class="admin-table-wrapper"><table class="admin-table reports-table"><thead><tr><th scope="col">Target</th><th scope="col">Reason</th><th scope="col">Reporter</th><th scope="col">Status</th><th scope="col">Received</th><th scope="col">Action</th></tr></thead><tbody>
            @forelse($reports as $report)
                @php($reportStatusClass = $statusClasses[$report->status] ?? 'status-default')
                <tr>
                    <td><div class="target-cell"><span class="target-type-badge target-{{ $report->target_type }}">{{ ucfirst($report->target_type) }}</span>@if($report->target_title && $report->target_url)<a href="{{ $report->target_url }}" class="target-title">{{ $report->target_title }}</a>@elseif($report->target_title)<strong class="target-title-static">{{ $report->target_title }}</strong>@else<span class="muted-value">Target #{{ $report->target_id }} (not found)</span>@endif</div></td>
                    <td><strong class="reason-label">{{ $reasonLabels[$report->reason] ?? 'Other' }}</strong>@if($report->details)<small class="table-subtext">{{ \Illuminate\Support\Str::limit($report->details, 90, '…') }}</small>@endif</td>
                    <td>@if((int) $report->reporter_user_id > 0)<a href="{{ route('admin.user-details', ['id' => $report->reporter_user_id]) }}" class="table-user-link"><strong>{{ $report->reporter_name ?? 'User' }}</strong></a>@else<strong>{{ $report->reporter_email ?? 'Guest' }}</strong>@endif</td>
                    <td><span class="status-badge {{ $reportStatusClass }}">{{ str_replace('_', ' ', ucfirst($report->status)) }}</span></td>
                    <td><span title="{{ $report->created_at }}">{{ $report->created_at ? \Illuminate\Support\Carbon::parse($report->created_at)->diffForHumans() : '—' }}</span></td>
                    <td><a href="{{ route('admin.report-details', ['id' => $report->report_id]) }}" class="btn-sm btn-outline"><i class="fa-solid fa-eye" aria-hidden="true"></i> {{ $report->status === 'open' ? 'Review' : 'View' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-table">No reports match your filters.</td></tr>
            @endforelse
        </tbody></table></div>
        @if($reports->lastPage() > 1)<nav class="admin-pagination" aria-label="Pagination">@if($reports->previousPageUrl())<a href="{{ $reports->previousPageUrl() }}" class="pagination-btn"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Previous</a>@endif<div class="pagination-pages">@foreach($reports->getUrlRange(max(1, $reports->currentPage() - 2), min($reports->lastPage(), $reports->currentPage() + 2)) as $page => $url)<a href="{{ $url }}" class="pagination-number {{ $page === $reports->currentPage() ? 'active' : '' }}">{{ $page }}</a>@endforeach</div>@if($reports->nextPageUrl())<a href="{{ $reports->nextPageUrl() }}" class="pagination-btn">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>@endif</nav>@endif
    </section>
</div>
@endsection
